<?php
/* ==========================================================================
   Forest OS — Rich Text Editor 2.0: save endpoint
   --------------------------------------------------------------------------
   POST (application/x-www-form-urlencoded):
     token      — CSRF token from main.php (session)
     mode       — "save" (existing file) | "saveas" (documents folder)
     html, text — document body in both flavours; the file extension decides
     file       — for mode=save: path relative to DOCUMENT_ROOT
     name       — for mode=saveas: file name inside system/users/<login>/documents
     overwrite  — for mode=saveas: "1" allows replacing an existing file

   Every target must resolve inside DOCUMENT_ROOT. system/core, os.php,
   login.php and the RTE2 sources themselves are never writable.
   ========================================================================== */

ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/session.php';

function rte2_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$DOC = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
$me = (string)($_SESSION['loginuser'] ?? '');

if (empty($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true || $me === '') {
    rte2_out(array('ok' => false, 'error' => 'auth'), 401);
}
if (!ForestSession::validate($me, (string)($_SESSION['fo_session_token'] ?? ''))) {
    rte2_out(array('ok' => false, 'error' => 'kicked'), 401);
}
if (!preg_match('/^[A-Za-z0-9_.\-]{1,64}$/', $me)) {
    rte2_out(array('ok' => false, 'error' => 'auth'), 403);
}

$sessionToken = (string)($_SESSION['rte2_csrf'] ?? '');
$postedToken = (string)($_POST['token'] ?? '');
if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
    rte2_out(array('ok' => false, 'error' => 'token'), 403);
}

$mode = (string)($_POST['mode'] ?? '');
$html = isset($_POST['html']) ? (string)$_POST['html'] : '';
$text = isset($_POST['text']) ? (string)$_POST['text'] : '';

if (strlen($html) > 4 * 1024 * 1024 || strlen($text) > 4 * 1024 * 1024) {
    rte2_out(array('ok' => false, 'error' => 'too_large'), 413);
}

function rte2_inside(string $abs, string $doc): bool
{
    $abs = str_replace('\\', '/', $abs);
    $doc = rtrim(str_replace('\\', '/', $doc), '/');
    return $abs === $doc || strpos($abs, $doc . '/') === 0;
}

function rte2_forbidden(string $abs, string $doc): bool
{
    $abs = str_replace('\\', '/', $abs);
    $doc = rtrim(str_replace('\\', '/', $doc), '/');
    $lower = strtolower(substr($abs, strlen($doc)));
    if (strpos($lower, '/system/core/') === 0 || $lower === '/system/core') {
        return true;
    }
    $base = strtolower(basename($abs));
    if ($base === 'os.php' || $base === 'login.php') {
        return true;
    }
    if (strpos($lower, '/system/apps/rte2/') === 0) {
        return true;
    }
    return false;
}

function rte2_format_of(string $path): string
{
    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, array('html', 'htm', 'xhtml'), true) ? 'html' : 'text';
}

function rte2_rel(string $abs, string $doc): string
{
    return ltrim(str_replace('\\', '/', substr($abs, strlen(rtrim($doc, '/')))), '/');
}

function rte2_write(string $path, string $data): bool
{
    $ok = @file_put_contents($path, $data, LOCK_EX);
    if ($ok === false) {
        @chmod($path, 0666);
        $ok = @file_put_contents($path, $data, LOCK_EX);
    }
    return $ok !== false;
}

function rte2_has_dotdot(string $rel): bool
{
    foreach (explode('/', str_replace('\\', '/', $rel)) as $segment) {
        if ($segment === '..') {
            return true;
        }
    }
    return false;
}

if ($mode === 'save') {
    $file = str_replace('\\', '/', (string)($_POST['file'] ?? ''));
    if ($file === '') {
        rte2_out(array('ok' => false, 'error' => 'bad_path'), 400);
    }
    $rel = ltrim($file, '/');
    if (rte2_has_dotdot($rel)) {
        rte2_out(array('ok' => false, 'error' => 'bad_path'), 400);
    }
    $tokens = explode('/', $rel);
    foreach ($tokens as $token) {
        if ($token === '' || $token === '.') {
            rte2_out(array('ok' => false, 'error' => 'bad_path'), 400);
        }
    }
    $candidate = $DOC . '/' . $rel;
    $dirReal = @realpath(dirname($candidate));
    if ($dirReal === false) {
        rte2_out(array('ok' => false, 'error' => 'not_found'), 404);
    }
    $abs = str_replace('\\', '/', $dirReal) . '/' . basename($candidate);
    if (!rte2_inside($abs, $DOC)) {
        rte2_out(array('ok' => false, 'error' => 'outside'), 403);
    }
    if (rte2_forbidden($abs, $DOC)) {
        rte2_out(array('ok' => false, 'error' => 'forbidden'), 403);
    }
    if (!is_file($abs)) {
        rte2_out(array('ok' => false, 'error' => 'not_found'), 404);
    }
    $format = rte2_format_of($abs);
    $payload = ($format === 'html') ? $html : $text;
    if (!rte2_write($abs, $payload)) {
        rte2_out(array('ok' => false, 'error' => 'write'), 500);
    }
    rte2_out(array(
        'ok' => true,
        'file' => rte2_rel($abs, $DOC),
        'name' => basename($abs),
        'format' => $format,
        'bytes' => strlen($payload),
    ));
}

if ($mode === 'saveas') {
    $name = (string)($_POST['name'] ?? '');
    $name = str_replace('\\', '/', $name);
    $name = basename($name);
    $name = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F\x7F]/u', '_', $name);
    if (!is_string($name)) {
        rte2_out(array('ok' => false, 'error' => 'bad_name'), 400);
    }
    $name = trim($name, " .\t\n\r");
    if ($name === '' || $name === '.' || $name === '..') {
        rte2_out(array('ok' => false, 'error' => 'bad_name'), 400);
    }
    $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
    if ($nameLength > 120) {
        rte2_out(array('ok' => false, 'error' => 'bad_name'), 400);
    }
    if (!preg_match('/\.[A-Za-z0-9]{1,8}$/', $name)) {
        $name .= '.html';
    }

    $dir = $DOC . '/system/users/' . $me . '/documents';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $dirReal = @realpath($dir);
    if ($dirReal === false || !is_dir($dirReal)) {
        rte2_out(array('ok' => false, 'error' => 'write'), 500);
    }
    $dirReal = str_replace('\\', '/', $dirReal);
    if (!rte2_inside($dirReal, $DOC)) {
        rte2_out(array('ok' => false, 'error' => 'outside'), 403);
    }

    $abs = $dirReal . '/' . $name;
    if (rte2_forbidden($abs, $DOC)) {
        rte2_out(array('ok' => false, 'error' => 'forbidden'), 403);
    }
    if (is_file($abs) && (string)($_POST['overwrite'] ?? '') !== '1') {
        rte2_out(array('ok' => false, 'error' => 'exists', 'name' => $name), 409);
    }

    $format = rte2_format_of($abs);
    $payload = ($format === 'html') ? $html : $text;
    if (!rte2_write($abs, $payload)) {
        rte2_out(array('ok' => false, 'error' => 'write'), 500);
    }

    rte2_out(array(
        'ok' => true,
        'file' => rte2_rel($abs, $DOC),
        'name' => basename($abs),
        'format' => $format,
        'bytes' => strlen($payload),
    ));
}

rte2_out(array('ok' => false, 'error' => 'bad_mode'), 400);
