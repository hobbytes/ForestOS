<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#ebebeb; height:300px; width:400px; color:#000; max-height:95%; max-width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*Application Installer*/
//Подключаем библиотеки
include '../../core/library/gui.php';
//Инициализируем переменные
$gui=new gui;
$click=$_GET['mobile'];
$folder=$_GET['destination'];
$appdownload=$_GET['appdownload'];
$nameappdownload=str_replace('_',' ',$appdownload);
//Запускаем сессию
session_start();
//Логика
$appinstall=$_GET['appinstall'];
if(isset($appinstall)){
  $ch=curl_init('http://forest.hobbytes.com/media/os/apps/'.$appinstall.'/app.zip');
  if(!is_dir('./temp/')){mkdir('./temp/');}
  $temphash=md5(date('d.m.y.h.i.s').$appinstall);
  $fp=fopen('./temp/'.$appinstall.$temphash.'.zip','wb');
  curl_setopt($ch, CURLOPT_FILE,$fp);
  curl_setopt($ch, CURLOPT_HEADER,0);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
$zip=new ZipArchive;
if($zip->open('./temp/'.$appinstall.$temphash.'.zip') === TRUE){
$zip->extractTo('../../../'.str_replace($appinstall,'',$_GET['appinstdest']));
$zip->close();
$appname=str_replace("_"," ", $appinstall);
$myfile=fopen('../../users/'.$_SESSION["loginuser"].'/desktop/'.$appinstall.'.link',"w");
$content="[link]\ndestination=".$_GET['appinstdest']."/\nfile=main\nkey=\nparam=\nname='$appinstall'\nlinkname='$appinstall'\n";
fwrite($myfile,$content);fclose($myfile);

//$myfile=fopen('../../core/appinstall.foc',"w");
//$content='name='.$appinstall.'|'.$_GET['v'];
//fwrite($myfile,PHP_EOL.$content);fclose($myfile);


unlink('./temp/'.$appinstall.$temphash.'.zip');
$gui->newnotification($appname,'Установка','Приложение '.$appinstall.' установлено!');?><script>$(function(){$("#process<?echo $appid;?>").remove();});</script><?}else{$gui->newnotification($appname,'Установка','Приложение '.$appinstall.' не установлено!'); ?><script>$(function(){$("#process<?echo $appid;?>").remove();});</script><?}
}
else{
?>
<p style="text-align:center"><div style="background-image: url(http://forest.hobbytes.com/media/os/apps/<?echo $appdownload;?>/app.png); background-size:cover; margin:auto; height:64px; width:64px;"></div></p>
<div style="text-align:center; font-size:20px;">
  Установка приложения <b><?echo $nameappdownload;?></b>
</div>
<div style="margin-top:30px; margin-left:8%;"><label for="destinput<?echo $appid;?>">Путь для установки:</label> <input id="destinput<?echo $appid;?>" type="text" value="system/apps/<?echo $appdownload;?>/"></div>
<label style="margin-top:10px; display:none; margin-left:8%;" for="checkbox<?echo $appid;?>" >Создать ярлык
<input type="checkbox" name="checkbox<?echo $appid;?>" id="checkbox<?echo $appid;?>" ></input></label>
<br>
<div id="<?echo $appdownload;?>" onClick="appinstall<?echo $appid;?>(this);" style="background-color:#54c45c; color:#fff; width:200px; font-size:20px; text-align:center; margin-left:25%; cursor:pointer; padding:5px;">Установить</div>
<?}?>
</div>
<script>
$(function(){$("#checkbox<?echo $appid;?>").prop("checked",true);});
$(function(){$("#checkbox<?echo $appid;?>").checkboxradio();});
function appinstall<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?appinstall="+el.id+"&appinstdest="+$("#destinput<?echo $appid;?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
</script>
<?
unset($appid);
?>
