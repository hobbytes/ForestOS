<?
/**
 * Сохранение списка уведомлений пользователя.
 * Логин берётся только из сессии (раньше — из POST, что позволяло писать
 * в чужой каталог), пароль для шифрования — подготовленным запросом.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/etc/security.php';
require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/bd.php';

$security = new security;
$security->appprepare();

$login = (string)$_SESSION['loginuser'];
$body  = (string)($_POST['body'] ?? '');
if ($login === '' || $body === '') {
    exit('false');
}

try {
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $st = $pdo->prepare('SELECT password FROM forestusers WHERE login = ?');
    $st->execute(array($login));
    $key = (string)$st->fetchColumn();
} catch (Throwable $e) {
    exit('false');
}
if ($key === '') {
    exit('false');
}

$encoded = $security->__encode($body, $key);
$dir = $_SERVER['DOCUMENT_ROOT'] . '/system/users/' . $login . '/settings/notifications/';
if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}
@file_put_contents($dir . 'MainNotificationFile.hdf', $encoded);
echo 'true';
