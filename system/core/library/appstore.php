<?php
/**
 * Forest OS — AppsHouse client.
 *
 * Stores the installation identity (instance_id + secret) in system/instance.foc,
 * signs every hub request with HMAC-SHA256 and exposes the catalog / repos API.
 */
class AppStore
{
    public const DEFAULT_HUB = 'http://172.17.0.1:48090';
    public const MAX_APP_BYTES = 16777216;
    public const PROTECTED_APPS = array('Explorer', 'Settings', 'Apps_House', 'update');

    /** Репозиторий приложений «в комплекте»: папка catalog/ рядом с ядром. */
    public const BUNDLED_REPO = 'bundled';

    private string $doc;
    private string $login;
    private string $file;
    private array $cfg;

    public function __construct(string $login)
    {
        $this->doc = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
        $this->login = $login;
        $this->file = $this->doc . '/system/instance.foc';
        $this->load();
    }

    /* ---------------- identity ---------------- */

    private function load(): void
    {
        $ini = @parse_ini_file($this->file);
        $this->cfg = is_array($ini) ? $ini : array();
        $changed = false;

        if (empty($this->cfg['instance_id']) || !preg_match('/^[0-9a-f-]{36}$/i', (string)$this->cfg['instance_id'])) {
            $this->cfg['instance_id'] = self::uuid4();
            $changed = true;
        }
        if (empty($this->cfg['secret']) || strlen((string)$this->cfg['secret']) < 32) {
            $this->cfg['secret'] = bin2hex(random_bytes(32));
            $this->cfg['registered'] = '0';
            $changed = true;
        }
        if (empty($this->cfg['hub'])) {
            $this->cfg['hub'] = self::DEFAULT_HUB;
            $changed = true;
        }
        if ($changed) {
            $this->save();
        }
    }

    private function save(): void
    {
        $body = "[instance]\n"
            . 'instance_id="' . $this->cfg['instance_id'] . "\"\n"
            . 'secret="' . $this->cfg['secret'] . "\"\n"
            . 'hub="' . str_replace('"', '', (string)$this->cfg['hub']) . "\"\n"
            . 'registered="' . (string)($this->cfg['registered'] ?? '0') . "\"\n";
        @file_put_contents($this->file, $body);
        @chmod($this->file, 0666);
    }

    public function instanceId(): string
    {
        return (string)$this->cfg['instance_id'];
    }

    public function hub(): string
    {
        return rtrim((string)$this->cfg['hub'], '/');
    }

    public function setHub(string $url): bool
    {
        $url = trim($url);
        if (!preg_match('#^https?://[^\s"\']+$#i', $url)) {
            return false;
        }
        $this->cfg['hub'] = rtrim($url, '/');
        $this->cfg['registered'] = '0';
        $this->save();
        return true;
    }

    public static function uuid4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /* ---------------- transport ---------------- */

    private function headers(string $method, string $path, string $body): array
    {
        $ts = (string)time();
        $nonce = bin2hex(random_bytes(16));
        $canonical = strtoupper($method) . "\n" . $path . "\n" . $ts . "\n" . $nonce . "\n" . hash('sha256', $body);
        $sig = hash_hmac('sha256', $canonical, (string)$this->cfg['secret']);
        return array(
            'X-FO-Instance: ' . $this->cfg['instance_id'],
            'X-FO-Ts: ' . $ts,
            'X-FO-Nonce: ' . $nonce,
            'X-FO-Sign: ' . $sig,
            'Content-Type: application/json',
            'Accept: application/json',
        );
    }

