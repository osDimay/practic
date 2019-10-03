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
        return $mass;
    }

    public function buildTree($start_id = 0)
    {
        $rootId = 0;
        $mass = $this->selectMass();
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
        $this->selectMass();
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
            $result = $this->conn->prepare($sql);
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
        $mass = array();
        $sql="SELECT * FROM otv WHERE otv.podr_id =".$id;
        $result = $this->conn->prepare($sql);
        $result->execute();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $mass[] = $row["otv_name"];
        }
        return ($mass);
    }

    public function updateRespons($podr_id, array $arr)
    {
        $sql = "DELETE FROM otv WHERE podr_id=".$podr_id;
        $result = $this->conn->prepare($sql);
        $result->execute();

        foreach($arr as $name) {
            $sql = "INSERT INTO otv (podr_id, otv_name) VALUES ($podr_id, '$name')";
            $result = $this->conn->prepare($sql);
            $result->execute();
        }
    }
}

//$db = new DbClass();
//$db->select('*', 'spisok');
//$db->buildTree();//строит объёмное дерево на основе массива из selectMass
//$db->buildSimpleTree();//строит плоское дерево
//$db->delete(19);//удаляет запись таблицы по id
//$db->update('name', "'MSU'", 1);//обновляет данные таблицы по изменяемому полю, новому значению и id
//$db->peremes(8, 13);//меняет parent_id (меняет родительский элемент ветки), получая на вход новый и старый parent_id соответственно
//$mass = $db->showRespons(9);//выводит ответственных по id (ответственные записаны у факультетов (id=6-10))
//echo json_encode($mass);
//$arr = array('Shinkin','Blinov');//массив ответственных на ввод
//$db->updateRespons(19, $arr);//меняет ответственных, получая на вход id и массив с новым списком ответственных

/*$sql = "CREATE TABLE otv (
podr_id INTEGER (255) NOT NULL,
otv_name VARCHAR (255) NOT NULL,
FOREIGN KEY (podr_id) REFERENCES spisok(id) ON DELETE CASCADE ON UPDATE CASCADE
)";*/
