<?
/* Forest OS — AppsHouse 3.0 (store UI) */
if (!isset($_SESSION)) { session_start(); }
if (empty($_SESSION['fo_store_csrf'])) { $_SESSION['fo_store_csrf'] = bin2hex(random_bytes(16)); }

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'Apps House';
$AppContainer->SecondNameInfo = 'Магазин';
$AppContainer->VersionInfo = '3.0.0';
$AppContainer->AuthorInfo = 'Forest Media';
$AppContainer->LibraryArray = array();
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '488px';
$AppContainer->width = '820px';
$AppContainer->backgroundColor = '#fbfbfd';
$AppContainer->fontColor = '#151a17';
$AppContainer->customStyle = 'padding-top:0; max-width:100%; overflow:hidden;';
$AppContainer->isMobile = $mobile;
$AppContainer->StartContainer();
?>

<style>
/* ---------- AppsHouse 3.0 ------------------------------------------------ */
#st<?echo $AppID?>{
  --st-accent: var(--fo-accent, #5fe08c);
  position: relative; height: 100%; display: flex; flex-direction: column;
  font: 13px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
  color: #151a17; background: #fbfbfd;
}
#st<?echo $AppID?> *{ box-sizing: border-box; }
.st-head{ display:flex; align-items:center; gap:10px; padding:8px 12px; border-bottom:1px solid #e6e7ea; background:#fff; }
.st-tabs{ display:flex; gap:2px; }
.st-tab{ border:1px solid transparent; background:transparent; padding:6px 12px; border-radius:8px; cursor:default;
  font:600 12.5px/1 inherit; color:#5a6068; transition:background .12s,color .12s; }
.st-tab:hover{ background:#f0f1f3; }
.st-tab.is-on{ background:var(--st-accent); color:#0b160f; border-color:transparent; }
.st-hub{ margin-left:auto; display:flex; align-items:center; gap:6px; font-size:11px; color:#7a818a; }
.st-dot{ width:7px; height:7px; border-radius:50%; background:#c9ced6; }
.st-dot.ok{ background:var(--st-accent); box-shadow:0 0 0 3px color-mix(in srgb, var(--st-accent) 22%, transparent); }
.st-dot.bad{ background:#e2594b; }
.st-body{ flex:1 1 auto; min-height:0; overflow:auto; padding:12px; }
.st-panel{ display:none; }
.st-panel.is-on{ display:block; }

.st-toolbar{ display:flex; gap:8px; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
.st-search{ flex:1 1 200px; min-width:140px; height:34px; padding:0 12px; border:1px solid #d9dbe0; border-radius:9px;
  background:#fff; font:400 13px/1 inherit; color:#151a17; outline:none; }
.st-search:focus{ border-color:var(--st-accent); box-shadow:0 0 0 3px color-mix(in srgb, var(--st-accent) 20%, transparent); }
.st-sort{ height:34px; padding:0 10px; border:1px solid #d9dbe0; border-radius:9px; background:#fff; font:500 12.5px/1 inherit; color:#31363d; }
.st-btn{ height:34px; padding:0 14px; border:1px solid #d9dbe0; border-radius:9px; background:#fff; color:#31363d;
  font:600 12.5px/1 inherit; cursor:default; transition:background .12s,border-color .12s; }
.st-btn:hover{ background:#f2f3f5; }
.st-btn.primary{ background:var(--st-accent); border-color:transparent; color:#0b160f; }
.st-btn.primary:hover{ filter:brightness(1.05); }
.st-btn:disabled{ opacity:.5; }
.st-btn.danger:hover{ background:#fdeceb; border-color:#f0b5ae; color:#a8332a; }

.st-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:10px; }
.st-card{ display:flex; gap:10px; padding:10px; border:1px solid #e6e7ea; border-radius:12px; background:#fff; }
.st-ico{ flex:0 0 auto; width:46px; height:46px; border-radius:11px; background:#eef0f2 center/cover no-repeat; }
.st-main{ min-width:0; flex:1 1 auto; display:flex; flex-direction:column; gap:3px; }
.st-name{ font-weight:700; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.st-sub{ color:#6d747c; font-size:11.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.st-desc{ color:#7d848c; font-size:11.5px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.st-meta{ display:flex; gap:8px; color:#8b929a; font-size:11px; }
.st-actions{ display:flex; gap:6px; margin-top:4px; }
.st-actions .st-btn{ height:28px; padding:0 10px; font-size:11.5px; }

.st-list{ display:flex; flex-direction:column; gap:8px; }
.st-row{ display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid #e6e7ea; border-radius:12px; background:#fff; }
.st-row .st-main{ gap:2px; }
.st-empty{ padding:26px 12px; text-align:center; color:#8b929a; font-size:12.5px; }
.st-badge{ display:inline-block; padding:1px 7px; border-radius:999px; background:#eef0f2; color:#5c636b; font-size:10.5px; font-weight:600; }
.st-badge.ok{ background:color-mix(in srgb, var(--st-accent) 22%, #fff); color:#1c4630; }

.st-form{ display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:10px; padding:12px; border:1px dashed #d9dbe0; border-radius:12px; background:#fff; }
.st-form input{ height:34px; padding:0 10px; border:1px solid #d9dbe0; border-radius:9px; font:400 12.5px/1 inherit; background:#fff; color:#151a17; min-width:0; }
.st-form .wide{ grid-column:1 / -1; }
.st-form .st-actions{ margin-top:0; justify-content:flex-end; grid-column:1 / -1; }
.st-note{ color:#8b929a; font-size:11.5px; margin:6px 0 0; }
.st-note.bad{ color:#c9524a; }
.st-note.ok{ color:#2f8f4e; }
.st-repo{ display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid #e6e7ea; border-radius:12px; background:#fff; }
.st-repo .st-main{ gap:2px; }
.st-repo-url{ color:#6d747c; font-size:11.5px; word-break:break-all; }

/* publish dialog */
.st-overlay{ position:absolute; inset:0; background:rgba(12,16,14,.45); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; z-index:50; }
.st-overlay.is-on{ display:flex; }
.st-dialog{ width:min(520px, calc(100% - 24px)); max-height:calc(100% - 24px); overflow:auto; background:#fff; border-radius:14px;
  border:1px solid #e2e4e8; box-shadow:0 24px 60px rgba(0,0,0,.35); padding:16px; }
.st-dialog h3{ margin:0 0 12px; font-size:16px; letter-spacing:-0.01em; }
.st-field{ display:flex; flex-direction:column; gap:5px; margin-bottom:10px; }
.st-field span{ font-size:11.5px; color:#6d747c; font-weight:600; }
.st-field input, .st-field textarea{ width:100%; padding:8px 10px; border:1px solid #d9dbe0; border-radius:9px; font:400 13px/1.4 inherit; color:#151a17; background:#fff; }
.st-field textarea{ min-height:84px; resize:vertical; }
.st-field input:focus, .st-field textarea:focus{ outline:none; border-color:var(--st-accent); box-shadow:0 0 0 3px color-mix(in srgb, var(--st-accent) 20%, transparent); }
.st-dialog-actions{ display:flex; justify-content:flex-end; gap:8px; margin-top:6px; }
.st-dialog-note{ font-size:11px; color:#8b929a; margin:2px 0 10px; }
.st-publish-list{ border:1px solid #e6e7ea; border-radius:10px; padding:10px; background:#fbfbfd; margin-bottom:10px; }
.st-publish-head{ font-size:11.5px; color:#5a6068; font-weight:600; margin-bottom:6px; }
.st-publish-files{ max-height:104px; overflow:auto; display:flex; flex-direction:column; gap:3px; }
.st-publish-files span{ font-size:11px; color:#6d747c; font-family:ui-monospace,Consolas,monospace; display:flex; justify-content:space-between; gap:8px; }
.st-publish-note{ font-size:10.5px; color:#8b929a; margin-top:8px; }
.st-publish-note code{ background:#eef0f2; border-radius:4px; padding:0 4px; }

@media (max-width:760px){
  #st<?echo $AppID?>{ height:100%; }
  .st-grid{ grid-template-columns:1fr; }
  .st-head{ flex-wrap:wrap; padding:8px; }
  .st-tabs{ width:100%; overflow-x:auto; }
  .st-hub{ width:100%; margin-left:0; }
  .st-form{ grid-template-columns:1fr; }
}
</style>

<div id="st<?echo $AppID?>" data-appid="<?echo $AppID?>">
  <div class="st-head">
    <div class="st-tabs">
      <button type="button" class="st-tab is-on" data-tab="catalog">Каталог</button>
      <button type="button" class="st-tab" data-tab="installed">Установленные</button>
      <button type="button" class="st-tab" data-tab="updates">Обновления</button>
      <button type="button" class="st-tab" data-tab="repos">Репозитории</button>
    </div>
    <div class="st-hub"><span class="st-dot" id="st-hub-dot<?echo $AppID?>"></span><span id="st-hub-url<?echo $AppID?>">…</span></div>
  </div>

  <div class="st-body">
    <section class="st-panel is-on" data-panel="catalog">
      <div class="st-toolbar">
        <input class="st-search" id="st-q<?echo $AppID?>" placeholder="Поиск приложений…" autocomplete="off">
        <select class="st-sort" id="st-source<?echo $AppID?>">
          <option value="official">Официальный репозиторий</option>
        </select>
        <select class="st-sort" id="st-sort<?echo $AppID?>">
          <option value="popular">По популярности</option>
          <option value="new">Новые</option>
          <option value="name">По названию</option>
          <option value="size">По размеру</option>
        </select>
        <button type="button" class="st-btn" id="st-refresh<?echo $AppID?>">Обновить</button>
      </div>
      <div class="st-grid" id="st-catalog<?echo $AppID?>"></div>
      <div class="st-empty" id="st-catalog-empty<?echo $AppID?>" style="display:none"></div>
    </section>

    <section class="st-panel" data-panel="installed">
      <div class="st-list" id="st-installed<?echo $AppID?>"></div>
      <div class="st-empty" id="st-installed-empty<?echo $AppID?>" style="display:none">Пока ничего не установлено</div>
    </section>

    <section class="st-panel" data-panel="updates">
      <div class="st-list" id="st-updates<?echo $AppID?>"></div>
      <div class="st-empty" id="st-updates-empty<?echo $AppID?>" style="display:none">Обновлений нет</div>
    </section>

    <section class="st-panel" data-panel="repos">
      <div class="st-toolbar">
        <input class="st-search" id="st-hub-input<?echo $AppID?>" placeholder="Адрес хаба, например http://172.17.0.1:48090">
        <button type="button" class="st-btn" id="st-hub-save<?echo $AppID?>">Сохранить хаб</button>
        <button type="button" class="st-btn" id="st-hub-test<?echo $AppID?>">Проверить связь</button>
        <button type="button" class="st-btn" id="st-hub-find<?echo $AppID?>">Подобрать адрес</button>
      </div>
      <p class="st-note" id="st-hub-state<?echo $AppID?>" style="display:none"></p>
      <p class="st-note">Официальный репозиторий живёт на хабе. Дополнительные репозитории — любой URL с парой <b>index.json</b> + <b>index.sig</b> (ed25519).</p>
      <div class="st-list" id="st-repos<?echo $AppID?>" style="margin-top:10px"></div>
      <div class="st-form">
        <input id="st-repo-name<?echo $AppID?>" class="wide" placeholder="Название (необязательно)">
        <input id="st-repo-url<?echo $AppID?>" class="wide" placeholder="https://example.org/apphouse">
        <input id="st-repo-key<?echo $AppID?>" class="wide" placeholder="Публичный ключ ed25519 (base64url, необязательно)">
        <div class="st-actions">
          <button type="button" class="st-btn primary" id="st-repo-add<?echo $AppID?>">Добавить репозиторий</button>
        </div>
      </div>
    </section>
  </div>

  <div class="st-overlay" id="st-overlay<?echo $AppID?>">
    <div class="st-dialog" role="dialog" aria-modal="true">
      <h3 id="st-dialog-title<?echo $AppID?>">Публикация приложения</h3>
      <p class="st-dialog-note">Приложение уйдёт в официальный хаб AppsHouse. Повторная публикация заменяет предыдущую версию.</p>
      <input type="hidden" id="st-pub-app<?echo $AppID?>">
      <div class="st-field"><span>Название (как в каталоге)</span><input id="st-pub-title<?echo $AppID?>" maxlength="120"></div>
      <div class="st-field"><span>Версия</span><input id="st-pub-version<?echo $AppID?>" placeholder="1.0.0" maxlength="32"></div>
      <div class="st-field"><span>Описание</span><textarea id="st-pub-desc<?echo $AppID?>" maxlength="2000" placeholder="Что делает приложение"></textarea></div>
      <div class="st-field"><span>Требуемая версия ОС (необязательно)</span><input id="st-pub-os<?echo $AppID?>" placeholder="2.5" maxlength="32"></div>
      <div class="st-publish-list">
        <div class="st-publish-head">Уйдёт в архив <b id="st-pub-count<?echo $AppID?>">…</b></div>
        <div class="st-publish-files" id="st-pub-files<?echo $AppID?>"></div>
        <div class="st-publish-note">Служебные файлы не попадают в архив: <code>app.meta</code>, <code>data/</code>, <code>.env</code>, <code>*.log</code>, <code>.git/</code>, <code>node_modules/</code>.</div>
      </div>
      <div class="st-dialog-actions">
        <button type="button" class="st-btn" id="st-pub-cancel<?echo $AppID?>">Отмена</button>
        <button type="button" class="st-btn primary" id="st-pub-ok<?echo $AppID?>">Опубликовать</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var id = '<?echo $AppID?>';
  var root = document.getElementById('st' + id);
  var API = 'system/core/functions/appstore.php';
  var TOKEN = <?echo json_encode($_SESSION['fo_store_csrf'] ?? '');?>;
  var state = { tab: 'catalog', catalog: [], installed: [], repos: [], q: '', source: 'official' };

  function el(name){ return document.getElementById('st-' + name + id); }
  function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }
  function bytes(n){ n = Number(n)||0; if(n<1024) return n+' B'; if(n<1048576) return (n/1024).toFixed(1)+' KB'; return (n/1048576).toFixed(1)+' MB'; }
  function jsonGet(qs){
    return fetch(API + '?' + qs, {credentials:'same-origin'}).then(function(r){ return r.json(); });
  }
  function post(data){
    data.token = TOKEN;
    var body = new URLSearchParams();
    Object.keys(data).forEach(function(k){ body.append(k, data[k]); });
    return fetch(API, {method:'POST', credentials:'same-origin', body: body}).then(function(r){ return r.json(); });
  }
  function toast(msg){
    if(window.FO_TOAST){ window.FO_TOAST(msg); } else { alert(msg); }
  }
  function iconFor(hash){
    if(hash){ return API + '?action=icon&hash=' + encodeURIComponent(hash) + '&t=' + Date.now(); }
    return 'system/core/design/images/app.png';
  }

  /* ---------- tabs ---------- */
  root.querySelectorAll('.st-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      var tab = btn.getAttribute('data-tab');
      state.tab = tab;
      root.querySelectorAll('.st-tab').forEach(function(b){ b.classList.toggle('is-on', b === btn); });
      root.querySelectorAll('.st-panel').forEach(function(p){ p.classList.toggle('is-on', p.getAttribute('data-panel') === tab); });
      if(tab === 'installed'){ loadInstalled(); }
      if(tab === 'updates'){ loadUpdates(); }
      if(tab === 'repos'){ loadRepos(); }
      if(tab === 'catalog'){ loadCatalog(); }
    });
  });

  /* ---------- catalog ---------- */
  function renderCatalog(){
    var box = el('catalog'), empty = el('catalog-empty');
    var list = state.catalog;
    box.innerHTML = list.map(function(a){
      var installed = state.installed.some(function(i){ return i.hash === a.hash || i.name === a.name; });
      return '<div class="st-card">' +
        '<div class="st-ico" style="background-image:url(' + iconFor(a.hash) + ')"></div>' +
        '<div class="st-main">' +
          '<div class="st-name">' + esc(a.second_name || a.name) + '</div>' +
          '<div class="st-sub">' + esc(a.name) + ' · v' + esc(a.version) + '</div>' +
          '<div class="st-desc">' + esc(a.description || '') + '</div>' +
          '<div class="st-meta"><span>' + bytes(a.size) + '</span><span>' + Number(a.downloads||0) + ' устан.</span></div>' +
          '<div class="st-actions">' +
            (installed
              ? '<span class="st-badge ok">Установлено</span>'
              : '<button type="button" class="st-btn primary" data-install="' + esc(a.hash) + '" data-repo="' + esc(state.source) + '">Установить</button>') +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');
    var isEmpty = list.length === 0;
    empty.style.display = isEmpty ? 'block' : 'none';
    empty.textContent = state.q ? 'Ничего не найдено по запросу «' + state.q + '»' : 'Каталог пуст — опубликуйте первое приложение.';
  }

  function loadCatalog(){
    var q = state.q || '';
    if(state.source !== 'official'){
      jsonGet('action=repo-apps&id=' + encodeURIComponent(state.source)).then(function(d){
        state.catalog = (d.ok && d.apps) ? d.apps : [];
        if(!d.ok){ toast('Репозиторий недоступен: ' + (d.error || 'ошибка')); }
        if(!d.verified && state.catalog.length){ toast('Внимание: подпись репозитория не проверена'); }
        renderCatalog();
      });
      return;
    }
    jsonGet('action=catalog&q=' + encodeURIComponent(q) + '&sort=' + encodeURIComponent(el('sort').value) + '&page=1')
      .then(function(d){
        state.catalog = d.apps || [];
        if(!d.ok){
          state.catalog = [];
          var why = d.error === 'hub_unreachable' ? 'хаб не отвечает — проверьте адрес на вкладке «Репозитории»'
            : (d.error === 'not_registered' ? 'хаб не принял регистрацию установки'
            : (d.error || 'ошибка'));
          if(d.hub_status) why += ' (HTTP ' + d.hub_status + ')';
          if(d.hub_body) why += ': ' + String(d.hub_body).slice(0, 160);
          toast('Каталог не загрузился: ' + why);
          var empty = el('catalog-empty');
          if(empty){
            empty.style.display = 'block';
            empty.textContent = 'Каталог не загрузился: ' + why;
          }
          return;
        }
        renderCatalog();
        refreshHubBadge();
      });
  }

  function showHubState(d){
    var n = el('hub-state');
    if(!n) return;
    if(d && d.ok){
      n.style.display = 'block';
      n.className = 'st-note ok';
      n.textContent = 'Хаб доступен: ' + d.url + (d.ms ? '  ·  ' + d.ms + ' мс' : '');
    } else {
      n.style.display = 'block';
      n.className = 'st-note bad';
      n.textContent = 'Хаб недоступен. ' + ((d && (d.hint || d.error)) || 'причина неизвестна');
    }
  }

  function checkHub(url){
    var q = 'action=hub-check' + (url ? '&url=' + encodeURIComponent(url) : '');
    return jsonGet(q).then(function(d){ showHubState(d); return d; })
      .catch(function(){ showHubState({ ok:false, hint:'запрос не выполнился' }); });
  }

  function refreshHubBadge(){
    jsonGet('action=status').then(function(s){
      var dot = el('hub-dot'), url = el('hub-url');
      if(!dot || !url) return;
      dot.className = 'st-dot ' + (s.hub_ok ? 'ok' : 'bad');
      url.textContent = s.hub + (s.hub_ok ? '' : ' · недоступен');
      url.title = s.hub_hint || '';
      if(!s.hub_ok && s.hub_hint){ showHubState({ ok:false, hint: s.hub_hint }); }
    });
  }

  root.addEventListener('click', function(e){
    var b = e.target.closest('[data-install]');
    if(b){
      var hash = b.getAttribute('data-install');
      var repo = b.getAttribute('data-repo') || 'official';
      b.disabled = true; b.textContent = 'Установка…';
      post({action:'install', hash: hash, repo: repo}).then(function(d){
        if(d.ok){ toast('Установлено: ' + d.app + ' v' + d.version + (d.repo && d.repo !== 'official' ? ' (' + d.repo + ')' : '')); loadInstalled(); loadCatalog(); }
        else { toast('Не удалось установить: ' + (d.error || 'ошибка')); b.disabled = false; b.textContent = 'Установить'; }
      }).catch(function(){ b.disabled = false; b.textContent = 'Установить'; });
      return;
    }
    var u = e.target.closest('[data-uninstall]');
    if(u){
      var app = u.getAttribute('data-uninstall');
      if(!confirm('Удалить «' + app + '»?')) return;
      post({action:'uninstall', app: app}).then(function(d){
        if(d.ok){ toast('Удалено: ' + app); loadInstalled(); }
        else { toast('Не удалось удалить: ' + (d.error || 'ошибка')); }
      });
      return;
    }
    var p = e.target.closest('[data-publish]');
    if(p){
      openPublish(p.getAttribute('data-publish'));
      return;
    }
    var r = e.target.closest('[data-repo-remove]');
    if(r){
      var rid = r.getAttribute('data-repo-remove');
      if(!confirm('Убрать репозиторий?')) return;
      post({action:'repo-remove', id: rid}).then(function(d){
        if(d.ok){ loadRepos(); } else { toast('Не удалось: ' + (d.error || 'ошибка')); }
      });
      return;
    }
    var c = e.target.closest('[data-repo-check]');
    if(c){
      var cid = c.getAttribute('data-repo-check');
      c.disabled = true;
      jsonGet('action=repo-apps&id=' + encodeURIComponent(cid)).then(function(d){
        c.disabled = false;
        if(!d.ok){ toast('Репозиторий недоступен: ' + (d.error || 'ошибка')); return; }
        var n = (d.apps || []).length;
        toast('Приложений: ' + n + ' · подпись: ' + (d.verified ? 'проверена' : 'не проверена'));
      }).catch(function(){ c.disabled = false; toast('Репозиторий недоступен'); });
      return;
    }
  });

  /* ---------- publish dialog ---------- */
  function openPublish(app){
    var rec = null;
    state.installed.forEach(function(i){ if(i.app === app){ rec = i; } });
    rec = rec || { app: app };
    el('pub-app').value = app;
    el('pub-title').value = rec.title || rec.name || app;
    el('pub-version').value = rec.published_version || rec.version || '1.0.0';
    el('pub-desc').value = rec.description || '';
    el('pub-os').value = rec.os_version || '';
    el('dialog-title').textContent = 'Публикация: ' + app;
    el('overlay').classList.add('is-on');
    setTimeout(function(){ el('pub-title').focus(); }, 60);

    // what exactly will be uploaded
    el('pub-count').textContent = '…';
    el('pub-files').innerHTML = '';
    jsonGet('action=preview&app=' + encodeURIComponent(app)).then(function(d){
      if(!d || !d.ok){
        el('pub-count').textContent = 'не удалось получить список';
        return;
      }
      var total = d.total > 1048576 ? (d.total / 1048576).toFixed(1) + ' МБ' : Math.round(d.total / 1024) + ' КБ';
      el('pub-count').textContent = d.count + ' файл(ов) · ' + total;
      el('pub-files').innerHTML = (d.files || []).map(function(f){
        var sz = f.size > 1048576 ? (f.size / 1048576).toFixed(1) + ' МБ' : Math.round(f.size / 1024) + ' КБ';
        return '<span><b>' + esc(f.name) + '</b><i>' + sz + '</i></span>';
      }).join('');
    });
  }
  function closePublish(){ el('overlay').classList.remove('is-on'); }

  el('pub-cancel').addEventListener('click', closePublish);
  el('overlay').addEventListener('click', function(e){ if(e.target === this){ closePublish(); } });
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closePublish(); } });

  el('pub-ok').addEventListener('click', function(){
    var app = el('pub-app').value;
    var title = el('pub-title').value.trim() || app;
    var version = el('pub-version').value.trim() || '1.0.0';
    var desc = el('pub-desc').value.trim();
    var osv = el('pub-os').value.trim();
    var btn = this;
    btn.disabled = true; btn.textContent = 'Публикация…';
    post({action:'publish', app: app, version: version, second_name: title, description: desc, os_version: osv})
      .then(function(d){
        btn.disabled = false; btn.textContent = 'Опубликовать';
        if(d.ok){
          closePublish();
          toast('Опубликовано: ' + title + ' v' + version + ' (hash ' + String(d.hash).slice(0,8) + ')');
          loadInstalled();
        } else {
          toast('Публикация не удалась: ' + (d.error || 'ошибка'));
        }
      })
      .catch(function(){ btn.disabled = false; btn.textContent = 'Опубликовать'; toast('Публикация не удалась'); });
  });

  /* ---------- installed ---------- */
  function loadInstalled(){
    jsonGet('action=installed').then(function(d){
      state.installed = d.apps || [];
      var box = el('installed'), empty = el('installed-empty');
      box.innerHTML = state.installed.map(function(a){
        return '<div class="st-row">' +
          '<div class="st-ico" style="background-image:url(' + esc(a.icon) + ')"></div>' +
          '<div class="st-main">' +
            '<div class="st-name">' + esc(a.name) + ' <span class="st-badge">' + esc(a.app) + '</span></div>' +
            '<div class="st-sub">' + (a.version ? 'v' + esc(a.version) : 'локальное приложение') +
              (a.installed_at ? ' · ' + esc(a.installed_at) : '') + '</div>' +
          '</div>' +
          '<div class="st-actions">' +
            (a.protected ? '<span class="st-badge">системное</span>' : '<button type="button" class="st-btn" data-publish="' + esc(a.app) + '">Выложить</button>') +
            (a.protected ? '' : '<button type="button" class="st-btn danger" data-uninstall="' + esc(a.app) + '">Удалить</button>') +
          '</div>' +
        '</div>';
      }).join('');
      empty.style.display = state.installed.length ? 'none' : 'block';
      if(state.tab === 'catalog') renderCatalog();
    });
  }

  /* ---------- updates ---------- */
  function loadUpdates(){
    Promise.all([jsonGet('action=installed'), jsonGet('action=repo-apps&id=official')]).then(function(res){
      state.installed = res[0].apps || [];
      var repo = res[1].apps || [];
      var box = el('updates'), empty = el('updates-empty');
      var rows = [];
      state.installed.forEach(function(i){
        if(!i.app) return;
        var remote = repo.filter(function(a){ return a.name === i.app; })[0];
        if(!remote) return;
        if(String(remote.version) && String(i.version) && String(remote.version) !== String(i.version)){
          rows.push({local:i, remote:remote});
        }
      });
      box.innerHTML = rows.map(function(r){
        return '<div class="st-row">' +
          '<div class="st-ico" style="background-image:url(' + iconFor(r.remote.hash) + ')"></div>' +
          '<div class="st-main">' +
            '<div class="st-name">' + esc(r.local.name) + '</div>' +
            '<div class="st-sub">установлено v' + esc(r.local.version) + ' → доступно v' + esc(r.remote.version) + '</div>' +
          '</div>' +
          '<div class="st-actions"><button type="button" class="st-btn primary" data-install="' + esc(r.remote.hash) + '">Обновить</button></div>' +
        '</div>';
      }).join('');
      empty.style.display = rows.length ? 'none' : 'block';
    });
  }

  /* ---------- repos ---------- */
  function loadRepos(){
    jsonGet('action=repos').then(function(d){
      state.repos = d.repos || [];
      el('hub-url').textContent = d.hub || '';
      el('hub-input').value = d.hub || '';
      var sel = el('source');
      if(sel){
        sel.innerHTML = state.repos.map(function(r){
          return '<option value="' + esc(r.id) + '">' + esc(r.name) + (r.official ? '' : ' · сторонний') + '</option>';
        }).join('');
        if(!state.repos.some(function(r){ return r.id === state.source; })){ state.source = 'official'; }
        sel.value = state.source;
      }
      el('repos').innerHTML = state.repos.map(function(r){        return '<div class="st-repo">' +
          '<div class="st-main">' +
            '<div class="st-name">' + esc(r.name) + ' ' +
              (r.official ? '<span class="st-badge ok">официальный</span>' : '<span class="st-badge">сторонний</span>') + '</div>' +
            '<div class="st-repo-url">' + esc(r.url) + '</div>' +
            (r.pubkey ? '<div class="st-sub">key: ' + esc(String(r.pubkey).slice(0, 24)) + '…</div>' : '') +
          '</div>' +
          (r.official ? '' : '<div class="st-actions"><button type="button" class="st-btn" data-repo-check="' + esc(r.id) + '">Проверить</button><button type="button" class="st-btn danger" data-repo-remove="' + esc(r.id) + '">Убрать</button></div>') +
        '</div>';
      }).join('');
      refreshHubBadge();
    });
  }

  el('repo-add').addEventListener('click', function(){
    var name = el('repo-name').value.trim();
    var url = el('repo-url').value.trim();
    var key = el('repo-key').value.trim();
    if(!url){ toast('Укажите адрес репозитория'); return; }
    post({action:'repo-add', name:name, url:url, pubkey:key}).then(function(d){
      if(d.ok){ toast('Репозиторий добавлен'); el('repo-url').value=''; el('repo-key').value=''; loadRepos(); }
      else { toast('Не удалось: ' + (d.error || 'ошибка')); }
    });
  });

  el('hub-save').addEventListener('click', function(){
    var url = el('hub-input').value.trim();
    post({action:'hub-set', url:url}).then(function(d){
      if(d.ok && d.hub_ok){
        toast('Хаб сохранён: ' + d.hub);
        checkHub(d.hub).then(function(){ loadRepos(); loadCatalog(); });
      } else if(d.ok){
        showHubState({ ok:false, hint: d.hub_hint || 'сохранено, но хаб не отвечает' });
        toast('Адрес сохранён, но хаб не отвечает');
        loadRepos();
      } else if(d.error === 'hub_not_saved'){
        showHubState({ ok:false, hint: d.hint || 'нет прав на запись system/instance.foc' });
        toast('Не удалось сохранить адрес');
      } else {
        toast('Не удалось: ' + (d.error || 'ошибка'));
      }
    }).catch(function(){
      showHubState({ ok:false, hint:'запрос на сохранение не выполнился' });
    });
  });

  var hubFind = el('hub-find');
  if(hubFind) hubFind.addEventListener('click', function(){
    hubFind.disabled = true;
    var cur = el('hub-input').value.trim();
    var m = cur.match(/:(\d{2,5})\s*$/);
    var port = m ? m[1] : '48090';
    jsonGet('action=hub-probe&port=' + encodeURIComponent(port)).then(function(d){
      hubFind.disabled = false;
      if(d.ok){
        el('hub-input').value = d.url;
        showHubState(d.check);
        toast('Хаб найден: ' + d.url);
      } else {
        var lines = (d.tried || []).map(function(x){ return x.url + (x.ok ? ' — ок' : ' — нет'); });
        showHubState({ ok:false, hint:'ни один адрес не ответил. Проверены: ' + lines.join(', ') });
      }
    }).catch(function(){
      hubFind.disabled = false;
      showHubState({ ok:false, hint:'подбор не удался' });
    });
  });

  var hubTest = el('hub-test');
  if(hubTest) hubTest.addEventListener('click', function(){
    var u = el('hub-input').value.trim();
    hubTest.disabled = true;
    checkHub(u).then(function(){ hubTest.disabled = false; });
  });

  /* ---------- init ---------- */
  var t = null;
  el('q').addEventListener('input', function(){
    state.q = this.value.trim();
    clearTimeout(t);
    t = setTimeout(loadCatalog, 260);
  });
  el('sort').addEventListener('change', loadCatalog);
  el('source').addEventListener('change', function(){ state.source = this.value; loadCatalog(); });
  el('refresh').addEventListener('click', function(){ loadInstalled(); loadCatalog(); });

  window.FO_STORE = {
    token: TOKEN,
    reload: function(){ loadInstalled(); loadCatalog(); loadRepos(); },
    post: post,
    get: jsonGet
  };

  loadInstalled();
  loadCatalog();
})();
</script>

<?
$AppContainer->EndContainer();
?>
