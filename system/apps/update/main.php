<?

/* Update Tool — проверка и установка обновлений Forest OS */

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;

$AppContainer->AppNameInfo = 'Update Tool';
$AppContainer->SecondNameInfo = 'Обновление системы';
$AppContainer->VersionInfo = '1.1.0';
$AppContainer->AuthorInfo = 'Forest Media';

$AppContainer->LibraryArray = Array('filesystem','gui');

$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '100%';
$AppContainer->width = '100%';
$AppContainer->customStyle = 'padding:0; overflow:auto;';
$AppContainer->StartContainer();

$gui = new gui;
$fo = new filecalc;
$Folder = $_GET['destination'];
$updatefile = isset($_GET['updatefile']) ? $_GET['updatefile'] : '';

$update_lang = parse_ini_file('app.lang');
$cl = $_SESSION['locale'];

/* ---------- текущая версия ---------- */
$current = @parse_ini_file('../../core/osinfo.foc');
if (!is_array($current)) { $current = array(); }
$curVersion = (string)($current['version'] ?? '—');
$curSub = (string)($current['subversion'] ?? '—');
$curBuild = (string)($current['revision'] ?? '—');
$curCode = (string)($current['codename'] ?? '—');

/* ---------- источник обновлений ---------- */
$urlu = 'http://forest.hobbytes.com/media/os/update.php';
$ctx = stream_context_create(array('http' => array('timeout' => 4, 'ignore_errors' => true)));
$fileu = @file_get_contents($urlu, false, $ctx);
$arrayu = $fileu ? json_decode($fileu, TRUE) : NULL;

$remote = NULL;
if (!empty($arrayu) && is_array($arrayu)) {
  foreach ($arrayu as $key) {
    $remote = array(
      'file' => (string)($key['file'] ?? ''),
      'version' => (string)($key['version'] ?? ''),
      'subversion' => (string)($key['subversion'] ?? ''),
      'size' => (int)($key['size'] ?? 0),
      'codename' => (string)($key['codename'] ?? ''),
    );
  }
}

/* локальный пакет обновления (zip + ini рядом с приложением) */
$customfile = NULL;
foreach (glob('*.zip') as $filename) { $customfile = $filename; }
if (!empty($customfile)) {
  $fwInfo = @parse_ini_file(str_replace('.zip', '.ini', $customfile));
  $remote = array(
    'file' => str_replace('.zip', '', $customfile),
    'version' => (string)($fwInfo['version'] ?? ''),
    'subversion' => (string)($fwInfo['subversion'] ?? ''),
    'size' => (int)@filesize($customfile),
    'codename' => (string)($fwInfo['codename'] ?? ''),
  );
}

$hasUpdate = $remote !== NULL && $remote['subversion'] !== '' && $remote['subversion'] !== $curSub;
$isSuper = ($_SESSION['superuser'] == $_SESSION['loginuser']);
$statusText = 'Обновления не проверялись';
$statusClass = 'upd-status-idle';
if ($remote !== NULL) {
  if ($hasUpdate) { $statusText = 'Доступно обновление'; $statusClass = 'upd-status-new'; }
  else { $statusText = 'Установлена последняя версия'; $statusClass = 'upd-status-ok'; }
} else {
  $statusText = 'Сервер обновлений недоступен';
  $statusClass = 'upd-status-off';
}

/* ---------- установка (сохранён прежний механизм) ---------- */
if (!empty($updatefile) && $isSuper) {
  if (!is_dir('./temp/')) { @mkdir('./temp/'); }
  $filename = './temp/' . $updatefile;
  $ch = curl_init('http://forest.hobbytes.com/media/os/updates/' . $updatefile . '.zip');
  $fp = @fopen($filename . '.zip', 'wb');
  if ($fp) {
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    @curl_exec($ch);
    curl_close($ch);
    fclose($fp);
  }
  $zip = new ZipArchive;
  if (@$zip->open($filename . '.zip') === TRUE) {
    $zip->extractTo('../../../');
    $zip->close();
    $content = "[ForestOS]\nversion='{$remote['version']}'\nsubversion='{$remote['subversion']}'\nrevision='{$remote['file']}'\ncodename='{$remote['codename']}'";
    @file_put_contents('../../core/osinfo.foc', $content);
    @unlink($filename . '.zip');
    $gui->newnotification($AppName, 'Обновление системы', 'Установлено обновление <b>' . htmlspecialchars($updatefile) . '</b>');
    $statusText = 'Обновление установлено';
    $statusClass = 'upd-status-ok';
  } else {
    $statusText = 'Не удалось установить обновление';
    $statusClass = 'upd-status-off';
  }
}
?>

