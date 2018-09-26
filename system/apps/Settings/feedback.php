<?
/* Feedback */

/* get load data */
$AppID  = $_GET['appid'];
$AppName  = $_GET['appname'];
$Folder = $_GET['destination'];

/* get localization file */
$language  = parse_ini_file('lang/feedback.lang');

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('gui');
$AppContainer->StartContainer();

/* Make new objects */
$gui = new gui;
?>
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $AppID;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language[$_SESSION['locale'].'_feedback']?></div>
<?php
if(!empty($_GET['t']) && !empty($_GET['m'])){
  $osinfo = parse_ini_file('../../core/osinfo.foc', false);
  $status = file_get_contents('http://forest.hobbytes.com/media/os/feedback.php?u='.$_SESSION['loginuser'].'&t='.urlencode($_GET['t']).'&m='.urlencode($_GET['m']).'&v='.str_replace(' ','_',$osinfo['codename'].$osinfo['subversion']));
  if($status == 'true'){
    $gui->infoLayot($language[$_SESSION['locale'].'_status_true']);
  }else{
    $gui->errorLayot($language[$_SESSION['locale'].'_status_false']);
  }
}

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language[$_SESSION['locale'].'_feedback_label'].'</b>';
echo "<br><br><div>".$language[$_SESSION['locale'].'_inputtheme_label'].":</div>";
$gui->inputslabel('', 'text', ''.$AppID.'feedback_theme', '','50',$language[$_SESSION['locale'].'_inputtheme_label']);
echo "<div>".$language[$_SESSION['locale'].'_inputmessage_label'].":</div>";
echo '<div><textarea id="'.$AppID.'feedback_message" style="width:95%; max-width:95%;" rows="10" cols="80" >'.$text.'</textarea></div><br>';
echo '<div id="send'.$AppID.'" onClick="send_message'.$AppID.'();" class="ui-forest-button ui-forest-center ui-forest-accept">'.$language[$_SESSION['locale'].'_button_send'].'</div></div>';

echo '<div style="text-align:right; margin:22px;"><b>e-mail:</b><div>contact@hobbytes.com</div></div>';

$AppContainer->EndContainer();
?>
<script>

<?
// back button
$AppContainer->Event(
  "back",
  NULL,
  $Folder,
  'main'
);

//send message
$AppContainer->Event(
	"send_message",
  NULL,
	$Folder,
	'feedback',
	array(
    't' => '"+escape($("#'.$AppID.'feedback_theme").val())+"',
    'm' => '"+escape($("#'.$AppID.'feedback_message").val())+"'
	)
);
?>
</script>
