<?
//Инициализируем переменные
$appid=$_GET['appid'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:100%; width:100%; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span>О системе</div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/etc/security.php';
$settingsbd = new readbd;
$fo = new filecalc;
$security	=	new security;
session_start();
$security->appprepare();
$osinfo = parse_ini_file('../../core/osinfo.foc', false);
$fo->size_check(dirname(dirname(dirname(__DIR__))));
session_start();
$settingsbd->readglobal2("fuid","forestusers","login",$_SESSION["loginuser"]);
$fuid=$getdata;
$settingsbd->readglobal2("hdd","forestusers","login",$_SESSION["loginuser"]);
$getdata2=$getdata*1000000;
$getdata=$getdata*1000000-$size;
$fo->format($getdata2);
$format2=$format;
$fo->format($size);
$format3=$format;
$fo->format($getdata);


echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">Издание Forest OS</b><br>';
echo '<img style="width:128px; height:128px;" src="system/core/design/images/forestosicon.png"/>';
echo '<div><b>Forest OS</b> '.$osinfo['codename'].'<br>Версия '.$osinfo['version'].'<br><span style="font-size:13px; color:#313131;">сборка: <span style="text-transform:uppercase;">'.$osinfo['revision'].'</span><br>версия сборки: '.$osinfo['subversion'].'</span></div></div><hr>';

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">Пользователь</b>';
echo '<div style="padding-right:10px;">Имя учетной записи: '.$_SESSION["loginuser"].'<br> FUID: '.$fuid.'</div></div><hr>';

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">Диск</b>';
echo '<div>Всего: '.$format2 .'<br>Остаток: '.$format.'<br> Занято: '.$format3.'</div></div><hr>';

unset($settingsbd,$fo);
?>
</div>
<script>
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
</script>
