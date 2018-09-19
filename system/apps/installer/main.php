<?
/*Application Installer*/

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'installer';
$AppContainer->SecondNameInfo = 'Установщик';
$AppContainer->VersionInfo = '1.0.1';
$AppContainer->AuthorInfo = 'Forest Media';

/* Library List */
$AppContainer->LibraryArray = Array('gui');

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '230px';
$AppContainer->width = '400px';
$AppContainer->StartContainer();

//Инициализируем переменные
$gui = new gui;
$click = $_GET['mobile'];
$Folder = $_GET['destination'];
$appdownload = $_GET['appdownload'];
$nameappdownload=str_replace('_',' ',$appdownload);
//Загружаем файл локализации
$install_lang  = parse_ini_file('app.lang');
$cl = $_SESSION['locale'];
//Логика
$appinstall = $_GET['appinstall'];

function config_set($config_file, $section, $key, $value) {
    $config_data = parse_ini_file($config_file, true);
    $config_data[$section][$key] = $value;
    $new_content = '';
    foreach ($config_data as $section => $section_content) {
        $section_content = array_map(function($value, $key) {
            return "$key=$value";
        }, array_values($section_content), array_keys($section_content));
        $section_content = implode("\n", $section_content);
        $new_content .= "[$section]\n$section_content\n";
    }
    file_put_contents($config_file, $new_content);
}

if(isset($appinstall)){
  if($_SESSION['superuser'] == $_SESSION['loginuser']){
    $ch=curl_init('http://forest.hobbytes.com/media/os/apps/'.$appinstall.'/app.zip');
    if(!is_dir('./temp/')){mkdir('./temp/');}
    $temphash=md5(date('d.m.y.h.i.s').$appinstall);
    $fp=fopen('./temp/'.$appinstall.$temphash.'.zip','wb');
    curl_setopt($ch, CURLOPT_FILE,$fp);
    curl_setopt($ch, CURLOPT_HEADER,0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    $zip = new ZipArchive;

  if($zip->open('./temp/'.$appinstall.$temphash.'.zip') === TRUE){
    $zip->extractTo('../../../'.str_replace($appinstall,'',$_GET['appinstdest']));
    $zip->close();
    $AppName=str_replace("_"," ", $appinstall);
    $myfile=fopen('../../users/'.$_SESSION["loginuser"].'/desktop/'.$appinstall.'.link',"w");
    $content="[link]\ndestination=".$_GET['appinstdest']."/\nfile=main\nkey=\nparam=\nname='$appinstall'\nlinkname='$appinstall'\n";
    fwrite($myfile,$content);
		fclose($myfile);

    //update desktop
    ?>
    <script>
      UpdateDesktop();
    </script>
    <?


    $ini_array = parse_ini_file('../../core/appinstall.foc', true);
    if (array_key_exists($appinstall, $ini_array))
    {
      //config_set('../../core/appinstall.foc', $appinstall, 'version', $_SESSION['appversion']);
      $type=$install_lang[$cl.'_installer_msg_upd_1']; $type2=$install_lang[$cl.'_installer_msg_upd_2'];
      unlink('./temp/'.$appinstall.$temphash.'.zip');
    }else{
      unlink('./temp/'.$appinstall.$temphash.'.zip');
      $type=$install_lang[$cl.'_installer_msg_1']; $type2=$install_lang[$cl.'_installer_msg_2'];
    }
    $pubname = str_replace('_',' ',$appinstall);
    $gui->newnotification($AppName,$type2,$install_lang[$cl.'_installer_msg_label'].' <b>'.$pubname.'</b> '.$type.'!');?>
    <script>$(function(){$("#process<?echo $AppID;?>").remove();});</script>
  <?}else{
    $gui->newnotification($AppName,$install_lang[$cl.'_installer_msg_2'],$install_lang[$cl.'_installer_msg_label'].' <b>'.$pubname.'</b> '.$install_lang[$cl.'_installer_msg_label_2']); ?>
    <script>$(function(){$("#process<?echo $AppID;?>").remove();});</script>
    <?}
  }else{
    echo $install_lang[$cl.'_installer_error_prv'];
  }
}else{
?>
<p style="text-align:center"><div style="background-image: url(http://forest.hobbytes.com/media/os/apps/<?echo $appdownload;?>/app.png); background-size:cover; margin:auto; height:64px; width:64px;"></div></p>
<div style="text-align:center; font-size:20px;">
  <?echo $install_lang[$cl.'_installer_label']?>: <b><?echo $nameappdownload;?></b>
</div>
<div style="margin-top:30px; margin-left:8%; display:none;"><label for="destinput<?echo $AppID;?>">Путь для установки:</label> <input id="destinput<?echo $AppID;?>" type="text" value="system/apps/<?echo $appdownload;?>/"></div>
<label style="margin-top:10px; display:none; margin-left:8%;" for="checkbox<?echo $AppID;?>" >Создать ярлык
<input type="checkbox" name="checkbox<?echo $AppID;?>" id="checkbox<?echo $AppID;?>" ></input></label>
<br>
<div id="<?echo $appdownload;?>" onClick="appinstall<?echo $AppID;?>(this);" class="ui-forest-blink" style="background-color:#54c45c; color:#fff; width:200px; font-size:15px; text-align:center; margin:10px auto; cursor:pointer; padding:10px; border-radius:5px;"><?echo $install_lang[$cl.'_installer_button']?></div>
<?
}
$AppContainer->EndContainer();
?>
<script>

<?php
// install
$AppContainer->Event(
  "appinstall",
  'object',
  $Folder,
  'main',
  array(
    'appinstall' => '"+object.id+"',
    'appinstdest' => '"+$("#destinput'.$AppID.'").val()+"'
  )
);
?>

$(function(){
  $("#checkbox<?echo $AppID;?>").prop("checked",true);
});

$(function(){
  $("#checkbox<?echo $AppID;?>").checkboxradio();
});
</script>
<?
unset($AppID);
?>
