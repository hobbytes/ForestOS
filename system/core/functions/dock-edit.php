<?php
/**
 * Forest OS — закрепление приложений и папок в доке, порядок и разделители.
 *
 * Всё хранится в settings/Dock/layout.json (см. system/core/library/dock.php).
 * Actions:
 *   pin / unpin   + app=<App> | folder=<абсолютный или относительный путь>
 *   reorder       + order[] = "app:Explorer", "sep", "folder:documents/images"
 *   sep-add       + index=<позиция>
 *   sep-del       + index=<позиция>
 */
ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/dock.php';

function fo_dock_out(array $d, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    fo_dock_out(array('ok' => false, 'error' => 'auth'), 401);
}

$doc   = Dock::docRoot();
$login = (string)$_SESSION['loginuser'];
$dir   = Dock::dir($doc, $login);

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
$app    = isset($_POST['app']) ? basename((string)$_POST['app']) : '';
$folder = isset($_POST['folder']) ? (string)$_POST['folder'] : '';

$items = Dock::readLayout($dir, $doc, $login);

function dock_index_of_app(array $items, string $app): int
{
    foreach ($items as $i => $it) {
        if ($it['t'] === 'app' && $it['name'] === $app) {
            return (int)$i;
        }
    }
    return -1;
}

function dock_index_of_store(array $items, string $store): int
{
    foreach ($items as $i => $it) {
        if ($it['t'] === 'folder' && $it['store'] === $store) {
            return (int)$i;
        }
    }
    return -1;
}

/* ---------- приложения ---------- */

if ($app !== '') {
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $app) || !is_file($doc . '/system/apps/' . $app . '/main.php')) {
        fo_dock_out(array('ok' => false, 'error' => 'app'), 400);
    }
    if ($action === 'pin') {
        if (dock_index_of_app($items, $app) < 0) {
            /* новый значок встаёт после последнего приложения, перед разделителем */
            $pos = 0;
            foreach ($items as $i => $it) {
                if ($it['t'] === 'app') {
                    $pos = $i + 1;
                }
            }
            array_splice($items, $pos, 0, array(array('t' => 'app', 'name' => $app)));
            Dock::saveLayout($dir, $items);
        }
        fo_dock_out(array('ok' => true, 'pinned' => $app));
    }
    if ($action === 'unpin') {
        $items = array_values(array_filter($items, function ($it) use ($app) {
            return !($it['t'] === 'app' && $it['name'] === $app);
        }));
        Dock::saveLayout($dir, Dock::normalize($items, $doc, $login));
        fo_dock_out(array('ok' => true, 'unpinned' => $app));
    }
    fo_dock_out(array('ok' => false, 'error' => 'action'), 400);
}

/* ---------- папки ---------- */

if ($folder !== '') {
    $store = Dock::storeFromPath($folder, $doc, $login);
    if ($store === null) {
        fo_dock_out(array('ok' => false, 'error' => 'folder'), 400);
    }
    $abs = Dock::resolveStore($store, $doc, $login);

    if ($action === 'pin') {
        if ($abs === null || !is_dir($abs)) {
            fo_dock_out(array('ok' => false, 'error' => 'folder'), 400);
        }
        if (dock_index_of_store($items, $store) < 0) {
            $items[] = array('t' => 'folder', 'store' => $store);
            Dock::saveLayout($dir, $items);
        }
        fo_dock_out(array('ok' => true, 'pinned' => $store, 'title' => basename($abs)));
    }
    if ($action === 'unpin') {
        $items = array_values(array_filter($items, function ($it) use ($store) {
            return !($it['t'] === 'folder' && $it['store'] === $store);
        }));
        Dock::saveLayout($dir, Dock::normalize($items, $doc, $login));
        fo_dock_out(array('ok' => true, 'unpinned' => $store));
    }
    fo_dock_out(array('ok' => false, 'error' => 'action'), 400);
}

/* ---------- порядок ---------- */

if ($action === 'reorder') {
    $order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : array();
    $tokens = array();
    foreach ($items as $it) {
        $tokens[] = $it['t'] === 'app' ? 'app:' . $it['name']
            : ($it['t'] === 'folder' ? 'folder:' . $it['store'] : 'sep');
    }
    $incoming = array();
    foreach ($order as $tok) {
        if (!is_string($tok) || $tok === '') {
            continue;
        }
        $incoming[] = $tok;
    }
    $a = $tokens;
    $b = $incoming;
    sort($a);
    sort($b);
    if ($a !== $b) {
        fo_dock_out(array('ok' => false, 'error' => 'order_mismatch'), 400);
    }
    $byToken = array();
    foreach ($items as $it) {
        $tok = $it['t'] === 'app' ? 'app:' . $it['name']
            : ($it['t'] === 'folder' ? 'folder:' . $it['store'] : 'sep');
        $byToken[$tok] = $it;
    }
    $new = array();
    foreach ($incoming as $tok) {
        $new[] = $byToken[$tok];
    }
    $new = Dock::normalize($new, $doc, $login);
    Dock::saveLayout($dir, $new);
    fo_dock_out(array('ok' => true, 'count' => count($new)));
}

/* ---------- разделители ---------- */

if ($action === 'sep-add') {
    $index = isset($_POST['index']) ? (int)$_POST['index'] : count($items);
    $index = max(0, min($index, count($items)));
    array_splice($items, $index, 0, array(array('t' => 'sep')));
    $items = Dock::normalize($items, $doc, $login);
    Dock::saveLayout($dir, $items);
    fo_dock_out(array('ok' => true, 'items' => count($items)));
}

if ($action === 'sep-del') {
    $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
    if ($index < 0 || $index >= count($items) || $items[$index]['t'] !== 'sep') {
        fo_dock_out(array('ok' => false, 'error' => 'not_separator'), 400);
    }
    array_splice($items, $index, 1);
    $items = Dock::normalize($items, $doc, $login);
    Dock::saveLayout($dir, $items);
    fo_dock_out(array('ok' => true, 'items' => count($items)));
}

fo_dock_out(array('ok' => false, 'error' => 'action'), 400);
