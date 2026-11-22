<?

/* Update Tool — проверка и установка обновлений Forest OS из GitHub Releases */

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;

$AppContainer->AppNameInfo = 'Update Tool';
$AppContainer->SecondNameInfo = 'Обновление системы';
$AppContainer->VersionInfo = '2.0.0';
$AppContainer->AuthorInfo = 'Forest Media';

$AppContainer->LibraryArray = Array('filesystem','gui');

$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '100%';
$AppContainer->width = '100%';
$AppContainer->customStyle = 'padding:0; overflow:auto;';
$AppContainer->StartContainer();

require_once $_SERVER['DOCUMENT_ROOT'].'/system/core/library/osupdate.php';

if (empty($_SESSION['fo_update_csrf'])) {
  $_SESSION['fo_update_csrf'] = bin2hex(random_bytes(16));
}
$TOKEN = (string)$_SESSION['fo_update_csrf'];

$up = new OsUpdate();
$cur = $up->current();
$isSuper = ((string)($_SESSION['superuser'] ?? '') === (string)$_SESSION['loginuser']);
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
  align-self:flex-start; padding:5px 11px; border-radius:999px;
  font-size:11.5px; font-weight:700;
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
.upd-notes{
  white-space:pre-wrap; font-size:12.5px; line-height:1.5; opacity:.86;
  max-height:150px; overflow:auto;
}
.upd-actions{ display:flex; gap:10px; flex-wrap:wrap; margin-top:auto; }
.upd-btn{
  height:38px; padding:0 18px; border-radius:10px; border:0; cursor:pointer;
  font:600 13px/1 inherit; background:var(--fo-accent, #5fe08c); color:#0b160f;
}
.upd-btn.ghost{
  background:transparent; color:var(--fo-text, #151a17);
  border:1px solid color-mix(in srgb, var(--fo-text, #151a17) 18%, transparent);
}
.upd-btn:disabled{ opacity:.55; cursor:default; }
.upd-note{ font-size:11.5px; opacity:.55; }
</style>

<div class="upd-wrap">
  <div class="upd-head">
    <div class="upd-logo">↑</div>
    <div>
      <div class="upd-title">Обновление системы</div>
      <div class="upd-sub">Forest OS <? echo htmlspecialchars($cur['subversion']) ?> · <? echo htmlspecialchars($cur['codename']) ?></div>
    </div>
  </div>

  <div class="upd-status upd-status-idle" id="upd-status<?echo $AppID?>">Проверяем обновления…</div>

  <div class="upd-card">
    <div class="upd-row"><span>Текущая версия</span><b><? echo htmlspecialchars($cur['version']) ?></b></div>
    <div class="upd-row"><span>Выпуск</span><b><? echo htmlspecialchars($cur['subversion']) ?></b></div>
    <div class="upd-row"><span>Сборка</span><b><? echo htmlspecialchars($cur['revision']) ?></b></div>
    <div class="upd-row"><span>Кодовое имя</span><b><? echo htmlspecialchars($cur['codename']) ?></b></div>
    <div class="upd-row"><span>Источник</span><b id="upd-feed<?echo $AppID?>" style="font-weight:500;font-size:11.5px;opacity:.8;word-break:break-all;text-align:right"></b></div>
  </div>

  <div class="upd-card" id="upd-new<?echo $AppID?>" style="display:none">
    <div class="upd-row"><span>Доступно</span><b id="upd-latest<?echo $AppID?>"></b></div>
    <div class="upd-row"><span>Опубликовано</span><b id="upd-date<?echo $AppID?>"></b></div>
    <div class="upd-notes" id="upd-notes<?echo $AppID?>"></div>
  </div>

  <div class="upd-actions">
    <button type="button" class="upd-btn ghost" id="upd-check<?echo $AppID?>">Проверить обновления</button>
    <? if ($isSuper): ?>
      <button type="button" class="upd-btn" id="upd-install<?echo $AppID?>" style="display:none">Установить</button>
    <? endif; ?>
  </div>

  <div class="upd-note">
    Обновления собираются автоматически при публикации релиза в GitHub
    (тег <b>vX.Y</b> → zip + update.json). Пользовательские файлы, настройки и база
    при обновлении не затрагиваются.
  </div>
</div>

<script>
(function(){
  var API = 'system/core/functions/osupdate.php';
  var TOKEN = <? echo json_encode($TOKEN); ?>;
  var el = function(id){ return document.getElementById(id + '<?echo $AppID?>'); };
  var state = { latest: null };

  function setStatus(text, cls){
    var s = el('upd-status');
    if(!s) return;
    s.textContent = text;
    s.className = 'upd-status ' + (cls || 'upd-status-idle');
  }

  function bytes(n){
    n = Number(n || 0);
    if(n >= 1048576) return (n/1048576).toFixed(1) + ' МБ';
    if(n >= 1024) return Math.round(n/1024) + ' КБ';
    return n + ' Б';
  }

  function check(){
    var btn = el('upd-check');
    if(btn) btn.disabled = true;
    setStatus('Проверяем обновления…', 'upd-status-idle');
    fetch(API + '?action=check', {credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if(btn) btn.disabled = false;
        var feed = el('upd-feed');
        if(feed && d.feed) feed.textContent = d.feed;
        if(!d.ok){
          setStatus(d.error === 'feed_unavailable' ? 'Источник обновлений недоступен' : 'Не удалось проверить обновления', 'upd-status-off');
          return;
        }
        if(!d.has_update){
          setStatus('Установлена последняя версия', 'upd-status-ok');
          el('upd-new').style.display = 'none';
          return;
        }
        state.latest = d.latest;
        setStatus('Доступно обновление ' + d.latest.version, 'upd-status-new');
        el('upd-latest').textContent = d.latest.name + ' · ' + bytes(d.latest.size);
        el('upd-date').textContent = d.latest.published_at || '—';
        el('upd-notes').textContent = d.latest.notes || 'Без описания';
        el('upd-new').style.display = 'flex';
        var ib = el('upd-install');
        if(ib) ib.style.display = 'inline-flex';
      })
      .catch(function(){
        if(btn) btn.disabled = false;
        setStatus('Не удалось проверить обновления', 'upd-status-off');
      });
  }

  function install(){
    var btn = el('upd-install');
    if(!btn) return;
    if(!confirm('Установить обновление? Текущая версия будет заменена, пользовательские файлы останутся.')) return;
    btn.disabled = true;
    btn.textContent = 'Установка…';
    setStatus('Скачиваем и распаковываем…', 'upd-status-idle');
    var body = new URLSearchParams();
    body.append('action', 'install');
    body.append('token', TOKEN);
    fetch(API, {method:'POST', credentials:'same-origin', body: body})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if(d.ok){
          setStatus('Обновление установлено: ' + d.installed, 'upd-status-ok');
          btn.textContent = 'Готово';
          if(window.FO_TOAST) FO_TOAST('Обновление установлено: ' + d.installed);
          setTimeout(function(){ location.reload(); }, 1200);
        } else {
          btn.disabled = false;
          btn.textContent = 'Установить';
          setStatus('Не удалось установить: ' + (d.error || 'ошибка'), 'upd-status-off');
        }
      })
      .catch(function(){
        btn.disabled = false;
        btn.textContent = 'Установить';
        setStatus('Ошибка сети при установке', 'upd-status-off');
      });
  }

  fetch(API + '?action=status', {credentials:'same-origin'})
    .then(function(r){ return r.json(); })
    .then(function(d){
      var feed = el('upd-feed');
      if(feed && d.feed) feed.textContent = d.feed;
    })
    .catch(function(){});

  var cb = el('upd-check'); if(cb) cb.addEventListener('click', check);
  var ib = el('upd-install'); if(ib) ib.addEventListener('click', install);

  setTimeout(check, 250);
})();
</script>

<?
$AppContainer->EndContainer();
?>
