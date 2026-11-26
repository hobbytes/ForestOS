<?
/* Settings — Security */
if (!isset($_SESSION)) { session_start(); }

$DOC = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$me = (string)$_SESSION['loginuser'];
$token = (string)($_SESSION['fo_settings_csrf'] ?? '');
$appid = (string)($_GET['appid'] ?? '');
$msg = '';

require __DIR__ . '/_i18n.php';
list($T, $lc) = fo_settings_lang('security');

require_once $DOC . '/system/core/library/bd.php';
require_once $DOC . '/system/core/library/etc/security.php';
$security = new security();

/* TempKey column may not exist on a fresh database */
try {
    readbd::addColumn('forestusers', 'TempKey', 'VARCHAR', '255');
} catch (Throwable $e) {
}

function sec_pass(string $login): string
{
    try {
        $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $st = $pdo->prepare('SELECT password FROM forestusers WHERE login = ?');
        $st->execute(array($login));
        return (string)$st->fetchColumn();
    } catch (Throwable $e) {
        return '';
    }
}

function sec_keys(string $login): array
{
    try {
        $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        $st = $pdo->prepare('SELECT TempKey FROM forestusers WHERE login = ?');
        $st->execute(array($login));
        $raw = (string)$st->fetchColumn();
    } catch (Throwable $e) {
        return array();
    }
    $keys = array();
    if (preg_match_all('/\[([^\]]+)\]/', $raw, $m)) {
        foreach ($m[1] as $k) {
            if ($k !== '') { $keys[] = $k; }
        }
    }
    return $keys;
}

function sec_set_keys(string $login, array $keys): void
{
    $raw = '';
    foreach ($keys as $k) { $raw .= '[' . $k . ']'; }
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $pdo->prepare('UPDATE forestusers SET TempKey = ? WHERE login = ?')->execute(array($raw, $login));
}

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
if ($action !== '' && ($_GET['token'] ?? '') !== $token) {
    $msg = $T('msg_session_expired');
    $action = '';
}

if ($action === 'key') {
    $name = preg_replace('/[^a-z0-9_\-]/i', '', (string)($_GET['name'] ?? ''));
    if ($name === '') { $name = 'fos'; }
    $key = uniqid($name . '-', true);
    $keys = sec_keys($me);
    $keys[] = $key;
    try {
        sec_set_keys($me, $keys);
        $msg = $T('msg_key_created') . ' ' . $key;
    } catch (Throwable $e) {
        $msg = $T('msg_key_save_fail');
    }
}

if ($action === 'keydel') {
    $key = (string)($_GET['key'] ?? '');
    $keys = array_values(array_filter(sec_keys($me), function ($k) use ($key) { return $k !== $key; }));
    try {
        sec_set_keys($me, $keys);
        $msg = $T('msg_key_deleted');
    } catch (Throwable $e) {
        $msg = $T('msg_key_delete_fail');
    }
}

if ($action === 'keyclear') {
    try {
        sec_set_keys($me, array());
        $msg = $T('msg_keys_cleared');
    } catch (Throwable $e) {
        $msg = $T('msg_keys_clear_fail');
    }
}

if ($action === 'password') {
    $old = (string)($_GET['old'] ?? '');
    $new = (string)($_GET['new'] ?? '');
    $repeat = (string)($_GET['repeat'] ?? '');
    $stored = sec_pass($me);
    if ($stored === '') {
        $msg = $T('msg_account_read_fail');
    } elseif (!hash_equals($stored, $security->crypt_s($old, $me))) {
        $msg = $T('msg_old_wrong');
    } elseif (strlen($new) < 4) {
        $msg = $T('msg_new_short');
    } elseif ($new !== $repeat) {
        $msg = $T('msg_mismatch');
    } else {
        try {
            $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
            $pdo->prepare('UPDATE forestusers SET password = ? WHERE login = ?')
                ->execute(array($security->crypt_s($new, $me), $me));
            $msg = $T('msg_password_changed');
        } catch (Throwable $e) {
            $msg = $T('msg_password_fail');
        }
    }
}

if ($action === 'journal' && $me === ($_SESSION['superuser'] ?? '')) {
    $journal = $DOC . '/system/core/journal.mcj';
    if (is_file($journal)) {
        @file_put_contents($journal, '');
        @chmod($journal, 0666);
        $msg = $T('msg_journal_cleared');
    } else {
        $msg = $T('msg_journal_missing');
    }
}

