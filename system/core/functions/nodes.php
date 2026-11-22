<?php
/**
 * Forest OS — node workspaces: save / list / load / delete.
 * Files live in system/users/<login>/settings/nodes/<slug>.json
 */
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function nd_fail(string $error, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $error));
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    nd_fail('auth', 401);
}

$doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = (string)$_SESSION['loginuser'];
$dir = $doc . '/system/users/' . $login . '/settings/nodes';
if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}

$action = (string)($_POST['action'] ?? '');

function nd_name(string $raw): string
{
    $name = trim($raw);
    $slug = preg_replace('/[^A-Za-z0-9_.\-]+/u', '-', $name);
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'workspace-' . date('Ymd-His');
    }
    return mb_substr($slug, 0, 60);
}

if ($action === 'anchors-save') {
    $raw = (string)($_POST['anchors'] ?? '');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        nd_fail('bad_data');
    }
    $clean = array();
    foreach ($data as $app => $list) {
        $app = preg_replace('/[^A-Za-z0-9_.\-]/', '', (string)$app);
        if ($app === '' || !is_array($list)) {
            continue;
        }
        $clean[$app] = array();
        foreach (array_slice($list, 0, 60) as $a) {
            if (!is_array($a) || empty($a['sel'])) {
                continue;
            }
            $clean[$app][] = array(
                'sel' => mb_substr((string)$a['sel'], 0, 300),
                'kind' => (($a['kind'] ?? '') === 'in') ? 'in' : 'out',
                'port' => preg_replace('/[^a-z0-9_\-]/i', '', (string)($a['port'] ?? 'value')) ?: 'value',
                'label' => mb_substr((string)($a['label'] ?? ''), 0, 60),
            );
        }
    }
    $file = $dir . '/anchors.json';
    if (@file_put_contents($file, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        nd_fail('write_failed', 500);
    }
    @chmod($file, 0666);
    echo json_encode(array('ok' => true, 'count' => count($clean)), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'anchors-list') {
    $file = $dir . '/anchors.json';
    $anchors = array();
    if (is_file($file)) {
        $j = json_decode((string)@file_get_contents($file), true);
        if (is_array($j)) {
            $anchors = $j;
        }
    }
    echo json_encode(array('ok' => true, 'anchors' => $anchors), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save') {
    $name = nd_name((string)($_POST['name'] ?? ''));
    $data = (string)($_POST['data'] ?? '');
    $json = json_decode($data, true);
    if (!is_array($json)) {
        nd_fail('bad_data');
    }
    $payload = array(
        'name' => (string)($_POST['name'] ?? $name),
        'saved_at' => date('Y-m-d H:i:s'),
        'windows' => array_slice(is_array($json['windows'] ?? null) ? $json['windows'] : array(), 0, 40),
        'links' => array_slice(is_array($json['links'] ?? null) ? $json['links'] : array(), 0, 200),
    );
    $file = $dir . '/' . $name . '.json';
    if (@file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        nd_fail('write_failed', 500);
    }
    @chmod($file, 0666);
    echo json_encode(array('ok' => true, 'name' => $name), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'list') {
    $items = array();
    foreach (glob($dir . '/*.json') as $f) {
        $j = json_decode((string)@file_get_contents($f), true);
        $items[] = array(
            'name' => is_array($j) && !empty($j['name']) ? (string)$j['name'] : basename($f, '.json'),
            'file' => basename($f, '.json'),
            'saved_at' => is_array($j) ? (string)($j['saved_at'] ?? '') : '',
            'windows' => is_array($j) && is_array($j['windows'] ?? null) ? count($j['windows']) : 0,
            'links' => is_array($j) && is_array($j['links'] ?? null) ? count($j['links']) : 0,
        );
    }
    usort($items, function ($a, $b) { return strcmp($b['saved_at'], $a['saved_at']); });
    echo json_encode(array('ok' => true, 'items' => $items), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'load' || $action === 'delete') {
    $name = nd_name((string)($_POST['name'] ?? ''));
    $file = $dir . '/' . $name . '.json';
    if (!is_file($file)) {
        nd_fail('not_found', 404);
    }
    if ($action === 'delete') {
        @unlink($file);
        echo json_encode(array('ok' => true));
        exit;
    }
    $j = json_decode((string)@file_get_contents($file), true);
    echo json_encode(array('ok' => true, 'name' => $name, 'data' => is_array($j) ? $j : array()), JSON_UNESCAPED_UNICODE);
    exit;
}

nd_fail('unknown_action', 404);
