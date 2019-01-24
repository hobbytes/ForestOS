<?
/* Application Store */

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'Apps House';
$AppContainer->SecondNameInfo = 'Магазин';
$AppContainer->VersionInfo = '1.1';
$AppContainer->AuthorInfo = 'Forest Media';

/* Library List */
$AppContainer->LibraryArray = Array('filesystem', 'bd', 'http', 'gui');

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '530px';
$AppContainer->backgroundColor = '#e8e8e8';
$AppContainer->customStyle = 'padding-top:0px; max-width:100%;';
$AppContainer->StartContainer();

/* get data */
$activeTab = 0;
if(isset($_GET['activetab'])){
  $activeTab = $_GET['activetab'];
}

$FileCalc = new filecalc;
$FileAction = new fileaction;
$BD = new readbd;
$HttpRequest = new http;
$gui = new gui;

$server_url = "http://forest.hobbytes.com/media/os/AppsHouse/";

$FUID = $BD->readglobal2("fuid", "forestusers", "login", $_SESSION["loginuser"], true);
$PWD = $BD->readglobal2("password", "forestusers", "login", $_SESSION["loginuser"], true);
$DROOT = $_SERVER['DOCUMENT_ROOT'];
$token = md5($FUID.$DROOT.$PWD);

$GetApps = $HttpRequest->makeNewRequest($server_url.'GetApp.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token"));
$MaxRating = $HttpRequest->makeNewRequest($server_url.'MaxRating.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token"));

$GetApps = json_decode($GetApps, TRUE);
?>

<link rel="stylesheet" type="text/css" href="<? echo $Folder.$FileAction->filehash("assets/main.css") ?>">

<div id="Tabs<?echo $AppID?>">
  <ul>
    <li><a href="#Apps<?echo $AppID?>">Приложения</a></li>
    <li><a href="#Control<?echo $AppID?>">Личный кабинет</a></li>
    <li><a href="#Updates<?echo $AppID?>">Обновления</a></li>
  </ul>

<div id="Apps<? echo $AppID ?>" style="margin: 0 auto; overflow: auto;">
  <div style="padding: 10px;">
    <?

    /* install app */

    if(isset($_GET['install_app_hash'])){
      $AppDestination = "http://forest.hobbytes.com/media/os/AppsHouse/Apps/".$_GET['install_app_hash']."/app.zip";

      if(!is_dir('./temp/')){
        mkdir('./temp/');
      }

      $curlCh = curl_init();
      curl_setopt($curlCh, CURLOPT_URL, $AppDestination);
      curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curlCh, CURLOPT_SSLVERSION,3);
      $curlData = curl_exec($curlCh);
      curl_close($curlCh);

      $TempFile = './temp/'.$_GET['install_app_hash'].'.zip';
      $file = fopen($TempFile, "w+");
      fputs($file, $curlData);
      fclose($file);

      $zip = new ZipArchive;
      if($zip->open($TempFile) === TRUE){
        $zip->extractTo('../');
        $zip->close();
        unlink($TempFile);

        if($_SESSION['locale'] == 'en'){
          $pubname = $_GET['install_app_name'];
        }else{
          $pubname = $_GET['install_app_second_name'];
        }

        $pubname = str_replace('_', ' ', $pubname);

        $LinkFile = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/desktop/'.$_GET['install_app_name'].'_'.uniqid().'.link';
        $FileAction->makelink($LinkFile, $_SERVER['DOCUMENT_ROOT'].'/system/apps/'.$_GET['install_app_name'].'/', 'main', '', $app_link, $pubname, $pubname, 'system/apps/'.$_GET['install_app_name'].'/app.png');
        $gui->newnotification($AppName, 'Установка приложения', 'Приложение <b>'.$pubname.'</b> установлено');
      }

    }

    /* create array with installed apps */

    $InstalledApps = array();

    foreach (glob("../*/main.php") as $filename) {
      $filename = str_replace(array('..','/','main.php'), '', $filename);
      $InstalledApps[] = $filename;
    }

    /* rating function */
    function getRating($rating, $MaxRating){
      $r = ($rating / $MaxRating) * 5;
      $r_int = intval($r);
      $r_null = 5 - $r_int;
      $dots = NULL;

      for ($i = 0; $i < $r_int; $i++){
        $dots .= '<div class="AppTile-rating" ></div>';
      }

      for ($i = 0; $i < $r_null; $i++){
        $dots .= '<div class="AppTile-rating AppTile-rating-null" ></div>';
      }

      return $dots;
    }

    /* show available apps */

    foreach ($GetApps as $key) {

      if($_SESSION["locale"]  ==  'en'){
      	$AppName = str_replace('_', ' ', $key['name']);
    	}else{
      	$AppName = str_replace('_', ' ', $key['second_name']);
    	}

      $AppHash = $key['hash'];

      $rating = 'Рейтинг: '.getRating($key['rating'], $MaxRating).' ('.$key['rating'].')';
      $AppIcon = $server_url.'Apps/'.$key['hash'].'/app.png';
      $FileCalc->format($key['size']*1024);
      $size = $format;
      $description = preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))', $key['description']);


      if (in_array($key['name'], $InstalledApps)) {
        $ButtonClass = "A-button-open";
        $ButtonCaption = "Открыть";
      }else{
        $ButtonClass = "A-button-install";
        $ButtonCaption = "Установить";
      }

      echo '<div class="AppTile" onClick="showInfo'.$AppID.'(\''.$AppHash.'\')">';
      echo '<div class="AppTile-icon" style="background-image: url('.$AppIcon.'); ">';
    	echo '</div>';
      echo '<div class="AppTile-info">';
      echo '<div class="AppTile-name">';
      echo $AppName;
      echo '</div>';
      echo '<span>';
      echo 'Автор: '.$key['author'].'<br>';
      echo 'Версия: '.$key['version'].'<br>';
      echo '<div class="AppTile-rating-container">'.$rating.'</div>';
      echo 'Размер: '.$size.'<br>';
      echo '</span>';
      echo '</div>';
      echo '</div>';
      echo '<div class="AppTile-full" id="'.$AppHash.'">';
      echo '<div class="AppTile-close ui-forest-blink" onClick="closeInfo'.$AppID.'(\''.$AppHash.'\')"> x ';
      echo '</div>';
      echo '<div class="AppTile-icon" style="background-image: url('.$AppIcon.'); ">';
    	echo '</div>';
      echo '<div class="AppTile-name">';
      echo $AppName . '<span style="color: #9a9494; font-weight: 300; font-size: 14px;"> by '. $key['author'] . ', version: '.$key['version'].'</span>';
      echo '<div app="'.$key['name'].'" app_second="'.$key['second_name'].'" hash="'.$AppHash.'" class="AppTile-button '.$ButtonClass.'">';
      echo $ButtonCaption;
      echo '</div>';
      echo '</div>';
      echo '<div class="AppTile-description">';
      echo $description;
      echo '</div>';
      echo '</div>';
    }
    ?>
  </div>
