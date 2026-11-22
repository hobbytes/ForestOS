<?php
/**
 * Forest OS — save desktop icon positions per device.
 * Stores settings/desktop-desktop.json or settings/desktop-mobile.json.
 */
ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function fo_fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    fo_fail('auth', 401);
}

$device = (isset($_POST['device']) && $_POST['device'] === 'mobile') ? 'mobile' : 'desktop';
$raw = isset($_POST['layout']) ? (string)$_POST['layout'] : '';
$layout = json_decode($raw, true);
if (!is_array($layout)) {
    fo_fail('layout');
}

$login = (string)$_SESSION['loginuser'];
$home = realpath($_SERVER['DOCUMENT_ROOT'] . '/system/users/' . $login);
if ($home === false) {
    fo_fail('home');
}
$home = rtrim(str_replace('\\', '/', $home), '/');

$clean = array();
foreach ($layout as $key => $pos) {
    if (!is_string($key) || !is_array($pos)) {
        continue;
    }
    $real = realpath($key);
    if ($real === false) {
        continue;
    }
    $real = str_replace('\\', '/', $real);
    if (strpos($real, $home . '/desktop/') !== 0 || substr($real, -5) !== '.link') {
        continue;
    }
    $x = (int)(isset($pos['x']) ? $pos['x'] : 0);
    $y = (int)(isset($pos['y']) ? $pos['y'] : 0);
    if ($x < 0) $x = 0;
    if ($y < 0) $y = 0;
    if ($x > 6000) $x = 6000;
    if ($y > 6000) $y = 6000;
    $clean[$key] = array('x' => $x, 'y' => $y);
}

$settings = $home . '/settings';
if (!is_dir($settings)) {
    @mkdir($settings, 0775, true);
}
$file = $settings . '/desktop-' . $device . '.json';
if (@file_put_contents($file, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
    fo_fail('write', 500);
}

echo json_encode(array('ok' => true, 'count' => count($clean), 'device' => $device));
