<?
/* Forest OS — Settings 2.0 (unified design) */
if (!isset($_SESSION)) { session_start(); }
if (empty($_SESSION['fo_settings_csrf'])) { $_SESSION['fo_settings_csrf'] = bin2hex(random_bytes(16)); }

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

$DOC = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = (string)$_SESSION['loginuser'];
$home = $DOC . '/system/users/' . $login;
$settings = $home . '/settings';

$appear = @parse_ini_file($settings . '/appearance.foc');
if (!is_array($appear)) { $appear = array(); }
$win = @parse_ini_file($settings . '/AppWindow.foc');
if (!is_array($win)) { $win = array(); }

$accent = !empty($appear['accent']) ? (string)$appear['accent'] : '#5fe08c';
$frame = !empty($appear['frame']) && (string)$appear['frame'] === '1';
$ctrlMode = isset($win['ControlMode']) ? (string)$win['ControlMode'] : '1';
$winSize = isset($win['WindowSize']) ? (int)$win['WindowSize'] : 8;
$btnSize = isset($win['ButtonSize']) ? (int)$win['ButtonSize'] : 12;

$autorunFile = $settings . '/autorun.foc';
$autorun = is_file($autorunFile) ? trim((string)@file_get_contents($autorunFile)) : '';
$autorunList = array_filter(array_map('trim', explode(',', $autorun)));

$locale = isset($_SESSION['locale']) ? (string)$_SESSION['locale'] : 'ru';
$zoneFile = $settings . '/timezone.foc';
$timezone = is_file($zoneFile) ? trim((string)@file_get_contents($zoneFile)) : (isset($_SESSION['timezone']) ? $_SESSION['timezone'] : 'Europe/Moscow');

$apps = array();
foreach (glob($DOC . '/system/apps/*/main.php') as $f) {
    $slug = basename(dirname($f));
    $apps[] = array('slug' => $slug, 'title' => str_replace('_', ' ', $slug));
}
usort($apps, function ($a, $b) { return strcasecmp($a['title'], $b['title']); });

$pinned = array();
foreach (glob($settings . '/Dock/*/*') as $f) {
    $pinned[] = basename($f);
}
$pinned = array_values(array_unique($pinned));

$osinfo = @parse_ini_file($DOC . '/system/core/osinfo.foc');
$free = @disk_free_space($DOC);

/* themes installed system-wide */
$themes = array();
foreach (glob($DOC . '/system/core/design/themes/*.fth') as $tf) {
    $ti = @parse_ini_file($tf);
    $themes[] = array(
        'file' => basename($tf),
        'name' => (string)(is_array($ti) && !empty($ti['Name']) ? $ti['Name'] : basename($tf, '.fth')),
        'accent' => trim((string)(is_array($ti) ? ($ti['accentcolor'] ?? '') : ''), " ;\"'"),
    );
}
$currentThemeFile = '';
if (is_file($settings . '/etc/theme.src')) {
    $currentThemeFile = trim((string)@file_get_contents($settings . '/etc/theme.src'));
}
$dark = is_file($settings . '/etc/dark.foc') && trim((string)@file_get_contents($settings . '/etc/dark.foc')) === '1';
$swatches = array('#5fe08c', '#63b3ff', '#b48cff', '#ff8fab', '#ffb454', '#8ee06a', '#ff6b6b', '#64d2c8');
$zones = array('Europe/Moscow', 'Europe/Kaliningrad', 'Europe/Samara', 'Asia/Yekaterinburg', 'Asia/Novosibirsk', 'Asia/Krasnoyarsk', 'Asia/Irkutsk', 'Asia/Vladivostok', 'Europe/Kiev', 'Europe/Minsk', 'Asia/Almaty', 'UTC');

require $DOC . '/system/core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'Settings';
$AppContainer->SecondNameInfo = 'Параметры';
$AppContainer->VersionInfo = '2.0.0';
$AppContainer->AuthorInfo = 'Forest Media';
$AppContainer->LibraryArray = array();
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '488px';
$AppContainer->width = '820px';
$AppContainer->backgroundColor = '#fbfbfd';
$AppContainer->fontColor = '#151a17';
$AppContainer->customStyle = 'padding-top:0; max-width:100%; overflow:hidden;';
$AppContainer->isMobile = isset($mobile) ? $mobile : (isset($_GET['mobile']) ? (string)$_GET['mobile'] : 'false');
$AppContainer->StartContainer();
?>

