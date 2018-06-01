<?
if(!isset($_SESSION)){
  session_start();
}
if(isset($_SESSION['loginuser'])){
  if (function_exists('date_default_timezone_set'))
  date_default_timezone_set('Europe/Moscow');
  include 'security.php';
  include '../bd.php';
  global $getdata;
  $security = new security;
  $bd = new readbd;
  $bd->readglobal2("password","forestusers","login",$_SESSION["loginuser"]);
  $key = $getdata;
  $content = $_POST['content'];
  $id = $_POST['appid'];
  $content = $security->__encode($content, $key);
  $content  = "[info]\rtime_stamp = ".date('d.m.y, H:i:s')."\rlast_app_id = $id\rstate="."'$content'";
  file_put_contents('../../../users/'.$_SESSION['loginuser'].'/settings/state.hdf',$content);
}else{
  exit;
}
?>