$journalSize = is_file($DOC . '/system/core/journal.mcj') ? (int)@filesize($DOC . '/system/core/journal.mcj') : 0;
$keys = sec_keys($me);
?>

<h2 class="set-h"><? echo htmlspecialchars($T('title')) ?></h2>
<p class="set-sub"><? echo htmlspecialchars($T('subtitle')) ?></p>

<? if ($msg !== ''): ?>
  <div class="set-card" style="border-color:color-mix(in srgb, var(--set-accent) 40%, #e6e7ea); background:color-mix(in srgb, var(--set-accent) 10%, #fff)">
    <div class="set-lbl"><? echo htmlspecialchars($msg) ?></div>
  </div>
<? endif; ?>

<div class="set-card">
  <div class="set-lbl"><? echo htmlspecialchars($T('password_title')) ?></div>
  <div class="set-row" style="margin-top:10px;border-top:0;padding-top:0">
    <input class="set-btn grow" id="sec-old" type="password" placeholder="<? echo htmlspecialchars($T('ph_current')) ?>" style="height:32px">
  </div>
  <div class="set-row" style="border-top:0;padding-top:8px;margin-top:0">
    <input class="set-btn grow" id="sec-new" type="password" placeholder="<? echo htmlspecialchars($T('ph_new')) ?>" style="height:32px">
    <input class="set-btn grow" id="sec-repeat" type="password" placeholder="<? echo htmlspecialchars($T('ph_repeat')) ?>" style="height:32px">
  </div>
  <div class="set-row" style="border-top:0;padding-top:10px;margin-top:0">
    <button type="button" class="set-btn primary" id="sec-save"><? echo htmlspecialchars($T('change_button')) ?></button>
  </div>
</div>

<?
require_once $DOC . '/system/core/library/webauthn.php';
$waAllowed = ForestWebAuthn::allowed($me);
$waAvailable = ForestWebAuthn::available();
$waKeys = $waAllowed ? ForestWebAuthn::listKeys($me) : array();
?>
<div class="set-card" id="sec-faceid-card">
  <div class="set-row" style="border-top:0;padding-top:0;margin-top:0">
    <div class="grow">
      <div class="set-lbl"><? echo htmlspecialchars($T('faceid_title')) ?></div>
      <div class="set-hint">
        <? if (!$waAllowed): ?>
          <? echo htmlspecialchars($T('faceid_admin_only')) ?>
        <? elseif (!$waAvailable): ?>
          <? echo str_replace('{origin}', htmlspecialchars(ForestWebAuthn::origin()), $T('faceid_https')) ?>
        <? else: ?>
          <? echo htmlspecialchars($T('faceid_help')) ?>
        <? endif; ?>
      </div>
    </div>
    <? if ($waAllowed && $waAvailable): ?>
      <button type="button" class="set-btn primary" id="sec-faceid-add"><? echo htmlspecialchars($T('faceid_add')) ?></button>
    <? endif; ?>
  </div>
  <? if (count($waKeys)): ?>
    <div class="set-list" style="margin-top:10px">
      <? foreach ($waKeys as $k): ?>
        <div class="set-item">
          <span class="grow">
            <span class="set-lbl" style="display:block"><? echo htmlspecialchars((string)$k['label'] !== '' ? (string)$k['label'] : htmlspecialchars($T('device_default'))) ?></span>
            <span class="set-hint" style="display:block"><? echo htmlspecialchars($T('key_created_at')) ?> <? echo htmlspecialchars((string)$k['created_at']) ?><? echo !empty($k['last_used_at']) ? ' · ' . htmlspecialchars($T('key_last_used')) . ' ' . htmlspecialchars((string)$k['last_used_at']) : '' ?></span>
          </span>
          <button type="button" class="set-btn danger" data-faceid-del="<? echo (int)$k['id'] ?>"><? echo htmlspecialchars($T('delete')) ?></button>
        </div>
      <? endforeach; ?>
    </div>
  <? endif; ?>
  <div id="sec-faceid-msg" class="set-hint" style="margin-top:8px"></div>
</div>

