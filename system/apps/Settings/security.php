<?
//Инициализируем переменные
$appid  = $_GET['appid'];
$erase  = $_GET['erase'];
$appname  = $_GET['appname'];
$folder = $_GET['destination'];
$oldpassword  = $_GET['oldpassword'];
$newpassword  =  $_GET['newpassword'];
$checkpassword  =  $_GET['checkpassword'];
session_start();
$language_security  = parse_ini_file('lang/security.lang');
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_security[$_SESSION['locale'].'_settings_security']?></div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';
include '../../core/library/etc.php';
$security	=	new security;
$security->appprepare();
if($erase=='true'){
  file_put_contents('../../core/journal.mcj','');
}
$settingsbd = new readbd;
$gui = new gui;
$infob  = new info;

if(!empty($oldpassword) && !empty($newpassword) && !empty($checkpassword)){

  $oldpassword  = $security->crypt_s($_GET['oldpassword'],$_SESSION["loginuser"]);
  $newpassword  =  $security->crypt_s($_GET['newpassword'],$_SESSION["loginuser"]);
  $checkpassword  =  $security->crypt_s($_GET['checkpassword'],$_SESSION["loginuser"]);

  $settingsbd->readglobal2("password","forestusers","login",$_SESSION["loginuser"]);
  $bdpass=$getdata;

  if($bdpass==$oldpassword){
    if($newpassword==$checkpassword){
      $settingsbd->updatebd("forestusers",password,$newpassword,login,$_SESSION["loginuser"]);
      echo $language_security[$_SESSION['locale'].'_notchangepass'];
      file_put_contents('../../core/journal.mcj','');
    }else{
      echo $language_security[$_SESSION['locale'].'_notnewerrorpass'];
    }
  }else{
    echo $language_security[$_SESSION['locale'].'_notolderrorpass'];
  }
  unset($oldpassword,$newpassword,$checkpassword);
}

  echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language_security[$_SESSION['locale'].'_changepassword_label'].'</b>';
  echo "<div>".$language_security[$_SESSION['locale'].'_inputoldpass'].":</div>";
  $gui->inputslabel('', 'password', ''.$appid.'oldpassword', ''.$adduserlogin.'','50', $language_security[$_SESSION['locale'].'_inputoldpass']);
  echo "<div>".$language_security[$_SESSION['locale'].'_inputnewpass'].":</div>";
  $gui->inputslabel('', 'password', ''.$appid.'newpassword', ''.$adduserpassword.'','50',$language_security[$_SESSION['locale'].'_inputnewpass']);
  echo "<div>".$language_security[$_SESSION['locale'].'_inputnewpass_2'].":</div>";
  $gui->inputslabel('', 'password', ''.$appid.'checkpassword', ''.$adduserpassword.'','50',$language_security[$_SESSION['locale'].'_inputnewpass_2']);

  echo '<div id="changepassword'.$appid.'" onClick="changepassword'.$appid.'();" class="ui-forest-button ui-forest-accept">'.$language_security[$_SESSION['locale'].'_button_change'].'</div><hr>';

$infob->readstat('../../core/journal.mcj');
$text=$getstat;
echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language_security[$_SESSION['locale'].'_journal_label'].'</b>';
echo '<div><textarea style="width:95%; max-width:95%;" rows="10" cols="80" >'.$text.'</textarea></div></div>';
if($_SESSION['loginuser'] == $_SESSION['superuser']){
  echo '<div onClick="eraselog'.$appid.'();" style="margin:10px;" class="ui-forest-button ui-forest-cancel">'.$language_security[$_SESSION['locale'].'_button_journal'].'</div><hr>';
}
unset($settingsbd);
?>
</div>
<script>
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function eraselog<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder?>security.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid.'&erase=true';?>")};
function changepassword<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder?>security.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&oldpassword="+document.getElementById("<?echo $appid.'oldpassword';?>").value+"&newpassword="+document.getElementById("<?echo $appid.'newpassword';?>").value+"&checkpassword="+document.getElementById("<?echo $appid.'checkpassword';?>").value)};
</script>
