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
$AppContainer->LibraryArray = Array('filesystem', 'bd', 'http');

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

$server_url = "http://forest.hobbytes.com/media/os/AppsHouse/";

$FUID = $BD->readglobal2("fuid", "forestusers", "login", $_SESSION["loginuser"], true);
$PWD = $BD->readglobal2("password", "forestusers", "login", $_SESSION["loginuser"], true);
$DROOT = $_SERVER['DOCUMENT_ROOT'];
$token = md5($FUID.$DROOT.$PWD);

$GetApps = $HttpRequest->makeNewRequest($server_url.'GetApp.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token"));
$MaxRating = $HttpRequest->makeNewRequest($server_url.'MaxRating.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token"));

$GetApps = json_decode($GetApps, TRUE);

?>

<div id="Tabs<?echo $AppID?>">
  <ul>
    <li><a href="#Apps<?echo $AppID?>">Приложения</a></li>
    <li><a href="#Control<?echo $AppID?>">Личный кабинет</a></li>
    <li><a href="#Updates<?echo $AppID?>">Обновления</a></li>
  </ul>
</div>

<style>
.AppTile{
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

.AppTile-icon{
  background-size:cover;
  height:64px;
  width:64px;
}

.AppTile-name{
  word-break: break-word;
  color: #353535;
}

.AppTile-info span{
  font-size: 10px;
  color: #353535;
}

.AppTile-rating-container{
  text-align: left;
}

.AppTile-rating{
  background: #ffc107;
  width: 5px;
  height: 5px;
  margin: 0 2px;
  border-radius: 100%;
  display: inline-block;
}

.AppTile-rating-null{
  background: #9e9e9e;
}

.AppTile-full{
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

.AppTile-close {
  background: #ccc;
  font-size: 20px;
  width: 20px;
  text-align: center;
  float: right;
  color: #5f5f5f;
  cursor: default;
}

</style>

<div id="Apps<?echo $AppID?>" style="margin: 0 auto;">
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
      	$AppName = $key['name'];
    	}else{
      	$AppName = $key['second_name'];
    	}

      $AppHash = md5($AppName.$key['author'].$key['add_date']);

      $rating = 'Рейтинг: '.getRating($key['rating'], $MaxRating).' ('.$key['rating'].')';

      echo '<div class="AppTile" onClick="showInfo'.$AppID.'(\''.$AppHash.'\')">';
      echo '<div class="AppTile-icon" style="background-image: url(http://forestos.hobbytes.com/system/core/design/images/app.png); ">';
    	echo '</div>';
      echo '<div class="AppTile-info">';
      echo '<div class="AppTile-name">';
      echo $AppName;
      echo '</div>';
      echo '<span>';
      echo 'Автор: '.$key['author'].'<br>';
      echo 'Версия: '.$key['version'].'<br>';
      echo '<div class="AppTile-rating-container">'.$rating.'</div>';
      echo 'Размер: null '.'<br>';
      echo '</span>';
      echo '</div>';
      echo '</div>';
      echo '<div class="AppTile-full" id="'.$AppHash.'">';
      echo '<div class="AppTile-close ui-forest-blink" onClick="closeInfo'.$AppID.'(\''.$AppHash.'\')"> x ';
      echo '</div>';
      echo $key['description'];
      echo '</div>';
    }
    ?>
  </div>
</div>

<?
$AppContainer->EndContainer()
?>

<script>
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