<?
require_once $DOC . '/system/core/library/bans.php';
$isSuperUser = ($me === ($_SESSION['superuser'] ?? ''));
$bans = $isSuperUser ? ForestBans::active() : array();
?>
<? if ($isSuperUser): ?>
<div class="set-card" id="sec-bans-card">
  <div class="set-row" style="border-top:0;padding-top:0;margin-top:0">
    <div class="grow">
      <div class="set-lbl"><? echo htmlspecialchars($T('bans_title')) ?></div>
      <div class="set-hint"><? echo htmlspecialchars($T('bans_help')) ?></div>
    </div>
    <button type="button" class="set-btn danger" id="sec-bans-clear" style="<? echo count($bans) ? '' : 'display:none' ?>"><? echo htmlspecialchars($T('bans_clear_all')) ?></button>
  </div>
  <div class="set-list" id="sec-bans-list" style="margin-top:10px;<? echo count($bans) ? '' : 'display:none' ?>">
    <? foreach ($bans as $b): ?>
      <div class="set-item" data-ban-ip="<? echo htmlspecialchars($b['ip']) ?>">
        <span class="grow">
          <span class="set-lbl" style="display:block;font-family:ui-monospace,Consolas,monospace"><? echo htmlspecialchars($b['ip']) ?></span>
          <span class="set-hint" style="display:block">
            <? if ($b['banned']): ?>
              <? echo htmlspecialchars($T('banned_until')) ?> <? echo htmlspecialchars(ForestBans::untilText((int)$b['until'])) ?>
            <? else: ?>
              <? echo htmlspecialchars($T('failed_attempts')) ?>
            <? endif; ?>
            · <? echo htmlspecialchars($T('failures_label')) ?> <? echo (int)$b['failures'] ?>
            · <? echo htmlspecialchars($T('bans_count_label')) ?> <? echo (int)$b['bans'] ?>
            <? if ($b['last_login'] !== ''): ?> · <? echo htmlspecialchars($T('login_label')) ?> <? echo htmlspecialchars($b['last_login']) ?><? endif; ?>
          </span>
        </span>
        <button type="button" class="set-btn" data-ban-clear="<? echo htmlspecialchars($b['ip']) ?>"><? echo htmlspecialchars($T('unban')) ?></button>
      </div>
    <? endforeach; ?>
  </div>
  <div class="set-hint" id="sec-bans-empty" style="margin-top:8px;<? echo count($bans) ? 'display:none' : '' ?>"><? echo htmlspecialchars($T('bans_empty')) ?></div>
  <div id="sec-bans-msg" class="set-hint" style="margin-top:8px"></div>
</div>
<? endif; ?>

<div class="set-card">
  <div class="set-row" style="border-top:0;padding-top:0;margin-top:0">
    <div class="grow">
      <div class="set-lbl"><? echo htmlspecialchars($T('journal_title')) ?></div>
      <div class="set-hint"><? echo $journalSize > 0 ? round($journalSize / 1024, 1) . ' ' . htmlspecialchars($T('journal_size_unit')) : htmlspecialchars($T('journal_empty')) ?></div>
    </div>
    <? if ($me === ($_SESSION['superuser'] ?? '')): ?>
      <button type="button" class="set-btn danger" id="sec-journal"><? echo htmlspecialchars($T('clear')) ?></button>
    <? endif; ?>
  </div>
  <div class="set-row">
    <div class="grow">
      <div class="set-lbl"><? echo htmlspecialchars($T('keys_title')) ?></div>
      <div class="set-hint"><? echo htmlspecialchars($T('keys_help')) ?></div>
    </div>
    <? if (count($keys)): ?>
      <button type="button" class="set-btn danger" id="sec-keyclear"><? echo htmlspecialchars($T('delete_all')) ?></button>
    <? endif; ?>
  </div>

  <? if (count($keys)): ?>
    <div class="set-list" style="margin-top:10px">
      <? foreach ($keys as $k): ?>
        <div class="set-item">
          <span class="grow">
            <span class="set-lbl set-keyval" data-key="<? echo htmlspecialchars($k) ?>" data-shown="0"
                  style="font-family:ui-monospace,Consolas,monospace;font-size:11.5px;word-break:break-all">••••••••••••••••</span>
          </span>
          <button type="button" class="set-btn" data-key-copy="<? echo htmlspecialchars($k) ?>"><? echo htmlspecialchars($T('copy')) ?></button>
          <button type="button" class="set-btn" data-key-show="<? echo htmlspecialchars($k) ?>"><? echo htmlspecialchars($T('show')) ?></button>
          <button type="button" class="set-btn danger" data-key-del="<? echo htmlspecialchars($k) ?>"><? echo htmlspecialchars($T('delete')) ?></button>
        </div>
      <? endforeach; ?>
    </div>
  <? endif; ?>

  <div class="set-row" style="border-top:0;padding-top:10px;margin-top:0">
    <input class="set-btn grow" id="sec-keyname" placeholder="<? echo htmlspecialchars($T('key_name_ph')) ?>" style="height:32px">
    <button type="button" class="set-btn primary" id="sec-keyadd"><? echo htmlspecialchars($T('key_create')) ?></button>
  </div>
