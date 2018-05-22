<?

/* Update Tool */

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'Update Tool';
$AppContainer->SecondNameInfo = 'Update Tool';
$AppContainer->VersionInfo = '1.0';
$AppContainer->AuthorInfo = 'Forest Media';

/* Library List */
$AppContainer->LibraryArray = Array('filesystem','gui');

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '100%';
$AppContainer->width = '100%';
$AppContainer->StartContainer();

//get data
$gui=new gui;
$fo = new filecalc;
$click=$_GET['mobile'];
$folder=$_GET['destination'];
$updatefile=$_GET['updatefile'];

//Загружаем файл локализации
$update_lang  = parse_ini_file('app.lang');
$cl = $_SESSION['locale'];

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
$current = parse_ini_file('../../core/osinfo.foc');
if($current['subversion'] == $subversion){
  die('<div style="width:500px; height:300px;">'.$gui->infoLayot('Forest OS has already been updated').'</div>');
}
}
?>
<div style="width:500px; height:300px;">
<p style="text-align:center">
  <div style="background-image: url(http://forest.hobbytes.com/media/os/updates/uplogo.png); background-size:cover; margin:auto; height:90px; width:90px;">
  </div>
</p>
<div style="text-align:center; font-size:20px;">
  <?echo $update_lang[$cl.'_update_label']?></b>
  <?
  if($_SESSION['superuser'] == $_SESSION['loginuser']){
    if($updatefile==''){
      if($arrayu!=''){
      $fo->format($size*1024);
      echo '<br><span style="font-size:12px; font-weight:900; " >'.$update_lang[$cl.'_update_build'].': <span style="color:#363636; text-transform: uppercase;">'.$revision.'</span></span><br>
      <span style="font-size:12px; ">'.$update_lang[$cl.'_update_version'].': '.$version.'<br>'.$update_lang[$cl.'_update_subversion'].': '.$subversion.'<br>'.$update_lang[$cl.'_update_size'].': '.$format.'</span>';
    }
    ?>
    <div id="<?echo $revision;?>" onClick="updatenow<?echo $AppID;?>(this);" class="ui-forest-blink" style="background-color:#54c45c; color:#fff; width:200px; font-size:15px; text-align:center; margin:10px auto; cursor:pointer; padding:10px; border-radius:5px;">
      <?echo $update_lang[$cl.'_update_button']?>
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
  echo '<p>'.$update_lang[$cl.'_update_msg_1'].'<b>'.$updatefile.'</b>'.$update_lang[$cl.'_update_msg_2'].'</p>';
  $gui->newnotification($AppName, $update_lang[$cl.'_update_label'], $update_lang[$cl.'_update_msg_1'].'<b>'.$updatefile.'</b>'.$update_lang[$cl.'_update_msg_2']);
  unlink('./temp/'.$updatefile.$temphash.'.zip');
  file_get_contents('http://forest.hobbytes.com/media/os/ubase/updateuser.php?followlink='.$_SERVER['SERVER_NAME'].'&version='.str_replace(' ','_',$codename.$subversion));
  }
  }
}else{
  echo '<br><b>'.$update_lang[$cl.'_update_error_prv'].'</b>';
}
  ?>
</div>
</div>
<?
$AppContainer->EndContainer();
?>
<script>
<?php
// update
$AppContainer->Event(
  "updatenow",
  'object',
  $Folder,
  'main',
  array(
    'updatefile' => '"+object.id+"',
    'appinstdest' => '"+$("#destinput'.$AppID.'").val()+"'
  )
);
?>
</script>
