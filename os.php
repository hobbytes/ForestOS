<?

/* #Display errors */
ini_set('display_errors','Off');
/* error_reporting(E_ALL); */

require 'system/core/library/gui.php';
require 'system/core/library/bd.php';
require 'system/core/library/auth.php';
require 'system/core/library/etc.php';
require 'system/core/library/filesystem.php';
require 'system/core/library/prepare.php';
require 'system/core/library/etc/security.php';

if(!isset($_SESSION)){
  session_start();
}

$object = new gui;
$infob = new info;
$hashfile = new fileaction;
$prepare = new prepare;
$security = new security;
$auth = new AuthClassUser();

$auth->checkout();
$prepare->showversion();
$prepare->language();
$prepare->start();
$prepare->wall();

?>
<body class="ui-body backgroundtheme" style="position:relative; z-index:-2000; overflow-x:hidden; overflow-y:hidden; transition: all 0.5s ease;">
<div id="background-container"><img id="background-wall" src="<?echo $mainwall?>" style="position: absolute; z-index: -99999; width:100%; height:100%; object-fit:cover; user-select:none;"></div>
<?
if(isset($_SESSION['loginuser'])){
$prepare->themeload();
$prepare->DisplaySettings();
$prepare->welcomescreen();
$prepare->topbar();
$prepare->beacon();
?>
<div id="desktops">
<div id="desktop-1" class="desktop">
<?
$prepare->desktop("linkdiv");
$_SESSION['appid']=-1;
?>
<div id="notifications" class="notificationhide" style="display:block; position:absolute; right: 0; height: 100%; padding: 10px; transition:all 0.2s ease; z-index:2;">
<div id="notificationTopLabel" style="width:310px; color:#fff; font-size:20pt; text-align:center; user-select: none; cursor: default; display:none; padding: 10 0; background: rgba(119,119,119,0.5);">
  <div id="fulltime" style="font-size:20pt;">
  </div>
  <div class="date_" style="font-size:10pt;">
    <?php echo date('d.m.Y')?>
  </div>
  <div style="font-weight:600; padding: 10 0; text-transform: uppercase; font-size: 18pt;">
    <?
    echo $language[$_SESSION['locale'].'_notification_label'];
    ?>
  </div>
  <div id="clearNotifications" class="ui-forest-blink" onclick="NotificationClear();" style="font-size: 10pt; background: rgba(244,67,54,0.61); width:fit-content; padding:7px; margin:20 0; float:right; color:#fff; cursor:default; user-select: none;">
    <?
    echo $language[$_SESSION['locale'].'_notification_clear'];
    ?>
  </div>
</div>
<div id="notification-container">
</div>
</div>
</div>
</div>
<div class="selectors-container">
  <div id="selector-1" desktop="1" class="selector ui-forest-blink"></div>
</div>
<div id="proceses">
  <script>
  var id=<?echo $_SESSION['appid']=$_SESSION['appid']+1?>;
  <?
  require 'system/core/library/js/core-js.php';
  ?>
  </script>
  <?
  $prepare->hibernation();
  ?>
</div>
</body>
</html>
<?
$_SESSION['appid']  = '<script>document.writeln(id)</script>';
$prepare->autorun();
}else{
require 'login.php';
}
?>
