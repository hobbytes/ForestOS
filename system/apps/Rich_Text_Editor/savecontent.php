<?php
/**
 * Forest OS — сохранение содержимого Rich Text Editor.
 * POST: content=<текст>, folder=<абсолютный путь к файлу>
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function sc_fail(string $error, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $error));
    exit;
}

if (empty($_SESSION['loginuser']) || empty($_SESSION['is_authuser'])) {
    sc_fail('auth', 401);
}

$content = isset($_POST['content']) ? (string)$_POST['content'] : '';
$folder = isset($_POST['folder']) ? str_replace('\\', '/', (string)$_POST['folder']) : '';
$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');

if ($folder === '') {
    sc_fail('path');
}

$real = realpath($folder);
if ($real === false) {
    sc_fail('not_found', 404);
}
$real = str_replace('\\', '/', $real);

/* только внутри сайта */
if (strpos($real, $doc . '/') !== 0) {
    sc_fail('outside', 403);
}

/* системные файлы правит только суперпользователь */
$login = (string)$_SESSION['loginuser'];
$super = isset($_SESSION['superuser']) ? (string)$_SESSION['superuser'] : '';
if ($login !== $super) {
    if (stripos($real, '/system/core/') !== false || substr($real, -7) === '/os.php' || substr($real, -10) === '/login.php') {
        sc_fail('forbidden', 403);
    }
}

if (!is_file($real)) {
    sc_fail('not_found', 404);
}
if (!is_writable($real)) {
    @chmod($real, 0666);
}
if (@file_put_contents($real, $content) === false) {
    sc_fail('write', 500);
}

echo json_encode(array(
    'ok' => true,
    'bytes' => strlen($content),
    'file' => basename($real),
), JSON_UNESCAPED_UNICODE);
