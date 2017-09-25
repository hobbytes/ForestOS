<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#ebebeb; height:300px; width:400px; color:#000; max-height:95%; max-width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*Application Installer*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';

//Инициализируем переменные
$gui=new gui;
$fo = new filecalc;
$security	=	new security;
$click=$_GET['mobile'];
$folder=$_GET['destination'];
$updatefile=$_GET['updatefile'];
//Запускаем сессию
session_start();
$security->appprepare();
//Логика
$urlu='http://forest.hobbytes.com/media/os/update.php';
$fileu=file_get_contents($urlu);
$arrayu=json_decode($fileu,TRUE);

//разбираем массив
if($arrayu!=''){
foreach ($arrayu as $key)
{
  $revision = $key['file'];
  $version  = $key['version'];
  $subversion  = $key['subversion'];
  $size = $key['size'];
  $codename = $key['codename'];
}
}
?>

<p style="text-align:center">
  <div style="background-image: url(http://forest.hobbytes.com/media/os/updates/uplogo.png); background-size:cover; margin:auto; height:90px; width:90px;">
  </div>
</p>
<div style="text-align:center; font-size:20px;">
  Установка обновления</b>
  <?
  if($updatefile==''){
    if($arrayu!=''){
    $fo->format($size*1024);
    echo '<br><span style="font-size:12px; font-weight:900; " >сборка: <span style="color:#363636; text-transform: uppercase;">'.$revision.'</span></span><br>
    <span style="font-size:12px; ">версия: '.$version.'<br>версия сборки: '.$subversion.'<br>размер: '.$format.'</span>';
  }
  ?>
  <div id="<?echo $revision;?>" onClick="updatenow<?echo $appid;?>(this);" style="background-color:#54c45c; color:#fff; width:200px; font-size:15px; text-align:center; margin:10px auto; cursor:pointer; padding:5px;">
    обновить
  </div>
  <?
}else{
  $ch=curl_init('http://forest.hobbytes.com/media/os/updates/'.$updatefile.'.zip');
  if(!is_dir('./temp/')){mkdir('./temp/');}
  $temphash=md5(date('d.m.y.h.i.s').$updatefile);
  $fp=fopen('./temp/'.$updatefile.$temphash.'.zip','wb');
  curl_setopt($ch, CURLOPT_FILE,$fp);
  curl_setopt($ch, CURLOPT_HEADER,0);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
$zip=new ZipArchive;
if($zip->open('./temp/'.$updatefile.$temphash.'.zip') === TRUE){
$zip->extractTo('../../../');
$zip->close();

$myfile=fopen('../../core/osinfo.foc',"w");
$content='[forestos]'.PHP_EOL.PHP_EOL.'version='.$version.PHP_EOL.PHP_EOL.'subversion='.$subversion.PHP_EOL.PHP_EOL.'revision='.$revision.PHP_EOL.PHP_EOL.'codename='.$codename;
fwrite($myfile,PHP_EOL.$content);fclose($myfile);

echo '<p>Обновление '.$updatefile.' установлено!</p>';
$gui->newnotification($appname,'Установка обновления','Обновление '.$updatefile.' установлено!');
unlink('./temp/'.$updatefile.$temphash.'.zip');

if(is_dir('../../../forestos-master/')){
  //echo 'ok';
  //$fileaction = new fileaction;
  //$fileaction->rcopy('../../../forestos-master/', './temp/');
}
}
}
  ?>
</div>
</div>
<script>
function updatenow<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?updatefile="+el.id+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
</script>
<?
unset($appid);
?>
