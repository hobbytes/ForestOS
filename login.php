<?
/* Системный акцент (Settings → Персонализация) — экран входа использует его
   так же, как оболочка: та же кнопка, фокус и ссылки. */
if (!isset($_SESSION)) { session_start(); }

/* Прямой заход на /login (закладка, старая ссылка): библиотеки и язык
   подключает os.php, поэтому без него страница обрывается. Отправляем на
   канонический экран входа — это os.php в гостевом режиме. */
if (!defined('FO_LOGIN_INCLUDED')) {
    header('Location: /', true, 302);
    exit;
}

/* первый запуск: пока нет администратора — мастер настройки */
require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/install.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/bans.php';
if (!fo_has_superuser()) {
    header('Location: setup.php');
    exit;
}
$__accentUser = !empty($_SESSION['loginuser']) ? (string)$_SESSION['loginuser'] : (getenv('FOREST_ADMIN_LOGIN') ?: 'admin');
$__accent = '';
$__af = $_SERVER['DOCUMENT_ROOT'] . '/system/users/' . $__accentUser . '/settings/appearance.foc';
if (is_file($__af)) {
    $__at = @parse_ini_file($__af);
    if (!empty($__at['accent']) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $__at['accent'])) { $__accent = $__at['accent']; }
}
if ($__accent === '') {
    $__tf = $_SERVER['DOCUMENT_ROOT'] . '/system/users/' . $__accentUser . '/settings/etc/theme.fth';
    if (is_file($__tf)) {
        $__tt = @parse_ini_file($__tf);
        if (!empty($__tt['accentcolor'])) {
            $__tc = trim((string)$__tt['accentcolor'], " ;");
            if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $__tc)) { $__accent = $__tc; }
        }
    }
}
if ($__accent === '') { $__accent = '#5fe08c'; }
$__ink = '#ffffff';
if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $__accent, $__m)) {
    $__lum = (0.299 * hexdec($__m[1]) + 0.587 * hexdec($__m[2]) + 0.114 * hexdec($__m[3])) / 255;
    if ($__lum > 0.62) { $__ink = '#06130b'; }
}
?>
<style>
:root{
  --fo-accent: <?echo $__accent?>;
  --fo-accent-strong: color-mix(in srgb, <?echo $__accent?> 78%, #000);
  --fo-accent-ink: <?echo $__ink?>;
}
</style>
<div id="login-root">
<div class="login-inner">
<div id="login">
<div id="login-stage">
<div id="login-card">
<div id="labelos" class="login-brand">
<span class="login-brand-name">Forest OS</span>
<span class="login-brand-tag">Revival 2.0</span>
</div>
<?

/* #Display errors */

ini_set('display_errors','Off');

if (function_exists('date_default_timezone_set'))
date_default_timezone_set('Europe/Moscow');

global $loginin, $infob, $security, $language, $mobile;

$date = date("d.m.y, H:i:s");

if(isset($_POST['logins'])){
  $status2 = $_POST['logins'];
}

$keyaccess = NULL;

if (!empty($status2))
{
  if(isset($_POST['keyaccess'])){
    $keyaccess = $_POST['keyaccess'];
    $loginin = strtolower(addslashes(strip_tags(htmlspecialchars($_POST['loginin']))));
    $loginin = str_replace(' ', '_', $loginin);
    $passwordin = $security->crypt_s(addslashes(strip_tags(htmlspecialchars($_POST['passwordin']))),$loginin);
  }
}

if(!isset($_SESSION)){
  session_start();
}

if(empty($_SESSION['locale'])){
  $_SESSION['locale'] = 'ru';
}

if(empty($_SESSION['timezone'])){
  $_SESSION['timezone'] = 'Europe/Moscow';
}

if(!isset($_SESSION['counter'])){// prepare counter for capthca
  $_SESSION['counter'] = 0;
}

if(!isset($_SESSION['BlockDate'])){// prepare counter for capthca
  $_SESSION['BlockDate'] = false;
}

$auth = new AuthClassUser();

/* ключ доступа можно вставить и в поле пароля — так удобнее */
$__rawPass = isset($_POST['passwordin']) ? (string)$_POST['passwordin'] : '';
if (($keyaccess === NULL || $keyaccess === '') && $__rawPass !== '' && class_exists('readbd')) {
    $__bds = new readbd;
    $__found = $__bds->readglobal2("login", "forestusers", "TempKey", $__rawPass, true, true);
    if (!empty($__found)) {
        $keyaccess = $__rawPass;
    }
}

$auth->construct('login', $loginin, $keyaccess);

if (isset($loginin) && isset($passwordin)) {
  $_SESSION['safemode'] = isset($_POST['safemode']) ? $_POST['safemode'] : 'false';

  if (!$auth->auth($loginin, $passwordin, $keyaccess)) {

    echo '<h2 class="error-layout">'.$language[$_SESSION['locale'].'_login_error'].'</h2>';
    $login_get = $loginin;

    if(empty($keyaccess)){
      $infob->writestat('WARNING! Wrong login or password -> '.$loginin,'system/core/journal.mcj');
    }

    $_SESSION['counter'] = $_SESSION['counter'] + 1;// count
    if($_SESSION['counter'] >= 3 && !$_SESSION['BlockDate']){
      $startDate  = time();
      $_SESSION['BlockDate'] = date('d-m-y H:i:s', strtotime("+10 min", $startDate));
    }
  }
}

if (isset($_GET["exit"])) {
  if ($_GET["exit"] == 1) {
        $auth->out();
        echo '<script>window.location.href="?exit=0";</script>';
        exit;
  }
}

  if ($auth->isAuth()) {
    if(!empty($keyaccess)){
      $prefix = '[via Access Key: '.$keyaccess.']';
    }
    $infob->writestat('Success Login -> '.(isset($prefix) ? $prefix : '').$loginin,'system/core/journal.mcj');
    // Persist the authenticated session BEFORE the browser navigates away.
    // The redirect below is emitted via meta/JS (headers are already sent by
    // prepare.php), so without this the follow-up request can race the session
    // write and still see the guest session -> login form appears again.
    if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
      session_write_close();
    }
    echo '<meta http-equiv="refresh" content="0;url=os.php">';
    echo '<script>window.location.replace("os.php");</script>';
    exit;
    unset($_SESSION['counter']);
    $_SESSION['BlockDate'] = false;
}

