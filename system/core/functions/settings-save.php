<?php
/**
 * Forest OS — settings persistence (autorun, locale, timezone).
 * The appearance / window / dock endpoints already exist separately.
 */
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function st_fail(string $error, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $error));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    st_fail('auth', 401);
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = (string)$_SESSION['loginuser'];
$home = $doc . '/system/users/' . $login;
$settings = $home . '/settings';
if (!is_dir($settings)) {
    @mkdir($settings, 0777, true);
}

$token = isset($_POST['token']) ? (string)$_POST['token'] : '';
if (empty($_SESSION['fo_settings_csrf']) || !hash_equals((string)$_SESSION['fo_settings_csrf'], $token)) {
    st_fail('csrf', 419);
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';

if ($action === 'autorun') {
    $raw = isset($_POST['apps']) ? (string)$_POST['apps'] : '';
    $apps = array();
    foreach (explode(',', $raw) as $app) {
        $app = preg_replace('/[^A-Za-z0-9_.\-]/', '', trim($app));
        if ($app === '' || !is_file($doc . '/system/apps/' . $app . '/main.php')) {
            continue;
        }
        if (!in_array($app, $apps, true)) {
            $apps[] = $app;
        }
    }
    $file = $settings . '/autorun.foc';
    if (@file_put_contents($file, implode(',', $apps)) === false) {
        st_fail('write_failed', 500);
    }
    @chmod($file, 0666);
    echo json_encode(array('ok' => true, 'apps' => $apps));
    exit;
}

if ($action === 'locale') {
    $lang = isset($_POST['locale']) ? (string)$_POST['locale'] : '';
    if (!in_array($lang, array('ru', 'en'), true)) {
        st_fail('bad_locale');
    }
    @file_put_contents($settings . '/language.foc', $lang);
    @chmod($settings . '/language.foc', 0666);
    $_SESSION['locale'] = $lang;
    echo json_encode(array('ok' => true, 'locale' => $lang));
    exit;
}

if ($action === 'timezone') {
    $zone = isset($_POST['timezone']) ? (string)$_POST['timezone'] : '';
    if (!preg_match('#^[A-Za-z_]+/[A-Za-z_+\-0-9]+$#', $zone)) {
        st_fail('bad_timezone');
    }
    @file_put_contents($settings . '/timezone.foc', $zone);
    @chmod($settings . '/timezone.foc', 0666);
    $_SESSION['timezone'] = $zone;
    echo json_encode(array('ok' => true, 'timezone' => $zone));
    exit;
}

if ($action === 'emptrash') {
    $trash = $home . '/trash';
    $removed = 0;
    if (is_dir($trash)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($trash, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                if (@unlink($file->getPathname())) {
                    $removed++;
                }
            }
        }
    }
    echo json_encode(array('ok' => true, 'removed' => $removed));
    exit;
}

if ($action === 'dark') {
    $val = ((string)($_POST['value'] ?? '0') === '1') ? '1' : '0';
    $file = $settings . '/etc/dark.foc';
    if (!is_dir(dirname($file))) { @mkdir(dirname($file), 0777, true); }
    if (@file_put_contents($file, $val) === false) {
        st_fail('write_failed', 500);
    }
    @chmod($file, 0666);
    echo json_encode(array('ok' => true, 'dark' => $val));
    exit;
}

st_fail('unknown_action', 404);
