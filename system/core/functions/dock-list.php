<?php
/**
 * Forest OS — dock contents of the current user.
 */
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    http_response_code(401);
    echo json_encode(array('ok' => false, 'error' => 'auth'));
    exit;
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = (string)$_SESSION['loginuser'];
$dir = $doc . '/system/users/' . $login . '/settings/Dock';

$apps = array();
foreach (glob($dir . '/*/*') as $file) {
    $app = basename($file);
    if (!is_file($doc . '/system/apps/' . $app . '/main.php')) {
        continue;
    }
    if (in_array($app, array_column($apps, 'app'), true)) {
        continue;
    }
    $apps[] = array('app' => $app, 'title' => str_replace('_', ' ', $app));
}

echo json_encode(array('ok' => true, 'apps' => $apps), JSON_UNESCAPED_UNICODE);
