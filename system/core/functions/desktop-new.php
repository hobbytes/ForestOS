<?php
/**
 * Forest OS — создать папку или файл на рабочем столе.
 * POST: action=create-folder|create-file, name=<имя>
 */
ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function dn_fail(string $error, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $error));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    dn_fail('auth', 401);
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = (string)$_SESSION['loginuser'];
$desktop = $doc . '/system/users/' . $login . '/desktop';

if (!is_dir($desktop) && !@mkdir($desktop, 0777, true)) {
    dn_fail('no_desktop', 500);
}

$action = (string)($_POST['action'] ?? '');
$name = trim((string)($_POST['name'] ?? ''));
if ($action !== 'create-folder' && $action !== 'create-file') {
    dn_fail('unknown_action', 404);
}
if ($name === '') {
    dn_fail('no_name');
}

/* безопасное имя: без слэшей, точек в начале, служебных символов */
$name = str_replace(array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), '', $name);
$name = trim($name, " .\t\n\r");
if ($name === '' || mb_strlen($name) > 60) {
    dn_fail('bad_name');
}

function dn_link(string $desktop, string $file, array $data): bool
{
    $lines = array('[link]');
    foreach ($data as $k => $v) {
        $lines[] = $k . '="' . str_replace('"', '\"', (string)$v) . '"';
    }
    $ok = @file_put_contents($desktop . '/' . $file, implode("\n", $lines) . "\n");
    if ($ok !== false) {
        @chmod($desktop . '/' . $file, 0666);
    }
    return $ok !== false;
}

if ($action === 'create-folder') {
    $target = $desktop . '/' . $name;
    if (file_exists($target)) {
        dn_fail('exists', 409);
    }
    if (!@mkdir($target, 0777)) {
        dn_fail('mkdir', 500);
    }
    @chmod($target, 0777);
    $link = 'folder-' . substr(md5($target . microtime(true)), 0, 8) . '.link';
    dn_link($desktop, $link, array(
        'destination' => 'system/apps/Explorer/main.php',
        'file' => 'main',
        'key' => 'dir',
        'param' => $target,
        'name' => 'Explorer',
        'linkname' => $name,
        'icon' => 'system/apps/Explorer/assets/folderico.png',
    ));
    echo json_encode(array('ok' => true, 'kind' => 'folder', 'name' => $name), JSON_UNESCAPED_UNICODE);
    exit;
}

/* create-file */
$target = $desktop . '/' . $name;
if (file_exists($target)) {
    dn_fail('exists', 409);
}
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$app = 'Rich_Text_Editor';
$icon = 'system/apps/Rich_Text_Editor/app.png';
if (in_array($ext, array('png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'), true)) {
    $app = 'Image_Viewer';
    $icon = 'system/apps/Image_Viewer/app.png';
} elseif (in_array($ext, array('mp3', 'wav', 'ogg', 'aac', 'flac'), true)) {
    $app = 'Audio_Player';
    $icon = 'system/apps/Audio_Player/app.png';
}
if (@file_put_contents($target, '') === false) {
    dn_fail('write', 500);
}
@chmod($target, 0666);
$link = 'file-' . substr(md5($target . microtime(true)), 0, 8) . '.link';
dn_link($desktop, $link, array(
    'destination' => 'system/apps/' . $app . '/main.php',
    'file' => 'main',
    'key' => 'defaultloader',
    'param' => $target,
    'name' => $app,
    'linkname' => $name,
    'icon' => $icon,
));
echo json_encode(array('ok' => true, 'kind' => 'file', 'name' => $name, 'app' => $app), JSON_UNESCAPED_UNICODE);
