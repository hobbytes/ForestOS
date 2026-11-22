<?
/* Forest OS — Конвертер совместимости приложений */
if (!isset($_SESSION)) { session_start(); }
if (empty($_SESSION['fo_conv_csrf'])) { $_SESSION['fo_conv_csrf'] = bin2hex(random_bytes(16)); }

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

$DOC = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$APPS = $DOC . '/system/apps';

$apps = array();
foreach (glob($APPS . '/*/main.php') as $f) {
    $apps[] = basename(dirname($f));
}
sort($apps, SORT_NATURAL | SORT_FLAG_CASE);

$token = $_SESSION['fo_conv_csrf'];

require $DOC . '/system/core/library/Mercury/AppContainer.php';
$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'Converter';
$AppContainer->SecondNameInfo = 'Совместимость';
$AppContainer->VersionInfo = '1.1.0';
$AppContainer->AuthorInfo = 'Forest Media';
$AppContainer->LibraryArray = array();
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '488px';
$AppContainer->width = '820px';
$AppContainer->backgroundColor = 'var(--fo-win-body, #fbfbfd)';
$AppContainer->fontColor = 'var(--fo-text, #151a17)';
$AppContainer->customStyle = 'padding-top:0; max-width:100%; overflow:hidden;';
$AppContainer->isMobile = $mobile;
$AppContainer->StartContainer();
?>

