<?
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/bd.php';
$security	=	new security;
$bd = new readbd;
$security->appprepare();
  $bd->readglobal2("password","forestusers","login",$_SESSION['loginuser']);
  $key  = $getdata;
  $dir  = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/settings/notifications/';
  if(!is_dir($dir)){
    mkdir($dir);
  }
  $file = 'MainNotificationFile.hdf';
  $file = file_get_contents($dir.$file);
  $body = $security->__decode($file, $key);
  echo $body;
?>
