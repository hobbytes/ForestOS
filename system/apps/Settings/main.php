<?
//Инициализируем переменные
$appname=$_GET['appname'];
$appid=$_GET['appid'];
$folder=$_GET['destination'];
$version='0.1';
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/etc/security.php';
$security	=	new security;
$security->appprepare();
$language_settings  = parse_ini_file('app.lang');
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
	<div style="width:100%; text-align:left; padding:0 10px 5px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;"><?echo $language_settings[$_SESSION['locale'].'_settings_label']?></div>
<?php
/*Settings*/
function newbutton($name_btn){
	global $folder,	$appid,	$language_settings;
	echo '<div id="'.$name_btn.'" class="ui-button ui-widget ui-corner-all" onClick="loadsettings'.$appid.'(this);" style="word-wrap:break-word; text-overflow:ellipsis; text-align:center; position:relative; display:block; float:left; margin:5px; color:#000; width:80px; height:80px; padding:5px; font-size:12px; cursor:pointer;">
	<div style="-webkit-user-select:none; margin:auto; user-select:none; background-image: url('.$folder.'/icons/'.$name_btn.'.png); background-size:cover; height:50px; width:50px;">
	</div>
	<div>'.$language_settings[$_SESSION['locale'].'_settings_'.$name_btn].'</div>
	</div>';
}

echo '<div style="width:100%; height:auto; padding:10px; float:left; border-bottom:1px solid #d6d6d6;">';
newbutton('about');
newbutton('screen');
newbutton('language');
echo '</div>';

echo '<div style="width:100%; padding:10px; height:auto; background-color:#e5e5e5; border-bottom:1px solid #d6d6d6; float:left;">';
newbutton('security');
newbutton('users');
echo '</div>';

echo '<div style="width:100%; height:auto; padding:10px; float:left; border-bottom:1px solid #d6d6d6;">';
newbutton('autorun');
echo '</div>';
?>
</div>
<script>
function loadsettings<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>/"+el.id+".php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
</script>
