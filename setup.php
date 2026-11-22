<?
/**
 * Forest OS — первый запуск: создание администратора.
 * Показывается только пока в системе нет ни одного суперпользователя.
 */
if (!isset($_SESSION)) {
    session_start();
}

ini_set('display_errors', 'Off');

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/install.php';

$pdo = null;
$dbError = '';
try {
    $pdo = fo_db();
} catch (Throwable $e) {
    $dbError = 'Нет соединения с базой данных. Проверьте .env и перезапустите контейнер.';
}

if ($pdo !== null && fo_has_superuser($pdo)) {
    header('Location: os.php');
    exit;
}

$error = '';
$login = 'admin';
$lang = 'ru';

if ($dbError === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = strtolower(trim((string)($_POST['login'] ?? 'admin')));
    $password = (string)($_POST['password'] ?? '');
    $repeat = (string)($_POST['password2'] ?? '');
    $lang = ($_POST['lang'] ?? 'ru') === 'en' ? 'en' : 'ru';
    $tz = 'Europe/Moscow';

    if (!preg_match('/^[a-z0-9_\-\.]{3,64}$/', $login)) {
        $error = 'Логин: 3–64 символа, латиница, цифры, точка, дефис или подчёркивание.';
    } elseif (strlen($password) < 8) {
        $error = 'Пароль должен быть не короче 8 символов.';
    } elseif ($password !== $repeat) {
        $error = 'Пароли не совпадают.';
    } else {
        $res = fo_create_user($pdo, $login, $password, $lang, $tz, $_SERVER['DOCUMENT_ROOT']);
        if (empty($res['ok'])) {
            $error = $res['error'] === 'short_password'
                ? 'Пароль должен быть не короче 8 символов.'
                : 'Не удалось создать учётную запись.';
        } else {
            /* входим сразу, без повторного ввода пароля */
            $_SESSION['is_authuser'] = true;
            $_SESSION['loginuser'] = $login;
            $_SESSION['superuser'] = $login;
            $_SESSION['locale'] = $lang;
            $_SESSION['safemode'] = 'false';
            $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            $_SESSION['CookieIsMine'] = md5($login . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . $forwardedFor);
            header('Location: os.php');
            exit;
        }
    }
}

$accent = '#5fe08c';
$ink = '#06130b';
?>
<!DOCTYPE html>
<html lang="<? echo $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forest OS — первый запуск</title>
<link rel="stylesheet" href="system/core/design/login.css?h=setup">
<link rel="icon" href="system/core/design/images/favicons/favicon.ico">
<style>
:root { --fo-accent: <? echo $accent ?>; --fo-accent-ink: <? echo $ink ?>; }
html, body { height: 100%; margin: 0; }
body {
  min-height: 100%;
  display: flex; align-items: center; justify-content: center;
  background:
    radial-gradient(1200px 600px at 70% -10%, color-mix(in srgb, var(--fo-accent) 16%, transparent), transparent 60%),
    radial-gradient(900px 500px at 10% 110%, #1b2a3a 0%, transparent 60%),
    #0e1417;
  font: 13px/1.45 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
  color: #e8efe9;
}
#login-root, .login-inner, #login, #login-stage { min-height: 0; height: auto; padding: 0; background: none; }
#login-card.setup-card {
  width: min(430px, 92vw); box-sizing: border-box; padding: 26px 26px 22px;
  border-radius: 16px; background: rgba(20, 26, 30, .92);
  border: 1px solid rgba(255, 255, 255, .08);
  box-shadow: 0 30px 70px rgba(0, 0, 0, .55);
}
#login-card .login-brand { margin-bottom: 12px; }
#login-card .login-brand-name { font-size: 22px; font-weight: 800; letter-spacing: -.01em; }
#login-card .login-brand-tag {
  display: block; margin-top: 2px; font-size: 11px; letter-spacing: .14em;
  text-transform: uppercase; opacity: .5;
}
#login-card input.input-login, #login-card .setup-select {
  width: 100%; box-sizing: border-box; height: 40px; border-radius: 10px; padding: 0 12px;
  border: 1px solid rgba(255, 255, 255, .14); background: rgba(255, 255, 255, .05);
  color: inherit; font: 13px/1 inherit;
}
#login-card input.input-login:focus, #login-card .setup-select:focus {
  outline: none; border-color: var(--fo-accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--fo-accent) 22%, transparent);
}
#login-card button.login-submit {
  width: 100%; height: 42px; margin-top: 6px; border: 0; border-radius: 10px; cursor: pointer;
  background: var(--fo-accent); color: var(--fo-accent-ink);
  font: 700 13.5px/1 inherit;
}
#login-card button.login-submit:hover { filter: brightness(1.06); }
#login-card button.login-submit:disabled { opacity: .5; cursor: default; }
.setup-hint { font-size: 12px; opacity: .62; line-height: 1.5; margin: -4px 0 14px; }
.setup-row { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
.setup-row label { font-size: 12px; opacity: .7; }
.setup-error {
  margin: 0 0 12px; padding: 9px 12px; border-radius: 9px; font-size: 12.5px;
  background: color-mix(in srgb, #d05858 18%, transparent); color: #ffb9b1;
}
.setup-select {
  height: 38px; border-radius: 9px; padding: 0 10px;
  border: 1px solid rgba(255,255,255,.16); background: rgba(255,255,255,.06); color: inherit;
}
</style>
</head>
<body>
<div id="login-root">
  <div class="login-inner">
    <div id="login">
      <div id="login-stage">
        <div id="login-card" class="setup-card">
          <div id="labelos" class="login-brand">
            <span class="login-brand-name">Forest OS</span>
            <span class="login-brand-tag">первый запуск</span>
          </div>

          <div class="setup-hint">
            Система установлена. Задайте учётную запись администратора —
            этим логином и паролем вы будете входить в Forest OS.
          </div>

          <? if ($error !== ''): ?>
            <div class="setup-error"><? echo htmlspecialchars($error) ?></div>
          <? endif; ?>
          <? if ($dbError !== ''): ?>
            <div class="setup-error"><? echo htmlspecialchars($dbError) ?></div>
          <? endif; ?>

          <form method="post" autocomplete="off">
            <div class="setup-row">
              <label for="setup-login">Логин</label>
              <input class="input-login" id="setup-login" name="login" type="text"
                     value="<? echo htmlspecialchars($login) ?>" maxlength="64" autocapitalize="off" spellcheck="false" required>
            </div>
            <div class="setup-row">
              <label for="setup-password">Пароль</label>
              <input class="input-login" id="setup-password" name="password" type="password" minlength="8" required>
            </div>
            <div class="setup-row">
              <label for="setup-password2">Пароль ещё раз</label>
              <input class="input-login" id="setup-password2" name="password2" type="password" minlength="8" required>
            </div>
            <div class="setup-row">
              <label for="setup-lang">Язык интерфейса</label>
              <select class="setup-select" id="setup-lang" name="lang">
                <option value="ru"<? echo $lang === 'ru' ? ' selected' : '' ?>>Русский</option>
                <option value="en"<? echo $lang === 'en' ? ' selected' : '' ?>>English</option>
              </select>
            </div>
            <button type="submit" class="input-login login-submit" <? echo $dbError !== '' ? 'disabled' : '' ?>>Создать и войти</button>
          </form>

          <div class="setup-hint" style="margin-top:14px">
            Пароль можно будет сменить позже: <b>Настройки → Безопасность</b>.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
