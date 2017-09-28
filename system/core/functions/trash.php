<?
session_start();
include '../library/filesystem.php';
$faction = new fileaction;
$file_delete  = $_POST['file_delete'];
if(isset($file_delete)){
  $faction->rmdir_recursive($_SERVER['DOCUMENT_ROOT'].'/'.$file_delete, '../../users/'.$_SESSION["loginuser"].'/trash/');
}
?>
