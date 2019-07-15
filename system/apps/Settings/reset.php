<?
/* Reset */

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
$AppContainer->userControl = true;
$AppContainer->StartContainer();

/* Delete system */
$DeleteStatus = $AppContainer->GetAnyRequest('reset');

if($DeleteStatus){
  
}

/* Make new objects */
?>
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<? echo $AppID ?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668; </span><?echo $language[$_SESSION['locale'].'_feedback']?></div>
<?php

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;">';
echo '<b style="font-size:20px;">Удаление системы</b>';

echo '<p style=" border: 2px dashed #dc4444; padding: 10px; background: #ff8787; color: #3e0b0b; font-weight: 600; margin: 15px auto; width: 80%;
">Внимание! Все данные, а также учетные записи всех пользователей будут безвозвратно удалены!</p>';

echo '<div
      messageTitle="Полное удаление системы"
      messageBody="Вы уверены, что хотите полностью удалить систему?"
      okButton="Удалить" cancelButton="Отмена"
      onClick="ExecuteFunctionRequest'.$AppID.'(this, \'reset'.$AppID.'\')"
      class="ui-forest-button ui-forest-cancel ui-forest-center">
      Полное удаление системы
</div><hr>';

echo '</div>';

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
