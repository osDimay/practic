<?php
/* $servername = "localhost";
$username = "root";
$password = "";
$dbname = "mydb";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if ($conn->connect_error) {
	die("Connection failed: " . mysqli_connect_error());
}
//echo "Connection successfully";

 */




define('servername','localhost');
define('username','root');
define('pass','');
define('dbname','mydb');
define('charset','UTF8');

class DbClass
{
    private $servername, $username, $pass, $dbname, $charset, $db_ne_conn;
    private $conn = 0;

    function __construct($servername, $username, $pass, $dbname, $charset)
    {
        $this->servername = $servername;
        $this->username = $username;
        $this->pass = $pass;
        $this->dbname = $dbname;
		$this->charset = $charset;		
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
					"parent_id"=>$row["parent_id"]
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
					"parent_id"=>$row["parent_id"]
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
}

$db = new DbClass(servername, username, pass, dbname, charset);
//$db->select('*', 'spisok');
//$db->selectMass();
//$db->buildTree($mass);
$db->buildSimpleTree();
//$db->delete(7);
//$db->showTree(0,0);
//$db->update('name', "'MSU'", 1);
//$db->peremes(8, 13);

/* $sql = "INSERT INTO spisok (name) VALUES ('OilAndGasUniversity');";
$sql .= "INSERT INTO spisok (name) VALUES ('pro_Volkov');";
$sql .= "INSERT INTO spisok (name) VALUES ('pro_Shilov');";
$sql .= "INSERT INTO spisok (name) VALUES ('pro_Ustinov');";
$sql .= "INSERT INTO spisok (name) VALUES ('pro_Sulko');";
$sql .= "INSERT INTO spisok (name) VALUES ('fak_AiVT');";
$sql .= "INSERT INTO spisok (name) VALUES ('fak_Yuridich');";
$sql .= "INSERT INTO spisok (name) VALUES ('fak_Geologich');";
$sql .= "INSERT INTO spisok (name) VALUES ('fak_Razrabotki');";
$sql .= "INSERT INTO spisok (name) VALUES ('fak_Fiziki');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Matematiki');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Informatiki');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Kibernetiki');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Fiziki');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Bureniya');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Geologii');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_RusYaz');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Prava');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Economiki');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Mehaniki');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_FizikiPolya');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Himii');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Termodinamili');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Proectirovaniya');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_UgolnogoPrava');";
$sql .= "INSERT INTO spisok (name) VALUES ('kaf_Istorii');";

$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (1,2);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (1,3);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (1,4);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (1,5);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (2,10);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (4,6);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (4,7);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (4,8);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (5,9);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (6,11);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (6,12);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (6,13);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (7,17);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (7,18);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (7,25);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (7,26);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (8,15);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (8,16);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (9,22);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (9,24);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (7,19);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (10,14);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (10,20);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (10,21);";
$sql .= "INSERT INTO ekviv (parent_id, child_id) VALUES (10,23)";

if (mysqli_multi_query($conn,$sql)){
    echo "insertion successfully";
    } else {
	    echo "Error" . mysqli_error($conn);
    }  */

/* $sql = "UPDATE spisok SET parent_id=1 WHERE id=2;";
$sql .= "UPDATE spisok SET parent_id=1 WHERE id=3;";
$sql .= "UPDATE spisok SET parent_id=1 WHERE id=4;";
$sql .= "UPDATE spisok SET parent_id=1 WHERE id=5;";
$sql .= "UPDATE spisok SET parent_id=2 WHERE id=10;";
$sql .= "UPDATE spisok SET parent_id=4 WHERE id=6;";
$sql .= "UPDATE spisok SET parent_id=4 WHERE id=7;";
$sql .= "UPDATE spisok SET parent_id=4 WHERE id=8;";
$sql .= "UPDATE spisok SET parent_id=5 WHERE id=9;";
$sql .= "UPDATE spisok SET parent_id=6 WHERE id=11;";
$sql .= "UPDATE spisok SET parent_id=6 WHERE id=12;";
$sql .= "UPDATE spisok SET parent_id=6 WHERE id=13;";
$sql .= "UPDATE spisok SET parent_id=7 WHERE id=17;";
$sql .= "UPDATE spisok SET parent_id=7 WHERE id=18;";
$sql .= "UPDATE spisok SET parent_id=7 WHERE id=25;";
$sql .= "UPDATE spisok SET parent_id=7 WHERE id=26;";
$sql .= "UPDATE spisok SET parent_id=8 WHERE id=15;";
$sql .= "UPDATE spisok SET parent_id=8 WHERE id=16;";
$sql .= "UPDATE spisok SET parent_id=9 WHERE id=22;";
$sql .= "UPDATE spisok SET parent_id=9 WHERE id=24;";
$sql .= "UPDATE spisok SET parent_id=7 WHERE id=19;";
$sql .= "UPDATE spisok SET parent_id=10 WHERE id=14;";
$sql .= "UPDATE spisok SET parent_id=10 WHERE id=20;";
$sql .= "UPDATE spisok SET parent_id=10 WHERE id=21;";
$sql .= "UPDATE spisok SET parent_id=10 WHERE id=23;";

if (mysqli_multi_query($conn,$sql)){
    echo "Modify successfully";
    } else {
        echo "Error" . mysqli_error($conn);
    } */

?>