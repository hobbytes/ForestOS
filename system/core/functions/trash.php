<?
/**
 * Перемещение файла/папки в корзину текущего пользователя.
 * Раньше путь из POST принимался как есть (можно было выйти за DOCUMENT_ROOT),
 * а сессии «гостя» с loginuser=admin хватало для удаления файлов без входа.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/filesystem.php';
require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/etc/security.php';

$security = new security;
if (!isset($_SESSION)) {
    session_start();
}
$security->appprepare();

$doc   = realpath($_SERVER['DOCUMENT_ROOT']);
$login = (string)$_SESSION['loginuser'];

$rel = (string)($_POST['file_delete'] ?? '');
$rel = str_replace(array('\\', "\0"), array('/', ''), $rel);
if (strpos($rel, (string)$_SERVER['DOCUMENT_ROOT']) === 0) {
    $rel = substr($rel, strlen((string)$_SERVER['DOCUMENT_ROOT']));
}
$rel = ltrim($rel, '/');
if ($doc === false || $rel === '' || strpos($rel, '..') !== false) {
    http_response_code(400);
    exit;
}

$target = realpath($doc . '/' . $rel);
if ($target === false || strpos($target, $doc . '/') !== 0) {
    http_response_code(400);
    exit;
}

$faction = new fileaction;
$faction->rmdir_recursive($target, $doc . '/system/users/' . $login . '/trash/');
