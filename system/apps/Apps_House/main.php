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

<style>
.AppTile {
  display: grid;
  grid-template-columns: 37% 63%;
  padding: 10px;
  margin: 10px;
  width: 220px;
  height: 100px;
  float: left;
  text-overflow: ellipsis;
  overflow: hidden;
  cursor: default;
  user-select: none;
  background: #f3f3f3;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.AppTile-icon {
  background-size:cover;
  height:64px;
  width:64px;
}

.AppTile-name {
  word-break: break-word;
  color: #353535;
}

.AppTile-info span {
  font-size: 10px;
  color: #353535;
}

.AppTile-rating-container {
  text-align: left;
}

.AppTile-rating {
  background: #ffc107;
  width: 5px;
  height: 5px;
  margin: 0 2px;
  border-radius: 100%;
  display: inline-block;
}

.AppTile-rating-null {
  background: #9e9e9e;
}

.AppTile-full {
  display: none;
  min-width: 50%;
  max-width: 70%;
  height: 300px;
  background: #eee;
  position: absolute;
  margin: 0 auto;
  left: 0;
  right: 0;
  top: 170px;
  border: 1px solid rgba(182, 185, 187, 0.5);
  box-shadow: 0 2px 8px 0 rgba(50,50,50, .08);
  padding: 10px;
  z-index: 1;
}

.AppTile-full .AppTile-name {
  color: #000;
  padding: 10px 0px;
  border-bottom: 1px solid #ccc;
  font-weight: 600;
}

.AppTile-close {
  background: #ccc;
  font-size: 20px;
  width: 20px;
  text-align: center;
  float: right;
  color: #5f5f5f;
  cursor: default;
}

.AppTile-description {
  padding: 10px;
  color: #4a4a4a;
  word-break: break-word;
  overflow-y: auto;
  height: 150px;
}

.AppTile-button{
  float: right;
  padding: 2px 5px;
  margin: 0 5px;
  font-weight: normal;
  color: #fff;
  border-radius: 5px;
  cursor: pointer;
}

.A-button-install {
  background: #2196F3;
}

.A-button-install:hover {
  background: #38a7ff;
}

</style>

<div id="Tabs<?echo $AppID?>">
  <ul>
    <li><a href="#Apps<?echo $AppID?>">Приложения</a></li>
    <li><a href="#Control<?echo $AppID?>">Личный кабинет</a></li>
    <li><a href="#Updates<?echo $AppID?>">Обновления</a></li>
  </ul>

<div id="Apps<? echo $AppID ?>" style="margin: 0 auto; overflow: auto;">
  <div style="padding: 10px;">
    <?

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

    foreach ($GetApps as $key) {

      if($_SESSION["locale"]  ==  'en'){
      	$AppName = str_replace('_', ' ', $key['name']);
    	}else{
      	$AppName = str_replace('_', ' ', $key['second_name']);
    	}

      $AppHash = md5($AppName.$key['author'].$key['add_date']);

      $rating = 'Рейтинг: '.getRating($key['rating'], $MaxRating).' ('.$key['rating'].')';
      $AppIcon = $server_url.'Apps/'.$key['hash'].'/app.png';
      $FileCalc->format($key['size']*1024);
      $size = $format;
      $description = preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))', $key['description']);

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
      echo '<div class="AppTile-button A-button-install">';
      echo 'Установить';
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

?>

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
