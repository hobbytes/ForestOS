<?php
/**
 * Forest OS — AppsHouse local API.
 * Proxies the hub for the browser (so the hub address stays server-side),
 * and performs install / uninstall / publish on this machine.
 */
ini_set('display_errors', 'Off');
ini_set('memory_limit', '256M');
set_time_limit(120);

if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function st_out(array $d, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function st_fail(string $error, int $code = 400, array $extra = array()): void
{
    st_out(array_merge(array('ok' => false, 'error' => $error), $extra), $code);
}

function st_rm_rf(string $path): void
{
    $path = rtrim($path, '/');
    if ($path === '' || strpos($path, $_SERVER['DOCUMENT_ROOT']) !== 0) {
        return;
    }
    if (is_file($path) || is_link($path)) {
        @unlink($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        st_rm_rf($path . '/' . $item);
    }
    @rmdir($path);
}

function st_chmod_tree(string $dir): void
{
    @chmod($dir, 0777);
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $p = $dir . '/' . $item;
        if (is_dir($p)) {
            st_chmod_tree($p);
        } else {
            @chmod($p, 0666);
        }
    }
}

/** Extract a zip into $target (replacing it), with prefix stripping and zip-slip guard. */
function st_extract(string $zipPath, string $target): array
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return array('ok' => false, 'error' => 'zip_open');
    }
    $names = array();
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $n = (string)$zip->getNameIndex($i);
        if ($n === '' || substr($n, -1) === '/') {
            continue;
        }
        $names[] = $n;
    }
    if (!$names) {
        $zip->close();
        return array('ok' => false, 'error' => 'zip_empty');
    }

    $prefix = null;
    foreach ($names as $n) {
        $pos = strpos($n, '/');
        $root = $pos === false ? '' : substr($n, 0, $pos + 1);
        if ($prefix === null) {
            $prefix = $root;
        } elseif ($prefix !== $root) {
            $prefix = '';
            break;
        }
    }
    if ($prefix !== null && $prefix !== '' && in_array('main.php', $names, true)) {
        $prefix = '';
    }

    $stage = $target . '.new-' . bin2hex(random_bytes(4));
    if (!@mkdir($stage, 0777, true) && !is_dir($stage)) {
        /* system/apps мог достаться от образа с правами root:0755 — пробуем
           выдать права сами и повторить, иначе говорим прямо, что делать */
        @chmod(dirname($stage), 0777);
        if (!@mkdir($stage, 0777, true) && !is_dir($stage)) {
            $zip->close();
            return array('ok' => false, 'error' => 'no_write_access');
        }
    }

    $written = 0;
    foreach ($names as $n) {
        $rel = ($prefix !== null && $prefix !== '' && strpos($n, $prefix) === 0) ? substr($n, strlen($prefix)) : $n;
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        if ($rel === '' || strpos($rel, '..') !== false) {
            continue;
        }
        $dest = $stage . '/' . $rel;
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $data = $zip->getFromName($n);
        if ($data === false) {
            continue;
        }
        if (@file_put_contents($dest, $data) !== false) {
            $written++;
        }
    }
    $zip->close();

    if (!is_file($stage . '/main.php')) {
        st_rm_rf($stage);
        return array('ok' => false, 'error' => 'no_main_php');
    }
    if (is_dir($target)) {
        st_rm_rf($target);
    }
    if (!@rename($stage, $target)) {
        st_rm_rf($stage);
        return array('ok' => false, 'error' => 'swap_failed');
    }
    st_chmod_tree($target);
    return array('ok' => true, 'files' => $written);
}

/** Zip a directory (app.meta / data / secrets are never published). */
function st_zip_skip(string $rel): bool
{
    $rel = str_replace('\\', '/', $rel);
    if ($rel === 'app.meta' || $rel === '' ) { return true; }
    foreach (array('data/', '.git/', 'node_modules/', '.idea/', '.vscode/') as $dir) {
        if (strpos($rel, $dir) === 0) { return true; }
    }
    $base = basename($rel);
    if ($base === '.env' || substr($base, -4) === '.log' || $base === '.DS_Store') { return true; }
    return false;
}

function st_zip_dir(string $dir, string $zipPath): array
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return array('ok' => false, 'error' => 'zip_create');
    }
    $dir = rtrim($dir, '/');
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $n = 0;
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        $rel = substr($path, strlen($dir) + 1);
        if (st_zip_skip($rel)) {
            continue;
        }
        $zip->addFile($path, $rel);
        $n++;
    }
    $zip->close();
    return array('ok' => $n > 0, 'files' => $n, 'error' => $n ? null : 'empty_app');
}

