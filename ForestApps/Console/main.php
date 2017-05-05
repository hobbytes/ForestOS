<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#1b1b1b; height:100%; color:#f2f2f2; width:100%; border-radius:0px 0px 5px 5px; overflow:hidden;">
<?php
/*Console*/
//Подключаем библиотеки

//Инициализируем переменные
$click=$_GET['mobile'];
$appdownload=$_GET['appdownload'];
$type=$_GET['type'];
$folder=$_GET['destination'];
$text=preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$_GET["command"]);
//Логика
?>
<textarea cols="50" rows="10" style="background-color:#1b1b1b; color:#f2f2f2; width:100%; margin:auto; border: 1px solid #3c3c3c;" id="command<?echo $appid;?>"></textarea>
<div id="launchapp" style="display:block; margin:auto;" onClick="launch<?echo $appid;?>(this);" class="ui-button ui-widget ui-corner-all">Выполнить</div>
<div style="background-color:#1b1b1b; color:#fff; width:90%; padding:10px; margin:auto; text-align:left;">
<?echo 'Команда: <i style="color:grey;">'.$text.'</i><br>Ответ: ';
try {eval ($text);} catch (Exception $e) {echo 'Выброшено исключение: ',  $e->getMessage(), "\n";} echo "\n\n\n";?>
</div>
</div>
<script>
function launch<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder;?>main.php?command="+escape($("#command<?echo $appid;?>").val())+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
</script>
<?
unset($appid);
?>