</div>

<div id="Control<? echo $AppID ?>" style="margin: 0 auto;">
  <div style="padding: 10px;">
    <?
    echo '
    <div style="text-align:left; margin-bottom: 10px">
    <b style="font-size:20px;">Публикация приложения</b>
    </div>
    ';

    if(isset($_GET['name'])){

      $name = strip_tags(str_replace(' ', '_', $_GET['name']));
      $second_name = strip_tags(str_replace(' ', '_', $_GET['second_name']));
      $version = strip_tags($_GET['version']);
      $os_version = strip_tags($_GET['os_version']);
      $description = $_GET['description'];
      $file_url = strip_tags($_GET['file_url']);
      $icon_url = strip_tags($_GET['icon_url']);
      $hash = md5($name.$_SESSION["loginuser"].$token);
      $GetStatusUpload = $HttpRequest->makeNewRequest(
        $server_url.'AddApp.php',
        'Forest OS',
        $data = array(
          'author' => $_SESSION["loginuser"],
          'token' => "$token",
          'name' => "$name",
          'second_name' => "$second_name",
          'version' => "$version",
          'os_version' => "$os_version",
          'description' => "$description",
          'file_url' => "$file_url",
          'icon_url' => "$icon_url",
          'hash' => "$hash",
        )
      );

      $GetStatusUpload = json_decode($GetStatusUpload, TRUE);
      print_r($GetStatusUpload);
    }

    echo '<div>Имя приложения(латиница):</div>';
    $gui->inputslabel('', 'text', 'name'.$AppID, '','40', 'Имя приложения');

    echo '<div>Имя приложения(кириллица):</div>';
    $gui->inputslabel('', 'text', 'second_name'.$AppID, '','40', 'Имя приложения');

    echo '<div>Версия:</div>';
    $gui->inputslabel('', 'text', 'version'.$AppID, '1.0','40', 'Версия');

    echo '<div>Версия ОС:</div>';
    $gui->inputslabel('', 'text', 'os_version'.$AppID, $_SESSION['os_version'],'40', 'Версия ОС');

    echo '<div>Описание:</div>';
    ?>
    <textarea rows="10" id="description<? echo $AppID ?>" placeholder="Введите описание приложения" style="width: 40%; padding: 10px; margin: 10px 0; border: 1px solid #ccc;" name="description"></textarea>
    <?

    echo '<div>Приложение (zip):</div>';
    $gui->inputslabel('', 'url', 'file_url'.$AppID, '','40', 'Приложение (zip)');

    echo '<div>Иконка (png):</div>';
    $gui->inputslabel('', 'url', 'icon_url'.$AppID, '','40', 'Иконка (png)');

    echo '<div id="PublishApp'.$AppID.'" onClick="PublishNewApp'.$AppID.'();" class="ui-forest-button ui-forest-accept" style="margin:10 0;"> Загрузить </div>';
    ?>
  </div>
