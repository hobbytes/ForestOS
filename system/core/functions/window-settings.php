<?php
/**
 * Forest OS — window chrome settings (ControlMode / WindowSize / ButtonSize).
 * Writes system/users/<login>/settings/AppWindow.foc and returns JSON.
 */
ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function fo_win_fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    fo_win_fail('auth', 401);
}

$login = (string)$_SESSION['loginuser'];
$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$file = $doc . '/system/users/' . $login . '/settings/AppWindow.foc';

$cur = @parse_ini_file($file);
if (!is_array($cur)) { $cur = array(); }

$mode = (isset($cur['ControlMode']) && (string)$cur['ControlMode'] === '2') ? '2' : '1';
$size = isset($cur['WindowSize']) ? (int)$cur['WindowSize'] : 8;
$btn  = isset($cur['ButtonSize']) ? (int)$cur['ButtonSize'] : 12;

$changed = false;

if (isset($_POST['controlmode'])) {
    $mode = ((string)$_POST['controlmode'] === '2') ? '2' : '1';
    $changed = true;
}
if (isset($_POST['windowsize'])) {
    $size = (int)$_POST['windowsize'];
    $changed = true;
}
if (isset($_POST['buttonsize'])) {
    $btn = (int)$_POST['buttonsize'];
    $changed = true;
}

$size = max(5, min(12, $size));
$btn = max(8, min(18, $btn));

if ($changed) {
    $dir = dirname($file);
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $body = "[WindowSetting]\nControlMode='" . $mode . "'\nWindowSize='" . $size . "'\nButtonSize='" . $btn . "'\n";
    // Atomic write: the settings file may belong to another uid, so replacing it
    // through the (writable) directory is more reliable than truncating in place.
    $tmp = $file . '.tmp' . getmypid();
    $ok = (@file_put_contents($tmp, $body) !== false);
    if ($ok) { @chmod($tmp, 0666); $ok = @rename($tmp, $file); }
    if (!$ok) {
        @unlink($tmp);
        if (@file_put_contents($file, $body) === false) {
            fo_win_fail('write', 500);
        }
    }
}

echo json_encode(array(
    'ok' => true,
    'controlmode' => $mode,
    'windowsize' => $size,
    'buttonsize' => $btn
));
