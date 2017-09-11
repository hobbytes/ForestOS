<?
//Инициализируем переменные
$appname=$_GET['appname'];
$appid=$_GET['appid'];
$folder=$_GET['destination'];
$version='0.1';
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
	<div style="width:100%; text-align:left; padding:0 10px 5px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">Параметры</div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';

echo '<div style="width:100%; height:auto; padding:10px; float:left; border-bottom:1px solid #d6d6d6;">';
echo('<div id="about" class="ui-button ui-widget ui-corner-all" onClick="loadsettings'.$appid.'(this);" style="word-wrap:break-word; text-overflow:ellipsis; text-align:center; position:relative; display:block; float:left; margin:5px; color:#000; width:80px; height:80px; padding:5px; font-size:12px; height:auto; background-color:#d8d8d8; cursor:pointer;"><div style="-webkit-user-select:none; margin:auto; user-select:none; background-image: url('.$folder.'/icons/about.png); background-size:cover; height:64px; width:64px;"></div><div>О системе</div></div>');
echo('<div id="screen" class="ui-button ui-widget ui-corner-all" onClick="loadsettings'.$appid.'(this);" style="word-wrap:break-word; text-overflow:ellipsis; text-align:center; position:relative; display:block; float:left; margin:5px; color:#000; width:80px; height:80px; padding:5px; font-size:12px; height:auto; background-color:#d8d8d8; cursor:pointer;"><div style="-webkit-user-select:none; margin:auto; user-select:none; background-image: url('.$folder.'/icons/screen.png); background-size:cover; height:64px; width:64px;"></div><div>Персонализация</div></div>');
echo '</div>';

echo '<div style="width:100%; padding:10px; height:auto; background-color:#e5e5e5; border-bottom:1px solid #d6d6d6; float:left;">';
echo('<div id="security" class="ui-button ui-widget ui-corner-all" onClick="loadsettings'.$appid.'(this);" style="word-wrap:break-word; text-overflow:ellipsis; text-align:center; position:relative; display:block; float:left; margin:5px; color:#000; width:80px; height:80px; padding:5px; font-size:12px; height:auto; background-color:#d8d8d8; cursor:pointer;"><div style="-webkit-user-select:none; margin:auto; user-select:none; background-image: url('.$folder.'/icons/security.png); background-size:cover; height:64px; width:64px;"></div><div>Безопасность</div></div>');
echo('<div id="users" class="ui-button ui-widget ui-corner-all" onClick="loadsettings'.$appid.'(this);" style="word-wrap:break-word; text-overflow:ellipsis; text-align:center; position:relative; display:block; float:left; margin:5px; color:#000; width:80px; height:80px; padding:5px; font-size:12px; height:auto; background-color:#d8d8d8; cursor:pointer;"><div style="-webkit-user-select:none; margin:auto; user-select:none; background-image: url('.$folder.'/icons/users.png); background-size:cover; height:64px; width:64px;"></div><div>Учетные записи</div></div>');
echo '</div>';

echo '<div style="width:100%; height:auto; padding:10px; float:left; border-bottom:1px solid #d6d6d6;">';
echo('<div id="autorun" class="ui-button ui-widget ui-corner-all" onClick="loadsettings'.$appid.'(this);" style="word-wrap:break-word; text-overflow:ellipsis; text-align:center; position:relative; display:block; float:left; margin:5px; color:#000; width:80px; height:80px; padding:5px; font-size:12px; height:auto; background-color:#d8d8d8; cursor:pointer;"><div style="-webkit-user-select:none; margin:auto; user-select:none; background-image: url('.$folder.'/icons/autorun.png); background-size:cover; height:64px; width:64px;"></div><div>Менеджер Автозапуска</div></div>');
echo '</div>';
?>
</div>
<script>
function loadsettings<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>/"+el.id+".php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
</script>
