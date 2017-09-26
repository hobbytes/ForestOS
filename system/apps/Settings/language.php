<?
//Инициализируем переменные
$appid  = $_GET['appid'];
$appname  = $_GET['appname'];
$folder = $_GET['destination'];
$choose_lang  = $_GET['choose_lang'];

include '../../core/library/gui.php';
$newgui = new gui;
session_start();
$language_lang  = parse_ini_file('app.lang');

if(!empty($choose_lang)){
  file_put_contents('../../users/'.$_SESSION['loginuser'].'/settings/language.foc',$choose_lang);
  $newgui->newnotification($appname,$language_lang[$_SESSION['locale'].'_settings_language'],$language_lang[$_SESSION['locale'].'_save_lang']."<br><span id='restart' style='margin-left: 25%;' class='ui-button ui-widget ui-corner-all'>".$language_lang[$_SESSION['locale'].'_restart']."</span>");
  $_SESSION['locale']  = $choose_lang;
}
$current_lang = file_get_contents('../../users/'.$_SESSION['loginuser'].'/settings/language.foc');
if(empty($current_lang)){
  file_put_contents('../../users/'.$_SESSION['loginuser'].'/settings/language.foc','en');
  $current_lang = 'en';
  $_SESSION['locale']  = $current_lang;
}
?>
<div id="<?echo $appname.$appid;?>" style="text-align:center; background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_lang[$_SESSION['locale'].'_settings_language']?></div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/etc/security.php';
$security	=	new security;
$security->appprepare();

?>
<div style="margin:15%; font-size:20px">
  <span>
    <?echo $language_lang[$_SESSION['locale'].'_label_choose']?>
  </span>
<select id="selectlang<?echo $appid?>" style="margin:10px; width:200px; font-size:20px; padding:10px; -webkit-appearance:none;">
  <option value="ru">Русский</option>
  <option value="en">English</option>
</select>
<div onClick="savelang<?echo $appid?>();" class="ui-forest-button ui-forest-accept ui-forest-center"><?echo $language_lang[$_SESSION['locale'].'_button_save']?></div>
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
