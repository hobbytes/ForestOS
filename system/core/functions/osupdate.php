<?php
/**
 * Forest OS — API обновления системы.
 * GET  ?action=status   — текущая версия, источник, последняя проверка
 * GET  ?action=check    — запросить update.json и сравнить версии
 * POST ?action=install  — скачать и установить (только суперпользователь, token)
 */
ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function ou_fail(string $error, int $code = 400, array $extra = array()): void
{
    http_response_code($code);
    echo json_encode(array_merge(array('ok' => false, 'error' => $error), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['loginuser']) || empty($_SESSION['is_authuser'])) {
    ou_fail('auth', 401);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/osupdate.php';

/** Значение из .env (в веб-запросе переменные окружения контейнера не всегда видны). */
function ou_env(string $key, string $default = ''): string
{
    static $cfg = null;
    if ($cfg === null) {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/.env';
        $cfg = is_file($file) ? (array)@parse_ini_file($file) : array();
    }
    $v = $cfg[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : (string)$v;
}

/** Кто может публиковать релизы и ставить обновления. */
function ou_can_manage(): bool
{
    $me = (string)($_SESSION['loginuser'] ?? '');
    $super = (string)($_SESSION['superuser'] ?? '');
    if ($me === '' || $me !== $super) {
        return false;
    }
    $only = ou_env('FOREST_RELEASE_LOGIN', '');
    return $only === '' || $only === $me;
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : (isset($_GET['action']) ? (string)$_GET['action'] : 'status');
$up = new OsUpdate();

if ($action === 'status') {
    echo json_encode(array(
        'ok' => true,
        'current' => $up->current(),
        'feed' => $up->feedUrl(),
        'superuser' => ou_can_manage(),
        'can_write' => is_writable($_SERVER['DOCUMENT_ROOT'] . '/system/core'),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'check') {
    $res = $up->check();
    echo json_encode($res + array('ok' => !empty($res['ok'])), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'install') {
    if (!ou_can_manage()) {
        ou_fail('forbidden', 403);
    }
    $token = isset($_POST['token']) ? (string)$_POST['token'] : '';
    $csrf = (string)($_SESSION['fo_update_csrf'] ?? '');
    if ($csrf === '' || !hash_equals($csrf, $token)) {
        ou_fail('csrf', 403);
    }
    $res = $up->install();
    echo json_encode($res + array('ok' => !empty($res['ok'])), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'release') {
    if (!ou_can_manage()) {
        ou_fail('forbidden', 403);
    }
    $token = isset($_POST['token']) ? (string)$_POST['token'] : '';
    $csrf = (string)($_SESSION['fo_update_csrf'] ?? '');
    if ($csrf === '' || !hash_equals($csrf, $token)) {
        ou_fail('csrf', 403);
    }
    $version = isset($_POST['version']) ? trim((string)$_POST['version']) : '';
    $notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : '';
    if ($version === '') {
        ou_fail('no_version');
    }
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bin/release.php';
    $res = rl_build($version, $notes);
    echo json_encode($res + array('ok' => !empty($res['ok'])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

ou_fail('unknown_action', 404);
