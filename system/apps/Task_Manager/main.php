<?
if($_GET['getinfo'] == 'true'){
	include '../../core/library/etc/appinfo.php';
	$appinfo = new AppInfo;
	$appinfo->setInfo('Task Manager', '1.0', 'Forest OS Team', 'Диспетчер задач');
}
$appname=$_GET['appname'];
$appid=$_GET['appid'];
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:100%; width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*--------Подключаем библиотеки--------*/
require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
$language  = parse_ini_file('app.lang');
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
.tm-box-left{
  text-align: left;
  table-layout:fixed;
  color: #4654bb;
  cursor: pointer;
}
.tm-box-close{
  float: right;
  color: #69130c;
  background: #e43232;
  padding: 0px 5px 1px 6px;
  margin: 0px 5px;
  border-radius: 5px;
  border-radius: 1px solid #790f0f;
  font-weight: 900;
  font-size: 13px;
}
</style>
<div>
  <table border='1' cellpadding="5" style="border-collapse: collapse; border:1px solid #d4d4d4; width:100%; text-align: center;">
    <tbody id="process_manager<?echo $appid?>">
      <tr id="process_titles<?echo $appid?>" style="color:#f2f2f2; background-color:#3a3a3a;">
        <td><?echo $language[$_SESSION['locale'].'_name_title']?></td>
        <td><?echo $language['id_title']?></td>
        <td><?echo $language[$_SESSION['locale'].'_loc_title']?></td>
      </tr>
      <tbody id="taskcontainer<?echo $appid?>">
      </div>
    </tbody>
  </table>
</div>
</div>
<script>
clearInterval(timer<?echo $appid;?>);
var temp_id = 0;
var new_id  = 0;
var new_name = '';
var new_loc = '';
$(".process-container").each(function(index, element){
  var p_id = $(element).attr("id");
  var p_name = $("#drag"+p_id + "> .process-title").text();
  var p_loc = $(element).attr("location");
  $("#taskcontainer<?echo $appid?>").append('<tr t_id="'+p_id+'" id="task'+p_id+'"><td>'+p_name+'</td><td>'+p_id+'</td><td class="tm-box-left""><span onClick="open_folder('+p_id+')">'+p_loc+'</span><div class="tm-box-close ui-forest-blink" onClick="task_close('+p_id+'); checkwindows();">x</div></td></tr>');
  temp_id = p_id;
});

function task_check<?echo $appid;?>(){
  $(".process-container").each(function(index, element){
    new_id = $(element).attr("id");
    new_name = $("#drag"+new_id + "> .process-title").text();
    new_loc = $(element).attr("location");
  });
  if(new_id > temp_id){
    temp_id = new_id;
    $("#taskcontainer<?echo $appid?>").append('<tr t_id="'+new_id+'" id="task'+new_id+'"><td>'+new_name+'</td><td>'+new_id+'</td><td class="tm-box-left"><span onClick="open_folder('+new_id+')">'+new_loc+'</span><div class="tm-box-close ui-forest-blink" onClick="task_close('+new_id+'); checkwindows();">x</div></td></tr>');
  }
  $("#taskcontainer<?echo $appid?> > tr").each(function(index, element){
    var get_id = $(element).attr("t_id");
    if(!$("#process" + get_id).length){
      $("#task"+get_id).remove();
    }
  });
}

function task_close(id){
  $("#process"+id).remove();
  $("#task"+id).remove();
}

function open_folder(id){
var folder = $("#task"+id+' > .tm-box-left').text();
folder = folder.replace('/main.phpx','');
folder = '<?echo $_SERVER['DOCUMENT_ROOT']?>/'+folder+'';
makeprocess('<?echo $_SERVER['DOCUMENT_ROOT'].'/system/apps/Explorer/main.php'?>',folder,'dir','<?echo $language[$_SESSION['locale'].'_explorer_title']?>');
}

var timer<?echo $appid;?> = setInterval(function(){
  if($("#<?echo $appname.$appid;?>").length){
    task_check<?echo $appid;?>();
}else{
  clearInterval(timer<?echo $appid;?>);
}
},500);
</script>
<?
unset($appid);
?>
