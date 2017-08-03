<div id="login" style="margin:auto; text-align:center; max-width:500px; height:80%;">
<div style="text-align:center; margin:auto; width:auto; height:auto; ">
<div id="loginin" style="padding-top: 50%;">
<div style="font-size:30px; padding:30px; color:#fff; text-shadow: 1px 1px 1px grey; user-select: none; cursor: default; text-transform:uppercase; font-weight: 700;">Forest OS</div>
<?
if (function_exists('date_default_timezone_set'))
date_default_timezone_set('Europe/Moscow');
$date= date("d.m.y,H:i:s");
$ip=$_SERVER["REMOTE_ADDR"];
$browser=$infob->browser($_SERVER["HTTP_USER_AGENT"]);
$status2=$_POST['logins'];

if ($status2!='')
{
$loginin=strtolower(addslashes(strip_tags(htmlspecialchars($_POST['loginin']))));
$passwordin=md5(addslashes(strip_tags(htmlspecialchars($_POST['passwordin']))));
}
global $loginin;
  session_start();
  $auth = new AuthClassUser();
  $auth->construct('login',$loginin);
  if (isset($loginin) && isset($passwordin)) {
      if (!$auth->auth($loginin, $passwordin)) {
          echo "<h2 style=\"color:red;font-size:small;\">Логин или пароль введен не правильно!</h2>";
          $login_get=$loginin;
      }
  }

  if (isset($_GET["exit"])) {
      if ($_GET["exit"] == 1) {
            $auth->out(); header("Location: ?exit=0");
      }
  }

  if ($auth->isAuth()) {
    header("Location:os.php");
    $text='login:['.$date.'], browser:'.$browser.', ip:'.$ip;
$infob->writestat('system/users/'.$_SESSION["loginuser"].'/settings/login.stat',$text);
  }

  $gui = new gui;
  $gui->formstart('POST');
  $gui->inputslabel('Логин', 'text', 'loginin', "$login_get",'70', 'введите логин');
  $gui->inputslabel('Пароль', 'password', 'passwordin', '','70','введите пароль');
  $gui->button('Войти', '#fff', '#16638e', '30','logins');
  $gui->formend();


?>
</div>
<div id="registration" style="display:none;">
<div style="font-size:20px; padding:30px;">Регистрация</div>
<?
$gui = new gui;
$gui->formstart('POST');
$gui->inputslabel('Логин', 'text', 'reglogin', '','70', 'введите логин');
$gui->inputslabel('Пароль', 'password', 'regpassword', '','70','придумайте пароль');
$gui->inputslabel('Место на диске', 'text', 'reghdd', '60000','70','укажите место на диске');
$gui->button('Регистрация', '#fff', '#16638e', '30','registrations');
$gui->formend();

$status=$_POST['registrations'];
if ($status!='')
{
$reglogin=strtolower(addslashes(strip_tags(htmlspecialchars($_POST['reglogin']))));
$regpassword=md5(addslashes(strip_tags(htmlspecialchars($_POST['regpassword']))));
$reghdd=intval($_POST['reghdd']);
$fuid=strtoupper(md5($reglogin.$regpassword.$reghdd.$date));
if($reglogin!='' && $regpassword!='' && $reghdd!=''){
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="CREATE TABLE IF NOT EXISTS forestusers (login VARCHAR(50), password VARCHAR(50), hdd INT(50), fuid VARCHAR(50));
INSERT INTO forestusers (login, password,hdd,fuid) VALUES ('$reglogin', '$regpassword','$reghdd','$fuid')";
try {
  $conn->exec($sql);
  //подготавливаем нового пользователя;
  mkdir('system/users/'.$reglogin.'/');
  mkdir('system/users/'.$reglogin.'/desktop/');
  mkdir('system/users/'.$reglogin.'/settings/');
  mkdir('system/users/'.$reglogin.'/settings/etc/');
  copy('system/core/design/walls/water.jpg','system/users/'.$reglogin.'/settings/etc/wall.jpg');

}
catch (PDOException $e){
  echo 'false: '.$e->getMessage().'\n';
  die();
}
unset($conn,$sql);
} else {
  echo 'Некоторые поля не заполнены или не введены корректные данные!';
}}
?>
</div>
</div>
</div>
<div class="date" style="float:left; user-select: none; cursor: default; color:#fff; font-size:20pt; padding-left:30px; margin-bottom:10px; position:relative;">
    <?php echo $object->getDayRus().' '.date('d').'<br>';?>
  <span style="font-size:35pt;" id="time"></span>
</div>
<script type="text/javascript">
showTime();
</script>
<?
if($action=='login'){
echo '<script>login()</script>';}
