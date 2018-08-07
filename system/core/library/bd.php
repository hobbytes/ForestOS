<?php
/*FOREST BD_A*/

include ('bd_inc.php');

//класс чтения из БД
class readbd{
public static function read($data){
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql = "SELECT $data FROM forestos";
$id = $conn->query($sql);
$row = $id->fetch();
$f_data = $row[0];
echo $f_data;
$conn = null;
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

public static function updatebd($table,$key,$value,$key_2,$value_2){
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="UPDATE $table SET $key='$value' WHERE $key_2='$value_2'";
$conn->query($sql);
}

public static function addColumn($table, $newColumn, $type, $size){
  $dirname = $_SERVER['DOCUMENT_ROOT'].'/system/core/TempTable/';
  if(!is_dir($dirname)){
    mkdir($dirname,0777);
  }
  if(!file_exists($dirname.$newColumn.'.foc')){
    $type = mb_strtoupper($type);
    $conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
    $sql="ALTER TABLE $table ADD $newColumn $type( $size )";
    $conn->query($sql);
    file_put_contents($dirname.$newColumn.'.foc','0');
  }
}

public static function readglobal2($globaldata,$from,$what,$like, $Return = false, $likeMode = false){
global $getdata;
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
if($likeMode){
  $sql = "SELECT $globaldata,$what FROM $from where $what like '%$like%'";
}else{
  $sql = "SELECT $globaldata,$what FROM $from where $what='$like'";
}

$id = $conn->query($sql);
$row = $id->fetch();
if(!$Return){
  $getdata = $row[0];
}else{
  return $row[0];
}
unset($conn);
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
