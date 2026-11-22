<?
/* Settings → Безопасность (единый дизайн) */
if (!isset($_SESSION)) { session_start(); }

$DOC = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$me = (string)$_SESSION['loginuser'];
$token = (string)($_SESSION['fo_settings_csrf'] ?? '');
$appid = (string)($_GET['appid'] ?? '');
$msg = '';

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
    $msg = 'Сессия устарела, откройте окно заново';
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
        $msg = 'Ключ создан: ' . $key;
    } catch (Throwable $e) {
        $msg = 'Не удалось сохранить ключ';
    }
}

if ($action === 'keydel') {
    $key = (string)($_GET['key'] ?? '');
    $keys = array_values(array_filter(sec_keys($me), function ($k) use ($key) { return $k !== $key; }));
    try {
        sec_set_keys($me, $keys);
        $msg = 'Ключ удалён';
    } catch (Throwable $e) {
        $msg = 'Не удалось удалить ключ';
    }
}

if ($action === 'keyclear') {
    try {
        sec_set_keys($me, array());
        $msg = 'Все ключи удалены';
    } catch (Throwable $e) {
        $msg = 'Не удалось удалить ключи';
    }
}

if ($action === 'password') {
    $old = (string)($_GET['old'] ?? '');
    $new = (string)($_GET['new'] ?? '');
    $repeat = (string)($_GET['repeat'] ?? '');
    $stored = sec_pass($me);
    if ($stored === '') {
        $msg = 'Не удалось прочитать учётную запись';
    } elseif (!hash_equals($stored, $security->crypt_s($old, $me))) {
        $msg = 'Текущий пароль неверен';
    } elseif (strlen($new) < 4) {
        $msg = 'Новый пароль слишком короткий';
    } elseif ($new !== $repeat) {
        $msg = 'Пароли не совпадают';
    } else {
        try {
            $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
            $pdo->prepare('UPDATE forestusers SET password = ? WHERE login = ?')
                ->execute(array($security->crypt_s($new, $me), $me));
            $msg = 'Пароль изменён';
        } catch (Throwable $e) {
            $msg = 'Не удалось изменить пароль';
        }
    }
}

if ($action === 'journal' && $me === ($_SESSION['superuser'] ?? '')) {
    $journal = $DOC . '/system/core/journal.mcj';
    if (is_file($journal)) {
        @file_put_contents($journal, '');
        @chmod($journal, 0666);
        $msg = 'Журнал очищен';
    } else {
        $msg = 'Журнал не найден';
    }
}

$journalSize = is_file($DOC . '/system/core/journal.mcj') ? (int)@filesize($DOC . '/system/core/journal.mcj') : 0;
$keys = sec_keys($me);
?>

<h2 class="set-h">Безопасность</h2>
<p class="set-sub">Пароль учётной записи и системный журнал</p>

<? if ($msg !== ''): ?>
  <div class="set-card" style="border-color:color-mix(in srgb, var(--set-accent) 40%, #e6e7ea); background:color-mix(in srgb, var(--set-accent) 10%, #fff)">
    <div class="set-lbl"><? echo htmlspecialchars($msg) ?></div>
  </div>
<? endif; ?>

<div class="set-card">
  <div class="set-lbl">Смена пароля</div>
  <div class="set-row" style="margin-top:10px;border-top:0;padding-top:0">
    <input class="set-btn grow" id="sec-old" type="password" placeholder="текущий пароль" style="height:32px">
  </div>
  <div class="set-row" style="border-top:0;padding-top:8px;margin-top:0">
    <input class="set-btn grow" id="sec-new" type="password" placeholder="новый пароль" style="height:32px">
    <input class="set-btn grow" id="sec-repeat" type="password" placeholder="повторите" style="height:32px">
  </div>
  <div class="set-row" style="border-top:0;padding-top:10px;margin-top:0">
    <button type="button" class="set-btn primary" id="sec-save">Изменить пароль</button>
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
      <div class="set-lbl">Вход по Face ID</div>
      <div class="set-hint">
        <? if (!$waAllowed): ?>
          Доступно только администратору.
        <? elseif (!$waAvailable): ?>
          Нужен HTTPS: на http:// браузер не даст вызвать Face ID.
        <? else: ?>
          Face ID / отпечаток / Windows Hello вместо пароля. Ключ остаётся на устройстве.
        <? endif; ?>
      </div>
    </div>
    <? if ($waAllowed && $waAvailable): ?>
      <button type="button" class="set-btn primary" id="sec-faceid-add">Добавить</button>
    <? endif; ?>
  </div>
  <? if (count($waKeys)): ?>
    <div class="set-list" style="margin-top:10px">
      <? foreach ($waKeys as $k): ?>
        <div class="set-item">
          <span class="grow">
            <span class="set-lbl" style="display:block"><? echo htmlspecialchars((string)$k['label'] !== '' ? (string)$k['label'] : 'Устройство') ?></span>
            <span class="set-hint" style="display:block">ключ от <? echo htmlspecialchars((string)$k['created_at']) ?><? echo !empty($k['last_used_at']) ? ' · вход ' . htmlspecialchars((string)$k['last_used_at']) : '' ?></span>
          </span>
          <button type="button" class="set-btn danger" data-faceid-del="<? echo (int)$k['id'] ?>">Удалить</button>
        </div>
      <? endforeach; ?>
    </div>
  <? endif; ?>
  <div id="sec-faceid-msg" class="set-hint" style="margin-top:8px"></div>
