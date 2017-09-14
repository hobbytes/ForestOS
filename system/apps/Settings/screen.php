<?
//Инициализируем переменные
$appid=$_GET['appid'];
$wall=$_GET['wall'];
$theme=$_GET['theme'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px;  padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span>Персонализация</div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/gui.php';
$newgui = new gui;
if($wall!=''){
  session_start();
  if(copy('../../../system/core/design/walls/'.$wall.'','../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg'))  {?>
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
  $newgui->newnotification($appname,"Персонализация","Фоновое изображение рабочего стола изменено");
}
}
if ($theme!=''){
  session_start();
  if(copy('../../../system/core/design/themes/'.$theme.'','../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/theme.fth'))  {
  $newgui->newnotification($appname,"Персонализация","Цветовая тема изменена на <b>$theme</b>. Перезагрузите систему, чтобы изменения вступили в силу<br><span id='restart' style='margin-left: 25%;' class='ui-button ui-widget ui-corner-all'>Перезагрузить</span>");
}else{$newgui->newnotification($appname,"Персонализация","Произошла ошибка! Тема не установлена");}
}
$version='0.1';
    ?>

<div id="tabssettings<?echo $appid;?>">
  <ul>
    <li><a href="#themesettingtab<?echo $appid;?>">Цветовые темы</a></li>
    <li><a href="#wallsettingtab<?echo $appid;?>">Фоновые изображения</a></li>
  </ul>


  <div id="themesettingtab<?echo $appid;?>">
<?
    $dir2='../../core/design/themes/';
    $d2=dir($dir2);
    chdir($d2->path2);

    while (false !== ($entry2=$d2->read())) {
      $path2=$d2->path2;
      $name2=$entry2;
      $color2='#80abc6';
      if ($entry2!='.' && $entry2!='..'){
        $themeloadset=parse_ini_file('../../core/design/themes/'.$name2);
      echo('<div id="'.$name2.'" class="ui-button ui-widget ui-corner-all" onClick="loadtheme'.$appid.'(this);" style="-webkit-user-select:none; cursor:pointer; user-select:none; padding:5px; background-color:'.$themeloadset['backgroundcolor'].'; margin:5px; color:'.$themeloadset['backgroundfontcolor'].'; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; text-transform:uppercase; "><div style="height:15%; background-color:'.$themeloadset['topbarbackcolor'].';"></div><div style="height:15%; background-color:'.$themeloadset['draggablebackcolor'].'; margin-bottom: 5px;"></div>'.$themeloadset['Name'].'</div>');
    }}
    $dir2->close;
?>
</div>

<div id="wallsettingtab<?echo $appid;?>">
  <?
  $dir='../../core/design/walls/';
  $d=dir($dir);
  chdir($d->path);

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
function loadwall<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>screen.php?wall="+el.id+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&appid='.$appid;?>")};
function loadtheme<?echo $appid;?>(el2){$("#<?echo $appid;?>").load("<?echo $folder?>screen.php?theme="+el2.id+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&appid='.$appid;?>")};
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
</script>