$foBan = ForestBans::check(ForestBans::ip());
$foBanned = !empty($foBan['banned']);

if(!$foBanned && (!$_SESSION['BlockDate'] || date('d-m-y H:i:s') >= $_SESSION['BlockDate'])){
  $_SESSION['BlockDate'] = false;
  $_SESSION['counter'] = 0;
  $gui = new gui;
  $gui->formstart('POST');
  ?>
  <input class="loginin input-login" id="loginin" placeholder="<?=$language[$_SESSION['locale'].'_login_input']?>" type="text" name="loginin" value="<?=isset($login_get) ? $login_get : ''?>" autocomplete="username" autocapitalize="off" autocorrect="off" spellcheck="false">
  <input class="passwordin input-login" id="passwordin" placeholder="<?=$language[$_SESSION['locale'].'_password_input']?>" type="password" name="passwordin" value="" autocomplete="current-password">
  <?
}elseif($foBanned){
  echo '<h2 class="error-layout">Слишком много неудачных попыток. Вход с этого IP заблокирован до '
     . htmlspecialchars(ForestBans::untilText((int)$foBan['until'])) . '.</h2>';
}else{
  $_date = strtotime($_SESSION['BlockDate']) - strtotime(date('d-m-y H:i:s'));
  $timeleft = round(abs($_date/60));
  echo '<h2 class="error-layout">'.$language[$_SESSION['locale'].'_login_error_2'].$timeleft.' min</h2>';
}

