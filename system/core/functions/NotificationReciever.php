<?
if (function_exists('date_default_timezone_set'))
date_default_timezone_set('Europe/Moscow');
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/bd.php';
$security	=	new security;
$bd = new readbd;
$login = $_GET['login'];
$body = $_GET['body'];
$appname = $_GET['appname'];
if(isset($login) && isset($body) && isset($appname)){
  $bd->readglobal2("password","forestusers","login",$login);
  $key  = $getdata;
  $body = $security->__encode($body, $key);
  $dir  = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$login.'/settings/notifications/';
  if(!is_dir($dir)){
    mkdir($dir);
  }
  $file = date('dmy_His').md5($login.$appname.date('dmy_His')).'.not';
  $content  = "[info]\rappname = ".$appname."\rdate = ".date('d.m.y, H:i:s')."\rbody = "."'$body'";
  file_put_contents($dir.$file,$content);
  echo 'TRUE';
}else{
  exit("FALSE");
}
?>
