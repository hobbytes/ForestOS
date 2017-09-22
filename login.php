<div id="login" style="margin:auto; text-align:center; max-width:500px; height:80%;">
<div style="text-align:center; margin:auto; width:auto; height:auto; ">
<div id="loginin" style="padding-top: 50%;">
<div style="font-size:30px; padding:30px; color:#fff; text-shadow: 1px 1px 1px grey; user-select: none; cursor: default; text-transform:uppercase; font-weight: 700;">Forest OS</div>
<?
if (function_exists('date_default_timezone_set'))
date_default_timezone_set('Europe/Moscow');
global $loginin, $infob,  $security;
$date= date("d.m.y,H:i:s");
$status2=$_POST['logins'];
if ($status2!='')
{
$loginin=strtolower(addslashes(strip_tags(htmlspecialchars($_POST['loginin']))));
$passwordin=$security->crypt(addslashes(strip_tags(htmlspecialchars($_POST['passwordin']))),$loginin);
}
session_start();
$auth = new AuthClassUser();
$auth->construct('login',$loginin);
if (isset($loginin) && isset($passwordin)) {
  if (!$auth->auth($loginin, $passwordin)) {
    echo "<h2 style=\"color:#fff; background-color:#ec6767; border:2px solid #791a1a; width:350px; padding:13px 0; margin:10px auto; font-size:small;\">Логин или пароль введен не правильно!</h2>";
    $login_get=$loginin;
    $infob->writestat('WARNING! Wrong login or password','system/core/journal.mcj');
  }
}

if (isset($_GET["exit"])) {
  if ($_GET["exit"] == 1) {
        $auth->out(); header("Location: ?exit=0");
  }
}
  if ($auth->isAuth()) {
    header("Location:os.php");
    $infob->writestat('Success Login','system/core/journal.mcj');
}
$gui = new gui;
$gui->formstart('POST');
$gui->inputslabel('Логин', 'text', 'loginin', "$login_get",'70', 'введите логин');
$gui->inputslabel('Пароль', 'password', 'passwordin', '','70','введите пароль');
$gui->button('Войти', '#fff', '#092738', '30','logins');
$gui->formend();
?>
</div>
</div>
</div>
<div class="date" style="float:left; user-select: none; cursor: default; color:#fff; font-size:20pt; padding-left:30px; margin-bottom:10px; position:relative;">
  <?php echo $object->getDayRus().' '.date('d').'<br>';?>
  <span style="font-size:35pt;" id="time"></span>
</div>
<script type="text/javascript">
$( function() {
  $(window).load(function(){
    $(".welcomescreen").hide('fade',500);
    showTime();
  });
});
</script>
<div class="welcomescreen">
  <img src="system/core/design/images/forestosicon.png">
</div>
<?
