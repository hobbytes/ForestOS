<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#ebebeb; height:600px; width:700px; color:#000; max-height:95%; max-width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*Twitter Reader*/
//Инициализируем переменные
$click=$_GET['mobile'];
$folder=$_GET['destination'];
$tweetuser=$_GET['tweetuser'];
if(isset($_GET['tweetsetfollow']))
{
	$followersettings=$_GET['tweetsetfollow'];
	$fp=fopen('settings.ini','w');
	fwrite($fp,$followersettings);
	fclose($fp);
}
//Логика
require('mytwit.inc.php');
$tFeed = new MyTwit;
$tFeed->TWITTER_CONSUMER_KEY = 'MHsPnxPVvgKWTAUmT05SaYxLk';
$tFeed->TWITTER_CONSUMER_SECRET = 'eGPms1n03wydqkIMjUw2M1FHQyPtOFF9lyEjnQN5gQO0DKdOFP';
$tFeed->TWITTER_OAUTH_ACCESS_TOKEN = '2189131420-1NKf7jEgUq1SguRcc3cm6z8aKP4mjfJ3d74vcZx';
$tFeed->TWITTER_OAUTH_ACCESS_TOKEN_SECRET = 'oGm3zdDeVkByHJjD2xoPOczifTIjnaswYPBmi3v5wJg0A';
$tFeed->PostLimit = 20;
// $tFeed->ExcludeReplies = true;
function tweetsload($tweetuser2){
global $tFeed;
$tFeed->TwitterUser = $tweetuser2;
$tFeed->UpdateCache();
echo '<div id="TweetReader">';
if ($tFeed->ErrorMessage) {
	echo '<div>
		<h3>Не могу загрузить ленту</h3>
		<p>'.$tFeed->ErrorMessage.'</p>
	</div>';
} else {
	echo '<div>
			<img style="border: 2px solid #fff; border-radius: 10px;" src="'.$tFeed->UserInfo['user_profile_image_url_https'].'" alt="'.$tFeed->TwitterUser.'" /><span style="font-size:25px; font-weight:600;">'.$tFeed->UserInfo['user_name'].'</span>
      <img style="width:100%; height:auto; background-size:cover;" src="'.$tFeed->UserInfo['user_profile_banner_url'].'" alt="'.$tFeed->TwitterUser.'" />
		<p style="text-align:center;">
			<span><b>@'.$tFeed->TwitterUser.'</b></span>
			<span >'.$tFeed->UserInfo['user_description'].'</span>
		</p>
		<p style="text-align:center;">'.
			$tFeed->UserInfo['user_followers_count'].' followers | '.$tFeed->UserInfo['user_statuses_count'].' tweets
		</p>
	</div>
	<ol>';
	foreach ($tFeed->Tweets as $tweet) {
		echo '<li style="padding:10px; width: 80%; border-bottom: 2px solid #7ba4d4; font-size: 20px;">'.
			$tweet['MyText'].' <span style="font-size: 11px;">by
			<a href="https://twitter.com/'.$tFeed->TwitterUser.'/status/'.$tweet['id_str'].'" rel="nofollow">'.$tFeed->TwitterUser.'</a>
			'.$tweet['MyTimeAgo'].' via '.$tweet['source'].'</span>
		</li>';
	}
	echo '</ol>';
}
echo '</div>';
}

/*
"contributors_enabled"
, "created_at"
, "default_profile"
, "default_profile_image"
, "description"
, "email"
, "favourites_count"
, "follow_request_sent"
, "following"
, "followers_count"
, "friends_count"
, "geo_enabled"
, "id"
, "is_translator"
, "lang"
, "listed_count"
, "location"
, "name"
, "notifications"
, "profile_background_color"
, "profile_background_image_url"
, "profile_background_image_url_https"
, "profile_background_tile"
, "profile_banner_url"
, "profile_image_url"
, "profile_image_url_https"
, "profile_link_color"
, "profile_sidebar_border_color"
, "profile_sidebar_fill_color"
, "profile_text_color"
, "profile_use_background_image"
, "protected"
, "screen_name"
, "show_all_inline_media"
, "statuses_count"
, "time_zone"
, "url"
, "utc_offset"
, "verified"
, "withheld_in_countries"
, "withheld_scope"
*/

?>
<div id="tabs<?echo $appname.$appid;?>">
	<ul>
	<?
$follow=file_get_contents('settings.ini');
$countfollow=str_word_count($follow);
$followarray = str_word_count($follow,1);
foreach ($followarray as $countfollow) {
	?>

<li><a href="#<?echo $countfollow.$appid;?>">@<?echo $countfollow;?></a></li>
	<?

}
?>
<li><a href="#search<?echo $appname.$appid;?>">Поиск</a></li>
<li><a href="#settings<?echo $appname.$appid;?>">Настройки</a></li>
	</ul>
	<?
	foreach ($followarray as $countfollow) {
		$i=$i++;
		?>
		<div id="<?echo $countfollow.$appid;?>"><?tweetsload($countfollow);?></div>
		<?
}
	?>
	<div id="search<?echo $appname.$appid;?>">
		<input style="width: 80%;margin: 10px; font-size: 18px; margin-left: 10%;" type="text" placeholder="введи имя пользователя" name="tweetuser<?echo $appid;?>" id="tweetuser<?echo $appid;?>" value="">
		<?tweetsload($tweetuser);?>
	</div>
	<div id="settings<?echo $appname.$appid;?>">
		<input style="width: 80%;margin: 10px; font-size: 18px; margin-left: 10%;" type="text" placeholder="введи имена через запятую" name="tweetsetfollow<?echo $appid;?>" id="tweetsetfollow<?echo $appid;?>" value="<?echo $follow;?>">
		<span onClick="changeuser<?echo $appid;?>()" style="margin:10px;" class="ui-button ui-widget ui-corner-all">Сохранить</span>
	</div>
</div>
</div>
<script>
<?
if(!is_file('settings.ini')){
	?>
	$(function(){$("#tabs<?echo $appname.$appid;?>").tabs({active: <?echo $i-1;?>});});
	<?
}
if($tweetuser!=''){
	?>
$(function(){$("#tabs<?echo $appname.$appid;?>").tabs({active: <?echo $i-2;?>});});
<?
}
?>
$("#tweetuser<?echo $appid;?>").keyup(function(event){
    if(event.keyCode == 13){
        finduser<?echo $appid;?>();
  }
});
$(function(){$("#tabs<?echo $appname.$appid;?>").tabs();});
function changeuser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?tweetsetfollow="+escape($("#tweetsetfollow<?echo $appid;?>").val())+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
function finduser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?tweetuser="+$("#tweetuser<?echo $appid;?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
</script>
<?
unset($appid);
?>
