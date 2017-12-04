<?
include '../library/etc/security.php';
include '../library/filesystem.php';
$security	=	new security;
$fileaction = new fileaction;
session_start();
$security->appprepare();
$getFile = $_POST['f'];
$newPlace = $_POST['n'];
$action = $_POST['a'];

$fileaction->rcopy($getFile, $newPlace);
?>
