<?php
/**
 * Forest OS — apply a theme file from system/core/design/themes to the current user.
 */
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function th_fail(string $error, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $error));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    th_fail('auth', 401);
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = (string)$_SESSION['loginuser'];
$file = isset($_POST['theme']) ? basename((string)$_POST['theme']) : '';
if (!preg_match('/^[A-Za-z0-9_.\-]+\.fth$/', $file)) {
    th_fail('bad_theme');
}

$src = $doc . '/system/core/design/themes/' . $file;
if (!is_file($src)) {
    th_fail('theme_not_found', 404);
}

$dir = $doc . '/system/users/' . $login . '/settings/etc';
if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}
$dst = $dir . '/theme.fth';
if (@copy($src, $dst) === false) {
    th_fail('write_failed', 500);
}
@chmod($dst, 0666);
@file_put_contents($dir . '/theme.src', $file);
@chmod($dir . '/theme.src', 0666);

$th = @parse_ini_file($dst);
if (!is_array($th)) {
    $th = array();
}
$clean = function ($v) { return trim((string)$v, " ;"); };
$chrome = $clean($th['topbarbackcolor'] ?? '');
$accent = $clean($th['accentcolor'] ?? '');

echo json_encode(array(
    'ok' => true,
    'theme' => $file,
    'name' => (string)($th['Name'] ?? $file),
    'accent' => $accent,
    'topbar' => $chrome !== '' ? $chrome : 'rgba(28,28,30,0.9)',
    'topbarText' => $clean($th['topbarfontcolor'] ?? '') ?: '#ffffff',
    'menu' => $clean($th['menubackcolor'] ?? '') ?: 'rgba(28,28,30,0.95)',
    'shellBase' => preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $chrome, $m)
        ? (((int)$m[1] + (int)$m[2] + (int)$m[3]) / 3 > 150 ? '#f2f3f2' : '#101312')
        : '#101312',
), JSON_UNESCAPED_UNICODE);