?>
<div id="safemode" class="login-safemode" style="display:none;">
  <label class="login-safemode-label">
    <input type="checkbox" name="safemode" value="true">
    <span><? echo $language[$_SESSION['locale'].'_safemode_label']; ?></span>
  </label>
  <input class="keyaccess input-login" id="keyaccess" placeholder="Key Access" type="text" name="keyaccess" value="<?=isset($keyaccess) ? htmlspecialchars($keyaccess) : ''?>">
</div>
<?
if(!$_SESSION['BlockDate'] && !$foBanned){
  echo '<input class="buttoncustom" type="submit" name="logins" value="'.$language[$_SESSION['locale'].'_login_button'].'">';
  $gui->formend();
  /* вход по ключу устройства: Face ID / отпечаток / Windows Hello */
  echo '<div id="fo-faceid-wrap" class="fo-faceid-wrap" style="display:none">'
     . '<button type="button" id="fo-faceid" class="fo-faceid-btn">'
     . '<span class="fo-faceid-icon" aria-hidden="true">◉</span><span>Войти по Face ID</span></button>'
     . '<div id="fo-faceid-msg" class="fo-faceid-msg"></div>'
     . '</div>';
}

$timezone = $_SESSION['timezone'];
date_default_timezone_set("$timezone");
$date = date("m/d/Y H:i:s");
$_offset = new DateTime($date, new DateTimeZone("$timezone"));
$offset = $_offset->getOffset()/3600;
?>
</div><!-- /#login-card -->
</div><!-- /#login-stage -->
</div><!-- /#login -->
<div class="login-footer">
  <span id="date" class="login-date"><?php echo $object->getDayRus().', '.date('d');?></span>
  <span id="time" class="login-time"></span>
</div>
</div><!-- /.login-inner -->
</div><!-- /#login-root -->
<script type="text/javascript">
$( function() {
  $(window).on('load', function(){
    $(".welcomescreen").hide('fade',500);
  });
});




$("#labelos").click(function(){
  if($("#safemode").is( ":hidden" )){
  $("#safemode").slideDown("fast");
}else{
  $("#safemode").slideUp("fast");
}
});

// Some password managers fill the fields on the very click that submits the
// form, so the first POST can carry empty values. Wait one tick and submit
// again with the values autofill committed.
(function(){
  var form = document.querySelector('#login-card form');
  if(!form){ return; }
  var retried = false;
  form.addEventListener('submit', function(e){
    if(retried){ return; }
    var l = document.getElementById('loginin');
    var p = document.getElementById('passwordin');
    if(!l || !p || (l.value && p.value)){ return; }
    retried = true;
    e.preventDefault();
    setTimeout(function(){
      var btn = form.querySelector('input[name="logins"]');
      if(btn){ btn.click(); }
    }, 150);
  });
})();

$('.ui-body').css('background','linear-gradient(to top, #171717, #3a3a3a)')

function showTime()
{
  offset = "<?echo $offset?>"
  d = new Date();   
  utc = d.getTime() + (d.getTimezoneOffset() * 60000);   
  date = new Date(utc + (3600000*offset)); 
  var H = '' + date.getHours();
  H = H.length < 2 ? '0' + H:H;
  var M = '' + date.getMinutes();
  M = M.length < 2 ? '0' + M:M;
  var clock = H + ":" + M;
  $("#time").text(clock);
  setTimeout(showTime,1000);
}
showTime();
</script>
<script type="text/javascript">
/* Вход по Face ID / отпечатку (WebAuthn). Пароль остаётся основным способом:
   если ключа нет, кнопка просто не показывается. */
