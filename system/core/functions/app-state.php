<?php
/**
 * Forest OS — состояние приложения (черновик) для текущего пользователя.
 *
 *   GET  ?action=get&app=<App>     — {ok, state}
 *   POST ?action=save              — JSON {app, state}, не больше 200 КБ
 *   POST ?action=clear&app=<App>   — удалить черновик
 *
 * Хранится в system/users/<login>/settings/appstate/<App>.json, чтобы
 * недописанное переживало выход и переезд на другое устройство.
 */
ini_set('display_errors', 'Off');
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/session.php';

function st_out(array $d, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$me = (string)($_SESSION['loginuser'] ?? '');
if (empty($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true || $me === '') {
    st_out(array('ok' => false, 'error' => 'auth'), 401);
}
if (!ForestSession::validate($me, (string)($_SESSION['fo_session_token'] ?? ''))) {
    st_out(array('ok' => false, 'error' => 'kicked'), 401);
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$dir = $doc . '/system/users/' . $me . '/settings/appstate';

function st_app(string $name): string
{
    return preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $name) ? $name : '';
}

$action = (string)($_GET['action'] ?? '');

if ($action === 'get') {
    $app = st_app((string)($_GET['app'] ?? ''));
    if ($app === '') {
        st_out(array('ok' => false, 'error' => 'bad_app'), 400);
    }
    $file = $dir . '/' . $app . '.json';
    if (!is_file($file)) {
        st_out(array('ok' => true, 'state' => null));
    }
    $state = json_decode((string)@file_get_contents($file), true);
    st_out(array('ok' => true, 'state' => is_array($state) ? $state : null));
}

if ($action === 'save') {
    $raw = (string)file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        st_out(array('ok' => false, 'error' => 'bad_json'), 400);
    }
    $app = st_app((string)($data['app'] ?? ''));
    if ($app === '') {
        st_out(array('ok' => false, 'error' => 'bad_app'), 400);
    }
    $state = $data['state'] ?? null;
    if (!is_array($state)) {
        st_out(array('ok' => false, 'error' => 'bad_state'), 400);
    }
    $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || strlen($json) > 204800) {
        st_out(array('ok' => false, 'error' => 'too_large'), 413);
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $state['saved_at'] = time();
    $file = $dir . '/' . $app . '.json';
    $tmp = $file . '.tmp' . bin2hex(random_bytes(3));
    if (@file_put_contents($tmp, $json) === false || !@rename($tmp, $file)) {
        @unlink($tmp);
        st_out(array('ok' => false, 'error' => 'write_failed'), 500);
    }
    @chmod($file, 0666);
    st_out(array('ok' => true, 'bytes' => strlen($json)));
}

if ($action === 'clear') {
    $app = st_app((string)($_POST['app'] ?? ($_GET['app'] ?? '')));
    if ($app === '') {
        st_out(array('ok' => false, 'error' => 'bad_app'), 400);
    }
    @unlink($dir . '/' . $app . '.json');
    st_out(array('ok' => true));
}

st_out(array('ok' => false, 'error' => 'unknown_action'), 404);
