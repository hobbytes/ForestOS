<?

/* Security */

/* get load data */
$AppID  = $_GET['appid'];
$AppName  = $_GET['appname'];
$Folder = $_GET['destination'];


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
<span onClick="back<?echo $AppID;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668; </span><?echo $language_security[$_SESSION['locale'].'_settings_security']?></div>

<?php

/* get data */

$erase  = $_GET['erase'];
$oldpassword  = $_GET['oldpassword'];
$newpassword  =  $_GET['newpassword'];
$checkpassword  =  $_GET['checkpassword'];
$GetKeyStatus = $_GET['getkey'];

if($erase == 'true'){
  file_put_contents('../../core/journal.mcj','');
}

$settingsbd = new readbd;
$gui = new gui;
$infob  = new info;
global $security;

$settingsbd->addColumn("forestusers", "TempKey", "VARCHAR", "255");

if(isset($_GET['delete_key_name'])){
  $delete_key_name = $_GET['delete_key_name'];
  $TempKeyArray = $settingsbd->readglobal2("TempKey", "forestusers", "login", $_SESSION["loginuser"], true);

  $get_keys = explode("[", $TempKeyArray);

  foreach ($get_keys as $key) {
    if(!empty($key)){
      $key = str_replace(']', '', $key);
      if(preg_match("/$delete_key_name/",$key)){
        $_TempKeyArray = str_replace('['.$key.']', '', $TempKeyArray);
        if($_TempKeyArray != $TempKeyArray){
          $settingsbd->updatebd("forestusers", "TempKey", $_TempKeyArray, "login", $_SESSION["loginuser"]);
        }
      }
    }
  }
}

if($GetKeyStatus == 'true'){

  $GetKeyName = $_GET['key_name'];

  if(empty($GetKeyName)){
    $GetKeyName = 'fos';
  }

  $_p = $settingsbd->readglobal2("password", "forestusers", "login", $_SESSION["loginuser"], true);
  $TempKeyArray = $settingsbd->readglobal2("TempKey", "forestusers", "login", $_SESSION["loginuser"], true);
  $settingsbd->addColumn("forestusers", "TempKey", "VARCHAR", "255");

  $GetKey = uniqid($GetKeyName.'-', $security->crypt_s(md5($_p.date('d-m-y-h-i-s')), $_SESSION["loginuser"]));
  $TempKeyArray = $TempKeyArray.'['.$GetKey.']';

  if(!empty($GetKey)){
    $settingsbd->updatebd("forestusers", "TempKey", $TempKeyArray, "login", $_SESSION["loginuser"]);
  }
}

if($GetKeyStatus == 'false'){
  $settingsbd->updatebd("forestusers","TempKey","","login",$_SESSION["loginuser"]);
}

