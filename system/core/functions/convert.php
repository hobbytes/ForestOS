<?php
/**
 * Forest OS — converter backend: analyse and convert legacy apps to theme tokens.
 */
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function cv_fail(string $error, int $code = 400, array $extra = array()): void
{
    http_response_code($code);
    echo json_encode(array_merge(array('ok' => false, 'error' => $error), $extra));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    cv_fail('auth', 401);
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$appsDir = $doc . '/system/apps';

$token = (string)($_POST['token'] ?? '');
if (empty($_SESSION['fo_conv_csrf']) || !hash_equals((string)$_SESSION['fo_conv_csrf'], $token)) {
    cv_fail('csrf', 419);
}

$action = (string)($_POST['action'] ?? '');
$app = preg_replace('/[^A-Za-z0-9_.\-]/', '', (string)($_POST['app'] ?? ''));
$file = $appsDir . '/' . $app . '/main.php';
if ($app === '' || !is_file($file)) {
    cv_fail('app_not_found', 404);
}

function cv_src(string $file): string
{
    return (string)@file_get_contents($file);
}

function cv_analyze(string $file): array
{
    $src = cv_src($file);
    $colors = 0;
    if (preg_match_all('/#fff(?:fff)?\b|#ffffff\b|color\s*:\s*(?:white|black)\b|background(?:-color)?\s*:\s*#[0-9a-f]{3,6}/i', $src, $m)) {
        $colors = count($m[0]);
    }
    return array(
        'container' => (bool)preg_match('/new\s+AppContainer\s*\(/i', $src),
        'start' => (bool)preg_match('/->\s*StartContainer\s*\(/i', $src),
        'end' => (bool)preg_match('/->\s*EndContainer\s*\(/i', $src),
        'vars' => (strpos($src, '$Folder') !== false) && (strpos($src, '$AppID') !== false),
        'appgui' => (strpos($src, '$AppGUI') !== false),
        'eval' => (bool)preg_match('/\beval\s*\(/i', $src),
        'colors' => $colors,
        'size' => strlen($src),
    );
}

function cv_plan(string $src): array
{
    $rules = array(
        array(
            'label' => 'фон контейнера → токен',
            're' => '/(\$AppContainer\s*->\s*backgroundColor\s*=\s*)[\'"][^\'"]*[\'"]\s*;/i',
            'to' => '$1\'var(--fo-win-body, #fbfbfd)\';',
        ),
        array(
            'label' => 'цвет текста контейнера → токен',
            're' => '/(\$AppContainer\s*->\s*fontColor\s*=\s*)[\'"][^\'"]*[\'"]\s*;/i',
            'to' => '$1\'var(--fo-text, #151a17)\';',
        ),
        array(
            'label' => 'background:#fff → токен',
            're' => '/(background(?:-color)?\s*:\s*)(?:#fff(?:fff)?|white)\b/i',
            'to' => '$1var(--fo-win-body, #fff)',
        ),
        array(
            'label' => 'color:#000 → токен',
            're' => '/(?<![-\w])color\s*:\s*(?:#000(?:000)?|black)\b/i',
            'to' => 'color:var(--fo-text, #000)',
        ),
    );
    $plan = array();
    foreach ($rules as $r) {
        $count = preg_match_all($r['re'], $src, $m) ? count($m[0]) : 0;
        if ($count > 0) {
            $plan[] = array('label' => $r['label'], 'count' => $count, 're' => $r['re'], 'to' => $r['to']);
        }
    }
    return $plan;
}

function cv_apply(string $src, array $plan): string
{
    foreach ($plan as $p) {
        $src = preg_replace($p['re'], $p['to'], $src);
    }
    return $src;
}

if ($action === 'analyze') {
    $src = cv_src($file);
    echo json_encode(array(
        'ok' => true,
        'app' => $app,
        'report' => cv_analyze($file),
        'plan' => cv_plan($src),
    ));
    exit;
}

if ($action === 'convert') {
    $src = cv_src($file);
    $plan = cv_plan($src);
    $new = cv_apply($src, $plan);
    if ($new === $src) {
        echo json_encode(array('ok' => true, 'changed' => 0, 'msg' => 'Менять нечего — приложение уже современное', 'report' => cv_analyze($file), 'plan' => cv_plan($src)));
        exit;
    }
    $backup = $appsDir . '/' . $app . '/main.php.bak-' . date('Ymd-His');
    @copy($file, $backup);
    if (@file_put_contents($file, $new) === false) {
        cv_fail('write_failed', 500);
    }
    @chmod($file, 0666);
    echo json_encode(array(
        'ok' => true,
        'changed' => array_sum(array_column($plan, 'count')),
        'backup' => basename($backup),
        'msg' => 'Готово: ' . array_sum(array_column($plan, 'count')) . ' замен',
        'report' => cv_analyze($file),
        'plan' => cv_plan(cv_src($file)),
    ));
    exit;
}

cv_fail('unknown_action', 404);