    private static function http(string $method, string $url, ?string $body, array $headers, int $timeout = 25): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
        ));
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return array('status' => $status, 'raw' => $raw === false ? '' : (string)$raw, 'error' => $err);
    }

    private function call(string $method, string $path, ?array $payload = null, ?string $hub = null): array
    {
        $base = $hub ? rtrim($hub, '/') : $this->hub();
        $body = $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE);
        $headers = $this->headers($method, $path, $body);
        $res = self::http($method, $base . $path, $body === '' ? null : $body, $headers);
        $json = json_decode($res['raw'], true);
        return array(
            'status' => $res['status'],
            'ok' => is_array($json) && !empty($json['ok']),
            'json' => is_array($json) ? $json : array(),
            'error' => $res['error'],
            'raw' => $res['raw'],
        );
    }

    /** Registers this installation on the hub (once). */
    public function ensureRegistered(): array
    {
        if ((string)($this->cfg['registered'] ?? '0') === '1') {
            return array('ok' => true, 'registered' => true, 'instance_id' => $this->instanceId());
        }
        $body = json_encode(array(
            'instance_id' => $this->instanceId(),
            'secret' => (string)$this->cfg['secret'],
            'os_version' => $this->osVersion(),
            'label' => $this->login,
        ), JSON_UNESCAPED_UNICODE);

        $res = self::http('POST', $this->hub() . '/api/v1/instances/register', $body,
            array('Content-Type: application/json', 'Accept: application/json'));

        if ($res['status'] === 200) {
            $this->cfg['registered'] = '1';
            $this->save();
            return array('ok' => true, 'registered' => true, 'instance_id' => $this->instanceId());
        }
        if ($res['status'] === 409) {
            // the hub already knows this id with another secret: take a new identity
            $this->cfg['instance_id'] = self::uuid4();
            $this->cfg['secret'] = bin2hex(random_bytes(32));
            $this->cfg['registered'] = '0';
            $this->save();
            return $this->ensureRegistered();
        }
        return array(
            'ok' => false,
            'error' => $res['error'] !== '' ? 'hub_unreachable' : ('http_' . $res['status']),
            'raw' => substr($res['raw'], 0, 300),
        );
    }

    private function osVersion(): string
    {
        $ini = @parse_ini_file($this->doc . '/system/core/osinfo.foc');
        return is_array($ini) && !empty($ini['subversion']) ? (string)$ini['subversion'] : '2.5';
    }

    /* ---------------- hub API ---------------- */

    public function catalog(string $search = '', string $sort = 'popular', int $page = 1, int $per = 24): array
    {
        $reg = $this->ensureRegistered();
        if (!$reg['ok']) {
            return array('ok' => false, 'error' => $reg['error'], 'apps' => array());
        }
        $res = $this->call('POST', '/api/v1/catalog', array(
            'search' => $search, 'sort' => $sort, 'page' => $page, 'per_page' => $per,
        ));
        if (!$res['ok']) {
            return array('ok' => false, 'error' => $res['json']['error'] ?? ('http_' . $res['status']), 'apps' => array());
        }
        return array(
            'ok' => true,
            'apps' => is_array($res['json']['apps'] ?? null) ? $res['json']['apps'] : array(),
            'total' => (int)($res['json']['total'] ?? 0),
            'max_downloads' => (int)($res['json']['max_downloads'] ?? 0),
            'page' => (int)($res['json']['page'] ?? 1),
            'per_page' => (int)($res['json']['per_page'] ?? $per),
        );
    }

    public function appMeta(string $hash): array
    {
        $res = self::http('GET', $this->hub() . '/api/v1/apps/' . $hash, null, array('Accept: application/json'));
        $json = json_decode($res['raw'], true);
        if (!is_array($json) || empty($json['ok'])) {
            return array('ok' => false, 'error' => $json['error'] ?? ('http_' . $res['status']));
        }
        return $json;
    }

    public function downloadLink(string $hash): array
    {
        $reg = $this->ensureRegistered();
        if (!$reg['ok']) {
            return array('ok' => false, 'error' => $reg['error']);
        }
        $res = $this->call('POST', '/api/v1/apps/' . $hash . '/download', array());
        if (!$res['ok']) {
            return array('ok' => false, 'error' => $res['json']['error'] ?? ('http_' . $res['status']));
        }
        return $res['json'];
    }

    public function installStat(string $hash): void
    {
        $this->call('POST', '/api/v1/apps/' . $hash . '/install', array());
    }

    /** Raw-body call (used for zip uploads, where the signature covers the bytes). */
    private function callRaw(string $method, string $path, string $raw, int $timeout = 90): array
    {
        $headers = array();
        foreach ($this->headers($method, $path, $raw) as $h) {
            if (stripos($h, 'Content-Type:') === 0) {
                continue;
            }
            $headers[] = $h;
        }
        $headers[] = 'Content-Type: application/zip';
        $res = self::http($method, $this->hub() . $path, $raw, $headers, $timeout);
        $json = json_decode($res['raw'], true);
        return array(
            'status' => $res['status'],
            'ok' => is_array($json) && !empty($json['ok']),
            'json' => is_array($json) ? $json : array(),
            'error' => $res['error'],
            'raw' => $res['raw'],
        );
    }

    /** Publishes a zip to the hub: create (meta) + upload (bytes). */
    public function publish(array $meta, string $zipPath): array
    {
        $reg = $this->ensureRegistered();
        if (!$reg['ok']) {
            return array('ok' => false, 'error' => $reg['error']);
        }
        $raw = @file_get_contents($zipPath);
        if ($raw === false || strlen($raw) < 200) {
            return array('ok' => false, 'error' => 'zip_read');
        }
        if (strlen($raw) > self::MAX_APP_BYTES) {
            return array('ok' => false, 'error' => 'too_large');
        }
        $create = $this->call('POST', '/api/v1/apps/create', $meta);
        if (!$create['ok'] || empty($create['json']['upload_token'])) {
            return array('ok' => false, 'error' => $create['json']['error'] ?? 'create_failed');
        }
        $up = $this->callRaw('PUT', '/api/v1/upload/' . $create['json']['upload_token'], $raw);
        if (!$up['ok']) {
            return array('ok' => false, 'error' => $up['json']['error'] ?? 'upload_failed');
        }
        return array('ok' => true, 'hash' => (string)($up['json']['hash'] ?? ''));
    }

    public function hubRepos(): array
    {
        $res = self::http('GET', $this->hub() . '/api/v1/repos', null, array('Accept: application/json'));
        $json = json_decode($res['raw'], true);
        return is_array($json) && !empty($json['ok']) && is_array($json['repos'] ?? null) ? $json['repos'] : array();
    }

    /* ---------------- repos ---------------- */

    private function customFile(): string
    {
        return $this->doc . '/system/users/' . $this->login . '/settings/appstore-repos.json';
    }

    public function customRepos(): array
    {
        $f = $this->customFile();
        if (!is_file($f)) {
            return array();
        }
        $j = json_decode((string)@file_get_contents($f), true);
        return is_array($j) ? $j : array();
    }

    private function saveCustom(array $list): void
    {
        $f = $this->customFile();
        $dir = dirname($f);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents($f, json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        @chmod($f, 0666);
    }

    public function addRepo(string $name, string $url, string $pubkey): array
    {
        $url = rtrim(trim($url), '/');
        if (!preg_match('#^https?://[^\s"\']+$#i', $url)) {
            return array('ok' => false, 'error' => 'bad_url');
        }
        $name = trim($name) !== '' ? trim($name) : parse_url($url, PHP_URL_HOST);
        $probe = $this->fetchRepoIndex($url);
        if (!$probe['ok']) {
            return array('ok' => false, 'error' => 'index_unreachable', 'detail' => $probe['error'] ?? '');
        }
        $id = 'r' . substr(md5($url), 0, 10);
        $list = $this->customRepos();
        foreach ($list as $i => $r) {
            if (($r['url'] ?? '') === $url) {
                $list[$i] = array('id' => $id, 'name' => $name, 'url' => $url, 'pubkey' => $pubkey);
                $this->saveCustom($list);
                return array('ok' => true, 'id' => $id, 'updated' => true);
            }
        }
        $list[] = array('id' => $id, 'name' => $name, 'url' => $url, 'pubkey' => $pubkey);
        $this->saveCustom($list);
        return array('ok' => true, 'id' => $id);
    }

    public function removeRepo(string $id): array
    {
        $list = $this->customRepos();
        $out = array();
        $removed = false;
        foreach ($list as $r) {
            if (($r['id'] ?? '') === $id) {
                $removed = true;
                continue;
            }
            $out[] = $r;
        }
        $this->saveCustom($out);
        return array('ok' => $removed, 'error' => $removed ? null : 'not_found');
    }

    public static function verifyIndex(string $json, string $sigB64, string $pubB64): bool
    {
        $sig = self::b64urlDecode($sigB64);
        $pk = self::b64urlDecode($pubB64);
        if ($sig === '' || $pk === '' || strlen($pk) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }
        try {
            return sodium_crypto_sign_verify_detached($sig, $json, $pk);
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function b64urlDecode(string $s): string
    {
        $s = strtr(trim($s), '-_', '+/');
        $pad = strlen($s) % 4;
        if ($pad) {
            $s .= str_repeat('=', 4 - $pad);
        }
        $bin = base64_decode($s, true);
        return $bin === false ? '' : $bin;
    }

    private function fetchRepoIndex(string $base): array
    {
        return $this->fetchIndexUrl(rtrim($base, '/') . '/index.json', rtrim($base, '/') . '/index.sig');
    }

    /** Fetch + parse a repo index from explicit URLs. */
    private function fetchIndexUrl(string $indexUrl, string $sigUrl): array
    {
        $idx = self::http('GET', $indexUrl, null, array('Accept: application/json'), 15);
        if ($idx['status'] !== 200) {
            return array('ok' => false, 'error' => 'http_' . $idx['status']);
        }
        $json = json_decode($idx['raw'], true);
        if (!is_array($json) || !isset($json['apps'])) {
            return array('ok' => false, 'error' => 'bad_index');
        }
        $sig = self::http('GET', $sigUrl, null, array('Accept: application/json'), 15);
        return array(
            'ok' => true,
            'json' => $json,
            'raw' => $idx['raw'],
            'sig_raw' => $sig['status'] === 200 ? $sig['raw'] : '',
        );
    }

    /** Full repo list: official (from the hub) + custom. */
    /* ---------------- встроенный каталог (catalog/) ---------------- */

    public function bundledDir(): string
    {
        return $this->doc . '/catalog';
    }

    public function bundledIndex(): array
    {
        $file = $this->bundledDir() . '/index.json';
        if (!is_file($file)) {
            return array();
        }
        $j = json_decode((string)@file_get_contents($file), true);
        if (!is_array($j) || empty($j['apps']) || !is_array($j['apps'])) {
            return array();
        }
        return $j['apps'];
    }

    /**
     * Приложения, которые поставляются вместе с ОС, но не установлены сразу.
     * Ставятся из локального zip — без интернета и без подписи.
     */
    public function bundledApps(): array
    {
        $apps = array();
        foreach ($this->bundledIndex() as $a) {
            if (!is_array($a)) {
                continue;
            }
            $name = (string)($a['name'] ?? '');
            if (!preg_match('/^[A-Za-z0-9_.\-]{2,64}$/', $name)) {
                continue;
            }
            $zipRel = (string)($a['zip'] ?? '');
            if ($zipRel === '') {
                continue;
            }
            $zipAbs = $this->doc . '/' . ltrim($zipRel, '/');
            if (!is_file($zipAbs)) {
                continue;
            }
            $installed = is_dir($this->appsDir() . '/' . $name);
            $apps[] = array(
                'hash' => substr(md5('bundled:' . $name), 0, 32),
                'name' => $name,
                'second_name' => (string)($a['title'] ?? $name),
                'description' => (string)($a['description'] ?? ''),
                'version' => (string)($a['version'] ?? ''),
                'author_label' => (string)($a['author'] ?? 'Forest OS'),
                'size' => (int)@filesize($zipAbs),
                'sha256' => (string)@hash_file('sha256', $zipAbs),
                'zip' => $zipRel,
                'repo' => self::BUNDLED_REPO,
                'icon' => (string)($a['icon'] ?? ''),
                'downloads' => 0,
                'installed' => $installed,
                'ok' => true,
            );
        }
        usort($apps, function ($x, $y) {
            return strcasecmp((string)$x['name'], (string)$y['name']);
        });
        return array('ok' => true, 'verified' => true, 'repo' => self::BUNDLED_REPO, 'apps' => $apps);
    }

    public function repos(): array
    {
        $out = array();
        $out[] = array(
            'id' => self::BUNDLED_REPO,
            'name' => 'В комплекте',
            'url' => 'catalog/',
            'pubkey' => '',
            'official' => true,
            'builtin' => true,
        );
        foreach ($this->hubRepos() as $r) {
            if (!empty($r['official'])) {
                $out[] = array(
                    'id' => 'official',
                    'name' => (string)($r['name'] ?? 'Forest OS AppsHouse'),
                    'url' => $this->hub(),
                    'pubkey' => (string)($r['pubkey'] ?? ''),
                    'official' => true,
                );
            }
        }
        if (!$out) {
            $out[] = array('id' => 'official', 'name' => 'Forest OS AppsHouse', 'url' => $this->hub(), 'pubkey' => '', 'official' => true);
        }
        foreach ($this->customRepos() as $r) {
            $out[] = array(
                'id' => (string)$r['id'],
                'name' => (string)$r['name'],
                'url' => rtrim((string)$r['url'], '/'),
                'pubkey' => (string)($r['pubkey'] ?? ''),
                'official' => false,
            );
        }
        return $out;
    }

    /** Apps of one repo, signature-verified when a key is known. */
    public function repoApps(string $id): array
    {
        if ($id === self::BUNDLED_REPO) {
            return $this->bundledApps();
        }
        $repo = null;
        foreach ($this->repos() as $r) {
            if ($r['id'] === $id) {
                $repo = $r;
                break;
            }
        }
        if ($repo === null) {
            return array('ok' => false, 'error' => 'repo_not_found', 'apps' => array());
        }

        // official index lives on the hub (served through a non-.json route)
        if ($repo['official']) {
            $fetch = $this->fetchIndexUrl(
                $this->hub() . '/api/v1/repo-index/official',
                $this->hub() . '/api/v1/repo-sig/official'
            );
        } else {
            $fetch = $this->fetchRepoIndex($repo['url']);
        }
        if (!$fetch['ok']) {
            return array('ok' => false, 'error' => $fetch['error'], 'apps' => array());
        }

        $sigJson = json_decode((string)($fetch['sig_raw'] ?? ''), true);
        $key = $repo['pubkey'];
        if ($key === '' && is_array($sigJson) && !empty($sigJson['key'])) {
            $key = (string)$sigJson['key'];
        }
        $verified = false;
        if (is_array($sigJson) && !empty($sigJson['sig'])) {
            $verified = self::verifyIndex($fetch['raw'], (string)$sigJson['sig'], $key);
        }

        return array(
            'ok' => true,
            'repo' => $repo['id'],
            'verified' => $verified,
            'key' => $key,
            'apps' => is_array($fetch['json']['apps']) ? $fetch['json']['apps'] : array(),
        );
    }

    /* ---------------- local state ---------------- */

    public function appsDir(): string
    {
        return $this->doc . '/system/apps';
    }

    public function installed(): array
    {
        $out = array();
        foreach (glob($this->appsDir() . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            if (!preg_match('/^[A-Za-z0-9_.\-]{2,64}$/', $name)) {
                continue;
            }
            if (!is_file($dir . '/main.php')) {
                continue;
            }
            $meta = array();
            if (is_file($dir . '/app.meta')) {
                $j = json_decode((string)@file_get_contents($dir . '/app.meta'), true);
                if (is_array($j)) {
                    $meta = $j;
                }
            }
            $out[] = array(
                'app' => $name,
                'name' => (string)($meta['name'] ?? $name),
                'title' => (string)($meta['title'] ?? ''),
                'description' => (string)($meta['description'] ?? ''),
                'os_version' => (string)($meta['os_version'] ?? ''),
                'version' => (string)($meta['version'] ?? ''),
                'hash' => (string)($meta['hash'] ?? ''),
                'installed_at' => (string)($meta['installed_at'] ?? ''),
                'published_version' => (string)($meta['published_version'] ?? ''),
                'size' => (int)($meta['size'] ?? 0),
                'published' => !empty($meta['hash']),
                'protected' => in_array($name, self::PROTECTED_APPS, true),
                'icon' => is_file($dir . '/app.png') ? 'system/apps/' . $name . '/app.png' : 'system/core/design/images/app.png',
            );
        }
        usort($out, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        return $out;
    }

    /* ---------------- files ---------------- */

    public function downloadTo(string $url, string $dest): array
    {
        /* локальный файл (встроенный каталог): просто копируем */
        if (!preg_match('#^https?://#i', $url)) {
            $src = $this->doc . '/' . ltrim($url, '/');
            if (!is_file($src)) {
                return array('ok' => false, 'error' => 'file_not_found');
            }
            if (!@copy($src, $dest)) {
                return array('ok' => false, 'error' => 'copy_failed');
            }
            return array('ok' => true, 'bytes' => (int)@filesize($dest), 'local' => true);
        }
        $fp = @fopen($dest, 'wb');
        if (!$fp) {
            return array('ok' => false, 'error' => 'tmp_open');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_MAXFILESIZE => self::MAX_APP_BYTES,
        ));
        $ok = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if (!$ok || $status !== 200) {
            @unlink($dest);
            return array('ok' => false, 'error' => $err !== '' ? 'download_failed' : ('http_' . $status));
        }
        $size = (int)@filesize($dest);
        if ($size <= 0 || $size > self::MAX_APP_BYTES) {
            @unlink($dest);
            return array('ok' => false, 'error' => 'bad_size');
        }
        return array('ok' => true, 'size' => $size);
    }
}
