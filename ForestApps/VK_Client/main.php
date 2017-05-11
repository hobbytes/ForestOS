<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#ebebeb; height:600px; width:700px; color:#000; max-height:95%; max-width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*Twitter Reader*/
//Инициализируем переменные
$click=$_GET['mobile'];
$folder=$_GET['destination'];
$redirect=$_SERVER['SERVER_NAME'].'/'.$folder;
$url='https://oauth.vk.com/authorize?response_type=token&client_id=5831884&client_secret=AGvQAxf9faG5FhN44i70&scope=friends,photos,audio,wall,offline,groups,status&display=page&redirect_uri='.$redirect.'/authorize.php&v=5.64';
?>
<a href="<?echo $url;?>" target="_blank">Auth</a>
</div>
<script>
function changeuser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?tweetsetfollow="+escape($("#tweetsetfollow<?echo $appid;?>").val())+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
function finduser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?tweetuser="+$("#tweetuser<?echo $appid;?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
</script>
<?
unset($appid);
?>
