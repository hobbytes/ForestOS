<?

//Check security
require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
$security	=	new security;
$security->appprepare();


ini_set('display_errors','On');
error_reporting(E_ALL);

if(!isset($_SESSION)){
  session_start();
}

// if user auth
require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/prepare.php';
require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc.php';
require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/filesystem.php';
$prepare = new prepare;
$infob = new info;
$hashfile = new fileaction;

global $hashfile, $infob;

echo '<div id="desktop-1" class="desktop" desktopid="1">';
$infob->ismobile();

if($mobile == 'true'){
  $click = 'click';
  $top = '20px';
  $left = '0px';
  $maxwidth = '100%';
}else{
  $click = 'dblclick';
  $top = '25%';
  $left = '25%';
  $maxwidth = '90%';
}

$prepare->desktop("linkdiv", $_SESSION['loginuser']);
echo '</div>';
?>