<style>
#set<?echo $AppID?>{
  --set-accent: var(--fo-accent, #5fe08c);
  height:100%; display:flex; background:#fbfbfd; color:#151a17;
  font:13px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
}
#set<?echo $AppID?> *{ box-sizing:border-box; }
.set-side{ flex:0 0 186px; background:#f2f3f6; border-right:1px solid #e4e6ea; padding:10px 8px; overflow:auto; }
.set-side-title{ font-size:10.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#8b929a; padding:8px 10px 5px; }
.set-nav{ display:flex; align-items:center; gap:9px; width:100%; padding:8px 10px; border:0; border-radius:8px;
  background:transparent; color:#31363d; font:600 12.5px/1 inherit; text-align:left; cursor:default; }
.set-nav:hover{ background:#e9ebef; }
.set-nav.is-on{ background:var(--set-accent); color:#0b160f; }
.set-nav .dot{ width:16px; height:16px; flex:0 0 auto; border-radius:5px; background:#d7dbe0; }
.set-nav.is-on .dot{ background:rgba(0,0,0,.22); }
.set-body{ flex:1 1 auto; min-width:0; overflow:auto; padding:16px 18px; }
.set-pane{ display:none; }
.set-pane.is-on{ display:block; }
.set-h{ margin:0 0 3px; font-size:19px; letter-spacing:-.01em; }
.set-sub{ margin:0 0 16px; color:#7d848c; font-size:12px; }
.set-card{ border:1px solid #e6e7ea; border-radius:12px; background:#fff; padding:14px; margin-bottom:12px; }
.set-row{ display:flex; align-items:center; gap:12px; }
.set-row + .set-row{ margin-top:12px; padding-top:12px; border-top:1px solid #f0f1f3; }
.set-row .grow{ flex:1 1 auto; min-width:0; }
.set-lbl{ font-weight:600; font-size:12.5px; }
.set-hint{ color:#8b929a; font-size:11.5px; margin-top:2px; }
.set-swatches{ display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
.set-sw{ width:26px; height:26px; border-radius:50%; border:2px solid transparent; cursor:default; padding:0; }
.set-sw.is-on{ border-color:#151a17; box-shadow:0 0 0 2px #fff inset; }
.set-color{ width:34px; height:26px; border:1px solid #d9dbe0; border-radius:7px; background:#fff; padding:2px; }
.set-themes{ display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
.set-theme{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid #d9dbe0; border-radius:9px;
  background:#fff; color:#31363d; font:600 12px/1 inherit; cursor:default; }
.set-theme:hover{ background:#f2f3f5; }
.set-theme.is-on{ border-color:var(--set-accent); box-shadow:0 0 0 2px color-mix(in srgb, var(--set-accent) 20%, transparent); }
.set-theme-dot{ width:12px; height:12px; border-radius:50%; }
.set-seg{ display:inline-flex; background:#eff0f3; border-radius:9px; padding:2px; }
.set-seg button{ border:0; background:transparent; padding:6px 12px; border-radius:7px; font:600 12px/1 inherit; color:#5a6068; cursor:default; }
.set-seg button.is-on{ background:#fff; color:#151a17; box-shadow:0 1px 3px rgba(0,0,0,.12); }
.set-slider{ width:160px; accent-color:var(--set-accent); }
.set-val{ min-width:34px; text-align:right; font-variant-numeric:tabular-nums; color:#5a6068; font-size:12px; }
.set-btn{ height:30px; padding:0 12px; border:1px solid #d9dbe0; border-radius:8px; background:#fff; color:#31363d;
  font:600 12px/1 inherit; cursor:default; }
.set-btn:hover{ background:#f2f3f5; }
.set-btn.primary{ background:var(--set-accent); border-color:transparent; color:#0b160f; }
.set-btn.danger{ color:#a8332a; }
.set-btn.danger:hover{ background:#fdeceb; border-color:#f0b5ae; }
.set-list{ display:flex; flex-direction:column; gap:8px; }
.set-item{ display:flex; align-items:center; gap:10px; padding:9px 11px; border:1px solid #e6e7ea; border-radius:10px; background:#fff; }
.set-ico{ width:30px; height:30px; border-radius:8px; background:#eef0f2 center/cover no-repeat; flex:0 0 auto; }
.set-chk{ display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:9px; }
.set-chk:hover{ background:#f5f6f8; }
.set-chk input{ accent-color:var(--set-accent); width:15px; height:15px; }
.set-about dt{ color:#8b929a; font-size:11.5px; margin-top:10px; }
.set-about dd{ margin:1px 0 0; font-weight:600; font-size:12.5px; }
.set-toast{ position:absolute; left:50%; bottom:14px; transform:translate(-50%, 20px); opacity:0;
  background:#151a17; color:#fff; padding:8px 14px; border-radius:999px; font-size:12px; transition:opacity .18s, transform .18s; pointer-events:none; }
.set-toast.is-on{ opacity:1; transform:translate(-50%, 0); }

@media (max-width:760px){
  #set<?echo $AppID?>{ flex-direction:column; }
  .set-side{ flex:0 0 auto; width:100%; display:flex; gap:6px; overflow-x:auto; border-right:0; border-bottom:1px solid #e4e6ea; padding:8px; }
  .set-side-title{ display:none; }
  .set-nav{ width:auto; white-space:nowrap; }
  .set-body{ padding:12px; }
}
</style>

<div id="set<?echo $AppID?>">
  <aside class="set-side">
    <div class="set-side-title">Параметры</div>
    <button type="button" class="set-nav is-on" data-nav="look"><span class="dot"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.2"/><circle cx="9" cy="9.6" r="1.17" fill="currentColor" stroke="none"/><circle cx="14.6" cy="9.6" r="1.17" fill="currentColor" stroke="none"/><circle cx="12" cy="14.6" r="1.17" fill="currentColor" stroke="none"/></svg></span>Внешний вид</button>
    <button type="button" class="set-nav" data-nav="dock"><span class="dot"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="8.4" width="18" height="7.4" rx="3.6"/><circle cx="8" cy="12.1" r="1.15" fill="currentColor" stroke="none"/><circle cx="12" cy="12.1" r="1.15" fill="currentColor" stroke="none"/><circle cx="16" cy="12.1" r="1.15" fill="currentColor" stroke="none"/></svg></span>Док</button>
    <button type="button" class="set-nav" data-nav="autorun"><span class="dot"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.2"/><path d="M10.4 9 15 12l-4.6 3z" fill="currentColor" stroke="none"/></svg></span>Автозапуск</button>
    <button type="button" class="set-nav" data-nav="lang"><span class="dot"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.2"/><path d="M3.8 12h16.4M12 3.8c2.3 2.3 3.4 5.1 3.4 8.2s-1.1 5.9-3.4 8.2c-2.3-2.3-3.4-5.1-3.4-8.2S9.7 6.1 12 3.8z"/></svg></span>Язык и время</button>
    <button type="button" class="set-nav" data-nav="about"><span class="dot"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.2"/><path d="M12 10.6v6"/><circle cx="12" cy="7.9" r="1.1" fill="currentColor" stroke="none"/></svg></span>О системе</button>
    <div class="set-side-title">Администрирование</div>
    <button type="button" class="set-nav" data-legacy="users"><span class="dot"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9.2" cy="8.6" r="3"/><path d="M3.8 19.2c0-3 2.4-5.1 5.4-5.1s5.4 2.1 5.4 5.1"/><path d="M15.6 6.1a2.6 2.6 0 0 1 0 5"/><path d="M16.4 14.3c2.3.3 3.9 2.1 3.9 4.9"/></svg></span>Пользователи</button>
    <button type="button" class="set-nav" data-legacy="security"><span class="dot"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.6 18.9 6v6.1c0 4-2.8 6.9-6.9 8.3-4.1-1.4-6.9-4.3-6.9-8.3V6z"/><path d="m9.2 11.9 2 2 3.6-3.9"/></svg></span>Безопасность</button>
    <button type="button" class="set-nav" data-legacy="appmanager"><span class="dot"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.6" y="3.6" width="7.2" height="7.2" rx="2"/><rect x="13.2" y="3.6" width="7.2" height="7.2" rx="2"/><rect x="3.6" y="13.2" width="7.2" height="7.2" rx="2"/><rect x="13.2" y="13.2" width="7.2" height="7.2" rx="2"/></svg></span>Приложения</button>
  </aside>

  <div class="set-body">
    <section class="set-pane is-on" data-pane="look">
      <h2 class="set-h">Внешний вид</h2>
      <p class="set-sub">Акцент применяется ко всей системе: док, кнопки, переключатели, каталог.</p>

      <div class="set-card">
        <div class="set-lbl">Акцентный цвет</div>
        <div class="set-swatches" id="set-swatches<?echo $AppID?>">
          <? foreach ($swatches as $c): ?>
            <button type="button" class="set-sw<? echo (strcasecmp($c, $accent) === 0 ? ' is-on' : '') ?>" data-color="<? echo $c ?>" style="background:<? echo $c ?>"></button>
          <? endforeach; ?>
          <input type="color" class="set-color" id="set-accent<?echo $AppID?>" value="<? echo htmlspecialchars($accent) ?>">
        </div>
      </div>

      <div class="set-card">
        <div class="set-row">
          <div class="grow">
            <div class="set-lbl">Тёмная тема</div>
            <div class="set-hint">Тёмный интерфейс окон и приложений — меньше нагрузки на глаза</div>
          </div>
          <label class="set-seg" id="set-dark<?echo $AppID?>">
            <button type="button" class="<? echo $dark ? '' : 'is-on' ?>" data-dark="0">Светлая</button>
            <button type="button" class="<? echo $dark ? 'is-on' : '' ?>" data-dark="1">Тёмная</button>
          </label>
        </div>
      </div>

      <div class="set-card">
        <div class="set-lbl">Тема</div>
        <div class="set-themes" id="set-themes<?echo $AppID?>">
          <? foreach ($themes as $t): ?>
            <button type="button" class="set-theme<? echo ($t['file'] === $currentThemeFile ? ' is-on' : '') ?>" data-theme="<? echo htmlspecialchars($t['file']) ?>">
              <span class="set-theme-dot" style="background:<? echo htmlspecialchars($t['accent'] !== '' ? $t['accent'] : '#5fe08c') ?>"></span>
              <span><? echo htmlspecialchars($t['name']) ?></span>
            </button>
          <? endforeach; ?>
        </div>
      </div>

      <div class="set-card">
        <div class="set-row">
          <div class="grow">
            <div class="set-lbl">Рамка окна</div>
            <div class="set-hint">Тонкая акцентная обводка вокруг активного окна</div>
          </div>
          <label class="set-seg">
            <button type="button" class="<? echo $frame ? '' : 'is-on' ?>" data-frame="0">Нет</button>
            <button type="button" class="<? echo $frame ? 'is-on' : '' ?>" data-frame="1">Акцент</button>
          </label>
        </div>
      </div>

      <div class="set-card">
        <div class="set-row">
          <div class="grow">
            <div class="set-lbl">Кнопки управления</div>
            <div class="set-hint">Слева или справа в заголовке окна</div>
          </div>
          <label class="set-seg" id="set-ctrl<?echo $AppID?>">
            <button type="button" class="<? echo $ctrlMode === '2' ? 'is-on' : '' ?>" data-ctrl="2">Слева</button>
            <button type="button" class="<? echo $ctrlMode === '2' ? '' : 'is-on' ?>" data-ctrl="1">Справа</button>
          </label>
        </div>
        <div class="set-row">
          <div class="grow">
            <div class="set-lbl">Ширина окна</div>
            <div class="set-hint">Базовый размер новых окон</div>
          </div>
          <input type="range" class="set-slider" id="set-wsize<?echo $AppID?>" min="6" max="14" step="1" value="<? echo $winSize ?>">
          <span class="set-val" id="set-wsize-val<?echo $AppID?>"><? echo $winSize ?></span>
        </div>
        <div class="set-row">
          <div class="grow">
            <div class="set-lbl">Размер кнопок</div>
            <div class="set-hint">Значков «свернуть / развернуть / закрыть»</div>
          </div>
          <input type="range" class="set-slider" id="set-bsize<?echo $AppID?>" min="8" max="18" step="1" value="<? echo $btnSize ?>">
          <span class="set-val" id="set-bsize-val<?echo $AppID?>"><? echo $btnSize ?></span>
        </div>
      </div>
    </section>

    <section class="set-pane" data-pane="dock">
      <h2 class="set-h">Док</h2>
      <p class="set-sub">Приложения, закреплённые внизу экрана.</p>
      <div class="set-card">
        <div class="set-list" id="set-dock<?echo $AppID?>">
          <? foreach ($pinned as $p): if (!is_file($DOC . '/system/apps/' . $p . '/main.php')) continue; ?>
            <div class="set-item" data-app="<? echo htmlspecialchars($p) ?>">
              <span class="set-ico" style="background-image:url(system/apps/<? echo htmlspecialchars($p) ?>/app.png)"></span>
              <span class="grow"><? echo htmlspecialchars(str_replace('_', ' ', $p)) ?></span>
              <button type="button" class="set-btn danger" data-unpin="<? echo htmlspecialchars($p) ?>">Убрать</button>
            </div>
          <? endforeach; ?>
          <? if (!$pinned): ?><div class="set-hint" id="set-dock-empty<?echo $AppID?>">В доке пока ничего нет</div><? endif; ?>
        </div>
      </div>
      <div class="set-card">
        <div class="set-lbl">Добавить приложение</div>
        <div class="set-row" style="margin-top:8px">
          <select class="set-btn grow" id="set-dock-add<?echo $AppID?>" style="height:32px">
            <? foreach ($apps as $a): ?>
              <option value="<? echo htmlspecialchars($a['slug']) ?>"><? echo htmlspecialchars($a['title']) ?></option>
            <? endforeach; ?>
          </select>
          <button type="button" class="set-btn primary" id="set-dock-pin<?echo $AppID?>">Закрепить</button>
        </div>
      </div>
    </section>

    <section class="set-pane" data-pane="autorun">
      <h2 class="set-h">Автозапуск</h2>
      <p class="set-sub">Эти приложения откроются при входе в систему.</p>
      <div class="set-card">
        <? foreach ($apps as $a): ?>
          <label class="set-chk">
            <input type="checkbox" data-app="<? echo htmlspecialchars($a['slug']) ?>" <? echo in_array($a['slug'], $autorunList, true) ? 'checked' : '' ?>>
            <span><? echo htmlspecialchars($a['title']) ?></span>
          </label>
        <? endforeach; ?>
        <div class="set-row" style="margin-top:12px; border-top:0; padding-top:0">
          <button type="button" class="set-btn primary" id="set-autorun-save<?echo $AppID?>">Сохранить</button>
        </div>
      </div>
    </section>

    <section class="set-pane" data-pane="lang">
      <h2 class="set-h">Язык и время</h2>
      <p class="set-sub">Интерфейс и часовой пояс оболочки.</p>
      <div class="set-card">
        <div class="set-row">
          <div class="grow"><div class="set-lbl">Язык</div><div class="set-hint">Нужен перезапуск оболочки</div></div>
          <label class="set-seg" id="set-lang<?echo $AppID?>">
            <button type="button" class="<? echo $locale === 'ru' ? 'is-on' : '' ?>" data-lang="ru">Русский</button>
            <button type="button" class="<? echo $locale === 'en' ? 'is-on' : '' ?>" data-lang="en">English</button>
          </label>
        </div>
        <div class="set-row">
          <div class="grow"><div class="set-lbl">Часовой пояс</div><div class="set-hint">Время в верхней панели</div></div>
          <select class="set-btn" id="set-zone<?echo $AppID?>" style="height:32px">
            <? foreach ($zones as $z): ?>
              <option value="<? echo $z ?>" <? echo $z === $timezone ? 'selected' : '' ?>><? echo $z ?></option>
            <? endforeach; ?>
          </select>
        </div>
      </div>
    </section>

    <section class="set-pane" data-pane="about">
      <h2 class="set-h">О системе</h2>
      <p class="set-sub">Forest OS — веб-операционная система.</p>
      <div class="set-card set-about">
        <dl>
          <dt>Версия</dt><dd><? echo htmlspecialchars(($osinfo['codename'] ?? 'Forest') . ' ' . ($osinfo['subversion'] ?? '')) ?></dd>
          <dt>PHP</dt><dd><? echo PHP_VERSION ?></dd>
          <dt>Пользователь</dt><dd><? echo htmlspecialchars($login) ?></dd>
          <dt>Свободно на диске</dt><dd><? echo $free ? round($free / 1073741824, 1) . ' ГБ' : 'нет данных' ?></dd>
          <dt>Устройство</dt><dd><? echo !empty($_SESSION['is_mobile']) ? 'смартфон' : 'компьютер' ?></dd>
        </dl>
      </div>
      <div class="set-card">
        <div class="set-row">
          <div class="grow"><div class="set-lbl">Корзина</div><div class="set-hint">Очистить удалённые файлы</div></div>
          <button type="button" class="set-btn danger" id="set-empty-trash<?echo $AppID?>">Очистить</button>
        </div>
        <div class="set-row">
          <div class="grow"><div class="set-lbl">Сбросить оформление</div><div class="set-hint">Акцент, рамка и размеры окон — по умолчанию</div></div>
          <button type="button" class="set-btn" id="set-reset-look<?echo $AppID?>">Сбросить</button>
        </div>
      </div>
    </section>

    <section class="set-pane" data-pane="legacy"><div id="set-legacy<?echo $AppID?>"></div></section>
  </div>

  <div class="set-toast" id="set-toast<?echo $AppID?>"></div>
</div>

<script>
(function(){
  var id = '<?echo $AppID?>';
  var root = document.getElementById('set' + id);
  var TOKEN = <?echo json_encode($_SESSION['fo_settings_csrf']);?>;
  var FOLDER = <?echo json_encode($Folder);?>;
  var ACCENT = <?echo json_encode($accent);?>;
  var toastTimer = null;

  function el(n){ return document.getElementById(n + id); }
  function toast(msg){
    var t = el('set-toast');
    if(!t) return;
    t.textContent = msg;
    t.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ t.classList.remove('is-on'); }, 2200);
  }
  function post(url, data){
    var body = new URLSearchParams();
    Object.keys(data).forEach(function(k){ body.append(k, data[k]); });
    return fetch(url, {method:'POST', credentials:'same-origin', body: body}).then(function(r){ return r.json().catch(function(){ return {ok:false}; }); });
  }
  function save(data){
    data.token = TOKEN;
    return post('system/core/functions/settings-save.php', data);
  }

  /* tabs */
  root.querySelectorAll('.set-nav').forEach(function(btn){
    btn.addEventListener('click', function(){
      var pane = btn.getAttribute('data-nav');
      var legacy = btn.getAttribute('data-legacy');
      root.querySelectorAll('.set-nav').forEach(function(b){ b.classList.toggle('is-on', b === btn); });
      root.querySelectorAll('.set-pane').forEach(function(p){ p.classList.remove('is-on'); });
      if(legacy){
        var box = root.querySelector('[data-pane="legacy"]');
        box.classList.add('is-on');
        var pane = box.querySelector('#set-legacy' + id);
        pane.innerHTML = '<div class="set-hint">Загрузка…</div>';
        fetch(FOLDER + legacy + '.php?appid=' + id + '&appname=Settings&destination=' + encodeURIComponent(FOLDER), {credentials:'same-origin'})
          .then(function(r){ return r.text(); })
          .then(function(html){
            // jQuery executes the inline scripts of the fragment
            if(window.jQuery){ window.jQuery(pane).html(html); } else { pane.innerHTML = html; }
          })
          .catch(function(){ pane.innerHTML = '<div class="set-hint">Не удалось открыть раздел</div>'; });
        return;
      }
      root.querySelector('[data-pane="' + pane + '"]').classList.add('is-on');
    });
  });

  /* appearance */
  function applyAccent(color){
    ACCENT = color;
    document.documentElement.style.setProperty('--fo-accent', color);
    root.style.setProperty('--set-accent', color);
    root.querySelectorAll('.set-sw').forEach(function(s){
      s.classList.toggle('is-on', s.getAttribute('data-color').toLowerCase() === color.toLowerCase());
    });
  }
  function saveAppearance(frame){
    return post('system/core/functions/appearance.php', { accent: ACCENT, frame: frame ? 1 : 0 })
      .then(function(res){ if(res && res.ok === false){ toast('Не удалось сохранить'); } });
  }
  var frameOn = <?echo $frame ? 'true' : 'false';?>;

  root.querySelectorAll('.set-sw').forEach(function(s){
    s.addEventListener('click', function(){
      applyAccent(s.getAttribute('data-color'));
      el('set-accent').value = ACCENT;
      saveAppearance(frameOn).then(function(){ toast('Акцент сохранён'); });
    });
  });
  el('set-accent').addEventListener('input', function(){
    applyAccent(this.value);
    saveAppearance(frameOn);
  });
  root.querySelectorAll('[data-frame]').forEach(function(b){
    b.addEventListener('click', function(){
      frameOn = b.getAttribute('data-frame') === '1';
      root.querySelectorAll('[data-frame]').forEach(function(x){ x.classList.toggle('is-on', x === b); });
      document.documentElement.setAttribute('data-fo-frame', frameOn ? 'accent' : 'plain');
      saveAppearance(frameOn).then(function(){ toast(frameOn ? 'Рамка включена' : 'Рамка выключена'); });
    });
  });

  /* window chrome */
  function applyChrome(ctrl, size, btn){
    document.querySelectorAll('.dragwindow').forEach(function(bar){
      bar.classList.toggle('controls-left', ctrl === '2');
    });
    document.documentElement.style.setProperty('--fo-winsize', size);
    document.documentElement.style.setProperty('--fo-winbtn', btn + 'px');
  }
  function saveChrome(){
    var on = root.querySelector('[data-ctrl].is-on') || root.querySelector('[data-ctrl]');
    var ctrl = on ? on.getAttribute('data-ctrl') : '1';
    var size = parseInt(el('set-wsize').value, 10);
    var btn = parseInt(el('set-bsize').value, 10);
    applyChrome(ctrl, size, btn);
    post('system/core/functions/window-settings.php', { controlmode: ctrl, windowsize: size, buttonsize: btn })
      .then(function(res){ if(res && res.ok === false){ toast('Не удалось сохранить'); } else { toast('Оформление окна сохранено'); } });
  }
  root.querySelectorAll('[data-ctrl]').forEach(function(b){
    b.addEventListener('click', function(){
      root.querySelectorAll('[data-ctrl]').forEach(function(x){ x.classList.toggle('is-on', x === b); });
      saveChrome();
    });
  });
  el('set-wsize').addEventListener('input', function(){ el('set-wsize-val').textContent = this.value; });
  el('set-wsize').addEventListener('change', saveChrome);
  el('set-bsize').addEventListener('input', function(){ el('set-bsize-val').textContent = this.value; });
  el('set-bsize').addEventListener('change', saveChrome);

  /* dock */
  function renderDock(){
    fetch('system/core/functions/dock-list.php', {credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if(!d || !d.ok) return;
        var box = el('set-dock');
        box.innerHTML = d.apps.map(function(a){
          return '<div class="set-item" data-app="' + a.app + '">' +
            '<span class="set-ico" style="background-image:url(system/apps/' + a.app + '/app.png)"></span>' +
            '<span class="grow">' + a.title + '</span>' +
            '<button type="button" class="set-btn danger" data-unpin="' + a.app + '">Убрать</button></div>';
        }).join('') || '<div class="set-hint">В доке пока ничего нет</div>';
      });
  }
  el('set-dock-pin').addEventListener('click', function(){
    var app = el('set-dock-add').value;
    post('system/core/functions/dock-edit.php', { action:'pin', app: app }).then(function(d){
      toast(d && d.ok ? 'Закреплено: ' + app : 'Не удалось закрепить');
      renderDock();
    });
  });
  root.addEventListener('click', function(e){
    var u = e.target.closest ? e.target.closest('[data-unpin]') : null;
    if(u){
      var app = u.getAttribute('data-unpin');
      post('system/core/functions/dock-edit.php', { action:'unpin', app: app }).then(function(){
        toast('Убрано из дока');
        renderDock();
      });
    }
  });

  /* autorun */
  el('set-autorun-save').addEventListener('click', function(){
    var apps = [];
    root.querySelectorAll('[data-pane="autorun"] input[type=checkbox]').forEach(function(c){
      if(c.checked) apps.push(c.getAttribute('data-app'));
    });
    save({ action:'autorun', apps: apps.join(',') }).then(function(d){
      toast(d && d.ok ? 'Автозапуск сохранён' : 'Не удалось сохранить');
    });
  });

  /* language */
  root.querySelectorAll('[data-lang]').forEach(function(b){
    b.addEventListener('click', function(){
      var lang = b.getAttribute('data-lang');
      root.querySelectorAll('[data-lang]').forEach(function(x){ x.classList.toggle('is-on', x === b); });
      save({ action:'locale', locale: lang }).then(function(d){
        toast(d && d.ok ? 'Язык сохранён' : 'Не удалось сохранить');
      });
    });
  });
  el('set-zone').addEventListener('change', function(){
    save({ action:'timezone', timezone: this.value }).then(function(d){
      toast(d && d.ok ? 'Часовой пояс сохранён' : 'Не удалось сохранить');
    });
  });

  /* trash */
  el('set-empty-trash').addEventListener('click', function(){
    if(!confirm('Очистить корзину? Это необратимо.')) return;
    save({ action:'emptrash' }).then(function(d){
      toast(d && d.ok ? 'Корзина очищена' : 'Не удалось очистить');
    });
  });

  /* dark mode */
  root.querySelectorAll('[data-dark]').forEach(function(b){
    b.addEventListener('click', function(){
      var on = b.getAttribute('data-dark') === '1';
      root.querySelectorAll('[data-dark]').forEach(function(x){ x.classList.toggle('is-on', x === b); });
      document.documentElement.setAttribute('data-fo-dark', on ? '1' : '0');
      save({ action:'dark', value: on ? '1' : '0' }).then(function(d){
        toast(d && d.ok ? (on ? 'Тёмная тема включена' : 'Тёмная тема выключена') : 'Не удалось сохранить');
      });
    });
  });

  /* themes */
  function applyThemeVars(t){
    var rootEl = document.documentElement;
    if(t.accent){ rootEl.style.setProperty('--fo-accent', t.accent); }
    if(t.topbar){ rootEl.style.setProperty('--fo-theme-topbar', t.topbar); }
    if(t.topbarText){ rootEl.style.setProperty('--fo-theme-text', t.topbarText); }
    if(t.menu){ rootEl.style.setProperty('--fo-theme-menu', t.menu); }
    if(t.shellBase){ rootEl.style.setProperty('--fo-shell-base', t.shellBase); }
    if(t.accent){ applyAccent(t.accent); }
  }
  root.querySelectorAll('[data-theme]').forEach(function(b){
    b.addEventListener('click', function(){
      var file = b.getAttribute('data-theme');
      root.querySelectorAll('[data-theme]').forEach(function(x){ x.classList.toggle('is-on', x === b); });
      post('system/core/functions/theme-set.php', { theme: file }).then(function(t){
        if(t && t.ok){ applyThemeVars(t); toast('Тема: ' + t.name); }
        else { toast('Не удалось применить тему'); }
      });
    });
  });

  /* reset look & feel */
  el('set-reset-look').addEventListener('click', function(){
    applyAccent('#5fe08c');
    el('set-accent').value = '#5fe08c';
    frameOn = false;
    document.documentElement.setAttribute('data-fo-frame', 'plain');
    root.querySelectorAll('[data-frame]').forEach(function(x){ x.classList.toggle('is-on', x.getAttribute('data-frame') === '0'); });
    el('set-wsize').value = 8; el('set-wsize-val').textContent = '8';
    el('set-bsize').value = 12; el('set-bsize-val').textContent = '12';
    root.querySelectorAll('[data-ctrl]').forEach(function(x){ x.classList.toggle('is-on', x.getAttribute('data-ctrl') === '1'); });
    applyChrome('1', 8, 12);
    saveAppearance(false);
    post('system/core/functions/window-settings.php', { controlmode: '1', windowsize: 8, buttonsize: 12 })
      .then(function(){ toast('Оформление сброшено'); });
  });

  applyChrome(<?echo json_encode($ctrlMode)?>, <?echo $winSize?>, <?echo $btnSize?>);
})();
</script>

<?
$AppContainer->EndContainer();
?>
