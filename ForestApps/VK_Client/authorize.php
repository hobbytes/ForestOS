<?
$redirect=$_GET['redirect'];
$access_token=$_GET['access_token'];
$user_id=$_GET['user_id'];
if(!isset($access_token)){
?>
<script>
var hash = window.location.hash.substr(1);

location.href = 'authorize?'+hash;

</script>
<?
}else{
$content='access_token='.$access_token.PHP_EOL.'user_id='.$user_id.PHP_EOL;
$fp=fopen('settings.ini','w');
fwrite($fp,$content);
fclose($fp);
echo 'Вы успешно авторизовались! Перейдите обратно в Forest OS для продолжения';
}
  ?>
