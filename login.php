
<div id="login" style="margin:auto; text-align:center; max-width:500px; height:80%;">
<div style="text-align:center; margin:auto; width:auto; height:auto; ">
<div id="loginin" style="padding-top: 50%;">
<div id="labelos">Forest OS</div>
<?

/* #Display errors */

ini_set('display_errors','Off');

if (function_exists('date_default_timezone_set'))
date_default_timezone_set('Europe/Moscow');

global $loginin, $infob, $security, $language, $mobile;

$date = date("d.m.y, H:i:s");

if(isset($_POST['logins'])){
  $status2 = $_POST['logins'];
}

$keyaccess = NULL;

if (!empty($status2))
{
  if(isset($_POST['keyaccess'])){
    $keyaccess = $_POST['keyaccess'];
    $loginin = strtolower(addslashes(strip_tags(htmlspecialchars($_POST['loginin']))));
    $loginin = str_replace(' ', '_', $loginin);
    $passwordin = $security->crypt_s(addslashes(strip_tags(htmlspecialchars($_POST['passwordin']))),$loginin);
  }
}

if(!isset($_SESSION)){
  session_start();
}

if(!isset($_SESSION['counter'])){// prepare counter for capthca
  $_SESSION['counter'] = 0;
}

if(!isset($_SESSION['BlockDate'])){// prepare counter for capthca
  $_SESSION['BlockDate'] = false;
}

$auth = new AuthClassUser();
$auth->construct('login', $loginin, $keyaccess);

if (isset($loginin) && isset($passwordin)) {
  $_SESSION['safemode'] = $_POST['safemode'];

  if (!$auth->auth($loginin, $passwordin, $keyaccess)) {

    echo '<h2 style="color:#fff; background-color:#ec6767; border:2px solid #791a1a; width:350px; padding:13px 0; margin:10px auto; font-size:small;">'.$language[$_SESSION['locale'].'_login_error'].'</h2>';
    $login_get = $loginin;

    if(empty($keyaccess)){
      $infob->writestat('WARNING! Wrong login or password -> '.$loginin,'system/core/journal.mcj');
    }

    $_SESSION['counter'] = $_SESSION['counter'] + 1;// count
    if($_SESSION['counter'] >= 3 && !$_SESSION['BlockDate']){
      $startDate  = time();
      $_SESSION['BlockDate'] = date('d-m-y H:i:s', strtotime("+10 min", $startDate));
    }
  }
}

if (isset($_GET["exit"])) {
  if ($_GET["exit"] == 1) {
        $auth->out();
        header("Location: ?exit=0");
  }
}

  if ($auth->isAuth()) {
    if(!empty($keyaccess)){
      $prefix = '[via Access Key: '.$keyaccess.']';
    }
    header("Location:os.php");
    $infob->writestat('Success Login -> '.$prefix.$loginin,'system/core/journal.mcj');
    unset($_SESSION['counter']);
    $_SESSION['BlockDate'] = false;
}

if(!$_SESSION['BlockDate'] || date('d-m-y H:i:s') >= $_SESSION['BlockDate']){
  $_SESSION['BlockDate'] = false;
  $gui = new gui;
  $gui->formstart('POST');
  $gui->inputslabel('Логин', 'text', 'loginin', "$login_get",'70', $language[$_SESSION['locale'].'_login_input']);
  $gui->inputslabel('Пароль', 'password', 'passwordin', '','70', $language[$_SESSION['locale'].'_password_input']);
}else{
  $_date = strtotime($_SESSION['BlockDate']) - strtotime(date('d-m-y H:i:s'));
  $timeleft = round(abs($_date/60));
  echo '<h2 style="color:#fff; background-color:#ec6767; border:2px solid #791a1a; width:350px; padding:13px 0; margin:10px auto; font-size:small;">'.$language[$_SESSION['locale'].'_login_error_2'].$timeleft.' min</h2>';
}

?>
<div id="safemode" style="color:#63e47a; margin:10px 0; display:none;">
  <div style="margin:10 0;">
  <input type="checkbox" name="safemode" value="true" style="vertical-align:top; margin: 0 3px 0 0; width:17px; height:17px;">
  <?
  echo $language[$_SESSION['locale'].'_safemode_label'].'</div>';

  $gui->inputslabel('keyaccess', 'text', 'keyaccess', "$keyaccess",'70', 'Key Access');
  ?>
</div>
<?
$gui->button($language[$_SESSION['locale'].'_login_button'], '#fff', '#f45c43', '30','logins');
$gui->formend();

$timezone = file_get_contents('system/users/'.$_SESSION['superuser'].'/settings/timezone.foc');
date_default_timezone_set("$timezone");
$date = date("m/d/Y H:i:s");
$_offset = new DateTime($date, new DateTimeZone("$timezone"));
$offset = $_offset->getOffset()/3600;
?>
</div>
</div>
</div>
<div class="date" style="float:left; user-select: none; cursor: default; color:#fff; padding-left:30px; margin-bottom:10px; position:relative;">
  <span id="date" style="font-size:27pt; font-weight:700; text-transform:uppercase;"><?php echo $object->getDayRus().', '.date('d').'<br>';?></span>
  <span id="time" style="font-size:35pt;"></span>
</div>
<script type="text/javascript">
$( function() {
  $(window).load(function(){
    $(".welcomescreen").hide('fade',500);
    var is_mobile = "<?echo $mobile?>";
    if(is_mobile == "true"){
      $("#loginin").css("padding-top","0");
      $("#date").css("font-size","20pt");
      $("#time").css("font-size","30pt");
    }
  });
});




$("#labelos").click(function(){
  if($("#safemode").is( ":hidden" )){
  $("#safemode").slideDown("fast");
}else{
  $("#safemode").slideUp("fast");
}
});

$('.ui-body').css('background','linear-gradient(to top, #171717, #3a3a3a)')

function showTime()
{
  offset = "<?echo $offset?>"
  d = new Date();   
  utc = d.getTime() + (d.getTimezoneOffset() * 60000);   
  date = new Date(utc + (3600000*offset)); 
  var H = '' + date.getHours();
  H = H.length < 2 ? '0' + H:H;
  var M = '' + date.getMinutes();
  M = M.length < 2 ? '0' + M:M;
  var clock = H + ":" + M;
  $("#time").text(clock);
  setTimeout(showTime,1000); // перерисовать 1 раз в сек.
}
showTime();
</script>
<div class="welcomescreen">
  <img src="system/core/design/images/forestosicon.png">
</div>
<?
