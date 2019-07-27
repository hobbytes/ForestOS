<?
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/bd.php';
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/gui.php';
$security	=	new security;
$bd = new readbd;
$gui = new gui;
$security->appprepare();
$bd->readglobal2("password","forestusers","login",$_SESSION['loginuser']);
$key  = $getdata;
$dir  = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/settings/notifications/';

foreach(glob($dir.'*.not') as $filename){
  $NArray = parse_ini_file($filename);
  $body = $security->__decode($NArray['body'], $key);
  $gui->newnotification($NArray['appname'], $NArray['appname'], $body, 0, $NArray['date']);
  unset($NArray,$body);
  unlink($filename);
}
?>
