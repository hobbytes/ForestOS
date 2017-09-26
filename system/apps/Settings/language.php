<?
//Инициализируем переменные
$appid  = $_GET['appid'];
$appname  = $_GET['appname'];
$folder = $_GET['destination'];
?>
<div id="<?echo $appname.$appid;?>" style="text-align:center; background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span>Язык и регион</div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';
$newgui = new gui;
$security	=	new security;
session_start();
$security->appprepare();
$choose_lang  = $_GET['choose_lang'];
if(!empty($choose_lang)){
  file_put_contents('../../users/'.$_SESSION['loginuser'].'/settings/language.foc',$choose_lang);
  $newgui->newnotification($appname,"Язык и регион","Язык системы был изменен. Перезагрузите систему, чтобы изменения вступили в силу<br><span id='restart' style='margin-left: 25%;' class='ui-button ui-widget ui-corner-all'>Перезагрузить</span>");
  $_SESSION['loacale']  = $choose_lang;
}
$current_lang = file_get_contents('../../users/'.$_SESSION['loginuser'].'/settings/language.foc');
if(empty($current_lang)){
  file_put_contents('../../users/'.$_SESSION['loginuser'].'/settings/language.foc','en');
  $current_lang = 'en';
  $_SESSION['loacale']  = $current_lang;
}
?>
<div style="margin:15%; font-size:20px">
  <span>
    Выберите язык
  </span>
<select id="selectlang<?echo $appid?>" style="margin:10px; width:200px; font-size:20px; padding:10px; -webkit-appearance:none;">
  <option value="ru">Русский</option>
  <option value="en">English</option>
</select>
<div onClick="savelang<?echo $appid?>();" class="ui-forest-button ui-forest-accept ui-forest-center">Сохранить</div>
</div>
</div>
<script>
$("#selectlang<?echo $appid?>").val("<?echo $current_lang?>");

function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>/main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
$( "#restart" ).on( "click", function() {return location.href = 'os.php';});
function savelang<?echo $appid?>(){
  var choose_lang = $("#selectlang<?echo $appid?>").val();
  $("#<?echo $appid;?>").load("<?echo $folder?>/language.php?choose_lang="+choose_lang+"&id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>");
}
</script>