/** What would be packed, so the UI can show it before publishing. */
function st_app_preview(string $dir): array
{
    $dir = rtrim($dir, '/');
    $files = array();
    $total = 0;
    $skipped = 0;
    if (!is_dir($dir)) {
        return array('ok' => false, 'error' => 'not_installed');
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        $rel = substr($path, strlen($dir) + 1);
        if (st_zip_skip($rel)) { $skipped++; continue; }
        $size = (int)$file->getSize();
        $total += $size;
        if (count($files) < 200) {
            $files[] = array('name' => $rel, 'size' => $size);
        }
    }
    usort($files, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
    return array('ok' => true, 'files' => $files, 'total' => $total, 'skipped' => $skipped, 'count' => count($files));
}

/* ---------- auth ---------- */
if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    st_fail('auth', 401);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/appstore.php';

$login = (string)$_SESSION['loginuser'];
$store = new AppStore($login);

if (empty($_SESSION['fo_store_csrf'])) {
    $_SESSION['fo_store_csrf'] = bin2hex(random_bytes(16));
}
$CSRF = (string)$_SESSION['fo_store_csrf'];

$action = isset($_POST['action']) ? (string)$_POST['action'] : (isset($_GET['action']) ? (string)$_GET['action'] : '');
$readOnly = in_array($action, array('status', 'catalog', 'installed', 'repos', 'repo-apps', 'icon', 'preview', 'hub-check', 'hub-probe'), true);

if (!$readOnly) {
    $token = isset($_POST['token']) ? (string)$_POST['token'] : '';
    if (!hash_equals($CSRF, $token)) {
        st_fail('csrf', 419);
    }
}

/* ---------- actions ---------- */
if ($action === 'status') {
    $check = $store->hubCheck();
    st_out(array(
        'ok' => true,
        'hub' => $store->hub(),
        'instance_id' => $store->instanceId(),
        'hub_ok' => !empty($check['ok']),
        'hub_hint' => (string)($check['hint'] ?? ''),
        'hub_ms' => (int)($check['ms'] ?? 0),
        'registered' => !empty($check['ok']),
    ));
}

if ($action === 'icon') {
    $hash = isset($_GET['hash']) ? strtolower((string)$_GET['hash']) : '';
    if (!preg_match('/^[a-f0-9]{32}$/', $hash)) {
        http_response_code(400);
        exit;
    }
    $ch = curl_init($store->hub() . '/api/v1/apps/' . $hash . '/icon');
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ));
    $png = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($png === false || $code !== 200) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    echo $png;
    exit;
}

if ($action === 'catalog') {
    $q = isset($_GET['q']) ? (string)$_GET['q'] : '';
    $sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'popular';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $res = $store->catalog($q, $sort, $page);
    st_out($res);
}

if ($action === 'installed') {
    st_out(array('ok' => true, 'apps' => $store->installed()));
}

if ($action === 'repos') {
    $check = $store->hubCheck();
    st_out(array(
        'ok' => true,
        'hub' => $store->hub(),
        'hub_ok' => !empty($check['ok']),
        'hub_error' => (string)($check['error'] ?? ''),
        'hub_hint' => (string)($check['hint'] ?? ''),
        'hub_status' => (int)($check['status_root'] ?? 0),
        'repos' => $store->repos(),
    ));
}

if ($action === 'hub-probe') {
    $port = isset($_GET['port']) ? (int)$_GET['port'] : 48090;
    if ($port < 1 || $port > 65535) {
        $port = 48090;
    }
    $candidates = array();
    foreach (array('host.docker.internal', '172.17.0.1', '172.18.0.1', '172.19.0.1', '172.20.0.1') as $host) {
        $candidates[] = 'http://' . $host . ':' . $port;
    }
    $candidates[] = $store->hub();
    $tried = array();
    foreach (array_unique($candidates) as $candidate) {
        $check = $store->hubCheck($candidate);
        $tried[] = array(
            'url' => $candidate,
            'ok' => !empty($check['ok']),
            'hint' => (string)($check['hint'] ?? ''),
        );
        if (!empty($check['ok'])) {
            st_out(array('ok' => true, 'url' => $candidate, 'check' => $check, 'tried' => $tried));
        }
    }
    st_out(array('ok' => false, 'error' => 'no_hub_found', 'tried' => $tried));
}

if ($action === 'hub-check') {
    $url = isset($_GET['url']) ? (string)$_GET['url'] : '';
    st_out($store->hubCheck($url !== '' ? $url : null));
}

