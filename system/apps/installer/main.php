<?
/*Application Installer*/

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'uploader';
$AppContainer->SecondNameInfo = 'Uploader';
$AppContainer->VersionInfo = '1.0';
$AppContainer->AuthorInfo = 'Forest Media';

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '230px';
$AppContainer->width = '400px';
$AppContainer->StartContainer();


$click = $_GET['mobile'];
$Folder = $_GET['destination'];
$AppContainer->EndContainer();
?>