</div>

<script>
var FO_SEC_I18N = <? echo fo_settings_js($T, array('confirm_journal','confirm_key_del','confirm_keys_clear','copied','copy','show','hide','delete','device_default','key_created_at','key_last_used','delete_fail','faceid_need_https','faceid_waiting','faceid_done','key_added','faceid_cancelled','faceid_failed','error_word','confirm_revoke','bans_blocked_min','bans_confirm_unban','bans_unbanned','failed_attempts','failures_label','bans_count_label','login_label','unban','bans_error','bans_error_short','bans_confirm_clear','bans_cleared')) ?>;
var FO_SEC_DATELOCALE = <? echo json_encode($lc === 'en' ? 'en-GB' : 'ru-RU') ?>;
(function(){
  var T = FO_SEC_I18N;
  var pane = document.getElementById('set-legacy' + <? echo json_encode($appid) ?>);
  var base = <? echo json_encode('system/apps/Settings/security.php?appid=' . $appid . '&appname=Settings&destination=' . urlencode((string)($_GET['destination'] ?? ''))) ?>;
  var token = <? echo json_encode($token) ?>;
  function go(extra){
    fetch(base + '&token=' + encodeURIComponent(token) + extra, {credentials:'same-origin'})
      .then(function(r){ return r.text(); })
      .then(function(html){
        if(!pane) return;
        if(window.jQuery){ window.jQuery(pane).html(html); } else { pane.innerHTML = html; }
      });
  }
  var save = document.getElementById('sec-save');
  if(save){
    save.addEventListener('click', function(){
      go('&action=password&old=' + encodeURIComponent(document.getElementById('sec-old').value) +
         '&new=' + encodeURIComponent(document.getElementById('sec-new').value) +
         '&repeat=' + encodeURIComponent(document.getElementById('sec-repeat').value));
    });
  }
  var j = document.getElementById('sec-journal');
  if(j){
    j.addEventListener('click', function(){
      if(!confirm(T.confirm_journal)) return;
      go('&action=journal');
    });
  }
  var add = document.getElementById('sec-keyadd');
  if(add){
    add.addEventListener('click', function(){
      var n = (document.getElementById('sec-keyname').value || 'fos').trim();
      go('&action=key&name=' + encodeURIComponent(n));
    });
  }
  document.querySelectorAll('[data-key-del]').forEach(function(b){
    b.addEventListener('click', function(){
      if(!confirm(T.confirm_key_del)) return;
      go('&action=keydel&key=' + encodeURIComponent(b.getAttribute('data-key-del')));
    });
  });
  function copyText(text){
    if(navigator.clipboard && navigator.clipboard.writeText){
      return navigator.clipboard.writeText(text);
    }
    var ta = document.createElement('textarea');
    ta.value = text; document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(ta);
    return Promise.resolve();
  }
  document.querySelectorAll('[data-key-copy]').forEach(function(b){
    b.addEventListener('click', function(){
      var key = b.getAttribute('data-key-copy');
      copyText(key).then(function(){
        b.textContent = T.copied;
        setTimeout(function(){ b.textContent = T.copy; }, 1400);
      });
    });
  });
  document.querySelectorAll('[data-key-show]').forEach(function(b){
    b.addEventListener('click', function(){
      var key = b.getAttribute('data-key-show');
      var val = document.querySelector('.set-keyval[data-key="' + key.replace(/"/g, '\\"') + '"]');
      if(!val) return;
      var shown = val.getAttribute('data-shown') === '1';
      val.textContent = shown ? '••••••••••••••••' : key;
      val.setAttribute('data-shown', shown ? '0' : '1');
      b.textContent = shown ? T.show : T.hide;
    });
  });
  var clear = document.getElementById('sec-keyclear');
  if(clear){
    clear.addEventListener('click', function(){
      if(!confirm(T.confirm_keys_clear)) return;
      go('&action=keyclear');
    });
  }
})();
</script>
<script>
/* Face ID sign-in: registering and revoking WebAuthn keys */
(function(){
  var T = FO_SEC_I18N;
  var API = 'system/core/functions/webauthn.php';
  var add = document.getElementById('sec-faceid-add');
  var msg = document.getElementById('sec-faceid-msg');
  function say(t, bad){ if(!msg) return; msg.textContent = t || ''; msg.style.color = bad ? '#c0392b' : ''; }
  function b64(buf){ var s = '', b = new Uint8Array(buf); for(var i = 0; i < b.length; i++){ s += String.fromCharCode(b[i]); } return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''); }
  function unb64(t){ t = t.replace(/-/g, '+').replace(/_/g, '/'); while(t.length % 4){ t += '='; } var s = atob(t), o = new Uint8Array(s.length); for(var i = 0; i < s.length; i++){ o[i] = s.charCodeAt(i); } return o.buffer; }
  function post(act, data){
    return fetch(API + '?action=' + act, { method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/json'}, body: JSON.stringify(data || {}) }).then(function(r){ return r.json(); });
  }
  function reload(){ location.reload(); }
  function renderKeys(keys){
    var card = document.getElementById('sec-faceid-card');
    if(!card) return;
    var old = card.querySelector('.set-list');
    if(old){ old.remove(); }
    if(!keys || !keys.length){ return; }
    var list = document.createElement('div');
    list.className = 'set-list';
    list.style.marginTop = '10px';
    keys.forEach(function(k){
      var item = document.createElement('div');
      item.className = 'set-item';
      var span = document.createElement('span');
      span.className = 'grow';
      var lbl = document.createElement('span');
      lbl.className = 'set-lbl';
      lbl.style.display = 'block';
      lbl.textContent = k.label || T.device_default;
      var hint = document.createElement('span');
      hint.className = 'set-hint';
      hint.style.display = 'block';
      hint.textContent = T.key_created_at + ' ' + (k.created_at || '').slice(0, 10) + (k.last_used_at ? ' · ' + T.key_last_used + ' ' + k.last_used_at : '');
      span.appendChild(lbl);
      span.appendChild(hint);
      var del = document.createElement('button');
      del.type = 'button';
      del.className = 'set-btn danger';
      del.textContent = T.delete;
      del.addEventListener('click', function(){
        if(!confirm(T.confirm_revoke)) return;
        post('delete', { id: k.id }).then(function(d){
          if(d && d.ok){ refresh(); } else { say(T.delete_fail, true); }
        });
      });
      item.appendChild(span);
      item.appendChild(del);
      list.appendChild(item);
    });
    card.appendChild(list);
  }
  function refresh(){
    fetch(API + '?action=list', { credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){ if(d && d.ok){ renderKeys(d.keys || []); } })
      .catch(function(){});
  }

  if(add){
    add.addEventListener('click', function(){
      if(!window.PublicKeyCredential || !window.isSecureContext){ say(T.faceid_need_https, true); return; }
      add.disabled = true; say(T.faceid_waiting);
      var label = T.device_default + ' ' + new Date().toLocaleDateString(FO_SEC_DATELOCALE);
      post('begin-register', { label: label })
        .then(function(d){
          if(!d || !d.ok){ throw new Error((d && d.error) || 'begin_failed'); }
          var pk = d.publicKey;
          return navigator.credentials.create({ publicKey: {
            challenge: unb64(pk.challenge),
            rp: pk.rp,
            user: { id: unb64(pk.user.id), name: pk.user.name, displayName: pk.user.displayName },
            pubKeyCredParams: pk.pubKeyCredParams,
            timeout: pk.timeout || 60000,
            authenticatorSelection: pk.authenticatorSelection,
            excludeCredentials: (pk.excludeCredentials || []).map(function(c){
              return { type: c.type, id: unb64(c.id), transports: c.transports };
            }),
            attestation: pk.attestation || 'none'
          }});
        })
        .then(function(cred){
          if(!cred){ throw new Error('cancelled'); }
          var transports = [];
          try { transports = cred.response.getTransports ? cred.response.getTransports() : []; } catch(e) {}
          return post('finish-register', { label: label, credential: {
            id: cred.id,
            rawId: b64(cred.rawId),
            type: cred.type,
            clientDataJSON: b64(cred.response.clientDataJSON),
            attestationObject: b64(cred.response.attestationObject),
            transports: transports
          }});
        })
        .then(function(d){
          if(!d || !d.ok){ throw new Error((d && d.error) || 'failed'); }
          say(T.faceid_done + ' ' + (d.label || T.key_added));
          add.disabled = false;
          refresh();
        })
        .catch(function(e){
          add.disabled = false;
          if((e && e.name) === 'NotAllowedError'){ say(T.faceid_cancelled, true); return; }
          say(T.faceid_failed + ' ' + ((e && e.message) || T.error_word), true);
        });
    });
  }

  document.querySelectorAll('[data-faceid-del]').forEach(function(b){
    b.addEventListener('click', function(){
      if(!confirm(T.confirm_revoke)) return;
      post('delete', { id: parseInt(b.getAttribute('data-faceid-del'), 10) })
        .then(function(d){ if(d && d.ok){ reload(); } else { say(T.delete_fail, true); } })
        .catch(function(){ say(T.delete_fail, true); });
    });
  });
})();
</script>

<script>
/* IP blocking (fail2ban): list and removal of blocks without a page reload */
(function(){
  var T = FO_SEC_I18N;
  var API = 'system/core/functions/bans.php';
  var token = <? echo json_encode($token) ?>;
  var msg = document.getElementById('sec-bans-msg');
  function say(t, bad){ if(!msg) return; msg.textContent = t || ''; msg.style.color = bad ? '#c0392b' : ''; }

  function minutes(left){ return Math.max(1, Math.ceil(Number(left || 0) / 60)); }

  function render(rows){
    var list = document.getElementById('sec-bans-list');
    var empty = document.getElementById('sec-bans-empty');
    var clear = document.getElementById('sec-bans-clear');
    if(!list) return;
    list.innerHTML = '';
    (rows || []).forEach(function(b){
      var item = document.createElement('div');
      item.className = 'set-item';
      item.setAttribute('data-ban-ip', b.ip);

      var span = document.createElement('span');
      span.className = 'grow';

      var ip = document.createElement('span');
      ip.className = 'set-lbl';
      ip.style.display = 'block';
      ip.style.fontFamily = 'ui-monospace,Consolas,monospace';
      ip.textContent = b.ip;

      var hint = document.createElement('span');
      hint.className = 'set-hint';
      hint.style.display = 'block';
      hint.textContent = (b.banned ? T.bans_blocked_min.replace('{min}', String(minutes(b.left))) : T.failed_attempts) +
        ' · ' + T.failures_label + ' ' + b.failures + ' · ' + T.bans_count_label + ' ' + b.bans +
        (b.last_login ? ' · ' + T.login_label + ' ' + b.last_login : '');

      span.appendChild(ip);
      span.appendChild(hint);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'set-btn';
      btn.setAttribute('data-ban-clear', b.ip);
      btn.textContent = T.unban;
      btn.addEventListener('click', function(){ unban(b.ip); });

      item.appendChild(span);
      item.appendChild(btn);
      list.appendChild(item);
    });
    var has = !!(rows && rows.length);
    list.style.display = has ? '' : 'none';
    if(empty){ empty.style.display = has ? 'none' : ''; }
    if(clear){ clear.style.display = has ? '' : 'none'; }
  }

  function load(){
    fetch(API + '?action=list', { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){ if(d && d.ok){ render(d.bans || []); } })
      .catch(function(){});
  }

  function post(action, data){
    var body = new URLSearchParams();
    body.append('action', action);
    body.append('token', token);
    Object.keys(data || {}).forEach(function(k){ body.append(k, data[k]); });
    return fetch(API, { method: 'POST', credentials: 'same-origin', body: body }).then(function(r){ return r.json(); });
  }

  function unban(ip){
    if(!confirm(T.bans_confirm_unban.replace('{ip}', ip))){ return; }
    post('unban', { ip: ip }).then(function(d){
      if(d && d.ok){ say(T.bans_unbanned + ' ' + ip); load(); }
      else { say(T.bans_error + ' ' + ((d && d.error) || T.error_word), true); }
    }).catch(function(){ say(T.bans_error_short, true); });
  }

  var clear = document.getElementById('sec-bans-clear');
  if(clear){
    clear.addEventListener('click', function(){
      if(!confirm(T.bans_confirm_clear)){ return; }
      post('unban-all').then(function(d){
        if(d && d.ok){ say(T.bans_cleared); load(); }
        else { say(T.bans_error_short, true); }
      });
    });
  }

  load();
  setInterval(load, 30000);
})();
</script>
