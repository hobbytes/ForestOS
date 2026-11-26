<?
/* Settings → Пользователи (единый дизайн) */
if (!isset($_SESSION)) { session_start(); }

$DOC = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$me = (string)($_SESSION['loginuser'] ?? '');

require __DIR__ . '/_i18n.php';
list($T, $lc) = fo_settings_lang('users');

/* страница меняет пароли и учётки: только для вошедшего администратора */
if (empty($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true || $me === '') {
    http_response_code(403);
    echo '<div class="set-hint" style="color:#a8332a">' . htmlspecialchars($T('no_access')) . '</div>';
    exit;
}

$isSuper = ($me === ($_SESSION['superuser'] ?? ''));
$token = (string)($_SESSION['fo_settings_csrf'] ?? '');
if ($token === '') {
    $token = bin2hex(random_bytes(16));
    $_SESSION['fo_settings_csrf'] = $token;
}

require_once $DOC . '/system/core/library/bd.php';
require_once $DOC . '/system/core/library/gui.php';
require_once $DOC . '/system/core/library/filesystem.php';
require_once $DOC . '/system/core/library/etc/security.php';

/* учётку можно заблокировать: колонка появляется на существующей базе */
try {
    readbd::addColumn('forestusers', 'blocked', 'TINYINT', '1');
} catch (Throwable $e) {
}

$security = new security();
$gui = new gui();
$files = new fileaction();

function su_out(string $error = ''): void
{
    echo '<div class="set-hint" style="color:#a8332a">' . htmlspecialchars($error) . '</div>';
}

function su_users(): array
{
    try {
        $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $rows = $pdo->query('SELECT login, status, fuid, blocked FROM forestusers ORDER BY login')->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : array();
    } catch (Throwable $e) {
        try {
            $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
            $rows = $pdo->query('SELECT login, status, fuid FROM forestusers ORDER BY login')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $i => $r) { $rows[$i]['blocked'] = 0; }
            return is_array($rows) ? $rows : array();
        } catch (Throwable $e2) {
            return array();
        }
    }
}

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$user = isset($_GET['user']) ? preg_replace('/[^a-z0-9_]/', '', strtolower((string)$_GET['user'])) : '';
$pass = isset($_GET['pass']) ? (string)$_GET['pass'] : '';
$msg = '';

if ($action !== '' && (!isset($_GET['token']) || !hash_equals($token, (string)$_GET['token']))) {
    $msg = $T('msg_session_expired');
    $action = '';
}

if ($action !== '' && $isSuper) {
    $pdo = null;
    try {
        $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    } catch (Throwable $e) {
        $msg = $T('msg_db_fail');
    }

    if ($pdo && $action === 'add' && $user !== '' && $pass !== '') {
        if (!preg_match('/^[a-z0-9_]{2,40}$/', $user)) {
            $msg = $T('msg_login_format');
        } elseif (strlen($pass) < 4) {
            $msg = $T('msg_pass_short');
        } else {
            try {
                $hash = $security->crypt_s($pass, $user);
                $fuid = strtoupper(md5($user . $pass . $hash . date('d.m.y,H:i:s') . mt_rand()));
                $st = $pdo->prepare('INSERT INTO forestusers (login, password, fuid, status) VALUES (?,?,?,?)');
                $st->execute(array($user, $hash, $fuid, 'normaluser'));
                $home = $DOC . '/system/users/' . $user;
                foreach (array('', '/desktop', '/trash', '/settings', '/settings/etc', '/documents') as $sub) {
                    if (!is_dir($home . $sub)) { @mkdir($home . $sub, 0775, true); }
                }
                $walls = glob($DOC . '/system/core/design/walls/*.jpg');
                if (!empty($walls[0])) { @copy($walls[0], $home . '/settings/etc/wall.jpg'); }
                @file_put_contents($home . '/settings/language.foc', $_SESSION['locale'] ?? 'ru');
                @file_put_contents($home . '/desktop/trash.link',
                    "[link]\ndestination=system/apps/Explorer/main.php\nfile=main\nkey=dir\nparam=system/users/$user/trash\nname=trash\nlinkname=" . $T('trash_link_name') . "\nicon=system/apps/Explorer/assets/trashicon.png");
                foreach (glob($home . '/*') as $f) { @chmod($f, 0777); }
                $msg = str_replace('{user}', $user, $T('msg_created'));
            } catch (Throwable $e) {
                $msg = $T('msg_create_fail');
            }
        }
    }

    if ($pdo && $action === 'delete' && $user !== '' && $user !== $me) {
        $st = $pdo->prepare('SELECT status FROM forestusers WHERE login = ?');
        $st->execute(array($user));
        $status = (string)$st->fetchColumn();
        if ($status === 'superuser') {
            $msg = $T('msg_cant_delete_admin');
        } else {
            $pdo->prepare('DELETE FROM forestusers WHERE login = ?')->execute(array($user));
            $dir = $DOC . '/system/users/' . $user;
            if (is_dir($dir)) { $files->deleteDir($dir); }
            $msg = $T('msg_deleted');
        }
    }

    if ($pdo && $action === 'super' && $user !== '' && $user !== $me) {
        $st = $pdo->prepare('SELECT status FROM forestusers WHERE login = ?');
        $st->execute(array($user));
        $status = (string)$st->fetchColumn();
        $new = ($status === 'superuser') ? 'normaluser' : 'superuser';
        $pdo->prepare('UPDATE forestusers SET status = ? WHERE login = ?')->execute(array($new, $user));
        $msg = $new === 'superuser' ? $T('msg_rights_given') : $T('msg_rights_removed');
    }

    if ($pdo && $action === 'pass' && $user !== '' && $pass !== '') {
        $hash = $security->crypt_s($pass, $user);
        $pdo->prepare('UPDATE forestusers SET password = ? WHERE login = ?')->execute(array($hash, $user));
        $msg = $T('msg_password_updated');
    }

    if ($pdo && $action === 'block' && $user !== '' && $user !== $me) {
        $st = $pdo->prepare('SELECT COALESCE(blocked,0) FROM forestusers WHERE login = ?');
        $st->execute(array($user));
        $cur = (int)$st->fetchColumn();
        $new = $cur === 1 ? 0 : 1;
        $pdo->prepare('UPDATE forestusers SET blocked = ? WHERE login = ?')->execute(array($new, $user));
        $msg = $new ? $T('msg_blocked') : $T('msg_unblocked');
    }
}

