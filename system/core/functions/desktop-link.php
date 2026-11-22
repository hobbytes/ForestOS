<?php
/**
 * Forest OS — desktop shortcuts: delete (and inspect) a .link file.
 */
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function dl_fail(string $error, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $error));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    dl_fail('auth', 401);
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = (string)$_SESSION['loginuser'];
$desktop = $doc . '/system/users/' . $login . '/desktop';

$action = (string)($_POST['action'] ?? '');
$file = (string)($_POST['file'] ?? '');

/* the key is the absolute path of the .link file (as rendered by prepare.php) */
$file = str_replace('\\', '/', $file);
$real = realpath($file);
$dir = realpath($desktop);
if ($real === false || $dir === false || strpos($real, $dir . '/') !== 0 || substr($real, -5) !== '.link') {
    dl_fail('bad_link', 404);
}

if ($action === 'delete') {
    if (@unlink($real) === false) {
        dl_fail('unlink_failed', 500);
    }
    echo json_encode(array('ok' => true, 'deleted' => basename($real)));
    exit;
}

if ($action === 'info') {
    $ini = @parse_ini_file($real);
    echo json_encode(array('ok' => true, 'link' => is_array($ini) ? $ini : array()));
    exit;
}

dl_fail('unknown_action', 404);
