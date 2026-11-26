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

$resetMsg = $language[$_SESSION['locale'].'_unavailable'];

if($DeleteStatus){
  // Legacy flow did TRUNCATE TABLE forestusers and then wiped system/.
  // It is intentionally disabled here: nothing is modified.
  echo '<div style="text-align:left; margin:10px;"><p style="border:2px dashed #dc4444; padding:10px; background:#ff8787; color:#3e0b0b; font-weight:600;">'.$resetMsg.'</p></div>';
}
?>
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<? echo $AppID ?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668; </span><?echo $language[$_SESSION['locale'].'_reset']?></div>
<?php

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;">';
echo '<b style="font-size:20px;">'.$language[$_SESSION['locale'].'_reset_label'].'</b>';
echo '<p style="border:2px dashed #dc4444; padding:10px; background:#ff8787; color:#3e0b0b; font-weight:600; margin:15px auto; width:80%;">'.$resetMsg.'</p>';
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