(function(){
  var API = 'system/core/functions/webauthn.php';
  var wrap = document.getElementById('fo-faceid-wrap');
  var btn = document.getElementById('fo-faceid');
  var msg = document.getElementById('fo-faceid-msg');
  var loginInput = document.getElementById('loginin');
  if(!wrap || !btn || !loginInput){ return; }

  var canWebAuthn = !!(window.PublicKeyCredential && navigator.credentials && window.isSecureContext);
  var currentLogin = '';

  function say(t, bad){ if(!msg) return; msg.textContent = t || ''; msg.className = 'fo-faceid-msg' + (bad ? ' bad' : ''); }
  function b64(buf){ var s = '', b = new Uint8Array(buf); for(var i = 0; i < b.length; i++){ s += String.fromCharCode(b[i]); } return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''); }
  function unb64(t){ t = t.replace(/-/g, '+').replace(/_/g, '/'); while(t.length % 4){ t += '='; } var s = atob(t), o = new Uint8Array(s.length); for(var i = 0; i < s.length; i++){ o[i] = s.charCodeAt(i); } return o.buffer; }
  function post(act, data){
    return fetch(API + '?action=' + act, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data || {})
    }).then(function(r){ return r.json(); });
  }

  function refresh(){
    var login = (loginInput.value || '').trim().toLowerCase();
    if(!canWebAuthn || login === ''){ wrap.style.display = 'none'; currentLogin = ''; return; }
    if(login === currentLogin){ return; }
    currentLogin = login;
    fetch(API + '?action=status&login=' + encodeURIComponent(login), { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if(d && d.ok && d.available && d.allowed && d.has_keys){ wrap.style.display = 'block'; say(''); }
        else { wrap.style.display = 'none'; }
      })
      .catch(function(){ wrap.style.display = 'none'; });
  }

  loginInput.addEventListener('input', function(){ setTimeout(refresh, 250); });
  loginInput.addEventListener('blur', refresh);

  btn.addEventListener('click', function(){
    var login = (loginInput.value || '').trim().toLowerCase();
    if(!login){ say('Сначала введите логин', true); loginInput.focus(); return; }
    btn.disabled = true;
    say('Запрашиваем Face ID…');
    post('begin-login', { login: login })
      .then(function(d){
        if(!d || !d.ok){ var e = new Error((d && d.error) || 'begin_failed'); e.code = (d && d.error) || 'begin_failed'; throw e; }
        var pk = d.publicKey;
        return navigator.credentials.get({ publicKey: {
          challenge: unb64(pk.challenge),
          rpId: pk.rpId,
          timeout: pk.timeout || 60000,
          userVerification: pk.userVerification || 'required',
          allowCredentials: (pk.allowCredentials || []).map(function(c){
            return { type: c.type, id: unb64(c.id), transports: c.transports };
          })
        }});
      })
      .then(function(cred){
        if(!cred){ var e = new Error('cancelled'); e.code = 'cancelled'; throw e; }
        return post('finish-login', { login: login, credential: {
          id: cred.id,
          rawId: b64(cred.rawId),
          type: cred.type,
          clientDataJSON: b64(cred.response.clientDataJSON),
          authenticatorData: b64(cred.response.authenticatorData),
          signature: b64(cred.response.signature),
          userHandle: cred.response.userHandle ? b64(cred.response.userHandle) : null
        }});
      })
      .then(function(d){
        if(!d || !d.ok){ var e = new Error((d && d.error) || 'auth_failed'); e.code = (d && d.error) || 'auth_failed'; throw e; }
        say('Входим…');
        window.location.replace('os.php');
      })
      .catch(function(err){
        btn.disabled = false;
        var name = (err && err.name) || '';
        var code = (err && err.code) || '';
        if(name === 'NotAllowedError'){ say('На этом устройстве нет ключа Face ID. Войдите паролем — и добавьте ключ: Settings → Безопасность.', true); return; }
        if(code === 'no_keys' || code === 'not_allowed'){ wrap.style.display = 'none'; return; }
        if(code === 'challenge_expired'){ say('Истекло время — попробуйте ещё раз', true); return; }
        if(code === 'too_many_attempts'){ say('Слишком много попыток, подождите пару минут', true); return; }
        if(code === 'bad_signature' || code === 'unknown_key'){ say('Ключ не подошёл — войдите паролем', true); return; }
        if(code === 'insecure_context'){ say('Вход по Face ID работает только по HTTPS', true); return; }
        say('Не получилось: ' + (code || name || 'ошибка'), true);
      });
  });

  refresh();
})();
</script>
<div class="welcomescreen">
  <img src="system/core/design/images/forestosicon.png">
</div>
<?