<style>
#cv<?echo $AppID?>{ --cv-accent: var(--fo-accent, #5fe08c); height:100%; display:flex; flex-direction:column;
  font:13px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
  color:var(--fo-text, #151a17); background:var(--fo-win-body, #fbfbfd); }
#cv<?echo $AppID?> *{ box-sizing:border-box; }
.cv-head{ display:flex; gap:10px; align-items:center; padding:10px 14px; border-bottom:1px solid #e6e7ea; background:var(--fo-win-body, #fff); }
.cv-head b{ font-size:14px; }
.cv-head .cv-sub{ color:#8b929a; font-size:11.5px; }
.cv-body{ flex:1 1 auto; min-height:0; overflow:auto; padding:12px 14px; display:grid; grid-template-columns:200px 1fr; gap:12px; }
.cv-list{ display:flex; flex-direction:column; gap:4px; max-height:100%; overflow:auto; }
.cv-app{ text-align:left; border:1px solid transparent; background:transparent; border-radius:8px; padding:8px 10px;
  font:600 12.5px/1.2 inherit; color:inherit; cursor:default; }
.cv-app:hover{ background:#f0f1f3; color:#151a17; }
.cv-app.is-on{ background:var(--cv-accent); color:#0b160f; }
.cv-panel{ min-width:0; }
.cv-card{ border:1px solid #e6e7ea; border-radius:12px; background:var(--fo-win-body, #fff); padding:12px; margin-bottom:10px; }
.cv-row{ display:flex; align-items:center; gap:10px; padding:6px 0; border-bottom:1px solid #f2f3f5; }
.cv-row:last-child{ border-bottom:0; }
.cv-ok{ color:#1c8c52; font-weight:700; }
.cv-warn{ color:#a8332a; font-weight:700; }
.cv-btn{ height:32px; padding:0 14px; border:1px solid #d9dbe0; border-radius:8px; background:var(--fo-win-body, #fff);
  font:600 12px/1 inherit; color:inherit; cursor:default; }
.cv-btn:hover{ background:#f2f3f5; color:#151a17; }
.cv-btn.primary{ background:var(--cv-accent); border-color:transparent; color:#0b160f; }
.cv-note{ color:#8b929a; font-size:11.5px; }
.cv-diff{ font-family:ui-monospace, Consolas, monospace; font-size:11px; color:#5a6068; }
.cv-diff span{ display:block; }
.cv-msg{ border:1px solid color-mix(in srgb, var(--cv-accent) 40%, #e6e7ea); background:color-mix(in srgb, var(--cv-accent) 10%, var(--fo-win-body, #fff));
  border-radius:10px; padding:9px 12px; margin-bottom:10px; font-size:12.5px; }
@media (max-width:760px){ .cv-body{ grid-template-columns:1fr; } .cv-list{ flex-direction:row; overflow-x:auto; } }
</style>

<div id="cv<?echo $AppID?>" data-token="<?echo htmlspecialchars($token)?>">
  <div class="cv-head">
    <div><b>Конвертер совместимости</b><div class="cv-sub">старые приложения → токены темы и тёмный режим</div></div>
  </div>

  <div class="cv-body">
    <div class="cv-list">
      <? foreach ($apps as $a): ?>
        <button type="button" class="cv-app" data-app="<? echo htmlspecialchars($a) ?>"><? echo htmlspecialchars(str_replace('_', ' ', $a)) ?></button>
      <? endforeach; ?>
    </div>
    <div class="cv-panel" id="cv-panel<?echo $AppID?>">
      <div class="cv-card"><div class="cv-note">Выберите приложение слева — покажу, что с ним не так и что можно заменить.</div></div>
    </div>
  </div>
</div>

<script>
(function(){
  var id = '<?echo $AppID?>';
  var root = document.getElementById('cv' + id);
  var panel = document.getElementById('cv-panel' + id);
  var token = root.getAttribute('data-token');
  var current = '';

  function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g, function(c){
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }

  function api(action, app){
    var body = new URLSearchParams();
    body.append('action', action);
    body.append('app', app);
    body.append('token', token);
    return fetch('system/core/functions/convert.php', { method:'POST', credentials:'same-origin', body: body })
      .then(function(r){ return r.json(); });
  }

  function render(d, msg){
    if(!d || !d.ok){ panel.innerHTML = '<div class="cv-card"><div class="cv-note">Ошибка: ' + esc(d && d.error) + '</div></div>'; return; }
    var r = d.report || {};
    var plan = d.plan || [];
    var html = '';
    if(msg){ html += '<div class="cv-msg">' + esc(msg) + '</div>'; }
    html += '<div class="cv-card">' +
      '<div style="font-weight:700;margin-bottom:8px">' + esc(current.replace(/_/g, ' ')) + '</div>' +
      '<div class="cv-row"><span>AppContainer и Start/EndContainer</span><span style="margin-left:auto" class="' + ((r.container && r.start && r.end) ? 'cv-ok' : 'cv-warn') + '">' + ((r.container && r.start && r.end) ? 'ok' : 'проверить') + '</span></div>' +
      '<div class="cv-row"><span>$Folder и $AppID</span><span style="margin-left:auto" class="' + (r.vars ? 'cv-ok' : 'cv-warn') + '">' + (r.vars ? 'ok' : 'нет') + '</span></div>' +
      '<div class="cv-row"><span>Mercury AppGUI</span><span style="margin-left:auto" class="cv-ok">' + (r.appgui ? 'используется' : '—') + '</span></div>' +
      '<div class="cv-row"><span>Хардкод цветов</span><span style="margin-left:auto" class="' + (r.colors ? 'cv-warn' : 'cv-ok') + '">' + (r.colors || 0) + '</span></div>' +
      '<div class="cv-row"><span>Размер</span><span style="margin-left:auto">' + Math.round((r.size || 0) / 102.4) / 10 + ' КБ</span></div>' +
    '</div>';

    html += '<div class="cv-card"><div style="font-weight:700;margin-bottom:6px">Что изменится</div>';
    if(!plan.length){
      html += '<div class="cv-note">Менять нечего — приложение уже на токенах или не использует цвета.</div>';
    } else {
      html += '<div class="cv-diff">';
      plan.forEach(function(p){ html += '<span>• ' + esc(p.label) + ' — ' + p.count + ' шт.</span>'; });
      html += '</div>';
      html += '<div class="cv-note" style="margin-top:8px">Перед записью создаётся резервная копия <code>main.php.bak-…</code>. Меняются только цвета оформления, логика не трогается.</div>';
      html += '<div style="margin-top:10px;display:flex;gap:8px">' +
        '<button type="button" class="cv-btn primary" data-cv="convert">Сконвертировать</button>' +
        '<button type="button" class="cv-btn" data-cv="analyze">Проверить ещё раз</button></div>';
    }
    html += '</div>';
    panel.innerHTML = html;

    panel.querySelectorAll('[data-cv]').forEach(function(b){
      b.addEventListener('click', function(){ run(b.getAttribute('data-cv'), current); });
    });
  }

  function run(action, app){
    api(action, app).then(function(d){
      if(action === 'convert' && d && d.ok){
        render(d, d.msg || (d.changed ? 'Готово' : 'Менять нечего'));
      } else {
        render(d, '');
      }
    }).catch(function(){ panel.innerHTML = '<div class="cv-card"><div class="cv-note">Не удалось связаться с сервером</div></div>'; });
  }

  root.querySelectorAll('.cv-app').forEach(function(b){
    b.addEventListener('click', function(){
      root.querySelectorAll('.cv-app').forEach(function(x){ x.classList.toggle('is-on', x === b); });
      current = b.getAttribute('data-app');
      run('analyze', current);
    });
  });
})();
</script>

<?
$AppContainer->EndContainer();
?>
