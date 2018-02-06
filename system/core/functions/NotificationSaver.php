<?
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/bd.php';
$security	=	new security;
$bd = new readbd;
session_start();
$security->appprepare();
$login = $_POST['login'];
$body = $_POST['body'];
if(isset($login) && isset($body)){
  $bd->readglobal2("password","forestusers","login",$login);
  $key  = $getdata;
  $body = $security->__encode($body, $key);
  $dir  = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$login.'/settings/notifications/';
  if(!is_dir($dir)){
    mkdir($dir);
  }
  $file = 'MainNotificationFile.hdf';
  file_put_contents($dir.$file,$body);
  echo "true";
}else{
  exit("false");
}
?>
