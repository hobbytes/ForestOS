<?
//Инициализируем переменные
$AppID  = $_GET['appid'];
$AppName  = $_GET['appname'];
$Folder = $_GET['destination'];
$choose_lang  = $_GET['choose_lang'];
$choose_zone  = $_GET['choose_zone'];

require '../../core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('gui');
$AppContainer->StartContainer();

$newgui = new gui;
$language_lang  = parse_ini_file('lang/language.lang');

$dir = '../../users/'.$_SESSION['loginuser'].'/settings/';

if(!empty($choose_lang)){
  file_put_contents($dir.'language.foc',$choose_lang);
  file_put_contents($dir.'timezone.foc',$choose_zone);
  $newgui->newnotification($AppName,$language_lang[$_SESSION['locale'].'_settings_language'],$language_lang[$_SESSION['locale'].'_save_lang']."<br><span id='restart' style='margin-left: 25%;' class='ui-button ui-widget ui-corner-all'>".$language_lang[$_SESSION['locale'].'_restart']."</span>");
  $_SESSION['locale']  = $choose_lang;
  $_SESSION['timezone']  = $choose_zone;
  date_default_timezone_set("$choose_zone");
}

$current_lang = file_get_contents($dir.'language.foc');
$current_zone = file_get_contents($dir.'timezone.foc');

if(empty($current_lang)){
  file_put_contents($dir.'language.foc','en');
  $current_lang = 'en';
  $_SESSION['locale']  = $current_lang;
}

if(empty($current_zone)){
  file_put_contents($dir.'timezone.foc','en');
  $current_zone = 'Europe/Moscow';
  $_SESSION['timezone']  = $current_zone;
  date_default_timezone_set("$choose_zone");
}


function tz_list() {
  $zones_array = array();
  $timestamp = time();
  foreach(timezone_identifiers_list() as $key => $zone) {
    date_default_timezone_set($zone);
    $zones_array[$key]['zone'] = $zone;
    $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
  }
  return $zones_array;
}

?>
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $AppID;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_lang[$_SESSION['locale'].'_settings_language']?></div>
<?php
/*Settings*/
?>
<div style="margin:15%; font-size:20px; text-align:center;">
<div>
  <div>
    <?echo $language_lang[$_SESSION['locale'].'_label_choose']?>
  </div>
<select id="selectlang<?echo $AppID?>" style="margin:10px; width:200px; font-size:20px; padding:10px; -webkit-appearance:none;">
  <option value="ru">Русский</option>
  <option value="en">English</option>
</select>
</div>

<div style="margin-top:10px;">
<div>
  <?echo $language_lang[$_SESSION['locale'].'_zone_choose']?>
</div>
<select id="selectzone<?echo $AppID?>" style="margin:10px; width:200px; font-size:20px; padding:10px; -webkit-appearance:none;">
<?
foreach (tz_list() as $t) {
  $zone = $t['zone'];
  echo '<option value="'.$zone.'">'.$zone.'</option>';
}
?>
</select>
</div>

<div onClick="savesettings<?echo $AppID?>();" class="ui-forest-button ui-forest-accept ui-forest-center"><?echo $language_lang[$_SESSION['locale'].'_button_save']?></div>
</div>
<?
$AppContainer->EndContainer();
?>
<script>
$("#selectlang<?echo $AppID?>").val("<?echo $current_lang?>");
$("#selectzone<?echo $AppID?>").val("<?echo $current_zone?>");

<?
// back button
$AppContainer->Event(
  "back",
  NULL,
  $Folder,
  'main'
);

// savesettings
$AppContainer->Event(
  "savesettings",
  NULL,
  $Folder,
  'language',
  array(
    'choose_lang' => '"+$("#selectlang'.$AppID.'").val()+"',
    'choose_zone' => '"+$("#selectzone'.$AppID.'").val()+"'
  )
);

?>

$( "#restart" ).on( "click", function() {
  return location.href = 'os.php';
});
</script>
