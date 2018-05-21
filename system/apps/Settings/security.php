<?

/* Security */

/* get load data */
$AppID  = $_GET['appid'];
$AppName  = $_GET['appname'];
$Folder = $_GET['destination'];
$erase  = $_GET['erase'];
$oldpassword  = $_GET['oldpassword'];
$newpassword  =  $_GET['newpassword'];
$checkpassword  =  $_GET['checkpassword'];

/* get localization file */
$language_security  = parse_ini_file('lang/security.lang');

require '../../core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('filesystem', 'bd', 'gui', 'etc');
$AppContainer->StartContainer();

?>

<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $AppID;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_security[$_SESSION['locale'].'_settings_security']?></div>

<?php

if($erase == 'true'){
  file_put_contents('../../core/journal.mcj','');
}
$settingsbd = new readbd;
$gui = new gui;
$infob  = new info;
global $security;

if(!empty($oldpassword) && !empty($newpassword) && !empty($checkpassword)){

  $oldpassword  = $security->crypt_s($_GET['oldpassword'],$_SESSION["loginuser"]);
  $newpassword  =  $security->crypt_s($_GET['newpassword'],$_SESSION["loginuser"]);
  $checkpassword  =  $security->crypt_s($_GET['checkpassword'],$_SESSION["loginuser"]);

  $settingsbd->readglobal2("password","forestusers","login",$_SESSION["loginuser"]);
  $bdpass=$getdata;

  if($bdpass == $oldpassword){
    if($newpassword == $checkpassword){
      $settingsbd->readglobal2("fuid","forestusers","login",$_SESSION["loginuser"]);
      $fuid = $getdata;
      $d_root = $_SERVER['DOCUMENT_ROOT'];
      $token = md5($fuid.$d_root.$newpassword);
      $oldtoken = md5($fuid.$d_root.$oldpassword);
      $getRequest = file_get_contents('http://forest.hobbytes.com/media/os/ubase/updatetoken.php?token='.$token.'&oldtoken='.$oldtoken.'&followlink='.$_SERVER['SERVER_NAME']);
      if($getRequest != "OK"){
        $gui->errorLayot("Invalid token!");
        exit();
      }
      $settingsbd->updatebd("forestusers",password,$newpassword,login,$_SESSION["loginuser"]);
      $gui->newnotification($AppName,$language_security[$_SESSION['locale'].'_settings_security'],$language_security[$_SESSION['locale'].'_notchangepass']);
      file_put_contents('../../core/journal.mcj','');
    }else{
      $gui->newnotification($AppName,$language_security[$_SESSION['locale'].'_settings_security'],$language_security[$_SESSION['locale'].'_notnewerrorpass']);
    }
  }else{
    $gui->newnotification($AppName,$language_security[$_SESSION['locale'].'_settings_security'],$language_security[$_SESSION['locale'].'_notolderrorpass']);
  }
  unset($oldpassword,$newpassword,$checkpassword);
}

  echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language_security[$_SESSION['locale'].'_changepassword_label'].'</b>';
  echo "<div>".$language_security[$_SESSION['locale'].'_inputoldpass'].":</div>";
  $gui->inputslabel('', 'password', ''.$AppID.'oldpassword', ''.$adduserlogin.'','50', $language_security[$_SESSION['locale'].'_inputoldpass']);
  echo "<div>".$language_security[$_SESSION['locale'].'_inputnewpass'].":</div>";
  $gui->inputslabel('', 'password', ''.$AppID.'newpassword', ''.$adduserpassword.'','50',$language_security[$_SESSION['locale'].'_inputnewpass']);
  echo "<div>".$language_security[$_SESSION['locale'].'_inputnewpass_2'].":</div>";
  $gui->inputslabel('', 'password', ''.$AppID.'checkpassword', ''.$adduserpassword.'','50',$language_security[$_SESSION['locale'].'_inputnewpass_2']);

  echo '<div id="changepassword'.$AppID.'" onClick="changepassword'.$AppID.'();" class="ui-forest-button ui-forest-accept">'.$language_security[$_SESSION['locale'].'_button_change'].'</div><hr>';

$infob->readstat('../../core/journal.mcj');
$text=$getstat;
echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language_security[$_SESSION['locale'].'_journal_label'].'</b>';
echo '<div><textarea style="width:95%; max-width:95%;" rows="10" cols="80" >'.$text.'</textarea></div></div>';
if($_SESSION['loginuser'] == $_SESSION['superuser']){
  echo '<div onClick="eraselog'.$AppID.'();" style="margin:10px;" class="ui-forest-button ui-forest-cancel">'.$language_security[$_SESSION['locale'].'_button_journal'].'</div><hr>';
}
unset($settingsbd);

$AppContainer->EndContainer();
?>
<script>
<?php
// back button
$AppContainer->Event(
  "back",
  NULL,
  $Folder,
  'main'
);

// erase log
$AppContainer->Event(
	"eraselog",
  NULL,
	$Folder,
	'security',
	array(
		'erase' => 'true'
	)
);

$AppContainer->Event(
	"changepassword",
  NULL,
	$Folder,
	'security',
	array(
    'oldpassword' => '"+escape($("#'.$AppID.'oldpassword").val())+"',
    'newpassword' => '"+escape($("#'.$AppID.'newpassword").val())+"',
    'checkpassword' => '"+escape($("#'.$AppID.'checkpassword").val())+"'
	)
);

?>
</script>
