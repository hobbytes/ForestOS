<?
if($_GET['getinfo'] == 'true'){
	include '../../core/library/etc/appinfo.php';
	$appinfo = new AppInfo;
	$appinfo->setInfo('Image Viewer', '1.0', 'Forest OS Team', 'Image Viewer');
}
$appname=$_GET['appname'];
$appid=$_GET['appid'];
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#ebebeb; height:100%; color:#f2f2f2; width:100%; border-radius:0px 0px 5px 5px; overflow:hidden;">
<?php
/*Image Viewer*/

//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/gui.php';
include '../../core/library/permissions.php';
include '../../core/library/etc/security.php';
//Инициализируем переменные
$hash = new fileaction;
$object = new gui;
$newpermission = new PermissionRequest;
$security	=	new security;
$click=$_GET['mobile'];
$folder=$_GET['destination'];
$security->appprepare();
$_dest = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_GET['defaultloader']);
if(empty($_dest)){
  $_dest = $_GET['photoviewload'];
}
$dest = $hash->filehash('../../..'.$_dest,'false');
//Ассоциируем файлы
$newpermission->fileassociate(array('png','jpg','jpeg','bmp','gif'), $folder.'main.php', 'photoviewload', $appname);

if($dest  ==  ''){
  $dest = $_dest;
}
$photo='('.$dest.')';

/*local file?*/
$isLocal = realpath((dirname($dest)));

/*download image*/
if($_GET['download'] == 'true'){
  $downloadDir = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/documents/images';
  if(!is_dir($downloadDir)){
    mkdir($downloadDir);
  }
  $ch=curl_init($dest);
  $fp=fopen($downloadDir.'/'.basename($dest),'wb');
  curl_setopt($ch, CURLOPT_FILE,$fp);
  curl_setopt($ch, CURLOPT_HEADER,0);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
  if(is_file($downloadDir.'/'.basename($dest))){
    ?>
    <script>
      makeprocess("system/apps/Explorer/main.php" , "<?echo $downloadDir?>", "dir", "Explorer");
    </script>
    <?
  }
}
?>

<style>
<?echo '#'.$appname.$appid;?> {
  background-color: #3e3d40;
  transition: background-color 0.3s ease-out;
  overflow-y: hidden;
}
<?echo '#'.$appname.$appid;?>.zoom {
  background-color: #262626;
}

<?echo ".photo".$appid;?> {
  background: url<?echo $photo;?> center center/cover no-repeat;
  width: 100%;
  height: 100%;
  min-width: 450px;
  min-height: 450px;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  margin: auto;
  transition: all 0.2s ease-out;
  transform: scale(0.5);
}
<?echo ".photo".$appid;?>:hover {
  box-shadow:rgba(16, 16, 22, 0.2) 0px 0px 1px 1px;
}

<?echo ".button".$appid;?> {
  width: 30px;
  height: 30px;
  background-color: #000000;
  border-radius: 2px;
  position: absolute;
  right: 5%;
  top: 50%;
  transition: all 0.2s ease-in-out;
  box-shadow: 0px 0px 2px 2px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  opacity: 0.5;
}
<?echo ".button".$appid;?>:hover {
  opacity: 1;
}
<?echo ".button".$appid;?> i.material-icons {
  color: #fff;
  padding: 3px 3px;
  user-select: none;
}

<?echo ".zoom".$appid;?><?echo " .button".$appid;?>  {
  right: 20px;
}

<?echo ".zoom-in".$appid;?> {
  margin-top: 15px;
}

<?echo ".zoom-out".$appid;?> {
  margin-top: -20px;
}
</style>

<div id="photo<?echo $appname.$appid?>" class="photo<?echo $appid?>"></div>
<div class="button<?echo $appid?> zoom-in<?echo $appid?>"><i class="material-icons">-</i></div>
<div class="button<?echo $appid?> zoom-out<?echo $appid?>"><i class="material-icons">+</i></div>
<?
if(empty($isLocal)){
  ?>
  <div class="ui-forest-blink" id="downloadImage<?echo $appid?>" style="background:rgba(0,0,0,0.82); text-align:center; position:absolute; top:88%; left:46%; padding:0 20px; color:#8BC34A; font-size:30px; font-weight:900;">&#11015;</div>
  <?
}
?>
</div>
<script>
var zoom = 0.5;
$(document).ready(function(){

  $('.zoom-in<?echo $appid?>').click(function(){
    zoom-=0.5;
    var k = parseFloat(0.5+zoom/7);
    $('.photo<?echo $appid?>').css('transform','scale('+k+')');
  });
  $('.zoom-out<?echo $appid?>').click(function(){
    zoom+=0.5;
    var k = parseFloat(0.5+zoom/7);
    $('.photo<?echo $appid?>').css('transform','scale('+k+')');
  });
});

/*download image*/
$('#downloadImage<?echo $appid?>').click(function(){
  $("#<?echo $appid?>").load("<?echo $folder;?>main.php?photoviewload=<?echo $dest?>&download=true&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>");
});

$( function() {
  $( "#photo<?echo $appname.$appid;?>" ).draggable();
});
</script>
<?
unset($appid);
?>
