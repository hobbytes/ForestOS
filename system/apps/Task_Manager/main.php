<?
/*--------Получаем App Name и App ID--------*/
$appname=$_GET['appname'];
$appid=$_GET['appid'];
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:100%; width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*--------Подключаем библиотеки--------*/
require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
/*--------Запускаем сессию--------*/
session_start();
/*--------Проверяем безопасность--------*/
$security	=	new security;
$security->appprepare();
$click=$_GET['mobile'];
$folder=$_GET['destination'];
/*--------Логика--------*/
?>
<style>
.tm_box{
  width: 86%;
  padding: 10px;
  border: 1px solid #d4d4d4;
}
</style>
<div>
  <table border='1' cellpadding="10" style="border-collapse: collapse; border:1px solid #d4d4d4; width:100%; text-align: center;">
    <tbody id="process_manager<?echo $appid?>">
      <tr id="process_titles<?echo $appid?>" style="color:#f2f2f2; background-color:#3a3a3a;">
        <td>имя</td>
        <td>id процесса</td>
        <td>расположение</td>
      </tr>
    </tbody>
  </table>
</div>
</div>
<script>
$(".process-container").each(function(index, element){
  var p_id = $(element).attr("id");
  var p_name = $("#drag"+p_id + "> .process-title").text();
  var p_loc = $(element).attr("location");
  $("#process_manager<?echo $appid?>").append('<tr><td>'+p_name+'</td><td>'+p_id+'</td><td width="50px;">'+p_loc+'</td></tr>');
  });

</script>
<?
unset($appid);
?>
