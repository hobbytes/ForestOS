<?
/* Reset */

/* get load data */
$AppID  = $_GET['appid'];
$AppName  = $_GET['appname'];
$Folder = $_GET['destination'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->userControl = true;
$AppContainer->LibraryArray = Array('bd', 'filesystem');
$AppContainer->StartContainer();

/* Make new objects */
$FileAction = new fileaction;

/* Get localization file */
$language = parse_ini_file('lang/reset.lang');

/* Delete system */
$DeleteStatus = $AppContainer->GetAnyRequest('reset');

if($DeleteStatus){

  $bd = new readbd;
  $UsersList = $bd->GetAllUsers();
  $server_url = "https://forest.hobbytes.com/media/os/ubase/deleteuser.php";

  foreach ($UsersList as $key => $value) {

    $password = $UsersList[$key]['password'];
    $fuid = $UsersList[$key]['fuid'];

    $userhash = md5($fuid.$_SERVER['DOCUMENT_ROOT'].$password);

    $data = http_build_query(array('fuid' => "$fuid", 'followlink' => $_SERVER['SERVER_NAME'], 'userhash' => "$userhash"));
    $request = file_get_contents($server_url.'?'.$data);

  }

  $conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
  $conn->query("TRUNCATE TABLE forestusers");

  unset($password, $fuid, $userhash);

  $curlCh = curl_init();
  curl_setopt($curlCh, CURLOPT_URL, "https://forest.hobbytes.com/media/os/installer.zip");
  curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curlCh, CURLOPT_SSLVERSION,3);
  $curlData = curl_exec($curlCh);
  curl_close($curlCh);

  $TempFile = '../../../installer.zip';
  $file = fopen($TempFile, "w+");
  fputs($file, $curlData);
  fclose($file);

  $zip = new ZipArchive;
  if($zip->open($TempFile) === TRUE){
    $zip->extractTo('../../../');
    $zip->close();
    unlink($TempFile);

    $FileAction->deleteDir('../../../system');

    foreach (glob('../../../{,.}*', GLOB_BRACE) as $file){
      if ($file != "../../../index.php"){
        unlink($file);
      }
    }
  }

  ?>

  <script language="JavaScript">

  function timer(time,update,complete) {
      var start = new Date().getTime();
      var interval = setInterval(function() {
          var now = time-(new Date().getTime()-start);
          if( now <= 0) {
              clearInterval(interval);
              complete();
          }
          else update(Math.floor(now/1000));
      },100);
  }

  timer(
      11000, // milliseconds
      function(timeleft) {
          document.getElementById('timersad').innerHTML = timeleft+" ";
      },
      function() {
          window.location.replace("http://<?php echo $_SERVER['SERVER_NAME'] ?>");
      }
  );

  </script>

  <?php

}

?>
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<? echo $AppID ?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668; </span><?echo $language[$_SESSION['locale'].'_reset']?></div>

<?php

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;">';
echo '<b style="font-size:20px;">'.$language[$_SESSION['locale'].'_reset_label'].'</b>';

if(!$DeleteStatus){
  echo '<p style=" border: 2px dashed #dc4444; padding: 10px; background: #ff8787; color: #3e0b0b; font-weight: 600; margin: 15px auto; width: 80%;
  ">'.$language[$_SESSION['locale'].'_reset_warning_1'].'</p>';

  echo '<div
        messageTitle="'.$language[$_SESSION['locale'].'_messageTitle'].'"
        messageBody="'.$language[$_SESSION['locale'].'_messageBody'].'"
        okButton="'.$language[$_SESSION['locale'].'_okButton'].'" cancelButton="'.$language[$_SESSION['locale'].'_cancelButton'].'"
        onClick="ExecuteFunctionRequest'.$AppID.'(this, \'reset'.$AppID.'\')"
        class="ui-forest-button ui-forest-cancel ui-forest-center">
        '.$language[$_SESSION['locale'].'_messageTitle'].'
  </div>';
}else{
  echo '<p style=" border: 2px dashed #dc4444; padding: 10px; background: #ff8787; color: #3e0b0b; font-weight: 600; margin: 15px auto; width: 80%;
  ">'.$language[$_SESSION['locale'].'_reset_warning_2'].'</p>';
}



echo '<hr></div>';

$AppContainer->EndContainer();
?>
<script>

<?

//Execute Function Request
$AppContainer->ExecuteFunctionRequest();

// back button
$AppContainer->Event(
  "back",
  NULL,
  $Folder,
  'main'
);

//reset
$AppContainer->Event(
	"reset",
  NULL,
	$Folder,
	'reset',
	array(
    'reset' => true
	)
);
?>

</script>
