<?
/**
 * Приём уведомления от приложения для текущего пользователя.
 * Раньше логин приходил из POST (запись в чужой каталог), кнопка «Open»
 * собиралась из параметров без экранирования (stored XSS), а запрос к БД
 * шёл конкатенацией (SQL-инъекция).
 */
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Europe/Moscow');
}
require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/etc/security.php';
require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/bd.php';

$security = new security;
$security->appprepare();

$login = (string)$_SESSION['loginuser'];
$body  = trim((string)($_POST['body'] ?? ''));
$appname = trim((string)($_POST['appname'] ?? ''));
$_key  = (string)($_POST['key'] ?? '');
$value = (string)($_POST['value'] ?? '');

if ($body === '' || $appname === '') {
    exit('FALSE');
}
if (!preg_match('/^[A-Za-z0-9_\- ]{1,64}$/', $appname)) {
    exit('FALSE');
}
$_appname = str_replace(' ', '_', $appname);
$_target = md5($appname);

try {
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $st = $pdo->prepare('SELECT password FROM forestusers WHERE login = ?');
    $st->execute(array($login));
    $key = (string)$st->fetchColumn();
} catch (Throwable $e) {
    exit('FALSE');
}
if ($key === '') {
    exit('FALSE');
}

$dir = $_SERVER['DOCUMENT_ROOT'] . '/system/users/' . $login . '/settings/notifications/';
if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}

$hash = md5($login . $appname . date('dmy_His'));
if ($_key !== '' && $value !== '') {
    $h = function ($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };
    $body .= "<br><span style='margin-left: 37%;' class='not-btn ui-button ui-widget ui-corner-all'"
        . " onclick='makeprocess(&quot;system/apps/" . $h($_appname) . "/main.php&quot; , &quot;" . $h($value)
        . "&quot; , &quot;" . $h($_key) . "&quot; , &quot;" . $h($_appname) . "&quot;); $(&quot;." . $h($_target) . "&quot;).remove();'>Open</span>";
}

$body = $security->__encode($body, $key);
$file = date('dmy_His') . $hash . '.not';
$content = "[info]\rappname = " . str_replace(array("\r", "\n"), '', $appname)
    . "\rdate = " . date('d.m.y, H:i:s') . "\rbody = " . "'$body'";
@file_put_contents($dir . $file, $content);
echo 'TRUE';
