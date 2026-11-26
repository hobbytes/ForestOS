<?php
/**
 * Forest OS — данные системного монитора (JSON).
 */
ini_set('display_errors', 'Off');
if (!isset($_SESSION)) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['loginuser']) || empty($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    http_response_code(401);
    echo json_encode(array('ok' => false, 'error' => 'auth'));
    exit;
}

function mon_out(array $d): void
{
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$doc = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');

/* загрузка */
$load = function_exists('sys_getloadavg') ? @sys_getloadavg() : array(0, 0, 0);
if (!is_array($load) || count($load) < 3) {
    $load = array(0, 0, 0);
}

/* память */
$memTotal = 0;
$memUsed = 0;
$mi = @file_get_contents('/proc/meminfo');
if (is_string($mi) && $mi !== '') {
    $vals = array();
    foreach (explode("\n", $mi) as $line) {
        if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
            $vals[$m[1]] = (int)$m[2] * 1024;
        }
    }
    $memTotal = (int)($vals['MemTotal'] ?? 0);
    $avail = (int)($vals['MemAvailable'] ?? ($vals['MemFree'] ?? 0));
    $memUsed = max(0, $memTotal - $avail);
}

/* диск */
$diskTotal = (float)@disk_total_space($doc);
$diskFree = (float)@disk_free_space($doc);
$diskTotal = $diskTotal > 0 ? $diskTotal : 0;
$diskUsed = max(0, $diskTotal - $diskFree);

/* аптайм */
$uptime = 0.0;
$up = @file_get_contents('/proc/uptime');
if (is_string($up) && trim($up) !== '') {
    $uptime = (float)explode(' ', trim($up))[0];
}

/* приложения */
$apps = 0;
foreach (glob($doc . '/system/apps/*', GLOB_ONLYDIR) ?: array() as $dir) {
    if (is_file($dir . '/main.php')) {
        $apps++;
    }
}

/* активные сессии и пользователи */
$sessions = 0;
$users = 0;
try {
    require_once $doc . '/system/core/library/bd_inc.php';
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $users = (int)$pdo->query('SELECT COUNT(*) FROM forestusers')->fetchColumn();
    try {
        $sessions = (int)$pdo->query('SELECT COUNT(*) FROM forestusers WHERE session_token IS NOT NULL AND session_token <> ""')->fetchColumn();
    } catch (Throwable $e) {
        $sessions = 0;
    }
} catch (Throwable $e) {
    $users = 0;
}

mon_out(array(
    'ok' => true,
    'load' => array((float)$load[0], (float)$load[1], (float)$load[2]),
    'mem_total' => $memTotal,
    'mem_used' => $memUsed,
    'disk_total' => $diskTotal,
    'disk_used' => $diskUsed,
    'uptime' => $uptime,
    'apps' => $apps,
    'sessions' => $sessions,
    'users' => $users,
    'php' => PHP_VERSION,
    'server' => (string)($_SERVER['SERVER_SOFTWARE'] ?? 'unknown'),
    'time' => time(),
));