if ($action === 'repo-apps') {
    $id = isset($_GET['id']) ? (string)$_GET['id'] : 'official';
    st_out($store->repoApps($id));
}

if ($action === 'hub-set') {
    $url = isset($_POST['url']) ? (string)$_POST['url'] : '';
    if (!$store->setHub($url)) {
        $err = $store->lastSaveError();
        if ($err === 'not_writable') {
            st_fail('hub_not_saved', 500, array(
                'hint' => 'Не удалось записать system/instance.foc. Перезапустите контейнер: '
                    . 'docker compose restart web (bootstrap выдаст права) — или выполните '
                    . 'chmod 666 system/instance.foc.',
            ));
        }
        st_fail($err !== '' ? $err : 'bad_url');
    }
    $check = $store->hubCheck();
    $reg = $check['ok'] ? $store->ensureRegistered() : array('ok' => false, 'error' => 'hub_unreachable');
    st_out(array(
        'ok' => true,
        'hub' => $store->hub(),
        'saved' => true,
        'hub_ok' => !empty($check['ok']),
        'hub_hint' => (string)($check['hint'] ?? ''),
        'registered' => !empty($reg['ok']),
        'error' => !empty($check['ok']) ? ($reg['ok'] ? null : ($reg['error'] ?? 'not_registered')) : 'hub_unreachable',
    ));
}

