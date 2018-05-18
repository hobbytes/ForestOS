<?
include '../library/etc/security.php';
include '../library/filesystem.php';
$security	=	new security;
$fileaction = new fileaction;
if(!isset($_SESSION)){
  session_start();
}
$security->appprepare();
$getFile = $_POST['f'];
$newPlace = $_POST['n'];
$action = $_POST['a'];

$fileaction->rcopy($getFile, $newPlace, 1);
if($action == 'cut'){
  if(is_dir($getFile)){
    $fileaction->deleteDir($getFile);
  }else{
    unlink($getFile);
  }
}
?>
