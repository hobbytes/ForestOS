<?
//Инициализируем переменные
$appid  = $_GET['appid'];
$appname  = $_GET['appname'];
$folder = $_GET['destination'];
$language  = parse_ini_file('lang/appmanager.lang');
session_start();
/*Settings*/
//Подключаем библиотеки
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';
$security	=	new security;
$security->appprepare();
$gui = new gui;

?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language[$_SESSION['locale'].'_name']?></div>
<?
foreach (glob($_SERVER['DOCUMENT_ROOT']."/system/apps/*/main.php") as $filenames)
{
  $get_name = preg_match('/apps.*?\/(.*?)\/main.php/',$filenames, $app_name);
  $_app_name = $app_name[1];
  $app_icon = 'system/apps/'.$_app_name.'/app.png';
  $app_name = str_replace('_', ' ', $_app_name);
  echo'
  <div id="'.$_app_name.$appid.'" class="app-container" style="display:flex; width:100%; padding:10px; border-bottom:1px solid #ccc; transition:all 0.1s ease-in;">
  <div style="background-color:transparent;  background-image: url('.$app_icon.'); background-size:cover; height:30px; width:30px; float:left;"></div>
  <div style="padding:7px 25px; width:200px; border-right:1px solid #ccc;">'.$app_name.'</div>
  <div id="button_layer'.$_app_name.$appid.'" class="button_layer" style="opacity:0; display:none; padding:7 10px;">
  <div style="float:right;">
  <div class="ui-forest-accept ui-forest-button ui-forest-center" >
  Создать ярлык
  </div>
  <div class="ui-forest-accept ui-forest-button ui-forest-center" >
  Открыть папку
  </div>
  <div class="ui-forest-cancel ui-forest-button ui-forest-center">
  Удалить
  </div>
  </div>
  <div style="float:right; width: 160px; background-color:#89b7f7; margin:11px; padding:10px;">
  App Info
  </div>
  </div>
  </div>
  ';
}
?>
</div>
<script>
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function send_message<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder?>feedback.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&t="+escape($('#<?echo $appid?>feedback_theme').val())+"&m="+escape($('#<?echo $appid?>feedback_message').val()))};
UpdateWindow("<?echo $appid?>","<?echo $appname?>");
$(".app-container").click(function(){
  var id = $(this).attr('id');
  $(".app-container").css({
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
