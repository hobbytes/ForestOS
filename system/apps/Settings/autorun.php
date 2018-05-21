<?
/* Autorun */

/* get load data */
$AppID  = $_GET['appid'];
$AppName  = $_GET['appname'];
$Folder = $_GET['destination'];
$checked  = $_GET['checked'];
$savestatus  = $_GET['save'];

$language_autorun  = parse_ini_file('lang/autorun.lang');

/* Make new container */
require '../../core/library/Mercury/AppContainer.php';
$AppContainer = new AppContainer;
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('gui');
$AppContainer->StartContainer();

?>

<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $AppID;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_autorun[$_SESSION['locale'].'_settings_autorun']?></div>

<?php

$newgui = new gui;
if($savestatus == 'true'){
  file_put_contents('../../users/'.$_SESSION["loginuser"].'/settings/autorun.foc',$checked);
  if(empty($checked)){
    $text = $language_autorun[$_SESSION['locale'].'_autorun_msg1'];
  }else{
    $text = $language_autorun[$_SESSION['locale'].'_autorun_msg2'].": <b>$checked</b>";
  }
  $newgui->newnotification($AppName,$language_autorun[$_SESSION['locale'].'_settings_autorun'],$text);
  unset($text);
}
echo '<div class="checkboxfix" style="width:80%; text-align:left; margin:15px auto;"><fieldest>';
$i=0;
foreach (glob("../*/main.php") as $filename)
{
  $i++;
  $name = str_replace(array('main.php','..','/'),'',$filename);
  $pubname  = str_replace('_',' ',$name);
  echo '<label for="checkbox'.$name.$AppID.'">'.$pubname.'</label>';
  echo '<input type="checkbox" class="checkboxclass'.$AppID.'" id="checkbox'.$name.$AppID.'" name="'.$name.'">';
}
echo '</fieldest></div>';
echo '<hr><div onClick="saveautoset'.$AppID.'();" class="ui-forest-button ui-forest-accept ui-forest-center">'.$language_autorun[$_SESSION['locale'].'_button_save'].'</div>';

$content  = file_get_contents('../../users/'.$_SESSION["loginuser"].'/settings/autorun.foc');
if($content){
  $array  = explode(",",$content);
  foreach ($array as $value){
    ?>
    <script>
    $("#checkbox<?echo $value.$AppID?>").prop("checked",true);
    </script>
    <?
  }
}

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
?>
$(function(){
  $(".checkboxclass<?echo $AppID;?>").checkboxradio({
    icon: false
  });
});
function saveautoset<?echo $AppID;?>(){
  var checkboxradio<?echo $AppID;?> = [];
  $('.checkboxclass<?echo $AppID;?>:checked').each(function(){
    checkboxradio<?echo $AppID;?>.push(this.name);
  });
  $("#<?echo $AppID;?>").load("<?echo $Folder?>autorun.php?checked="+escape(checkboxradio<?echo $AppID;?>)+"&save=true&id=<?echo rand(0,10000).'&destination='.$Folder.'&appname='.$AppName.'&appid='.$AppID;?>");
};
</script>
<style>
.checkboxfix label{
  padding:20px 5px;
  margin: 5px;
  width:100px;
  border-radius: 10px;
}
</style>
