<?

/* первый запуск: пока нет ни одного администратора, ведём на мастер настройки.
   Проверка стоит до подключения библиотек: prepare.php печатает <script> и
   заголовки уже нельзя будет отправить. */
if (!isset($_SESSION)) {
  session_start();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/install.php';
if (empty($_SESSION['is_authuser']) && !fo_has_superuser()) {
  header('Location: setup.php');
  exit;
}

/* #Display errors */
ini_set('display_errors','Off');

require 'system/core/library/gui.php';
require 'system/core/library/bd.php';
require 'system/core/library/auth.php';
require 'system/core/library/etc.php';
require 'system/core/library/filesystem.php';
require 'system/core/library/prepare.php';
require 'system/core/library/dock.php';
require 'system/core/library/etc/security.php';

if(!isset($_SESSION)){
  session_start();
}


$object = new gui;
$infob = new info;
$hashfile = new fileaction;
$prepare = new prepare;
$Dock = new Dock;
$security = new security;
$auth = new AuthClassUser();

$auth->checkout();
$prepare->showversion();
$prepare->language();

$forestGuestMode = !(isset($_SESSION["is_authuser"]) && $_SESSION["is_authuser"] === true && !empty($_SESSION["loginuser"]));

if($forestGuestMode){
  $_SESSION["loginuser"] = getenv("FOREST_ADMIN_LOGIN") ?: "admin";
  $_SESSION["safemode"] = "false";
}

$prepare->start();
$prepare->wall();

if($forestGuestMode){
  $_SESSION["is_authuser"] = false;
}

?>
<body style="position:fixed; width:100%; height:100%; z-index:-2000; overflow-x:hidden; overflow-y:hidden; transition: all 0.5s ease; text-rendering: optimizeLegibility;" class="ui-body backgroundtheme" >
  <div id="background-container">
    <img id="background-wall" src="<?echo $mainwall?>" style="position: absolute; z-index: -99999; width:100%; height:100%; object-fit:cover; user-select:none;" draggable="false">
  </div>
<?

if(isset($_SESSION["is_authuser"]) && $_SESSION["is_authuser"] === true && !empty($_SESSION["loginuser"])){
$prepare->themeload();
$prepare->DisplaySettings();
$prepare->welcomescreen();
$prepare->topbar();
$infob->beacon();
?>
<div id="proceses">
  <?
  $prepare->hibernation();
  ?>
</div>
<div id="notifications" class="fo-notif-panel notificationhide" aria-label="<? echo $language[$_SESSION['locale'].'_notification_label']; ?>">
  <div class="fo-notif-head">
    <div class="fo-notif-head-title">
      <? echo $language[$_SESSION['locale'].'_notification_label']; ?>
      <span id="fo-notif-count" class="fo-notif-count fo-hidden">0</span>
    </div>
    <div class="fo-notif-head-actions">
      <button type="button" id="clearNotifications" class="fo-notif-clear" onclick="NotificationClear();"><? echo $language[$_SESSION['locale'].'_notification_clear']; ?></button>
      <button type="button" class="fo-notif-x" title="Закрыть" onclick="$('#notifications').removeClass('notificationshow');">&#215;</button>
    </div>
  </div>
  <div class="fo-notif-sub">
    <span id="fulltime"></span>
    <span class="fo-notif-date"><?php echo date('d.m.Y')?></span>
  </div>
  <div id="notification-container" class="fo-notif-list fo-scroll"></div>
  <div id="fo-notif-empty" class="fo-notif-empty">Пока нет уведомлений</div>
</div>

<div id="desktops">
  <div id="desktop-1" class="desktop" desktopid="1">
    <?
    $prepare->desktop("linkdiv");
    if(!$_SESSION['h_status']){
      $_SESSION['appid'] = -1;
    }else{
      $_SESSION['h_status'] = false;
    }
    ?>
  </div>
</div>

<div class="selectors-container">
  <div id="selector-1" desktop="1" class="selector ui-forest-blink"></div>
</div>

<?
  $Dock->CreateNewDock();
?>

<script>
var id = <? echo $_SESSION['appid'] = $_SESSION['appid'] + 1 ?>;
<?
require 'system/core/library/js/core-js.php';
?>
</script>
<script src="<?echo $hashfile->filehash('system/core/library/js/launcher.js')?>"></script>
<script src="<?echo $hashfile->filehash('system/core/library/js/nodes.js')?>"></script>
<script src="<?echo $hashfile->filehash('system/core/library/js/mobile.js')?>"></script>
<script src="<?echo $hashfile->filehash('system/core/library/js/shell-refresh.js')?>"></script>
</body>
</html>
<?
$_SESSION['appid']  = '<script>document.writeln(id)</script>';
$prepare->autorun();
}else{
  define('FO_LOGIN_INCLUDED', true);
  require 'login.php';
}
?>
