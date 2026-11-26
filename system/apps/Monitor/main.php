<?php
/**
 * Forest OS — системный монитор.
 *
 * Живые показатели сервера: загрузка, память, диск, аптайм, версии, число
 * приложений и активных сессий. Данные отдаёт stats.php, обновление — раз в 3 с.
 */

if (!isset($_SESSION)) {
    session_start();
}

$AppName = (string)($_GET['appname'] ?? 'Monitor');
$AppID = (string)($_GET['appid'] ?? '1');
$Folder = (string)($_GET['destination'] ?? 'system/apps/Monitor/');

require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'Monitor';
$AppContainer->SecondNameInfo = 'System Monitor';
$AppContainer->VersionInfo = '1.0';
$AppContainer->AuthorInfo = 'Forest OS';
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('gui');
$AppContainer->height = '100%';
$AppContainer->width = '100%';
$AppContainer->customStyle = 'min-width:300px; min-height:220px; overflow:hidden;';
$AppContainer->StartContainer();

$loc = (string)($_SESSION['locale'] ?? 'ru');
if (!in_array($loc, array('ru', 'en'), true)) { $loc = 'ru'; }
$lang = array();
$ini = @parse_ini_file(__DIR__ . '/lang/' . $loc . '.lang', true);
if (is_array($ini) && isset($ini['Monitor'])) {
    $lang = $ini['Monitor'];
}
function m_t(string $key, string $fallback): string
{
    global $lang;
    return isset($lang[$key]) && $lang[$key] !== '' ? (string)$lang[$key] : $fallback;
}
?>
<style>
.mon-wrap{
  display:flex; flex-direction:column; height:100%; box-sizing:border-box;
  padding:14px; gap:12px; overflow:auto;
  background:var(--fo-win-body,#fff); color:var(--fo-text,#111);
  font:13px/1.45 var(--fo-font, system-ui, sans-serif);
}
.mon-head{ display:flex; align-items:center; gap:10px; }
.mon-title{ font-weight:700; font-size:15px; letter-spacing:.2px; }
.mon-sub{ opacity:.6; font-size:11.5px; margin-left:auto; }
.mon-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:10px; }
.mon-card{
  border:1px solid color-mix(in srgb, var(--fo-text,#111) 14%, transparent);
  border-radius:10px; padding:10px 12px; min-height:74px;
  display:flex; flex-direction:column; gap:4px; justify-content:center;
}
.mon-k{ font-size:10.5px; letter-spacing:.6px; text-transform:uppercase; opacity:.55; }
.mon-v{ font-size:20px; font-weight:700; line-height:1.1; }
.mon-v small{ font-size:11.5px; font-weight:500; opacity:.6; }
.mon-bar{ height:6px; border-radius:99px; background:color-mix(in srgb, var(--fo-text,#111) 12%, transparent); overflow:hidden; margin-top:4px; }
.mon-bar i{ display:block; height:100%; width:0; border-radius:99px; background:var(--fo-accent,#3b82f6); transition:width .35s ease; }
.mon-foot{ margin-top:auto; display:flex; align-items:center; gap:10px; font-size:11.5px; opacity:.65; }
.mon-dot{ width:7px; height:7px; border-radius:50%; background:var(--fo-ok,#22c55e); }
.mon-err{ color:var(--fo-danger,#e5484d); }
</style>

<div class="mon-wrap" id="mon<?echo $AppID?>">
  <div class="mon-head">
    <span class="mon-title"><?echo htmlspecialchars(m_t('title', 'Системный монитор'))?></span>
    <span class="mon-sub" data-mon="updated">—</span>
  </div>

  <div class="mon-grid">
    <div class="mon-card">
      <span class="mon-k"><?echo htmlspecialchars(m_t('load', 'Загрузка CPU'))?></span>
      <span class="mon-v" data-mon="load">—</span>
      <span class="mon-k" data-mon="load15" style="text-transform:none;letter-spacing:0"></span>
    </div>
    <div class="mon-card">
      <span class="mon-k"><?echo htmlspecialchars(m_t('memory', 'Память'))?></span>
      <span class="mon-v" data-mon="mem">—</span>
      <div class="mon-bar"><i data-mon="membar"></i></div>
    </div>
    <div class="mon-card">
      <span class="mon-k"><?echo htmlspecialchars(m_t('disk', 'Диск'))?></span>
      <span class="mon-v" data-mon="disk">—</span>
      <div class="mon-bar"><i data-mon="diskbar"></i></div>
    </div>
    <div class="mon-card">
      <span class="mon-k"><?echo htmlspecialchars(m_t('uptime', 'Аптайм'))?></span>
      <span class="mon-v" data-mon="uptime">—</span>
    </div>
    <div class="mon-card">
      <span class="mon-k"><?echo htmlspecialchars(m_t('apps', 'Приложения'))?></span>
      <span class="mon-v" data-mon="apps">—</span>
    </div>
    <div class="mon-card">
      <span class="mon-k"><?echo htmlspecialchars(m_t('sessions', 'Активные сессии'))?></span>
      <span class="mon-v" data-mon="sessions">—</span>
    </div>
    <div class="mon-card">
      <span class="mon-k">PHP</span>
      <span class="mon-v" data-mon="php" style="font-size:15px">—</span>
    </div>
    <div class="mon-card">
      <span class="mon-k"><?echo htmlspecialchars(m_t('server', 'Сервер'))?></span>
      <span class="mon-v" data-mon="server" style="font-size:12px;font-weight:500">—</span>
    </div>
  </div>

  <div class="mon-foot">
    <span class="mon-dot" data-mon="dot"></span>
    <span data-mon="status"><?echo htmlspecialchars(m_t('connecting', 'Подключаемся…'))?></span>
  </div>
</div>

<script>
(function(){
  var root = document.getElementById('mon<?echo $AppID?>');
  if (!root) return;
  var API = '<?echo $Folder?>stats.php';
  var every = 3000, timer = null;
  var I18N = <? echo json_encode(array(
      'updated' => m_t('updated', 'обновлено'),
      'error' => m_t('error', 'нет связи с сервером'),
      'online' => m_t('online', 'сервер отвечает'),
      'of' => m_t('of', 'из'),
      'day' => m_t('day', 'д'),
      'hour' => m_t('hour', 'ч'),
      'min' => m_t('min', 'мин'),
  ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;

  function $(k){ return root.querySelector('[data-mon="' + k + '"]'); }
  function set(k, v){ var e = $(k); if (e) e.textContent = v; }
  function bar(k, pct){ var e = $(k); if (e) e.style.width = Math.max(0, Math.min(100, pct)) + '%'; }
  function bytes(n){
    if (!n && n !== 0) return '—';
    var u = ['B','KB','MB','GB','TB'], i = 0;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return (n >= 10 ? Math.round(n) : n.toFixed(1)) + ' ' + u[i];
  }
  function dur(s){
    s = Math.max(0, Math.round(s));
    var d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
    var out = [];
    if (d) out.push(d + ' ' + I18N.day);
    if (h) out.push(h + ' ' + I18N.hour);
    if (m || !out.length) out.push(m + ' ' + I18N.min);
    return out.join(' ');
  }
  function paint(d){
    set('load', d.load.map(function(x){ return x.toFixed(2); }).join('  '));
    set('load15', I18N.updated + ' 1 / 5 / 15');
    set('mem', bytes(d.mem_used) + ' / ' + bytes(d.mem_total));
    bar('membar', d.mem_total ? d.mem_used / d.mem_total * 100 : 0);
    set('disk', bytes(d.disk_used) + ' / ' + bytes(d.disk_total));
    bar('diskbar', d.disk_total ? d.disk_used / d.disk_total * 100 : 0);
    set('uptime', dur(d.uptime));
    set('apps', d.apps);
    set('sessions', d.sessions);
    set('php', d.php);
    set('server', d.server);
    set('status', I18N.online);
    set('updated', I18N.updated + ' ' + new Date().toLocaleTimeString());
    var dot = $('dot'); if (dot) dot.style.background = 'var(--fo-ok,#22c55e)';
  }
  function fail(){
    set('status', I18N.error);
    var dot = $('dot'); if (dot) dot.style.background = 'var(--fo-danger,#e5484d)';
  }
  function tick(){
    fetch(API, {credentials:'same-origin', cache:'no-store'})
      .then(function(r){ if (r.status === 401) { window.location.href = '/?kicked=1'; throw new Error('auth'); } return r.json(); })
      .then(function(d){ if (!d || !d.ok) throw new Error('stats'); paint(d); })
      .catch(fail);
  }
  tick();
  timer = setInterval(tick, every);
  window.addEventListener('pagehide', function(){ clearInterval(timer); });
})();
</script>

<?
$AppContainer->EndContainer();
?>