</div>

<div class="set-card">
  <div class="set-row" style="border-top:0;padding-top:0;margin-top:0">
    <div class="grow">
      <div class="set-lbl">Системный журнал</div>
      <div class="set-hint"><? echo $journalSize > 0 ? round($journalSize / 1024, 1) . ' КБ записей' : 'пусто' ?></div>
    </div>
    <? if ($me === ($_SESSION['superuser'] ?? '')): ?>
      <button type="button" class="set-btn danger" id="sec-journal">Очистить</button>
    <? endif; ?>
  </div>
  <div class="set-row">
    <div class="grow">
      <div class="set-lbl">Ключи доступа</div>
      <div class="set-hint">Для приложений, которым нужен доступ к вашей учётной записи</div>
    </div>
    <? if (count($keys)): ?>
      <button type="button" class="set-btn danger" id="sec-keyclear">Удалить все</button>
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
          <button type="button" class="set-btn" data-key-copy="<? echo htmlspecialchars($k) ?>">Копировать</button>
          <button type="button" class="set-btn" data-key-show="<? echo htmlspecialchars($k) ?>">Показать</button>
          <button type="button" class="set-btn danger" data-key-del="<? echo htmlspecialchars($k) ?>">Удалить</button>
        </div>
      <? endforeach; ?>
    </div>
  <? endif; ?>

  <div class="set-row" style="border-top:0;padding-top:10px;margin-top:0">
    <input class="set-btn grow" id="sec-keyname" placeholder="название ключа (например telegram)" style="height:32px">
    <button type="button" class="set-btn primary" id="sec-keyadd">Создать ключ</button>
  </div>
</div>

<script>
(function(){
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
      if(!confirm('Очистить системный журнал?')) return;
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
      if(!confirm('Удалить ключ?')) return;
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
        b.textContent = 'Скопировано';
        setTimeout(function(){ b.textContent = 'Копировать'; }, 1400);
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
      b.textContent = shown ? 'Показать' : 'Скрыть';
    });
  });
  var clear = document.getElementById('sec-keyclear');
  if(clear){
    clear.addEventListener('click', function(){
      if(!confirm('Удалить все ключи доступа?')) return;
      go('&action=keyclear');
    });
  }
})();
</script>
<script>
/* Вход по Face ID: привязка и отзыв ключей WebAuthn */
(function(){
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
      lbl.textContent = k.label || 'Устройство';
      var hint = document.createElement('span');
      hint.className = 'set-hint';
      hint.style.display = 'block';
      hint.textContent = 'ключ от ' + (k.created_at || '').slice(0, 10) + (k.last_used_at ? ' · вход ' + k.last_used_at : '');
      span.appendChild(lbl);
      span.appendChild(hint);
      var del = document.createElement('button');
      del.type = 'button';
      del.className = 'set-btn danger';
      del.textContent = 'Удалить';
      del.addEventListener('click', function(){
        if(!confirm('Отозвать этот ключ? Вход по Face ID с него перестанет работать.')) return;
        post('delete', { id: k.id }).then(function(d){
          if(d && d.ok){ refresh(); } else { say('Не удалось удалить', true); }
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
      if(!window.PublicKeyCredential || !window.isSecureContext){ say('Нужен HTTPS и поддерживаемый браузер', true); return; }
      add.disabled = true; say('Ожидаем Face ID…');
      var label = 'Устройство ' + new Date().toLocaleDateString('ru-RU');
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
          say('Готово: ' + (d.label || 'ключ добавлен'));
          add.disabled = false;
          refresh();
        })
        .catch(function(e){
          add.disabled = false;
          if((e && e.name) === 'NotAllowedError'){ say('Отменено или не распознано', true); return; }
          say('Не получилось: ' + ((e && e.message) || 'ошибка'), true);
        });
    });
  }

  document.querySelectorAll('[data-faceid-del]').forEach(function(b){
    b.addEventListener('click', function(){
      if(!confirm('Отозвать этот ключ? Вход по Face ID с него перестанет работать.')) return;
      post('delete', { id: parseInt(b.getAttribute('data-faceid-del'), 10) })
        .then(function(d){ if(d && d.ok){ reload(); } else { say('Не удалось удалить', true); } })
        .catch(function(){ say('Не удалось удалить', true); });
    });
  });
})();
</script>