if ($action === 'install') {
    $hash = isset($_POST['hash']) ? strtolower((string)$_POST['hash']) : '';
    $repoId = isset($_POST['repo']) ? (string)$_POST['repo'] : 'official';
    if (!preg_match('/^[a-f0-9]{32}$/', $hash)) {
        st_fail('bad_hash');
    }

    $meta = null;
    $downloadUrl = '';

    if ($repoId === 'official') {
        $meta = $store->appMeta($hash);
        if (empty($meta['ok'])) {
            st_fail($meta['error'] ?? 'meta_failed', 502);
        }
        $link = $store->downloadLink($hash);
        if (empty($link['ok']) || empty($link['url'])) {
            st_fail($link['error'] ?? 'link_failed', 502);
        }
        $downloadUrl = (string)$link['url'];
    } else {
        // third-party repo: metadata and zip come from the signed index
        $ra = $store->repoApps($repoId);
        if (empty($ra['ok'])) {
            st_fail($ra['error'] ?? 'repo_failed', 502);
        }
        foreach ($ra['apps'] as $a) {
            if (strtolower((string)($a['hash'] ?? '')) === $hash) {
                $meta = $a;
                break;
            }
        }
        if ($meta === null) {
            st_fail('app_not_in_repo', 404);
        }
        $downloadUrl = (string)($meta['zip'] ?? '');
        if (!preg_match('#^https?://#i', $downloadUrl)) {
            st_fail('bad_zip_url', 502);
        }
        $meta['ok'] = true;
    }

    $name = (string)$meta['name'];
    if (!preg_match('/^[A-Za-z0-9_.\-]{2,64}$/', $name)) {
        st_fail('bad_name');
    }
    if (in_array($name, AppStore::PROTECTED_APPS, true)) {
        st_fail('protected_app');
    }

    $tmp = tempnam(sys_get_temp_dir(), 'foapp');
    $dl = $store->downloadTo($downloadUrl, $tmp);
    if (empty($dl['ok'])) {
        st_fail($dl['error'] ?? 'download_failed', 502);
    }
    if (!empty($meta['sha256']) && !hash_equals((string)$meta['sha256'], (string)hash_file('sha256', $tmp))) {
        @unlink($tmp);
        st_fail('checksum_mismatch', 502);
    }
    $target = $store->appsDir() . '/' . $name;
    $res = st_extract($tmp, $target);
    @unlink($tmp);
    if (empty($res['ok'])) {
        $err = (string)($res['error'] ?? 'extract_failed');
        if ($err === 'no_write_access' || $err === 'mkdir' || $err === 'swap_failed') {
            st_fail('no_write_access', 500, array(
                'hint' => 'Нет прав на запись в system/apps. Перезапустите контейнер: docker compose restart web',
            ));
        }
        st_fail($err, 500);
    }
    @file_put_contents($target . '/app.meta', json_encode(array(
        'name' => $name,
        'title' => (string)($meta['second_name'] ?? '') !== '' ? (string)$meta['second_name'] : $name,
        'description' => (string)($meta['description'] ?? ''),
        'os_version' => (string)($meta['os_version'] ?? ''),
        'version' => (string)($meta['version'] ?? ''),
        'hash' => $hash,
        'repo' => $repoId,
        'author' => (string)($meta['author_label'] ?? $meta['author'] ?? ''),
        'size' => (int)($meta['size'] ?? 0),
        'installed_at' => date('Y-m-d H:i:s'),
        'downloads' => (int)($meta['downloads'] ?? 0),
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    @chmod($target . '/app.meta', 0666);
    if ($repoId === 'official') {
        $store->installStat($hash);
    }
    st_out(array('ok' => true, 'app' => $name, 'version' => (string)($meta['version'] ?? ''), 'repo' => $repoId, 'files' => (int)$res['files']));
}

if ($action === 'uninstall') {
    $app = isset($_POST['app']) ? (string)$_POST['app'] : '';
    if (!preg_match('/^[A-Za-z0-9_.\-]{2,64}$/', $app)) {
        st_fail('bad_app');
    }
    if (in_array($app, AppStore::PROTECTED_APPS, true)) {
        st_fail('protected_app');
    }
    $dir = $store->appsDir() . '/' . $app;
    if (!is_dir($dir)) {
        st_fail('not_installed', 404);
    }
    st_rm_rf($dir);
    st_out(array('ok' => true, 'app' => $app));
}

if ($action === 'preview') {
    $app = isset($_GET['app']) ? (string)$_GET['app'] : '';
    if (!preg_match('/^[A-Za-z0-9_.\-]{2,64}$/', $app)) {
        st_fail('bad_app');
    }
    $res = st_app_preview($store->appsDir() . '/' . $app);
    st_out($res, empty($res['ok']) ? 404 : 200);
}

if ($action === 'publish') {
    $app = isset($_POST['app']) ? (string)$_POST['app'] : '';
    if (!preg_match('/^[A-Za-z0-9_.\-]{2,64}$/', $app)) {
        st_fail('bad_app');
    }
    $dir = $store->appsDir() . '/' . $app;
    if (!is_file($dir . '/main.php')) {
        st_fail('not_installed', 404);
    }
    $version = isset($_POST['version']) ? trim((string)$_POST['version']) : '1.0.0';
    if (!preg_match('/^[0-9][0-9A-Za-z_.\-]{0,31}$/', $version)) {
        st_fail('bad_version');
    }
    $second = isset($_POST['second_name']) ? trim((string)$_POST['second_name']) : $app;
    $desc = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
    $osv = isset($_POST['os_version']) ? trim((string)$_POST['os_version']) : '';

    $zipTmp = tempnam(sys_get_temp_dir(), 'fozip');
    $z = st_zip_dir($dir, $zipTmp);
    if (empty($z['ok'])) {
        @unlink($zipTmp);
        st_fail($z['error'] ?? 'zip_failed', 500);
    }
    $res = $store->publish(array(
        'name' => $app,
        'second_name' => $second,
        'version' => $version,
        'description' => $desc,
        'os_version' => $osv,
    ), $zipTmp);
    @unlink($zipTmp);
    if (empty($res['ok'])) {
        st_fail($res['error'] ?? 'publish_failed', 502);
    }
    $metaFile = $dir . '/app.meta';
    $meta = is_file($metaFile) ? (@json_decode((string)file_get_contents($metaFile), true) ?: array()) : array();
    $meta['name'] = $app;
    // note: the local files are unchanged, so the installed version stays as is
    $meta['title'] = $second;
    $meta['description'] = $desc;
    $meta['os_version'] = $osv;
    $meta['published_version'] = $version;
    $meta['hash'] = (string)$res['hash'];
    $meta['published_at'] = date('Y-m-d H:i:s');
    @file_put_contents($metaFile, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    @chmod($metaFile, 0666);
    st_out(array('ok' => true, 'hash' => (string)$res['hash'], 'version' => $version));
}

if ($action === 'repo-add') {
    $name = isset($_POST['name']) ? (string)$_POST['name'] : '';
    $url = isset($_POST['url']) ? (string)$_POST['url'] : '';
    $key = isset($_POST['pubkey']) ? trim((string)$_POST['pubkey']) : '';
    $res = $store->addRepo($name, $url, $key);
    st_out($res, empty($res['ok']) ? 400 : 200);
}

if ($action === 'repo-remove') {
    $id = isset($_POST['id']) ? (string)$_POST['id'] : '';
    $res = $store->removeRepo($id);
    st_out($res, empty($res['ok']) ? 400 : 200);
}

st_fail('unknown_action', 404);
