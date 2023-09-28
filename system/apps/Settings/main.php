<?

/* set var */
$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

/* include Mercury library */
require '../../core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'Settings';
$AppContainer->SecondNameInfo = 'Параметры';
$AppContainer->VersionInfo = '1.2.2';
$AppContainer->AuthorInfo = 'Forest Media';
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->StartContainer();

/* get lang */
$language_settings  = parse_ini_file('lang/main.lang');
?>
	<div style="text-align:left; padding:0 10px 5px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;"><?echo $language_settings[$_SESSION['locale'].'_settings_label']?></div>

<?php
/* Settings */
function newbutton($name_btn){
	global $Folder,	$AppID,	$language_settings;
	echo '
	<div id="'.$name_btn.'" class="ui-button ui-widget ui-corner-all" onClick="loadsettings'.$AppID.'(this);" style="word-wrap:break-word; text-overflow:ellipsis; text-align:center; position:relative; display:block; float:left; margin:5px; color:#000; width:83px; height:83px; padding:13px; font-size:12px; cursor:pointer;">
		<div style="-webkit-user-select:none; margin:auto; user-select:none; background-image: url('.$Folder.'/icons/'.$name_btn.'.png); background-size:cover; height:60px; width:60px;">
		</div>
		<div>
			'.$language_settings[$_SESSION['locale'].'_settings_'.$name_btn].'
		</div>
	</div>
	';
}

echo '<div style="width:100%; height:auto; float:left; border-bottom:1px solid #d6d6d6;">';
newbutton('about');
newbutton('personalization');
newbutton('dock');
newbutton('language');
echo '</div>';

echo '<div style="width:100%; height:auto; background-color:#e5e5e5; border-bottom:1px solid #d6d6d6; float:left;">';
newbutton('security');
newbutton('users');
echo '</div>';

echo '<div style="width:100%; height:auto; float:left; border-bottom:1px solid #d6d6d6;">';
newbutton('autorun');
newbutton('appmanager');
echo '</div>';

echo '<div style="width:100%; height:auto; background-color:#e5e5e5; border-bottom:1px solid #d6d6d6; float:left;">';
newbutton('feedback');
if($_SESSION['superuser'] == $_SESSION['loginuser']){
	newbutton('reset');
}
echo '</div>';

$AppContainer->EndContainer();

?>

<script>
<?
	$AppContainer->Event("loadsettings", 'el', $Folder, '"+el.id+"', NULL);
?>
</script>