<style>
.upd-wrap{
  height:100%; box-sizing:border-box; padding:18px 20px 22px;
  display:flex; flex-direction:column; gap:14px;
  font:13px/1.45 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
  color:var(--fo-text, #151a17); background:var(--fo-win-body, #fbfbfd);
}
.upd-head{ display:flex; align-items:center; gap:12px; }
.upd-logo{
  width:46px; height:46px; border-radius:13px; flex:0 0 46px;
  background:linear-gradient(160deg, var(--fo-accent, #5fe08c), color-mix(in srgb, var(--fo-accent, #5fe08c) 35%, #101613));
  display:inline-flex; align-items:center; justify-content:center;
  font-weight:800; font-size:20px; color:#0b160f;
}
.upd-title{ font-size:15px; font-weight:700; }
.upd-sub{ font-size:12px; opacity:.65; margin-top:2px; }
.upd-status{
  align-self:flex-start; margin-top:2px; padding:5px 11px; border-radius:999px;
  font-size:11.5px; font-weight:700; letter-spacing:.01em;
}
.upd-status-idle{ background:color-mix(in srgb, var(--fo-text, #151a17) 8%, transparent); }
.upd-status-new{ background:color-mix(in srgb, var(--fo-accent) 26%, transparent); color:#0b160f; }
.upd-status-ok{ background:color-mix(in srgb, #2f9e4f 22%, transparent); color:#1d6b35; }
.upd-status-off{ background:color-mix(in srgb, #d05858 18%, transparent); color:#a63a3a; }
.upd-card{
  border:1px solid color-mix(in srgb, var(--fo-text, #151a17) 10%, transparent);
  border-radius:12px; padding:14px 16px; display:flex; flex-direction:column; gap:8px;
  background:color-mix(in srgb, var(--fo-text, #151a17) 3%, transparent);
}
.upd-row{ display:flex; justify-content:space-between; gap:16px; font-size:12.5px; }
.upd-row span:first-child{ opacity:.6; }
.upd-row b{ font-weight:600; }
.upd-actions{ display:flex; gap:10px; flex-wrap:wrap; margin-top:auto; }
.upd-btn{
  height:38px; padding:0 18px; border-radius:10px; border:0; cursor:pointer;
  font:600 13px/1 inherit; background:var(--fo-accent, #5fe08c); color:#0b160f;
}
.upd-btn.ghost{
  background:transparent; color:var(--fo-text, #151a17);
  border:1px solid color-mix(in srgb, var(--fo-text, #151a17) 18%, transparent);
}
.upd-btn:active{ transform:scale(.98); }
.upd-note{ font-size:11.5px; opacity:.55; }
</style>

<div class="upd-wrap">
  <div class="upd-head">
    <div class="upd-logo">↑</div>
    <div>
      <div class="upd-title">Обновление системы</div>
      <div class="upd-sub">Forest OS <? echo htmlspecialchars($curSub) ?> · <? echo htmlspecialchars($curCode) ?></div>
    </div>
  </div>

  <div class="upd-status <? echo $statusClass ?>"><? echo htmlspecialchars($statusText) ?></div>

  <div class="upd-card">
    <div class="upd-row"><span>Текущая версия</span><b><? echo htmlspecialchars($curVersion) ?></b></div>
    <div class="upd-row"><span>Выпуск</span><b><? echo htmlspecialchars($curSub) ?></b></div>
    <div class="upd-row"><span>Сборка</span><b><? echo htmlspecialchars($curBuild) ?></b></div>
    <div class="upd-row"><span>Кодовое имя</span><b><? echo htmlspecialchars($curCode) ?></b></div>
    <? if ($remote !== NULL): ?>
      <div class="upd-row"><span>Доступно</span><b><? echo htmlspecialchars($remote['subversion'] !== '' ? $remote['subversion'] : '—') ?></b></div>
      <div class="upd-row"><span>Размер пакета</span><b><? echo $remote['size'] > 0 ? round($remote['size'] / 1024) . ' КБ' : '—' ?></b></div>
    <? endif; ?>
  </div>

  <div class="upd-actions">
    <? if ($isSuper && $hasUpdate): ?>
      <button type="button" class="upd-btn" onclick="updatenow<?echo $AppID;?>(null);">Обновить до <? echo htmlspecialchars($remote['subversion']) ?></button>
    <? endif; ?>
    <button type="button" class="upd-btn ghost" onclick="location.reload();">Проверить снова</button>
  </div>

  <div class="upd-note">
    <? if ($remote === NULL): ?>
      Источник обновлений (forest.hobbytes.com) сейчас недоступен — проверьте подключение или
      положите пакет <b>.zip</b> с описанием <b>.ini</b> в папку приложения.
    <? else: ?>
      Обновление устанавливается поверх текущей копии. Перед установкой сделайте резервную копию.
    <? endif; ?>
  </div>
</div>

<script>
function updatenow<?echo $AppID;?>(el){
  var base = '<? echo $Folder ?>main.php?appname=<? echo $AppName ?>&appid=<? echo $AppID ?>&destination=<? echo $Folder ?>';
  <? if ($hasUpdate): ?>
  window.location.href = base + '&updatefile=<? echo rawurlencode($remote['file']) ?>';
  <? endif; ?>
}
</script>

<?
$AppContainer->EndContainer();
?>
