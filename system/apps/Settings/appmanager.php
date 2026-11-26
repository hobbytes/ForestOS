<?
/* Settings → Приложения (единый дизайн) */
if (!isset($_SESSION)) { session_start(); }

$DOC = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$me = (string)$_SESSION['loginuser'];
$isSuper = ($me === ($_SESSION['superuser'] ?? ''));
$token = (string)($_SESSION['fo_settings_csrf'] ?? '');
$appid = (string)($_GET['appid'] ?? '');

require __DIR__ . '/_i18n.php';
list($T, $lc) = fo_settings_lang('appmanager');

require_once $DOC . '/system/core/library/filesystem.php';
$files = new fileaction();

$protected = array('Explorer', 'Settings', 'Apps_House', 'update');
$msg = '';
$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$app = isset($_GET['app']) ? preg_replace('/[^A-Za-z0-9_.\-]/', '', (string)$_GET['app']) : '';

if ($action !== '' && ($_GET['token'] ?? '') !== $token) {
    $msg = $T('msg_session_expired');
    $action = '';
}

if ($isSuper && $action === 'delete' && $app !== '' && !in_array($app, $protected, true)) {
    $dir = $DOC . '/system/apps/' . $app;
    if (is_dir($dir)) {
        $files->deleteDir($dir);
        $msg = str_replace('{app}', $app, $T('msg_deleted'));
    } else {
        $msg = $T('msg_not_found');
    }
}

$apps = array();
foreach (glob($DOC . '/system/apps/*/main.php') as $f) {
    $slug = basename(dirname($f));
    $dir = dirname($f);
    $size = 0;
    foreach (glob($dir . '/*') as $file) {
        if (is_file($file)) { $size += (int)@filesize($file); }
    }
    $apps[] = array(
        'slug' => $slug,
        'title' => str_replace('_', ' ', $slug),
        'size' => $size,
        'protected' => in_array($slug, $protected, true),
        'icon' => is_file($dir . '/app.png') ? 'system/apps/' . $slug . '/app.png' : 'system/core/design/images/app.png',
    );
}
usort($apps, function ($a, $b) { return strcasecmp($a['title'], $b['title']); });
?>

<h2 class="set-h"><? echo htmlspecialchars($T('title')) ?></h2>
<p class="set-sub"><? echo htmlspecialchars($T('subtitle')) ?></p>

<? if ($msg !== ''): ?>
  <div class="set-card" style="border-color:color-mix(in srgb, var(--set-accent) 40%, #e6e7ea); background:color-mix(in srgb, var(--set-accent) 10%, #fff)">
    <div class="set-lbl"><? echo htmlspecialchars($msg) ?></div>
  </div>
<? endif; ?>

<div class="set-card">
  <div class="set-list">
    <? foreach ($apps as $a): ?>
      <div class="set-item">
        <span class="set-ico" style="background-image:url(<? echo htmlspecialchars($a['icon']) ?>)"></span>
        <span class="grow">
          <span class="set-lbl"><? echo htmlspecialchars($a['title']) ?></span>
          <span class="set-hint">
            <? echo htmlspecialchars($a['slug']) ?> · <? echo $a['size'] > 1048576 ? round($a['size'] / 1048576, 1) . ' ' . htmlspecialchars($T('unit_mb')) : round($a['size'] / 1024) . ' ' . htmlspecialchars($T('unit_kb')) ?>
            <? echo $a['protected'] ? ' · ' . htmlspecialchars($T('system_badge')) : '' ?>
          </span>
        </span>
        <button type="button" class="set-btn" data-app-open="<? echo htmlspecialchars($a['slug']) ?>"><? echo htmlspecialchars($T('open')) ?></button>
        <? if ($isSuper && !$a['protected']): ?>
          <button type="button" class="set-btn danger" data-app-delete="<? echo htmlspecialchars($a['slug']) ?>"><? echo htmlspecialchars($T('delete')) ?></button>
        <? endif; ?>
      </div>
    <? endforeach; ?>
  </div>
</div>

<script>
var FO_APP_I18N = <? echo fo_settings_js($T, array('confirm_delete')) ?>;
(function(){
  var T = FO_APP_I18N;
  var pane = document.getElementById('set-legacy' + <? echo json_encode($appid) ?>);
  var base = <? echo json_encode('system/apps/Settings/appmanager.php?appid=' . $appid . '&appname=Settings&destination=' . urlencode((string)($_GET['destination'] ?? ''))) ?>;
  var token = <? echo json_encode($token) ?>;
  function go(extra){
    fetch(base + '&token=' + encodeURIComponent(token) + extra, {credentials:'same-origin'})
      .then(function(r){ return r.text(); })
      .then(function(html){
        if(!pane) return;
        if(window.jQuery){ window.jQuery(pane).html(html); } else { pane.innerHTML = html; }
      });
  }
  document.querySelectorAll('[data-app-open]').forEach(function(b){
    b.addEventListener('click', function(){
      var app = b.getAttribute('data-app-open');
      if(window.makeprocess){ window.makeprocess('system/apps/' + app + '/main.php', '', '', app); }
    });
  });
  document.querySelectorAll('[data-app-delete]').forEach(function(b){
    b.addEventListener('click', function(){
      var app = b.getAttribute('data-app-delete');
      if(!confirm(T.confirm_delete.replace('{app}', app))) return;
      go('&action=delete&app=' + encodeURIComponent(app));
    });
  });
})();
</script>
