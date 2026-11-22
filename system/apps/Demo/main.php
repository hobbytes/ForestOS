<?
/* Forest OS — Demo app (catalog sample) */
$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'Demo';
$AppContainer->SecondNameInfo = 'Демо';
$AppContainer->VersionInfo = '1.0.0';
$AppContainer->AuthorInfo = 'Forest Media';
$AppContainer->LibraryArray = array();
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '488px';
$AppContainer->width = '560px';
$AppContainer->backgroundColor = 'var(--fo-win-body, #fbfbfd)';
$AppContainer->fontColor = 'var(--fo-text, #151a17)';
$AppContainer->customStyle = 'padding-top:0; max-width:100%;';
$AppContainer->isMobile = $mobile;
$AppContainer->StartContainer();
?>
<style>
#demo<?echo $AppID?>{ padding:22px; font:14px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color:#151a17; }
#demo<?echo $AppID?> h1{ margin:0 0 6px; font-size:26px; letter-spacing:-0.02em; }
#demo<?echo $AppID?> p{ color:#5f666e; margin:0 0 16px; }
#demo<?echo $AppID?> .clock{ font-size:44px; font-weight:700; font-variant-numeric:tabular-nums; letter-spacing:-0.02em; }
#demo<?echo $AppID?> .date{ color:#7d848c; margin-bottom:18px; }
#demo<?echo $AppID?> .row{ display:flex; gap:8px; flex-wrap:wrap; }
#demo<?echo $AppID?> .pill{ padding:6px 12px; border-radius:999px; background:color-mix(in srgb, var(--fo-accent,#5fe08c) 18%, #fff); color:#1c4630; font-weight:600; font-size:12px; }
</style>
<div id="demo<?echo $AppID?>">
  <h1>Demo</h1>
  <p>Демонстрационное приложение из каталога Forest OS.</p>
  <div class="clock" id="demo-clock<?echo $AppID?>">--:--:--</div>
  <div class="date" id="demo-date<?echo $AppID?>"></div>
  <div class="row">
    <span class="pill">Установлено из AppsHouse</span>
    <span class="pill">PHP <?echo PHP_VERSION?></span>
  </div>
</div>
<script>
(function(){
  function pad(n){ return n < 10 ? '0' + n : '' + n; }
  function tick(){
    var d = new Date();
    var c = document.getElementById('demo-clock<?echo $AppID?>');
    var t = document.getElementById('demo-date<?echo $AppID?>');
    if(c){ c.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()); }
    if(t){ t.textContent = d.toLocaleDateString('ru-RU', {weekday:'long', day:'numeric', month:'long', year:'numeric'}); }
  }
  tick();
  setInterval(tick, 1000);
})();
</script>
<?
$AppContainer->EndContainer();
?>
