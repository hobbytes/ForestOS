<?
include '../library/filesystem.php';
include '../library/etc/security.php';
$security	=	new security;
if(!isset($_SESSION)){
  session_start();
}
$security->appprepare();
$faction = new fileaction;
$file_delete  = $_POST['file_delete'];
if(isset($file_delete)){
  $faction->rmdir_recursive($_SERVER['DOCUMENT_ROOT'].'/'.$file_delete, '../../users/'.$_SESSION["loginuser"].'/trash/');
}
?>