$users = su_users();
$selected = isset($_GET['select']) ? preg_replace('/[^a-z0-9_]/', '', strtolower((string)$_GET['select'])) : '';
$current = null;
foreach ($users as $u) {
    if ($u['login'] === $selected) { $current = $u; break; }
}
$q = 'appid=' . urlencode((string)($_GET['appid'] ?? '')) . '&appname=Settings&destination=' . urlencode((string)($_GET['destination'] ?? ''));
?>

<h2 class="set-h"><? echo htmlspecialchars($T('title')) ?></h2>
<p class="set-sub"><? echo htmlspecialchars($isSuper ? $T('subtitle_super') : $T('subtitle_self')) ?></p>

<? if ($msg !== ''): ?>
  <div class="set-card" style="border-color:color-mix(in srgb, var(--set-accent) 40%, #e6e7ea); background:color-mix(in srgb, var(--set-accent) 10%, #fff)">
    <div class="set-lbl"><? echo htmlspecialchars($msg) ?></div>
  </div>
<? endif; ?>

<div class="set-card">
  <div class="set-list">
    <? foreach ($users as $u): ?>
      <? if (!$isSuper && $u['login'] !== $me) { continue; } ?>
      <div class="set-item">
        <span class="set-ico" style="display:flex;align-items:center;justify-content:center;font-weight:700;background:<? echo $u['login'] === $me ? 'var(--set-accent)' : '#e9ebef' ?>;color:#0b160f">
          <? echo htmlspecialchars(strtoupper(substr($u['login'], 0, 1))) ?>
        </span>
        <span class="grow">
          <span class="set-lbl"><? echo htmlspecialchars(str_replace('_', ' ', $u['login'])) ?></span>
          <span class="set-hint">
            <? echo htmlspecialchars($u['status'] === 'superuser' ? $T('role_admin') : $T('role_user')) ?>
            <? echo !empty($u['blocked']) ? ' · ' . htmlspecialchars($T('badge_blocked')) : '' ?>
            <? echo $u['login'] === $me ? ' · ' . htmlspecialchars($T('badge_you')) : '' ?>
          </span>
        </span>
        <? if ($isSuper && $u['login'] !== $me): ?>
          <button type="button" class="set-btn" data-user-select="<? echo htmlspecialchars($u['login']) ?>"><? echo htmlspecialchars($T('open')) ?></button>
        <? endif; ?>
      </div>
    <? endforeach; ?>
  </div>
</div>

