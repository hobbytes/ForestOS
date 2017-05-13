<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#ebebeb; height:600px; width:700px; color:#000; max-height:95%; max-width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width: 100%; font-size: 30px; background: #466991; box-shadow: 250px 0 0 #466991; color:#fff; padding:10px;">VK</div>
<?php
/*Twitter Reader*/
//Инициализируем переменные
$click=$_GET['mobile'];
$folder=$_GET['destination'];

$settings=parse_ini_file('./settings.ini');
$access_token=$settings['access_token'];
$user_id=$settings['user_id'];
if(!isset($access_token)){
$redirect=$_SERVER['SERVER_NAME'].'/'.$folder;
$url='https://oauth.vk.com/authorize?response_type=token&client_id=5831884&client_secret=AGvQAxf9faG5FhN44i70&scope=friends,photos,audio,wall,offline,groups,status&display=page&redirect_uri='.$redirect.'/authorize.php&v=5.64';
?>
<a href="<?echo $url;?>" target="_blank">Auth</a>

<?}
else
{
$request_params = array(
'user_id' => $user_id,
'fields' => 'first_name,last_name,bdate,photo_id',
'v' => '5.52'
);
$get_params = http_build_query($request_params);
$result = json_decode(file_get_contents('https://api.vk.com/method/users.get?'. $get_params));
$first_name=($result -> response[0] -> first_name);
$last_name=($result -> response[0] -> last_name);
$photo_id=($result -> response[0] -> photo_id);
}

?>
</div>
<script>
function changeuser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?tweetsetfollow="+escape($("#tweetsetfollow<?echo $appid;?>").val())+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
function finduser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?tweetuser="+$("#tweetuser<?echo $appid;?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
</script>
<?
unset($appid);
?>
