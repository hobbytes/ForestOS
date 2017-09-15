<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
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
$dest = $hash->filehash('../../..'.$_GET['photoviewload'],'false');
//Ассоциируем файлы
$newpermission->fileassociate(array('png','jpg','jpeg','bmp','gif'), $folder.'main.php', 'photoviewload', $appname);

if($dest==''){
  $dest = $_GET['photoviewload'];
}
$photo='('.$dest.')';
//Логика
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
  transition: all 0.1s ease-out;
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

<div id="photo<?echo $appname.$appid;?>" class="photo<?echo $appid;?>"></div>
<div class="button<?echo $appid;?> zoom-in<?echo $appid;?>"><i class="material-icons">-</i></div>
<div class="button<?echo $appid;?> zoom-out<?echo $appid;?>"><i class="material-icons">+</i></div>
</div>
<script>
var zoom = 0;
$(document).ready(function(){

  $('.zoom-in<?echo $appid;?>').click(function(){
    zoom-=1;
    if(zoom <= 0) zoom = 0;
    if(zoom > 15) zoom = 15;
    var k = parseFloat(1+zoom/5);
    $('.photo<?echo $appid;?>').css('transform','scale('+k+')');
  });
  $('.zoom-out<?echo $appid;?>').click(function(){
    zoom+=1;
    if(zoom <= 0) zoom = 0;
    if(zoom > 20) zoom = 20;
    var k = parseFloat(1+zoom/5);
    $('.photo<?echo $appid;?>').css('transform','scale('+k+')');
  });
});


$( function() {
  $( "#photo<?echo $appname.$appid;?>" ).draggable();
});

function photoload<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder;?>main.php?command=test&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
</script>
<?
unset($appid);
?>
