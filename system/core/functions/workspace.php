<?php
/**
 * Forest OS — сохранение и восстановление рабочего пространства.
 *
 *   GET  ?action=get     — состояние (окна, ревизия, token)
 *   POST ?action=save    — сохранить (JSON в теле, token в query)
 *   POST ?action=clear   — стереть (кнопка «Выключение»)
 */
ini_set('display_errors', 'Off');
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/workspace.php';

function ws_out(array $d, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$me = (string)($_SESSION['loginuser'] ?? '');
if (empty($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true || $me === '') {
    ws_out(array('ok' => false, 'error' => 'auth'), 401);
}

/* выкинутая сессия не должна сохранять своё состояние поверх нового устройства */
require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/session.php';
if (!ForestSession::validate($me, (string)($_SESSION['fo_session_token'] ?? ''))) {
    ws_out(array('ok' => false, 'error' => 'kicked'), 401);
}

if (empty($_SESSION['fo_workspace_csrf'])) {
    $_SESSION['fo_workspace_csrf'] = bin2hex(random_bytes(16));
}
$token = (string)$_SESSION['fo_workspace_csrf'];

$action = (string)($_GET['action'] ?? 'get');

if ($action === 'get') {
    $ws = ForestWorkspace::load($me);
    ws_out(array('ok' => true, 'token' => $token, 'workspace' => $ws));
}

if (!isset($_GET['token']) || !hash_equals($token, (string)$_GET['token'])) {
    ws_out(array('ok' => false, 'error' => 'csrf'), 419);
}

if ($action === 'save') {
    $raw = (string)file_get_contents('php://input');
    if ($raw === '') {
        $raw = (string)($_POST['payload'] ?? '');
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        ws_out(array('ok' => false, 'error' => 'bad_json'), 400);
    }
    $res = ForestWorkspace::save($me, $data);
    ws_out($res, $res['ok'] ? 200 : ($res['conflict'] ? 409 : 500));
}

if ($action === 'clear') {
    ws_out(array('ok' => ForestWorkspace::clear($me)));
}

ws_out(array('ok' => false, 'error' => 'unknown_action'), 404);
