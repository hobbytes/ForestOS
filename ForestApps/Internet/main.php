<?$appname=$_GET['appname'];$appidinternet=$_GET['appid'];?>
<div id="<?echo $appname.$appidinternet;?>" style="background-color:#f2f2f2; height:100%; width:100%; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:hidden;">
<?php
/*Explorer*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
//Инициализируем переменные
$link = $_GET['link'];
$click=$_GET['mobile'];
//Запускаем сессию
session_start();
//обрабатываем кновку удаления
//Логика
echo '<input style="width:96%; font-size:17px; margin-left:10px;" type="search" value="'.$link.'"></input>';

?>
<div id="browserwindow" style="background-color:#e2e2e2; width:100%; word-wrap:break-word;">
<?
function curl ($url,$user_agent,$retry=0){
  if ($retry>5){
  //  print "Maximum 5 retries";
    return "in loop!";
  }

$ch=curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_HEADER,true);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_COOKIEFILE,'./cookie.txt');
curl_setopt($ch,CURLOPT_COOKIEJAR,'./cookie.txt');
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
curl_setopt($ch,CURLOPT_USERAGENT,$user_agent);
$result=curl_exec($ch);
curl_close($ch);
  if(preg_match("|Location: (https?://\S+)|",$result,$m)){
    //print "Manually doing follow redirect!\n$m[1]\n";
    return curl($m[1],$user_agent,$retry+1);
  }
  return $result;
  }
  $response = curl("http://www.yandex.ru/",$_SERVER['HTTP_USER_AGENT']);
  print "$response\n";
?>
</div>

<script>
function load<?echo $appidinternet;?>(el){$("#<?echo $appidinternet;?>").load("system/apps/<?echo $appname;?>/main.php?dir="+el.id+"&id=<?echo rand(0,10000).'&appid='.$appidinternet.'&mobile='.$click.'&appname='.$appname;?>")};

</script>
<?
unset($appidinternet);
?>

</div>
