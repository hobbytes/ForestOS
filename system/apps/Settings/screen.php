<?
//Инициализируем переменные
$appid=$_GET['appid'];
$wall=$_GET['wall'];
$theme=$_GET['theme'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
session_start();
$language_screen  = parse_ini_file('lang/screen.lang');
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px;  padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_screen[$_SESSION['locale'].'_settings_screen']?></div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';
$security	=	new security;
$newgui = new gui;
$security->appprepare();
if($wall!=''){
  if($wall=='none'){
    if(unlink('../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg')){
      $newgui->newnotification($appname,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notwalldelete']);
    }
  }else{
    if(copy('../../../system/core/design/walls/'.$wall.'','../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg')){?>
      <script>
      function getRandomInt(min,max){
        return Math.floor(Math.random()*(max-min+1))+min;
      }
    function wallchange(){
        document.body.style.backgroundImage='url("../../../system/users/<? echo $_SESSION["loginuser"];?>/settings/etc/wall.jpg?ran='+getRandomInt(1,1000)+'")';
    };
    wallchange();
      </script>
    <?
    $newgui->newnotification($appname,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notwallchange']);
  }
}
}
if ($theme!=''){
  session_start();
  if(copy('../../../system/core/design/themes/'.$theme.'','../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/theme.fth'))  {
  $newgui->newnotification($appname,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notthemechange_1']."<b>".$theme."</b>. ".$language_screen[$_SESSION['locale'].'_notthemechange_2']."<br><span id='restart' style='margin-left: 25%;' class='ui-button ui-widget ui-corner-all'>".$language_screen[$_SESSION['locale'].'_restart']."</span>");
}else{$newgui->newnotification($appname,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notthemeerror']);}
}
    ?>

<div id="tabssettings<?echo $appid;?>">
  <ul>
    <li><a href="#themesettingtab<?echo $appid;?>"><?echo $language_screen[$_SESSION['locale'].'_themetab']?></a></li>
    <li><a href="#wallsettingtab<?echo $appid;?>"><?echo $language_screen[$_SESSION['locale'].'_walltab']?></a></li>
  </ul>


  <div id="themesettingtab<?echo $appid;?>">
<?
    $current_theme  = parse_ini_file('../../users/'.$_SESSION["loginuser"].'/settings/etc/theme.fth');
    $current_theme  = $current_theme['Name'];
    $dir2='../../core/design/themes/';
    $d2=dir($dir2);
    chdir($d2->path2);

    while (false !== ($entry2=$d2->read())) {
      $path2=$d2->path2;
      $name2=$entry2;
      if ($entry2!='.' && $entry2!='..'){
        $themeloadset=parse_ini_file('../../core/design/themes/'.$name2);
        if($current_theme == $themeloadset['Name']){
          $select_style  = 'border: 2px solid '.$themeloadset['draggablebackcolor'].';  box-shadow:0 0 5px '.$themeloadset['topbarbackcolor'].';';
          $select_text  = '<span style="font-size:10px;">('.$language_screen[$_SESSION['locale'].'_current_label'].')</span>';
        }
        echo('<div id="'.$name2.'" class="ui-button ui-widget ui-corner-all" onClick="loadtheme'.$appid.'(this);" style="-webkit-user-select:none; cursor:pointer; '.$select_style.' user-select:none; padding:5px; background-color:'.$themeloadset['backgroundcolor'].'; margin:5px; color:'.$themeloadset['backgroundfontcolor'].'; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; text-transform:uppercase; "><div style="height:15%; background-color:'.$themeloadset['topbarbackcolor'].';"></div><div style="height:15%; background-color:'.$themeloadset['draggablebackcolor'].'; margin-bottom: 5px;"></div>'.$themeloadset['Name'].'<br>'.$select_text.'</div>');
        $select_style  = '';
        $select_text  = '';
    }}
    $dir2->close;
?>


</div>

<div id="wallsettingtab<?echo $appid;?>">
  <?
  $dir='../../core/design/walls/';
  $d=dir($dir);
  chdir($d->path);
  echo('<div id="clearwall" class="ui-button ui-widget ui-corner-all" onClick="clearwall'.$appid.'();" style="-webkit-user-select:none; border:2px dashed #4a4a4a; background-color:#e4e4e4; cursor:pointer; user-select:none; padding:5px; margin:5px; color:#000; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; ">'.$language_screen[$_SESSION['locale'].'_clear_label'].'</div>');
  while (false !== ($entry=$d->read())) {
  	$path=$d->path;
  	$name=$entry;
    $color='#80abc6';
  	if ($entry!='.' && $entry!='..'){
  	echo('<div id="'.$name.'" class="ui-button ui-widget ui-corner-all" onClick="loadwall'.$appid.'(this);" style="-webkit-user-select:none; cursor:pointer; user-select:none; padding:5px; background-color:'.$color.'; margin:5px; color:#000; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; background-color:transparent;  background-image: url(../../system/core/design/walls/'.$name.'); background-size:cover;"></div>');
  }}
  $dir->close;
  ?>
</div>
</div>
</div>
<script>
$(function(){$("#tabssettings<?echo $appid;?>").tabs();});
$( "#restart" ).on( "click", function() {return location.href = 'os.php';});
function loadwall<?echo $appid;?>(el){
  if(el!='none'){
    el=el.id;
  }
  $("#<?echo $appid;?>").load("<?echo $folder?>screen.php?wall="+el+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&appid='.$appid;?>")};
function loadtheme<?echo $appid;?>(el2){$("#<?echo $appid;?>").load("<?echo $folder?>screen.php?theme="+el2.id+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&appid='.$appid;?>")};
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function clearwall<?echo $appid;?>(){
  $('.backgroundtheme').css('background','');
  loadwall<?echo $appid;?>('none');
}
</script>
