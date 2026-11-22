<?php
/**
 * Forest OS — список и снятие блокировок IP (fail2ban).
 * Доступно только администратору.
 */
ini_set('display_errors', 'Off');
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/bans.php';

function bans_out(array $d, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$me = (string)($_SESSION['loginuser'] ?? '');
if (empty($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true || $me === '') {
    bans_out(array('ok' => false, 'error' => 'auth'), 401);
}
if ($me !== (string)($_SESSION['superuser'] ?? '')) {
    bans_out(array('ok' => false, 'error' => 'forbidden'), 403);
}

$action = (string)($_POST['action'] ?? ($_GET['action'] ?? ''));
$token = (string)($_SESSION['fo_settings_csrf'] ?? '');

if ($action === '' || $action === 'list') {
    bans_out(array('ok' => true, 'bans' => ForestBans::active(), 'now' => time()));
}

if (!isset($_POST['token']) || $token === '' || !hash_equals($token, (string)$_POST['token'])) {
    bans_out(array('ok' => false, 'error' => 'csrf'), 419);
}

if ($action === 'unban') {
    $ip = (string)($_POST['ip'] ?? '');
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        bans_out(array('ok' => false, 'error' => 'bad_ip'), 400);
    }
    $ok = ForestBans::unban($ip);
    bans_out(array('ok' => $ok, 'ip' => $ip, 'error' => $ok ? '' : 'not_found'));
}

if ($action === 'unban-all') {
    $n = ForestBans::unbanAll();
    bans_out(array('ok' => true, 'removed' => $n));
}

bans_out(array('ok' => false, 'error' => 'unknown_action'), 404);
