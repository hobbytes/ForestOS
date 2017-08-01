<?
//Инициализируем переменные
$appid=$_GET['appid'];
$erase=$_GET['erase'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span>Безопасность</div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
session_start();
if($erase=='true'){
  file_put_contents('../../users/'.$_SESSION["loginuser"].'/settings/login.stat','');
}
$version='0.1';
$settingsbd = new readbd;
$settingsbd->readglobal2("fuid","forestusers","login",$_SESSION["loginuser"]);
$fuid=$getdata;

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">Изменение пароля</b>';
echo '<div>123</div></div><hr>';

$text=file_get_contents('../../users/'.$_SESSION["loginuser"].'/settings/login.stat');
echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">Журнал</b>';
echo '<div><textarea style="width:95%; max-width:95%;" rows="10" cols="80" >'.$text.'</textarea></div></div>';
echo '<span onClick="eraselog'.$appid.'();" style="margin:10px;" class="ui-button ui-widget ui-corner-all">Отчистить журнал</span><hr>';
unset($settingsbd);
?>
</div>
<script>
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function eraselog<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder?>security.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid.'&erase=true';?>")};
</script>
