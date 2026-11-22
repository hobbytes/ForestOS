<?php
/**
 * Forest OS — appearance settings (accent colour + window frame).
 * Stored per user in settings/appearance.foc.
 */
ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function fo_ap_fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    fo_ap_fail('auth', 401);
}

$login = (string)$_SESSION['loginuser'];
$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$file = $doc . '/system/users/' . $login . '/settings/appearance.foc';

$cur = @parse_ini_file($file);
if (!is_array($cur)) { $cur = array(); }

$accent = isset($cur['accent']) ? trim((string)$cur['accent'], " ;") : '#5fe08c';
if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $accent)) { $accent = '#5fe08c'; }
$frame = !isset($cur['frame']) || (string)$cur['frame'] === '1';
$frame = $frame ? '1' : '0';

$changed = false;

if (isset($_POST['accent'])) {
    $a = trim((string)$_POST['accent'], " ;");
    if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $a)) {
        fo_ap_fail('accent');
    }
    $accent = strtolower($a);
    $changed = true;
}

if (isset($_POST['frame'])) {
    $f = (string)$_POST['frame'];
    $frame = ($f === '1' || $f === 'on' || $f === 'true') ? '1' : '0';
    $changed = true;
}

if ($changed) {
    $dir = dirname($file);
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $body = 'accent="' . $accent . '"' . "\n" . 'frame="' . $frame . '"' . "\n";
    $tmp = $file . '.tmp' . getmypid();
    $ok = (@file_put_contents($tmp, $body) !== false);
    if ($ok) { @chmod($tmp, 0666); $ok = @rename($tmp, $file); }
    if (!$ok) {
        @unlink($tmp);
        if (@file_put_contents($file, $body) === false) {
            fo_ap_fail('write', 500);
        }
    }
}

echo json_encode(array('ok' => true, 'accent' => $accent, 'frame' => $frame));
