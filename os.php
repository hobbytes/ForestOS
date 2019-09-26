<?


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
$prepare->start();
$prepare->wall();

?>
<body style="position:fixed; width:100%; height:100%; z-index:-2000; overflow-x:hidden; overflow-y:hidden; transition: all 0.5s ease; text-rendering: optimizeLegibility;" class="ui-body backgroundtheme" >
  <div id="background-container">
    <img id="background-wall" src="<?echo $mainwall?>" style="position: absolute; z-index: -99999; width:100%; height:100%; object-fit:cover; user-select:none;" draggable="false">
  </div>
<?

if(isset($_SESSION['loginuser'])){
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
<div id="notifications" class="notificationhide" style="display:block; position:absolute; right: 0; height: 100%; padding: 10px; transition:all 0.2s ease; z-index:2;">
  <div id="notificationTopLabel" style="width:310px; color:#fff; font-size:20pt; text-align:center; user-select: none; cursor: default; display:none; padding: 10 0; background: rgba(160,160,160,0.9);">
    <div id="fulltime" style="font-size:20pt;">
    </div>
    <div class="date_" style="font-size:10pt;">
      <?php echo date('d.m.Y')?>
    </div>
    <div style="font-weight:600; padding: 10 0; text-transform: uppercase; font-size: 18pt;">
      <? echo $language[$_SESSION['locale'].'_notification_label']; ?>
    </div>
    <div id="clearNotifications" class="not-btn ui-forest-blink" onclick="NotificationClear();" style="font-size: 10pt; background: rgba(244,67,54,0.61); width:fit-content; padding:7px; margin:20 0; float:right; color:#fff; cursor:default; user-select: none;">
      <? echo $language[$_SESSION['locale'].'_notification_clear']; ?>
    </div>
  </div>
  <div id="notification-container">
  </div>
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
</body>
</html>
<?
$_SESSION['appid']  = '<script>document.writeln(id)</script>';
$prepare->autorun();
}else{
  require 'login.php';
}
?>