</div>

<div id="Updates<? echo $AppID ?>" style="margin: 0 auto;">
  <div style="padding: 10px;">
  </div>
</div>


</div>

<?
$AppContainer->EndContainer()
?>

<script>

<?

//Execute Function Request
$AppContainer->ExecuteFunctionRequest();

// Publish App!
$AppContainer->Event(
	"PublishNewApp",
  NULL,
	$Folder,
	'main',
	array(
    'name' => '"+escape($("#name'.$AppID.'").val())+"',
    'second_name' => '"+escape($("#second_name'.$AppID.'").val())+"',
    'version' => '"+escape($("#version'.$AppID.'").val())+"',
    'os_version' => '"+escape($("#os_version'.$AppID.'").val())+"',
    'description' => '"+escape($("#description'.$AppID.'").val())+"',
    'file_url' => '"+escape($("#file_url'.$AppID.'").val())+"',
    'icon_url' => '"+escape($("#icon_url'.$AppID.'").val())+"',
    'activetab' => '"+$("#Tabs'.$AppID.'").tabs(\'option\',\'active\')+"'
	)
);

// Install App!
$AppContainer->Event(
	"InstallApp",
  "AppHash, AppName, SecondName",
	$Folder,
	"main",
	array(
    'install_app_hash' => '"+AppHash+"',
    'install_app_name' => '"+AppName+"',
    'install_app_second_name' => '"+SecondName+"'
	)
);

?>

$('.A-button-open').click(function(){
  makeprocess('system/apps/'+$(this).attr('app')+'/main.php', '', '', $(this).attr('app'));
  closeInfo<?echo $AppID?>($(this).attr('hash'));
});

$('.A-button-install').click(function(){
  InstallApp<?echo $AppID?>($(this).attr('hash'), $(this).attr('app'), $(this).attr('app_second'));
  closeInfo<?echo $AppID?>($(this).attr('hash'));
});

$(function(){
  $("#Tabs<?echo $AppID?>").tabs();
});

//set active tab
$(function(){
  $("#Tabs<?echo $AppID?>").tabs({
    active: <?echo $activeTab?>
  });
});

function showInfo<?echo $AppID?>(object){
  $(".AppTile-full").css('display', 'none');
  $("#"+object).show("fade", 100);
}

function closeInfo<?echo $AppID?>(object){
  $("#"+object).css('display', 'none');
}
</script>
