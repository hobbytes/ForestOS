<?php
/*FOREST BD_A*/

include ('bd_inc.php');

//класс чтения из БД
class readbd{
public static function read($data){
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="SELECT $data FROM forestos";
$id=$conn->query($sql);
$row = $id->fetch();
$f_data=$row[0];
echo $f_data;
$conn=null;
}

public static function readglobal($globaldata){
global $getdata;
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="SELECT $globaldata FROM forestos";
$id=$conn->query($sql);
$row = $id->fetch();
$getdata=$row[0];
$conn=null;
}

public static function readglobal2($globaldata,$from,$what,$like){
global $getdata;
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="SELECT $globaldata,$what FROM $from where $what='$like'";
$id=$conn->query($sql);
$row = $id->fetch();
$getdata=$row[0];
$conn=null;
}

public static function readglobalfunction($globaldata,$table,$what,$this){
global $getdata;
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="SELECT $globaldata FROM forest$table where $what='$this'";
if($id=$conn->query($sql)){
$row = $id->fetch();
$getdata=$row[0];
$conn=null;}
}

public static function simpleinsert($table,$name,$value){
global $simpleinsertstatus;
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="INSERT INTO forest$table ($name) VALUES ('$value')";
if($conn->query($sql)){$simpleinsertstatus='true';}else{$simpleinsertstatus='false';}
$conn=null;
}

public static function simpledelete($table,$name,$value){
global $simpledeletestatus;
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="DELETE FROM forest$table WHERE $name=$value";
if($conn->query($sql)){$simpledeletestatus='true';}else{$simpledeletestatus='false';}
$conn=null;
}

public static function updateids($table){
  $conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
  $sql="ALTER TABLE forest$table MODIFY `id` INT(11);
  ALTER TABLE forest$table DROP PRIMARY KEY;
  UPDATE forest$table SET `id`='0';
  ALTER TABLE forest$table AUTO_INCREMENT=0;
  ALTER TABLE forest$table MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY; ";
  $conn->query($sql);
}
}
?>
