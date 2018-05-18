<?
//Инициализируем переменные
$appid  = $_GET['appid'];
$appname  = $_GET['appname'];
$folder = $_GET['destination'];
$language  = parse_ini_file('lang/appmanager.lang');
session_start();
/*Settings*/
//Подключаем библиотеки
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/filesystem.php';
include $_SERVER['DOCUMENT_ROOT'].'/system/core/library/gui.php';
require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';
$gui = new gui;
$fo = new filecalc;
$fileaction = new fileaction;

/* Make new container */
$AppContainer = new AppContainer;
$AppContainer->appName = $appname;
$AppContainer->appID = $appid;
$AppContainer->StartContainer();

$app_install = $_SERVER['DOCUMENT_ROOT'].'/system/core/appinstall.foc';
?>
<div style="text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language[$_SESSION['locale'].'_name']?></div>
<?
$warn_apps = array('Apps_House',  'installer',  'Explorer', 'update', 'Settings');

$app_delete = $_GET['app_delete'];
$app_link = $_GET['app_link'];

if(!empty($app_link)){
  $info = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/system/apps/'.$app_link.'/main.php?getinfo=true&h='.md5(date('dmyhis')));
  $arrayInfo = json_decode($info);
  if($_SESSION['locale'] == 'en'){
    $pubname	=	$arrayInfo->{'name'};
  }else{
    $pubname	=	$arrayInfo->{'secondname'};
  }
  $file = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/desktop/'.$app_link.'_'.uniqid().'.link';
  $fileaction->makelink($file, $_SERVER['DOCUMENT_ROOT'].'/system/apps/'.$app_link.'/', 'main', '', $app_link, $pubname, $pubname, 'system/apps/'.$app_link.'/app.png');
}

if(!empty($app_delete) && !in_array($app_delete, $warn_apps)){
  $fileaction->deleteDir('../'.$app_delete);
  $gui->newnotification($appname, $language[$_SESSION['locale'].'_name'], $language[$_SESSION['locale'].'_not_1'].': <b>'.$app_delete.'</b> '.$language[$_SESSION['locale'].'_not_2']);
}

foreach (glob($_SERVER['DOCUMENT_ROOT']."/system/apps/*/main.php") as $filenames)
{
  global $format, $size;
  $fo->size_check(dirname($filenames));
  $fo->format($size);

  $get_name = preg_match('/apps.*?\/(.*?)\/main.php/',$filenames, $app_name);
  $_app_name = $app_name[1];
  $info = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/system/apps/'.$_app_name.'/main.php?getinfo=true&h='.md5(date('dmyhis')));
  $arrayInfo = json_decode($info);
  $app_icon = 'system/apps/'.$_app_name.'/app.png?h='.md5($arrayInfo->{'version'});
  $app_name = str_replace('_', ' ', $_app_name);

  if(!in_array($_app_name, $warn_apps)){
    $delete_button = '<div app-delete="'.$_app_name.'" class="ui-forest-cancel ui-forest-button ui-forest-center app-delete'.$appid.'">'.$language[$_SESSION['locale'].'_delete_button'].'</div>';
  }else{
    $delete_button = '';
  }

  $version = $arrayInfo->{'version'};
  if(empty($version)){
    $version = $language[$_SESSION['locale'].'_unknow_version'];
  }

  if($_SESSION['locale'] == 'en'){
    $AppName	=	$arrayInfo->{'name'};
  }else{
    $AppName	=	$arrayInfo->{'secondname'};
  }
  if(empty($AppName)){
    $AppName = $app_name;
  }

  echo'
  <div id="'.$_app_name.$appid.'" class="app-container'.$appid.'" style="display:flex; padding:10px; border-bottom:1px solid #ccc; transition:all 0.1s ease-in;">
  <div style="background-color:transparent;  background-image: url('.$app_icon.'); background-size:cover; height:30px; width:30px; float:left;"></div>
  <div style="padding:7px 25px; width:200px; border-right:1px solid #ccc;">'.$AppName.'</div>
  <div id="button_layer'.$_app_name.$appid.'" class="button_layer" style="opacity:0; display:none; padding:7 10px;">
  <div style="float:right;">
  <div app-run="'.$_app_name.'" class="ui-forest-accept ui-forest-button ui-forest-center app-run'.$appid.'" >
  '.$language[$_SESSION['locale'].'_run_button'].'
  </div>
  <div app-open="'.$_app_name.'" class="ui-forest-accept ui-forest-button ui-forest-center app-open'.$appid.'" >
  '.$language[$_SESSION['locale'].'_open_button'].'
  </div>
  <div app-link="'.$_app_name.'" class="ui-forest-accept ui-forest-button ui-forest-center app-link'.$appid.'" >
  '.$language[$_SESSION['locale'].'_link_button'].'
  </div>
  '.$delete_button.'
  </div>
  <div style="float:right; width: 160px; background-color:#89b7f7; margin:11px; padding:10px;">
  '.$language[$_SESSION['locale'].'_version_label'].':
  '.$version.'<br>
  '.$language[$_SESSION['locale'].'_size_label'].':
  '.$format.'
  </div>
  </div>
  </div>
  ';
}

$AppContainer->EndContainer();
?>
<script>
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};

$(".app-delete<?echo $appid?>").click(function(){
var app_delete = $(this).attr('app-delete');
$("#<?echo $appid;?>").load("<?echo $folder?>appmanager.php?app_delete="+app_delete+"&id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>")
});

$(".app-link<?echo $appid?>").click(function(){
var app_link = $(this).attr('app-link');
$("#<?echo $appid;?>").load("<?echo $folder?>appmanager.php?app_link="+app_link+"&id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>")
});

$(".app-open<?echo $appid?>").click(function(){
var app_open = $(this).attr('app-open');
makeprocess('system/apps/Explorer/main.php',"<?echo $_SERVER['DOCUMENT_ROOT'].'/system/apps/'?>"+app_open+"",'dir','Explorer');
});

$(".app-run<?echo $appid?>").click(function(){
var app_run = $(this).attr('app-run');
makeprocess('system/apps/'+app_run+'/main.php','','',app_run);
});

$(".app-container<?echo $appid?>").click(function(){
  var id = $(this).attr('id');
  $(".app-container<?echo $appid?>").css({
    'background-color':'rgba(0,0,0,0)',
    'color':'#000'
  });
  $(".button_layer").css({
    'display':'none',
    'opacity':'0'
  });
  $("#"+id).css({
    'background-color':'#3e8eff',
    'color':'#fff'
  });
  $("#button_layer"+id).css({
    'display':'block',
    'opacity':'1'
  });
});
</script>
