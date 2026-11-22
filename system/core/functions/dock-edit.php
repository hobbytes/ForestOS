<?php
/**
 * Forest OS — pin/unpin applications and folders in the dock.
 * App pins:    settings/Dock/<group>/<App>       (empty file)
 * Folder pins: settings/Dock/<group>/folder_<md5> (file content = path inside the user home)
 */
ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function fo_dock_fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    fo_dock_fail('auth', 401);
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = (string)$_SESSION['loginuser'];
$home = $doc . '/system/users/' . $login;

$app = isset($_POST['app']) ? basename((string)$_POST['app']) : '';
$folder = isset($_POST['folder']) ? (string)$_POST['folder'] : '';
$action = isset($_POST['action']) ? (string)$_POST['action'] : '';

$dock = $home . '/settings/Dock';
if (!is_dir($dock . '/A')) {
    @mkdir($dock . '/A', 0775, true);
}

/* ---------- folder pins ---------- */
if ($folder !== '') {
    $rel = str_replace('\\', '/', $folder);
    $rel = preg_replace('#/+#', '/', $rel);
    $rel = ltrim($rel, '/');
    /* абсолютный путь внутри сайта приводим к виду от корня */
    if (strpos($rel, $doc . '/') === 0) {
        $rel = substr($rel, strlen($doc) + 1);
    }
    $rel = ltrim($rel, '/');

    $homeRel = preg_replace('#^system/users/' . preg_quote($login, '#') . '/#', '', $rel);
    $homeAbs = $doc . '/system/users/' . $login . '/' . $homeRel;
    $docAbs  = $doc . '/' . $rel;

    if (is_dir($homeAbs)) {
        /* папка пользователя: храним путь от домашней папки */
        $store = $homeRel;
        $abs = $homeAbs;
    } elseif (is_dir($docAbs)) {
        /* системная папка: '@' — путь от корня сайта */
        $store = '@' . $rel;
        $abs = $docAbs;
    } else {
        fo_dock_fail('folder');
    }
    $name = 'folder_' . md5($store);

    if ($action === 'pin') {
        if (@file_put_contents($dock . '/A/' . $name, $store) === false) {
            fo_dock_fail('write', 500);
        }
        @chmod($dock . '/A/' . $name, 0666);
        echo json_encode(array('ok' => true, 'pinned' => $name, 'folder' => $store, 'title' => basename($abs)), JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'unpin') {
        $n = 0;
        foreach (glob($dock . '/*/' . $name) as $file) {
            if (@unlink($file)) { $n++; }
        }
        echo json_encode(array('ok' => true, 'unpinned' => $name, 'removed' => $n));
        exit;
    }
    fo_dock_fail('action');
}

/* ---------- application pins ---------- */
if ($app === '' || !preg_match('/^[A-Za-z0-9_\-]+$/', $app) || !is_file($doc . '/system/apps/' . $app . '/main.php')) {
    fo_dock_fail('app');
}

if ($action === 'pin') {
    if (@file_put_contents($dock . '/A/' . $app, '') === false) {
        fo_dock_fail('write', 500);
    }
    echo json_encode(array('ok' => true, 'pinned' => $app));
    exit;
}

if ($action === 'unpin') {
    $removed = 0;
    foreach (glob($dock . '/*/*') as $file) {
        if (is_file($file) && basename($file) === $app) {
            if (@unlink($file)) { $removed++; }
        }
    }
    echo json_encode(array('ok' => true, 'unpinned' => $app, 'removed' => $removed));
    exit;
}

fo_dock_fail('action');
