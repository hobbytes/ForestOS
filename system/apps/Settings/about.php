<?

/* About */

/* get load data */
$AppID = $_GET['appid'];
$AppName = $_GET['appname'];
$Folder = $_GET['destination'];

/* get localization file */
$language_about  = parse_ini_file('lang/about.lang');

/* Make new container */
require '../../core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('filesystem', 'bd');
$AppContainer->height = '100%';
$AppContainer->width = '100%';
$AppContainer->StartContainer();

?>

<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $AppID;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_about[$_SESSION['locale'].'_settings_about']?></div>

<?php
/* make new object */
$settingsbd = new readbd;
$fo = new filecalc;

/* get localization OS file */
$osinfo = parse_ini_file('../../core/osinfo.foc', false);

/* read data from BD */
$settingsbd->readglobal2("fuid","forestusers","login",$_SESSION["loginuser"]);
$fuid = $getdata;

/* get disk capacity */
$fo->size_check(dirname(dirname(dirname(__DIR__))));
$fo->format($size);

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:40px; font-weight:100; font-variant: all-petite-caps;">Forest OS</b><br>';
echo '<img style="width:128px; height:128px;" src="system/core/design/images/forestosicon.png"/>';
echo '<div><b>Forest OS</b> '.$osinfo['codename'].'<br>'.$language_about[$_SESSION['locale'].'_version_label'].' '.$osinfo['version'].'<br><span style="font-size:13px; color:#313131;">'.$language_about[$_SESSION['locale'].'_revision_label'].': <span style="text-transform:uppercase;">'.$osinfo['revision'].'</span><br>'.$language_about[$_SESSION['locale'].'_subversion_label'].': '.$osinfo['subversion'].'</span></div></div><hr>';

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language_about[$_SESSION['locale'].'_user_label'].'</b>';
echo '<div style="padding-right:10px;">'.$language_about[$_SESSION['locale'].'_username_label'].': '.str_replace('_',' ',$_SESSION["loginuser"]).'<br> FUID: '.$fuid.'</div></div><hr>';

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language_about[$_SESSION['locale'].'_capacity_label'].'</b>';
echo '<div>'.$language_about[$_SESSION['locale'].'_capacityused_label'].': '.$format.'</div></div><hr>';

unset($settingsbd,$fo);

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

</script>
