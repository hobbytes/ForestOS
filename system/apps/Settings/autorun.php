<?
//Инициализируем переменные
$appid=$_GET['appid'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
?>
<div id="<?echo $appname.$appid;?>" style="text-align:center; background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span>Менеджер автозапуска</div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
session_start();
echo '<div style="width:70%; margin:15px auto;"><fieldest>';
$i=0;
foreach (glob("../*/main.php") as $filename)
{
  $i++;
  $name = str_replace(array('main.php','..','/','_'),array('','','',' '),$filename);
  echo '<label for="checkbox'.$name.'">'.$name.'</label>';
  echo '<input type="checkbox" id="checkbox'.$name.'" name="'.$name.'">';
}
echo '</fieldest></div>';
echo '<hr><div onClick="saveautoset'.$appid.'();" class="ui-button ui-widget ui-corner-all" style="margin:10px auto;" >Сохранить</div>';
?>
</div>
<script>
$(function(){
  $("input").checkboxradio({
    icon: false
  });
});
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function saveautoset<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder?>security.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid.'&erase=true';?>")};
</script>