if(!empty($oldpassword) && !empty($newpassword) && !empty($checkpassword)){

  $oldpassword  = $security->crypt_s($_GET['oldpassword'], $_SESSION["loginuser"]);
  $newpassword  =  $security->crypt_s($_GET['newpassword'], $_SESSION["loginuser"]);
  $checkpassword  =  $security->crypt_s($_GET['checkpassword'], $_SESSION["loginuser"]);

  $settingsbd->readglobal2("password", "forestusers", "login", $_SESSION["loginuser"]);
  $bdpass = $getdata;

  if($bdpass == $oldpassword){
    if($newpassword == $checkpassword){
      $settingsbd->readglobal2("fuid","forestusers","login",$_SESSION["loginuser"]);
      $fuid = $getdata;
      $d_root = $_SERVER['DOCUMENT_ROOT'];
      $token = md5($fuid.$d_root.$newpassword);
      $oldtoken = md5($fuid.$d_root.$oldpassword);
      $getRequest = file_get_contents('https://forest.hobbytes.com/media/os/ubase/updatetoken.php?token='.$token.'&oldtoken='.$oldtoken.'&login='.$_SESSION["loginuser"]);
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

  echo '
  <div style="text-align:left; margin-top:10px;">
    <b style="font-size:20px;">
      '.$language_security[$_SESSION['locale'].'_tempkey_label'].'
    </b>

    <br><br>
    <div>'.$language_security[$_SESSION['locale'].'_namekey_label'].':</div>';
    $gui->inputslabel('', 'text', 'key_name'.$AppID, 'fos','25', $language_security[$_SESSION['locale'].'_namekey_label']);

    if(!empty($GetKey)){
      echo
      $language_security[$_SESSION['locale'].'_tempkey_copy'].'
      <div style="margin: 10 0; padding: 7px; font-weight: 900; width: max-content; border: 2px dashed #144015; background: #4caf50; color: #074009;">
       '.$GetKey.'
      </div>';
    }

    echo '<div id="GetKey'.$AppID.'" onClick="GetKey'.$AppID.'();" class="ui-forest-button ui-forest-accept" style="margin:10 0;">
      '.$language_security[$_SESSION['locale'].'_tempkey_button'].'
    </div>';

    $TempKeyArray = $settingsbd->readglobal2("TempKey", "forestusers", "login", $_SESSION["loginuser"], true);

    if(!empty($TempKeyArray)){
      $get_keys = explode("[", $TempKeyArray);

      foreach ($get_keys as $key) {
       $temp = stristr($key, '.', true);
       $key = stristr($key, '-', true);

        if(!empty($key)){
          echo '<div style="margin: 10 0; padding: 7px; min-width:70px; font-weight: 900; width: max-content; border: 2px dashed #8c7f3b; background: #e6d26a;">
           '.$key.'
           <div style="float:right; color:#e66a6a;" messageTitle="'.$language_security[$_SESSION['locale'].'_deltekey_mt'].'" messageBody="'.$language_security[$_SESSION['locale'].'_deltekey_mb'].'" okButton="'.$language_security[$_SESSION['locale'].'_ok_btn'].'" cancelButton="'.$language_security[$_SESSION['locale'].'_cancel_btn'].'" onClick="ExecuteFunctionRequest'.$AppID.'(this, \'DeleteKey'.$AppID.'\', \''.$temp.'\')" class="ui-forest-blink">x</div>
          </div>';
        }
      }
  }

    unset($get_keys, $key, $TempKeyArray);

    echo
    '<div id="EraseKey'.$AppID.'"
     messageTitle="'.$language_security[$_SESSION['locale'].'_erasekey_mt'].'" messageBody="'.$language_security[$_SESSION['locale'].'_erasekey_mb'].'" okButton="'.$language_security[$_SESSION['locale'].'_ok_btn'].'" cancelButton="'.$language_security[$_SESSION['locale'].'_cancel_btn'].'" onClick="ExecuteFunctionRequest'.$AppID.'(this, \'EraseKey'.$AppID.'\')" class="ui-forest-button ui-forest-cancel" style="margin:10 0;">
    '.$language_security[$_SESSION['locale'].'_tempkeyerase_button'].'
    </div>';

  echo '</div><hr>';

$infob->readstat('../../core/journal.mcj');
$text = $getstat;
echo '<div style="text-align:left; margin-top:10px;"><b style="font-size:20px;">'.$language_security[$_SESSION['locale'].'_journal_label'].'</b>';
echo '<div><textarea style="width:95%; max-width:95%;" rows="10" cols="80" >'.$text.'</textarea></div></div>';
if($_SESSION['loginuser'] == $_SESSION['superuser']){
  echo '<div
  messageTitle="'.$language_security[$_SESSION['locale'].'_eraselog_mt'].'" messageBody="'.$language_security[$_SESSION['locale'].'_eraselog_mb'].'" okButton="'.$language_security[$_SESSION['locale'].'_ok_btn'].'" cancelButton="'.$language_security[$_SESSION['locale'].'_cancel_btn'].'" onClick="ExecuteFunctionRequest'.$AppID.'(this, \'eraselog'.$AppID.'\')" style="margin:10px 0;"
  class="ui-forest-button ui-forest-cancel">'.$language_security[$_SESSION['locale'].'_button_journal'].'</div><hr>';
}

unset($settingsbd);

$AppContainer->EndContainer();
?>

<script>

<?php

//Execute Function Request
$AppContainer->ExecuteFunctionRequest();

// back button
$AppContainer->Event(
  "back",
  NULL,
  $Folder,
  'main'
);

// Get Key
$AppContainer->Event(
	"GetKey",
  NULL,
	$Folder,
	'security',
	array(
    'key_name' => '"+escape($("#key_name'.$AppID.'").val())+"',
		'getkey' => 'true'
	)
);

// Delete Key
$AppContainer->Event(
	"DeleteKey",
  'key',
	$Folder,
	'security',
	array(
    'delete_key_name' => '"+key+"'
	)
);

// Erase Key
$AppContainer->Event(
	"EraseKey",
  NULL,
	$Folder,
	'security',
	array(
		'getkey' => 'false'
	)
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
