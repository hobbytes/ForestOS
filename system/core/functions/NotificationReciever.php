<?
if (function_exists('date_default_timezone_set'))
date_default_timezone_set('Europe/Moscow');
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/bd.php';
$security	=	new security;
$bd = new readbd;
$login = strip_tags($_POST['login']);
$body = strip_tags($_POST['body']);
$appname = strip_tags($_POST['appname']);
$_key = strip_tags($_POST['key']);
$value = strip_tags($_POST['value']);
$_appname = str_replace(" ","_",$appname);
if(isset($login) && !empty($body) && isset($appname)){
  $bd->readglobal2("password","forestusers","login",$login);
  $key  = $getdata;
  $dir  = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$login.'/settings/notifications/';
  if(!is_dir($dir)){
    mkdir($dir);
  }
  if(!empty($_key) && !empty($value)){
    $body = $body."<br><span style='margin-left: 37%;' class='ui-button ui-widget ui-corner-all' onclick='makeprocess(&quot;system/apps/$_appname/main.php&quot; , &quot;$value&quot; , &quot;$_key&quot; , &quot;$_appname&quot;);'>Open</span>";
  }
  $body = $security->__encode($body, $key);
  $file = date('dmy_His').md5($login.$appname.date('dmy_His')).'.not';
  $content  = "[info]\rappname = ".$appname."\rdate = ".date('d.m.y, H:i:s')."\rbody = "."'$body'";
  file_put_contents($dir.$file,$content);
  echo 'TRUE';
}else{
  exit("FALSE");
}
?>
