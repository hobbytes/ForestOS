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

$action = isset($_POST['action']) ? (string)$_POST['action'] : (isset($_GET['action']) ? (string)$_GET['action'] : 'status');
$up = new OsUpdate();

if ($action === 'status') {
    echo json_encode(array(
        'ok' => true,
        'current' => $up->current(),
        'feed' => $up->feedUrl(),
        'superuser' => (string)($_SESSION['superuser'] ?? '') === (string)$_SESSION['loginuser'],
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
    if ((string)($_SESSION['superuser'] ?? '') !== (string)$_SESSION['loginuser']) {
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

ou_fail('unknown_action', 404);
