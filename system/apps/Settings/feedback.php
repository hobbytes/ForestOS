<?
//Инициализируем переменные
$appid  = $_GET['appid'];
$appname  = $_GET['appname'];
$folder = $_GET['destination'];
$language  = parse_ini_file('lang/feedback.lang');
session_start();
/*Settings*/
//Подключаем библиотеки
include '../../core/library/gui.php';
require '../../core/library/Mercury/AppContainer.php';
$gui = new gui;

/* Make new container */
$AppContainer = new AppContainer;
$AppContainer->appName = $appname;
$AppContainer->appID = $appid;
$AppContainer->StartContainer();
?>
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language[$_SESSION['locale'].'_feedback']?></div>
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
$gui->inputslabel('', 'text', ''.$appid.'feedback_theme', '','50',$language[$_SESSION['locale'].'_inputtheme_label']);
echo "<div>".$language[$_SESSION['locale'].'_inputmessage_label'].":</div>";
echo '<div><textarea id="'.$appid.'feedback_message" style="width:95%; max-width:95%;" rows="10" cols="80" >'.$text.'</textarea></div><br>';
echo '<div id="send'.$appid.'" onClick="send_message'.$appid.'();" class="ui-forest-button ui-forest-center ui-forest-accept">'.$language[$_SESSION['locale'].'_button_send'].'</div></div>';

$AppContainer->EndContainer();
?>
<script>
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function send_message<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder?>feedback.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&t="+escape($('#<?echo $appid?>feedback_theme').val())+"&m="+escape($('#<?echo $appid?>feedback_message').val()))};
</script>
