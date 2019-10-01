<?php

class DbClass
{
    private $servername, $username, $pass, $dbname, $charset, $db_ne_conn;
    private $conn = 0;

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
        if(!$this->db_ne_conn) {
            $this->conn = new PDO(
                "mysql:host=$this->servername; 
                dbname=$this->dbname;
                charset=$this->charset",
                $this->username,
                $this->pass
            );
        }
    }

    public function selectMass($i=1)
    {
        global $i;
        global $mass;
        global $row;

        $sql="SELECT * FROM spisok";
        $result = $this->conn->prepare($sql);
        $result->execute();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $i++;
            $mass[$row["id"]] = array (
                "id"=>$row["id"],
                "name"=>$row["name"],
                "parent_id"=>$row["parent_id"],
                "creation_time"=>$row["time"],
                "otvetstvennye"=>$row["respons"]
            );
        }
        return $mass;
    }

    public function buildTree(array $mass, int $start_id = 0)
    {
        $rootId = 0;

        foreach ($mass as $id => $node) {
            if ($node['parent_id']) {
                $mass[$node['parent_id']]['sub'][] =&$mass[$id];
            } else {
                $rootId = $id;
            }
        }

        if ($start_id) {
            $rootId = $start_id;
        }

        $branch = array($rootId => $mass[$rootId]);
        echo json_encode($branch);
    }

    public function buildSimpleTree($i=1)
    {
        global $i;
        global $mass;
        global $row;

        $sql="SELECT * FROM spisok";
        $result = $this->conn->prepare($sql);
        $result->execute();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $i++;
            $mass[$row["id"]] = array (
                "id"=>$row["id"],
                "name"=>$row["name"],
                "parent_id"=>$row["parent_id"],
                "creation_time"=>$row["time"]
            );
        }
        echo json_encode($mass);
    }

    public function delete($parent_id)
    {
        $sql="DELETE FROM spisok WHERE id='".$parent_id."' LIMIT 1";
        $result = $this->conn->prepare($sql);
        $result->execute();

        if (isset($parent_id)) {
            $sql="SELECT * FROM spisok WHERE parent_id='".$parent_id."'";
            $result = $conn->prepare($sql);
            $result->execute();
            while ($row=$result->fetch(PDO::FETCH_ASSOC)) {
                $this->delete($row['id']);
            }
        }
        return true;
    }

    public function update($col, $value, $idI)
    {
        $sql = "UPDATE spisok SET " .$col."=".$value." WHERE id=".$idI;
        $result = $this->conn->prepare($sql);
        $result->execute();
    }

    public function peremes($value, $idI, $col='parent_id')
    {
        $sql = "UPDATE spisok SET " .$col."=".$value." WHERE id=".$idI;
        $result = $this->conn->prepare($sql);
        $result->execute();
    }

    public function showRespons($id)
    {
        global $i;
        global $resp;
        global $row;

        $sql="SELECT id,respons FROM spisok";
        $result = $this->conn->prepare($sql);
        $result->execute();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $i++;
            if ($row["id"]==$id) {
                $resp = explode(", ", $row["respons"]);
            }
        }
        echo json_encode($resp);
    }

    public function updateRespons($id, array $arr)
    {
        $zapis = implode(', ', $arr);
        $sql = "Select respons FROM spisok WHERE id=".$id;
        $result = $this->conn->prepare($sql);
        $result->execute();
        $row = $result->fetch(PDO::FETCH_ASSOC);

        if (strcmp($row['respons'], $zapis)) {
            $sql = "UPDATE spisok SET respons='".$zapis."' WHERE id=".$id;
            $result = $this->conn->prepare($sql);
            $result->execute();
        } else {
            echo json_encode ("Tyt toje samoe");
        }
    }
}

//$db->select('*', 'spisok');
//$db->selectMass();//формирует массив из данных таблицы
//$db->buildTree($mass);//строит объёмное дерево на основе массива из selectMass
//$db->buildSimpleTree();//строит плоское дерево
//$db->delete(7);//удаляет запись таблицы по id
//$db->update('name', "'MSU'", 1);//обновляет данные таблицы по изменяемому полю, новому значению и id
//$db->peremes(8, 13);//меняет parent_id (меняет родительский элемент ветки), получая на вход новый и старый parent_id соответственно
//$db->showRespons(6);//выводит ответственных по id (ответственные записаны у факультетов (id=6-10))
//$arr = array('Govorov','Blinov');//массив ответственных на ввод
//$db->updateRespons(6, $arr);//меняет ответственных, получая на вход id и массив с новым списком ответственных

?>