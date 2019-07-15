<?php
/*FOREST BD_A*/

require ('bd_inc.php');

//класс чтения из БД
class readbd{

public static function readglobal($globaldata){
  global $getdata;
  $conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
  $sql = "SELECT $globaldata FROM forestos";
  $id = $conn->query($sql);
  $row = $id->fetch();
  $getdata = $row[0];
  $conn = null;
}

public static function updatebd($table, $key, $value, $key_2, $value_2){
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

public static function readglobal2($globaldata, $from, $what, $like, $Return = false, $likeMode = false){
  global $getdata;
  $conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
  if($likeMode){
    $sql = "SELECT $globaldata, $what FROM $from where $what like '%$like%'";
  }else{
    $sql = "SELECT $globaldata, $what FROM $from where $what='$like'";
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

function GetAllUsers(){
  if( $_SESSION['superuser'] == $_SESSION['loginuser'] ){
    $usersArray = array();
    $conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
    $sql = "SELECT login, fuid, userhash FROM forestusers";
    $id = $conn->query($sql);
    while ($row = $id->fetch())
    {

      $usersArray['login'] = $row['login'];
      $usersArray['login'] = $row['fuid'];
      $usersArray['login'] = $row['userhash'];

    }
    return $usersArray;

  }else{

    return NULL;

  }
}

}
?>
