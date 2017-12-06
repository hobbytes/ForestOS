<?
$osinfo = parse_ini_file('system/core/osinfo.foc', false);
$os_version = $osinfo['codename'].' '.$osinfo['subversion']."\n";
?>
<!--
   ____                 __    ____  ____
  / __/__  _______ ___ / /_  / __ \/ __/
 / _// _ \/ __/ -_|_-</ __/ / /_/ /\ \
/_/  \___/_/  \__/___/\__/  \____/___/
          <?echo $os_version?>
-->
<?
require 'system/core/library/gui.php';
require 'system/core/library/bd.php';
require 'system/core/library/auth.php';
require 'system/core/library/etc.php';
require 'system/core/library/filesystem.php';
require 'system/core/library/prepare.php';
require 'system/core/library/etc/security.php';
session_start();
$object = new gui;
$infob = new info;
$hashfile = new fileaction;
$prepare = new prepare;
$security = new security;
$auth = new AuthClassUser();
$auth->checkout();
$prepare->language();
$prepare->start();
$prepare->wall();
$prepare->themeload();
?>
<body class="ui-body backgroundtheme" style="position:relative; z-index:-2000; overflow-x:hidden; overflow-y:hidden;">
<div id="background-container"><img id="background-wall" src="<?echo $mainwall?>" style="position: absolute; z-index: -99999; width:100%; height:100%; object-fit:cover;"></div>
<?
if(isset($_SESSION['loginuser'])){
$prepare->welcomescreen();
$prepare->topbar();
?>
<div id="desktop">
<?
$prepare->desktop("linkdiv");
$_SESSION['appid']=-1;
?>
<div id="notifications" class="notificationhide" style="display:block; position:absolute; right: 0; height: 100%; padding: 10px; transition:all 0.2s ease;">
</div>
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
include 'login.php';
}
?>
