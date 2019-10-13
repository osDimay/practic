<?php

namespace stormProject;
use \PDO;

class DbClass
{
    private $serverName;
    private $userName;
    private $pass;
    private $dbName;
    private $charset;
    private $connection;

    function __construct()
    {
        $this->serverName = 'localhost';
        $this->userName = 'root';
        $this->pass = '';
        $this->dbName = 'mydb';
        $this->charset = 'UTF8';
        $this->openConnection();
    }

    /*создаёт соединение с БД*/
    public function openConnection()
    {
        $this->connection= new PDO(
    "mysql:host=$this->serverName; 
        dbname=$this->dbName;
        charset=$this->charset",
        $this->userName,
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
        $sql="DELETE FROM university WHERE id= :parentId LIMIT 1";
        $result = $this->connection->prepare($sql);
        $result->execute([
            "parentId" => $parentId
        ]);

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

        /*удаляет непривязанных ответственных из таблицы*/
        foreach ($del as $outDatedId) {
            $sql = "DELETE FROM responsibles WHERE responsibleID = :outDatedId";
            $result = $this->connection->prepare($sql);
            $result->execute([
                "outDatedId" => $outDatedId
            ]);
        }
    }

    /*удаляет узел по id со всеми потомками*/
    public function deleteBranch ($parentId)
    {
        $this->connection->beginTransaction();
        try {
            /*вызов рекурсивной функции удаления узла по id со всеми потомками*/
            $this->deleteBranchRecursive($parentId);
            /*удаляет непривязанных ответственных из таблицы*/
            $this->deleteFreeResponsibles();
            $this->connection->commit();
        } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
        }
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

    /*обновляет ответственных для узла по id и обновлённому массиву ответственных*/
    public function updateResponsiblesForUnit($unitId, array $newResponsibles) {
        $sql="SELECT * FROM university_responsibles 
        JOIN responsibles USING (responsibleID)";
        $result = $this->connection->prepare($sql);
        $result->execute();

        $oldResponsibles = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $oldResponsibles[] = array(
                "unitID" => $row["unitID"],
                "responsibleID" => $row["responsibleID"],
                "responsibleName" => $row["responsibleName"]
            );
        }

        $newConn = array();
        $equalNames = array();
        foreach ($newResponsibles as $newkey => $newResp) {
            foreach ($oldResponsibles as $oldkey => $oldResp) {
                if  ((!strcasecmp($newResp ?? '', $oldResp["responsibleName"])) && ($oldResp["unitID"] != $unitId)) {
                    /*заполняем массив id ответственных из других для создания связей с нашим узлом*/
                    $newConn[] = $oldResp["responsibleID"];
                    unset($newResponsibles[$newkey]);
                }
                if  ((!strcasecmp($newResp ?? '', $oldResp["responsibleName"])) && ($oldResp["unitID"] == $unitId)) {
                    /*заполняем массив ранее записанных ответственных, повторяющихся в в этом обновлении*/
                    $equalNames[] = $oldResp["responsibleName"];
                    unset($newResponsibles[$newkey]);
                }
            }
        }

        $this->connection->beginTransaction();
        try {
            /*создаём новые связи для ответственных, уже приписанных к другим узлам*/
            foreach ($newConn as $newID) {
                $sql = "INSERT INTO university_responsibles (unitID, responsibleID) VALUES (:unitId, :newID)";
                $result = $this->connection->prepare($sql);
                $result->execute([
                    "unitId" => $unitId,
                    "newID" =>  $newID
                ]);
            }
            /*записываем новых ответственных и связи для них*/
            foreach ($newResponsibles as $newResp) {
                $sql = "INSERT INTO responsibles (responsibleName) VALUES (:newResp)";
                $result = $this->connection->prepare($sql);
                $result->execute([
                    "newResp" => $newResp
                ]);
                $newId = $this->connection->lastInsertId();
                /*запись новых связей в таблицу связей*/
                $sql = "INSERT INTO university_responsibles (unitID, responsibleID) VALUES (:unitId, :newId)";
                $result = $this->connection->prepare($sql);
                $result->execute([
                    "unitId" => $unitId,
                    "newId" => $newId
                ]);
            }
            /*формируем массив устаревших ответственных на удаление*/
            foreach ($oldResponsibles as $key => $oldResp) {
                foreach ($equalNames as $eqName) {
                    if  ((!strcasecmp($eqName, $oldResp["responsibleName"])) && ($oldResp["unitID"] == $unitId)) {
                        unset($oldResponsibles[$key]);
                    }
                }
            }
            /*удаляем устаревших ответственных*/
            foreach ($oldResponsibles as $oldResp) {
                if ($oldResp["unitID"] == $unitId) {
                    $sql = "DELETE FROM university_responsibles WHERE unitID = :unitId AND responsibleID = :oldResp";
                    $result = $this->connection->prepare($sql);
                    $result->execute([
                        "unitId" => $unitId,
                        "oldResp" => $oldResp["responsibleID"]
                    ]);
                }
            }
            /*удаляем ответственных, не привязанных к узлам*/
            $this->deleteFreeResponsibles();
            $this->connection->commit();
        } catch (Exeption $error) {
            $this->connection->rollBack();
            echo "Error: ".$error->getMessage();
        }
    }
}