<? if ($isSuper && $current !== null): ?>
  <div class="set-card">
    <div class="set-lbl"><? echo htmlspecialchars(str_replace('_', ' ', $current['login'])) ?></div>
    <div class="set-hint" style="margin-bottom:10px">FUID: <? echo htmlspecialchars((string)$current['fuid']) ?></div>

    <div class="set-row" style="border-top:0;padding-top:0;margin-top:0">
      <div class="grow">
        <div class="set-lbl"><? echo htmlspecialchars($T('new_password')) ?></div>
        <div class="set-hint"><? echo htmlspecialchars($T('new_password_hint')) ?></div>
      </div>
      <input class="set-btn" id="su-pass" type="password" placeholder="<? echo htmlspecialchars($T('ph_new_password')) ?>" style="width:170px;height:32px">
      <button type="button" class="set-btn primary" data-user-pass="<? echo htmlspecialchars($current['login']) ?>"><? echo htmlspecialchars($T('change')) ?></button>
    </div>

    <div class="set-row">
      <div class="grow">
        <div class="set-lbl"><? echo htmlspecialchars($T('admin_rights')) ?></div>
        <div class="set-hint"><? echo htmlspecialchars($T('admin_rights_hint')) ?></div>
      </div>
      <button type="button" class="set-btn" data-user-super="<? echo htmlspecialchars($current['login']) ?>">
        <? echo htmlspecialchars($current['status'] === 'superuser' ? $T('revoke') : $T('grant')) ?>
      </button>
    </div>

    <div class="set-row">
      <div class="grow">
        <div class="set-lbl"><? echo htmlspecialchars($T('access')) ?></div>
        <div class="set-hint"><? echo htmlspecialchars($T('access_hint')) ?></div>
      </div>
      <button type="button" class="set-btn" data-user-block="<? echo htmlspecialchars($current['login']) ?>">
        <? echo htmlspecialchars(!empty($current['blocked']) ? $T('unblock') : $T('block')) ?>
      </button>
    </div>

    <div class="set-row">
      <div class="grow">
        <div class="set-lbl"><? echo htmlspecialchars($T('delete_account')) ?></div>
        <div class="set-hint"><? echo htmlspecialchars($T('delete_account_hint')) ?></div>
      </div>
      <button type="button" class="set-btn danger" data-user-delete="<? echo htmlspecialchars($current['login']) ?>"><? echo htmlspecialchars($T('delete')) ?></button>
    </div>
  </div>
<? endif; ?>

<? if ($isSuper): ?>
  <div class="set-card">
    <div class="set-lbl"><? echo htmlspecialchars($T('new_user')) ?></div>
    <div class="set-row" style="margin-top:10px;border-top:0;padding-top:0">
      <input class="set-btn grow" id="su-login" placeholder="<? echo htmlspecialchars($T('ph_login')) ?>" style="height:32px">
      <input class="set-btn" id="su-newpass" type="password" placeholder="<? echo htmlspecialchars($T('ph_password')) ?>" style="height:32px;width:150px">
      <button type="button" class="set-btn primary" id="su-add"><? echo htmlspecialchars($T('create')) ?></button>
    </div>
  </div>
<? endif; ?>

<script>
var FO_USR_I18N = <? echo fo_settings_js($T, array('confirm_delete')) ?>;
(function(){
  var T = FO_USR_I18N;
  var base = <? echo json_encode('system/apps/Settings/users.php?' . $q) ?>;
  var token = <? echo json_encode($token) ?>;
  var pane = document.getElementById('set-legacy' + <? echo json_encode((string)($_GET['appid'] ?? '')) ?>);
  function go(extra){
    fetch(base + '&token=' + encodeURIComponent(token) + extra, {credentials:'same-origin'})
      .then(function(r){ return r.text(); })
      .then(function(html){
        if(!pane) return;
        if(window.jQuery){ window.jQuery(pane).html(html); } else { pane.innerHTML = html; }
      });
  }
  document.querySelectorAll('[data-user-select]').forEach(function(b){
    b.addEventListener('click', function(){ go('&select=' + encodeURIComponent(b.getAttribute('data-user-select'))); });
  });
  var add = document.getElementById('su-add');
  if(add){
    add.addEventListener('click', function(){
      var l = document.getElementById('su-login').value.trim().toLowerCase().replace(/\s+/g, '_');
      var p = document.getElementById('su-newpass').value;
      if(!l || !p) return;
      go('&action=add&user=' + encodeURIComponent(l) + '&pass=' + encodeURIComponent(p));
    });
  }
  document.querySelectorAll('[data-user-super]').forEach(function(b){
    b.addEventListener('click', function(){ go('&action=super&user=' + encodeURIComponent(b.getAttribute('data-user-super'))); });
  });
  document.querySelectorAll('[data-user-block]').forEach(function(b){
    b.addEventListener('click', function(){ go('&action=block&user=' + encodeURIComponent(b.getAttribute('data-user-block'))); });
  });
  document.querySelectorAll('[data-user-delete]').forEach(function(b){
    b.addEventListener('click', function(){
      if(!confirm(T.confirm_delete)) return;
      go('&action=delete&user=' + encodeURIComponent(b.getAttribute('data-user-delete')));
    });
  });
  document.querySelectorAll('[data-user-pass]').forEach(function(b){
    b.addEventListener('click', function(){
      var p = document.getElementById('su-pass').value;
      if(!p) return;
      go('&action=pass&user=' + encodeURIComponent(b.getAttribute('data-user-pass')) + '&pass=' + encodeURIComponent(p));
    });
  });
})();
</script>
