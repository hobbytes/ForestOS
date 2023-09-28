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
<span onClick="back<?echo $AppID;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668; </span><?echo $language[$_SESSION['locale'].'_feedback']?></div>

<script>
  $( function() {
    $( "#sortable1, #sortable2, #sortableA, #sortableB, #sortableC, #sortableD, #sortableE, #sortableF, #sortableG" ).sortable({
      connectWith: ".connectedSortable"
    }).disableSelection();
  } );
  </script>

<div style="display: flex; user-select: none;">
  <div id="sortable1" class="connectedSortable" style="flex: 50%; border-right: 1px solid #ccc;">

<?php

$dir_ = $_SERVER['DOCUMENT_ROOT'];
$dir = $dir_.'/system/users/'.$_SESSION["loginuser"].'/settings/Dock/';

if(!is_dir($dir))
  {
    mkdir($dir);
  }

$dir_array = array("A","B","C","D","E","F","G");

foreach ($dir_array as $key => $value) {
 
 if(!is_dir($dir.$value.'/')){
    mkdir($dir.$value.'/');
  }


}

function GetAppInfo($ApplicationName){

  $info = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/system/apps/'.$ApplicationName.'/main.php?getinfo=true&h='.md5(date('dmyhis')));
  $arrayInfo = json_decode($info);
  $app_icon = 'system/apps/'.$ApplicationName.'/app.png?h='.md5($arrayInfo->{'version'});
  $app_name = str_replace('_', ' ', $ApplicationName);

   if($_SESSION['locale'] == 'en'){

    $AppName_ = $arrayInfo->{'name'};

  }else{

    $AppName_ = $arrayInfo->{'secondname'};

  }

  if(empty($AppName_)){

    $AppName_ = $app_name;

  }

  $info = array("name" => $AppName_, "icon" => $app_icon);

  return $info;

}


foreach (glob($_SERVER['DOCUMENT_ROOT']."/system/apps/*/main.php") as $filenames)
{
  $get_name = preg_match('/apps.*?\/(.*?)\/main.php/',$filenames, $app_name);
  $_app_name = $app_name[1];

  $infoApp = GetAppInfo($_app_name);

  echo'
  <div id="'.$_app_name.$AppID.'" class="ui-state-default app-container'.$AppID.' ui-forest-blink" style="display:flex; padding:10px; border-bottom:1px solid #ccc; transition:all 0.1s ease-in;">
  <div style="background-color:transparent;  background-image: url('.$infoApp["icon"].'); background-size:cover; height:30px; width:30px; float:left;"></div>
  <div style="padding:7px 25px; width:200px;">'.$infoApp["name"].'</div>
  <div id="button_layer'.$_app_name.$AppID.'" class="button_layer" style="opacity:0; display:none; padding:7 10px;">
  </div>
  </div>
  ';

}
echo '</div><div id="sortable2" class="connectedSortable" style="flex:50%;">';
$CountFolders = count( glob("$dir/*", GLOB_ONLYDIR) ); // > 1
$Temp = "";
$SeparatorCount = 0;

$SeparatorCount_ = 0;

function is_dir_empty($dirs) {
  if (!is_readable($dirs)) return null; 
  return (count(scandir($dirs)) == 2);
}

$FoldersArray = glob($dir.'*/');

foreach ($FoldersArray as $keydir => $valuedir) {
  
if (is_dir_empty($valuedir)) {
  echo '
  <div id="sortable'.$dir_array[$keydir].'" class="connectedSortable">
    <div style="padding: 10px; background-color: #b1b1b1; color: #242424; font-size: 20px;">Разделитель '.$dir_array[$keydir].'</div>';
}
else{
  echo '
  <div id="sortable'.$dir_array[$keydir].'" class="connectedSortable">
    <div style="padding: 10px; background-color: #6ab6ff; color: #142b60; font-size: 20px;">Разделитель '.$dir_array[$keydir].'</div>';

  foreach (glob($valuedir.'*') as $key => $object) {

    $object = pathinfo($object);
    $ObjectName = $object['filename'];
    $ObjectExt = $object['extension'];
    $ObjectApp = $dir_."/system/apps/$ObjectName/main.php";  

    $infoApp_ = GetAppInfo($ObjectName);

    echo'
  <div id="'.$ObjectName.$AppID.'" class="ui-state-default app-container'.$AppID.' ui-forest-blink" style="display:flex; padding:10px; border-bottom:1px solid #ccc; transition:all 0.1s ease-in;">
  <div style="background-color:transparent;  background-image: url('.$infoApp_["icon"].'); background-size:cover; height:30px; width:30px; float:left;"></div>
  <div style="padding:7px 25px; width:200px;">'.$infoApp_["name"].'</div>
  <div id="button_layer'.$ObjectName.$AppID.'" class="button_layer" style="opacity:0; display:none; padding:7 10px;">
  </div>
  </div>
  ';    

  }
}
echo "</div>";
}
?>
</div>
</div>
<?php
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

?>
</script>
