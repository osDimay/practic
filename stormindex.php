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

    public function extractDataFromDb()
    {
        $sql="SELECT * FROM university";
        $result = $this->connection->prepare($sql);
        $result->execute();

        $mass = array();

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

    public function builtHierarchyTree($startUnitId = 0)
    {
        $rootUnitId = 0;
        $mass = $this->extractDataFromDb();
        foreach ($mass as $id => $node) {
            if ($node['parent_id']) {
                $mass[$node['parent_id']]['sub'][] =&$mass[$id];
            } else {
                $rootUnitId = $id;
            }
        }

        if ($startUnitId) {
            $rootUnitId = $startUnitId;
        }

        $branch = array($rootUnitId => $mass[$rootUnitId]);
        echo json_encode($branch);
    }

    public function buildFlatTree()
    {
        $dataMass = $this->extractDataFromDb();
        echo json_encode($dataMass);
    }

    public function deleteBranch($parentId)
    {
        $sql="DELETE FROM university WHERE id='".$parentId."' LIMIT 1";
        $result = $this->connection->prepare($sql);
        $result->execute();

        if (isset($parentId)) {
            $sql="SELECT * FROM university WHERE parent_id='".$parentId."'";
            $result = $this->connection->prepare($sql);
            $result->execute();
            while ($row=$result->fetch(PDO::FETCH_ASSOC)) {
                $this->delete($row['id']);
            }
        }
        return true;
    }

    public function updateUnitData($columnName, $newValue, $unitId)
    {
        $sql = "UPDATE university SET " .$columnName."=".$newValue." WHERE id=".$unitId;
        $result = $this->connection->prepare($sql);
        $result->execute();
    }

    public function changeParent($newParentId, $oldParentId, $columnName='parent_id')
    {
        $sql = "UPDATE university SET " .$columnName."=".$newParentId." WHERE id=".$oldParentId;
        $result = $this->connection->prepare($sql);
        $result->execute();
    }

    public function showResponsiblesForUnit($unitId)
    {
        $mass = array();
        $sql="SELECT * FROM responsibles WHERE responsibles.unitID =".$unitId;
        $result = $this->connection->prepare($sql);
        $result->execute();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $mass[] = $row["otv_name"];
        }
        return ($mass);
    }

    public function updateResponsiblesForUnit($unitId, array $newResponsibles)
    {
        $this->connection->beginTransaction();
        try {
            $sql = "SELECT responsibleName FROM responsibles WHERE unitID=" . $unitId;
            $result = $this->connection->prepare($sql);
            $result->execute();
        } catch (Exeption $error) {
            echo "Error: ".$error->getMessage();
        }
            $responsibles = array();
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $responsibles[] = $row['responsibleName'];
            }

            $insertion = array_diff($newResponsibles, $responsibles);//новые на запись
            $equal = array_intersect($responsibles, $newResponsibles);
            $del = array_diff($responsibles, $equal);

            foreach ($del as $outDatedName) {
                $sql = "DELETE FROM responsibles WHERE unitID=".$unitId." AND responsibleName = '$outDatedName'";
                $result = $this->connection->prepare($sql);
                $result->execute();
            }

            foreach ($insertion as $newName) {
                $sql = "INSERT INTO responsibles (unitID, responsibleName) VALUES ($unitId, '$newName')";
                $result = $this->connection->prepare($sql);
                $result->execute();
            }

            $this->connection->commit();
    }
}