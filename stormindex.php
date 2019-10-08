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

    public function openConnection()//создаёт соединение с БД
    {
        $this->connection= new PDO(
            "mysql:host=$this->servername; 
        dbname=$this->dbname;
        charset=$this->charset",
            $this->username,
            $this->pass
        );
    }

    public function extractDataFromDb()//формирует массив из данных таблицы
    {
        $sql="SELECT * FROM university";
        $result = $this->connection->prepare($sql);
        $result->execute();

        $mass = array();//массив с данными из таблицы university
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {// заполняет массив данными
            $mass[$row["id"]] = array (
                "id"=>$row["id"],
                "name"=>$row["name"],
                "parent_id"=>$row["parent_id"],
                "creation_time"=>$row["time"]
            );
        }
        return $mass;
    }

    public function builtHierarchyTree($startUnitId = 0)//строит объёмное дерево
    {
        $rootUnitId = 0;
        $mass = $this->extractDataFromDb();//массив с данными из таблицы university
        foreach ($mass as $id => $node) {
            if ($node['parent_id']) {
                $mass[$node['parent_id']] =&$mass[$id];
            } else {
                $rootUnitId = $id;//если отсутствует parent_id, то запоминаем id, как id корневого узла
            }
        }

        if ($startUnitId) {//если получен параметр startUnitId, то записываем его, как новый id корневого узла
            $rootUnitId = $startUnitId;
        }

        $branch = array($rootUnitId => $mass[$rootUnitId]);
        echo json_encode($branch);
    }

    public function buildFlatTree()//строит плоское дерево
    {
        $dataMass = $this->extractDataFromDb();
        echo json_encode($dataMass);
    }

    public function deleteBranchRecursive($parentId)//рекурсивная функция удаления узла по id со всеми потомками
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

    public function deleteFreeResponsibles()//удаляет непривязанных ответственных
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
            foreach ($del as $outDatedId) {//удаляет непривязанных ответственных из таблицы
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

    public function deleteBranch ($parentId)//удаляет узел по id со всеми потомками
    {
        $this->connection->beginTransaction();
        try {
            $this->deleteBranchRecursive($parentId);//вызов рекурсивной функции удаления узла по id со всеми потомками
            $this->connection->commit();
        } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
        }
        $this->deleteFreeResponsibles();
    }

    public function updateUnitData($columnName, $newValue, $unitId)//обновляет данные таблицы по изменяемому полю, новому значению и id узла
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

    public function changeParent($newParentId, $oldParentId, $columnName='parent_id')//меняет parent_id (меняет родительский элемент ветки), получая на вход новый и старый parent_id соответственно
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

    public function showResponsiblesForUnit($unitId)//выводит ответственных по id узла
    {
        $sql=$sql="SELECT * FROM university_responsibles 
        JOIN responsibles USING (responsibleID) WHERE university_responsibles.unitID=".$unitId;
        $result = $this->connection->prepare($sql);
        $result->execute();

        $mass = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $mass[] = $row["responsibleName"];
        }
        echo json_encode($mass);
    }

    public function updateResponsiblesForUnit($unitId, array $newResponsibles)//меняет ответственных, получая на вход id и массив с новым списком ответственных
    {
        $sql="SELECT * FROM university_responsibles 
        JOIN responsibles USING (responsibleID) WHERE university_responsibles.unitID=".$unitId;
        $result = $this->connection->prepare($sql);
        $result->execute();

        $responsibles = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $responsibles[] = $row['responsibleName'];
        }

        $insertion = array_diff($newResponsibles, $responsibles);//массив новых ответственных на запись (исключаем тех, которые уже есть в таблице)
        $equal = array_intersect($responsibles, $newResponsibles);
        $del = array_diff($responsibles, $equal);//массив устаревших ответственных из таблицы на удаление (те, которых нет в новом списке)

        $this->connection->beginTransaction();
        try {
            foreach ($del as $outDatedName) {//удаляет устаревших ответственных из таблицы
                $sql = "DELETE FROM responsibles WHERE responsibleName = '$outDatedName'";
                $result = $this->connection->prepare($sql);
                $result->execute();
            }
            $this->connection->commit();
        } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
        }

        $newResponsibleID = array();
        $this->connection->beginTransaction();
        try {
            foreach ($insertion as $newName) {//записывает новых ответственных в таблицу
                $sql = "INSERT INTO responsibles (responsibleName) VALUES ('$newName')";
                $result = $this->connection->prepare($sql);
                $result->execute();
                $newId = $this->connection->lastInsertId();
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