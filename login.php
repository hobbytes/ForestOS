<div style="display: grid; grid-template-rows: 70% 30%; width: 100%; height: 100%;">
<div id="login" style="width: 100%; margin:auto; text-align:center; max-width:500px; height: 100%;">
<div style="text-align:center; margin:auto; width:auto; height:auto; ">
<div id="loginin" style="padding-top: 25vh;">
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

    echo '<h2 class="error-layout">'.$language[$_SESSION['locale'].'_login_error'].'</h2>';
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
  $_SESSION['counter'] = 0;
  $gui = new gui;
  $gui->formstart('POST');
  ?>
  <input class="loginin input-login" id="loginin" placeholder="<?=$language[$_SESSION['locale'].'_login_input']?>" type="text" name="loginin" value="<?=$login_get?>">
  <input class="passwordin input-login" id="passwordin" placeholder="<?=$language[$_SESSION['locale'].'_password_input']?>" type="password" name="passwordin" value="">
  <?
}else{
  $_date = strtotime($_SESSION['BlockDate']) - strtotime(date('d-m-y H:i:s'));
  $timeleft = round(abs($_date/60));
  echo '<h2 class="error-layout">'.$language[$_SESSION['locale'].'_login_error_2'].$timeleft.' min</h2>';
}

?>
<div id="safemode" style="color:#63e47a; margin:10px 0; display:none;">
  <div style="margin:10 0;">
  <label>
    <input type="checkbox" name="safemode" value="true" style="vertical-align:top; margin: 0 3px 0 0; width:17px; height:17px;">
    <?
    echo $language[$_SESSION['locale'].'_safemode_label'].'</div>';
    ?>
  </label>
  <input class="keyaccess input-login" id="keyaccess" placeholder="Key Access" type="text" name="keyaccess" value="<?=$keyaccess?>">
</div>
<?
if(!$_SESSION['BlockDate']){
  echo '<input class="buttoncustom" type="submit" name="logins" value="'.$language[$_SESSION['locale'].'_login_button'].'">';
  $gui->formend();
}

$timezone = $_SESSION['timezone'];
date_default_timezone_set("$timezone");
$date = date("m/d/Y H:i:s");
$_offset = new DateTime($date, new DateTimeZone("$timezone"));
$offset = $_offset->getOffset()/3600;
?>
</div>
</div>
</div>
<div class="date" style="margin-top: 10vh; float: left; user-select: none; cursor: default; color:#fff; padding-left: 5vw; margin-bottom:10px; position:relative;">
  <span id="date" style="font-size: 27pt; font-weight: 700; text-transform: uppercase;"><?php echo $object->getDayRus().', '.date('d').'<br>';?></span>
  <span id="time" style="font-size: 35pt;"></span>
</div>
</div>
<script type="text/javascript">
$( function() {
  $(window).load(function(){
    var is_mobile = "<?echo $mobile?>";
    if(is_mobile == "true"){
      $("#loginin").css("padding-top","0");
      $("#date").css("font-size","20pt");
      $("#time").css("font-size","30pt");
    }
    $(".welcomescreen").hide('fade',500);
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
  setTimeout(showTime,1000);
}
showTime();
</script>
<div class="welcomescreen">
  <img src="system/core/design/images/forestosicon.png">
</div>
<?
