<?php

namespace stormproject;
use \PDO;

class DbClass
{
    private $servername;
    private $username;
    private $pass;
    private $dbname;
    private $charset;
    private $connection = 0;

    function __construct()
    {
        $this->servername = 'localhost';
        $this->username = 'root';
        $this->pass = '';
        $this->dbname = 'mydb';
        $this->charset = 'UTF8';
        $this->openConnection();
    }

    /*создаёт соединение с БД*/
    public function openConnection()
    {
        $this->connection= new PDO(
    "mysql:host=$this->servername; 
        dbname=$this->dbname;
        charset=$this->charset",
        $this->username,
        $this->pass
        );
    }

    /*формирует массив из данных таблицы*/
    public function extractDataFromDb()
    {
        $sql="SELECT * FROM university";
        $result = $this->connection->prepare($sql);
        $result->execute();

        /*$mass - массив с данными из таблицы university (пока пустой)*/
        $mass = array();
        /*заполняем массив данными*/
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $mass[$row["id"]] = array (
                "id"=>$row["id"],
                "name"=>$row["name"],
                "parent_id"=>$row["parent_id"],
                "creation_time"=>$row["time"]
            );
        }
        return $mass;
    }

    /*строит объёмное дерево*/
    public function builtHierarchyTree($startUnitId = 0)
    {
        $rootUnitId = 0;
        /*$mass - массив с данными из таблицы university*/
        $mass = $this->extractDataFromDb();
        foreach ($mass as $id => $node) {
            if ($node['parent_id']) {
                $mass[$node['parent_id']] =&$mass[$id];
            } else {
                /*если отсутствует parent_id, то запоминаем id, как id корневого узла*/
                $rootUnitId = $id;
            }
        }

        /*если получен параметр startUnitId, то записываем его, как новый id корневого узла*/
        if ($startUnitId) {
            $rootUnitId = $startUnitId;
        }

        $branch = array($rootUnitId => $mass[$rootUnitId]);
        echo json_encode($branch);
    }

    /*строит плоское дерево*/
    public function buildFlatTree()
    {
        $dataMass = $this->extractDataFromDb();
        echo json_encode($dataMass);
    }

    /*рекурсивная функция удаления узла по id со всеми потомками*/
    private function deleteBranchRecursive($parentId)
    {
        $sql="DELETE FROM university WHERE id='".$parentId."' LIMIT 1";
        $result = $this->connection->prepare($sql);
        $result->execute();

        if (isset($parentId)) {
            $sql="SELECT * FROM university WHERE parent_id='".$parentId."'";
            $result = $this->connection->prepare($sql);
            $result->execute();
            while ($row=$result->fetch(PDO::FETCH_ASSOC)) {
                $this->deleteBranchRecursive($row['id']);
            }
        }
        return true;
    }

    /*удаляет непривязанных ответственных*/
    private function deleteFreeResponsibles()
    {
        $sql="SELECT responsibleID FROM responsibles";
        $result = $this->connection->prepare($sql);
        $result->execute();
        $responsibles = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $responsibles[] = $row['responsibleID'];
        }

        $sql="SELECT responsibleID FROM university_responsibles";
        $result = $this->connection->prepare($sql);
        $result->execute();
        $usedResponsibles = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $usedResponsibles[] = $row['responsibleID'];
        }

        $del = array_diff($responsibles, $usedResponsibles);

        $this->connection->beginTransaction();
        try {
            /*удаляет непривязанных ответственных из таблицы*/
            foreach ($del as $outDatedId) {
                $sql = "DELETE FROM responsibles WHERE responsibleID = $outDatedId";
                $result = $this->connection->prepare($sql);
                $result->execute();
            }
            $this->connection->commit();
        } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
        }

    }

    /*удаляет узел по id со всеми потомками*/
    public function deleteBranch ($parentId)
    {
        $this->connection->beginTransaction();
        try {
            /*вызов рекурсивной функции удаления узла по id со всеми потомками*/
            $this->deleteBranchRecursive($parentId);
            $this->connection->commit();
        } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
        }
        $this->deleteFreeResponsibles();
    }

    /*обновляет данные таблицы по изменяемому полю, новому значению и id узла*/
    public function updateUnitData($columnName, $newValue, $unitId)
    {
        $this->connection->beginTransaction();
        try {
            $sql = "UPDATE university SET " .$columnName."=".$newValue." WHERE id=".$unitId;
            $result = $this->connection->prepare($sql);
            $result->execute();
            $this->connection->commit();
        } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
        }

    }

    /*меняет parent_id (меняет родительский элемент ветки),
    получая на вход новый и старый parent_id соответственно*/
    public function changeParent($newParentId, $oldParentId, $columnName='parent_id')
    {
        $this->connection->beginTransaction();
        try {
            $sql = "UPDATE university SET " .$columnName."=".$newParentId." WHERE id=".$oldParentId;
            $result = $this->connection->prepare($sql);
            $result->execute();
            $this->connection->commit();
        } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
        }
    }

    /*выводит ответственных по id узла*/
    public function showResponsiblesForUnit($unitId)
    {
        $sql="SELECT * FROM university_responsibles 
        JOIN responsibles USING (responsibleID) WHERE university_responsibles.unitID=".$unitId;
        $result = $this->connection->prepare($sql);
        $result->execute();

        $mass = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $mass[] = $row["responsibleName"];
        }
        echo json_encode($mass);
    }

    /*меняет ответственных, получая на вход id и массив с новым списком ответственных*/
    public function updateResponsiblesForUnit($unitId, array $newResponsibles)
    {
        $sql="SELECT * FROM university_responsibles 
        JOIN responsibles USING (responsibleID) WHERE university_responsibles.unitID=".$unitId;
        $result = $this->connection->prepare($sql);
        $result->execute();

        /*
        $responsibles - массив массивов данных об ответственных (id => имя)
        $responsibleNamess - массив имён ответственных, уже занесённых в БД
        $responsibleIDss - массив id ответственных, уже занесённых в БД
        */
        $responsibles = array();
        $responsibleNamess = array();
        $responsibleIDss = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $responsibles[] = array(
                "responsibleID" => $row["responsibleID"],
                "responsibleName" => $row["responsibleName"]
            );
            $responsibleNamess[] = $row["responsibleName"];
            $responsibleIDss[] = $row["responsibleID"];
        }

        /*
        $insertion - массив новых ответственных на запись (исключаем тех, которые уже есть в таблице)
        $equal - массив совпадающих имён в старом и новом массивах ответственных
        $equalID - массив совпадающих id в старом и новом массивах ответственных
        */
        $insertion = array_diff($newResponsibles, $responsibleNamess);
        $equal = array_intersect($responsibleNamess, $newResponsibles);
        $equalID = array();
        $countResponsibles = count($responsibles);
        $countEqual = count($equal);
        for ($i = 0; $i < $countResponsibles; $i++) {
            for ($c = 0; $c < $countEqual; $c++) {
                if (!strcasecmp($responsibles[$i]["responsibleName"], $equal[$c])) {
                    $equalID[] = $responsibles[$i]["responsibleID"];
                }
            }
        }
        /*$del - массив устаревших id ответственных из таблицы на удаление (те, которых нет в новом списке)*/
        $del = array_diff($responsibleIDss, $equalID);

        $this->connection->beginTransaction();
        try {
            /*удаляет устаревших ответственных из таблицы*/
            foreach ($del as $outDatedID) {
                $sql = "DELETE FROM responsibles WHERE responsibleID = '$outDatedID'";
                $result = $this->connection->prepare($sql);
                $result->execute();
            }

        $newResponsibleID = array();
            /*записывает новых ответственных в таблицу*/
            foreach ($insertion as $newName) {
                $sql = "INSERT INTO responsibles (responsibleName) VALUES ('$newName')";
                $result = $this->connection->prepare($sql);
                $result->execute();
                $newId = $this->connection->lastInsertId();
                /*запись новых связей в таблицу связей*/
                $sql = "INSERT INTO university_responsibles (unitID, responsibleID) VALUES ($unitId, $newId)";
                $result = $this->connection->prepare($sql);
                $result->execute();
            }
            $this->connection->commit();
            } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
            }
    }
}