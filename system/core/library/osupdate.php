<?php
/**
 * Forest OS — обновление системы из GitHub Releases.
 *
 * Схема: в репозитории ставится тег vX.Y → GitHub Actions собирает zip и
 * кладёт рядом update.json. Приложение «Обновление» читает update.json по
 * стабильному адресу /releases/latest/download/update.json и, если версия
 * новее, скачивает zip, проверяет sha256 и распаковывает поверх.
 */
class OsUpdate
{
    private string $doc;
    private string $osInfo;
    private array $cfg = array();

    /** Файл, который приложение ищет в релизе. */
    public const FEED_DEFAULT = 'https://github.com/hobbytes/ForestOS/releases/latest/download/update.json';

    public function __construct()
    {
        $this->doc = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
        $this->osInfo = $this->doc . '/system/core/osinfo.foc';
        $this->cfg = $this->readIni($this->osInfo);
    }

    private function readIni(string $file): array
    {
        if (!is_file($file)) {
            return array();
        }
        $ini = @parse_ini_file($file);
        return is_array($ini) ? $ini : array();
    }

    private function writeIni(string $file, array $data): bool
    {
        $out = "[forestos]\n\n";
        foreach ($data as $k => $v) {
            $out .= $k . '=' . str_replace(array("\r", "\n"), ' ', (string)$v) . "\n\n";
        }
        return @file_put_contents($file, $out) !== false;
    }

    /* ---------------- состояние ---------------- */

    public function current(): array
    {
        return array(
            'version' => (string)($this->cfg['version'] ?? '—'),
            'subversion' => (string)($this->cfg['subversion'] ?? '—'),
            'revision' => (string)($this->cfg['revision'] ?? '—'),
            'codename' => (string)($this->cfg['codename'] ?? '—'),
        );
    }

    /** Абсолютный адрес этого сервера (для собственной ленты обновлений). */
    private function selfBase(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? (getenv('FOREST_PUBLIC_HOST') ?: 'localhost');
        return $scheme . '://' . $host;
    }

    /** Источник обновлений: своя лента releases/ → конфиг → окружение → значение по умолчанию. */
    public function feedUrl(): string
    {
        if (is_file($this->doc . '/releases/update.json')) {
            return $this->selfBase() . '/releases/update.json';
        }
        $cfgFile = $this->doc . '/system/core/update.json';
        if (is_file($cfgFile)) {
            $j = json_decode((string)@file_get_contents($cfgFile), true);
            if (is_array($j) && !empty($j['feed'])) {
                return (string)$j['feed'];
            }
        }
        $env = getenv('FOREST_UPDATE_FEED');
        if ($env) {
            return (string)$env;
        }
        return self::FEED_DEFAULT;
    }

    private function httpGet(string $url, int $timeout = 12): array
    {
        $res = array('ok' => false, 'body' => '', 'code' => 0, 'error' => '');
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create(array('http' => array('timeout' => $timeout, 'ignore_errors' => true, 'follow_location' => 1)));
            $body = @file_get_contents($url, false, $ctx);
            if ($body === false) {
                $res['error'] = 'network';
                return $res;
            }
            $res['ok'] = true;
            $res['body'] = $body;
            return $res;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'ForestOS/' . ($this->cfg['subversion'] ?? 'unknown'),
            CURLOPT_HTTPHEADER => array('Accept: application/json, application/octet-stream'),
        ));
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $res['code'] = $code;
        if ($body === false || $code >= 400) {
            $res['error'] = $err !== '' ? $err : ('http_' . $code);
            return $res;
        }
        $res['ok'] = true;
        $res['body'] = (string)$body;
        return $res;
    }

    /* ---------------- проверка ---------------- */

    public function check(): array
    {
        $feed = $this->feedUrl();
        $r = $this->httpGet($feed);
        if (empty($r['ok'])) {
            return array('ok' => false, 'error' => 'feed_unavailable', 'detail' => $r['error'], 'feed' => $feed, 'current' => $this->current());
        }
        $j = json_decode((string)$r['body'], true);
        if (!is_array($j) || empty($j['version'])) {
            return array('ok' => false, 'error' => 'bad_feed', 'feed' => $feed, 'current' => $this->current());
        }

        $latest = (string)$j['version'];
        $local = (string)($this->cfg['subversion'] ?? '');
        $hasUpdate = version_compare($latest, $local, '>');

        return array(
            'ok' => true,
            'feed' => $feed,
            'current' => $this->current(),
            'latest' => array(
                'version' => $latest,
                'name' => (string)($j['name'] ?? $latest),
                'notes' => (string)($j['notes'] ?? ''),
                'published_at' => (string)($j['published_at'] ?? ''),
                'size' => (int)($j['size'] ?? 0),
                'sha256' => (string)($j['sha256'] ?? ''),
                'file' => (string)($j['file'] ?? ''),
                'url' => (string)($j['url'] ?? ''),
                'os_min' => (string)($j['os_min'] ?? ''),
                'codename' => (string)($j['codename'] ?? ''),
            ),
            'has_update' => $hasUpdate,
        );
    }

    /* ---------------- установка ---------------- */

    public function install(): array
    {
        $check = $this->check();
        if (empty($check['ok'])) {
            return $check;
        }
        if (empty($check['has_update'])) {
            return array('ok' => false, 'error' => 'already_latest', 'current' => $this->current());
        }
        $latest = $check['latest'];
        if ($latest['url'] === '') {
            return array('ok' => false, 'error' => 'no_url');
        }
        if (!class_exists('ZipArchive')) {
            return array('ok' => false, 'error' => 'no_zip_ext');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'foos');
        $fp = @fopen($tmp, 'wb');
        if (!$fp) {
            return array('ok' => false, 'error' => 'tmp_open');
        }
        $ch = curl_init($latest['url']);
        curl_setopt_array($ch, array(
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_USERAGENT => 'ForestOS/' . ($this->cfg['subversion'] ?? 'unknown'),
        ));
        $okDl = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if ($okDl === false || $code >= 400 || !is_file($tmp) || filesize($tmp) < 1024) {
            @unlink($tmp);
            return array('ok' => false, 'error' => 'download_failed', 'code' => $code);
        }

        if ($latest['sha256'] !== '' && !hash_equals($latest['sha256'], (string)hash_file('sha256', $tmp))) {
            @unlink($tmp);
            return array('ok' => false, 'error' => 'checksum_mismatch');
        }

        /* резервная копия osinfo перед установкой */
        @copy($this->osInfo, $this->osInfo . '.bak-' . date('Ymd-His'));

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            return array('ok' => false, 'error' => 'zip_open');
        }

        /* распаковываем поверх, пропуская пользовательские данные */
        $skip = array('system/users/', 'data/', '.env', 'system/instance.foc', 'system/core/library/bd_inc.php');
        $root = '';
        $files = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if ($root === '' && preg_match('#^([^/]+)/(?:os\.php|system/)#', $name, $m)) {
                $root = $m[1] . '/';
            }
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if ($name === '' || substr($name, -1) === '/') {
                continue;
            }
            $rel = $root !== '' && strpos($name, $root) === 0 ? substr($name, strlen($root)) : $name;
            $rel = ltrim(str_replace(array('..', '\\'), array('', '/'), $rel), '/');
            if ($rel === '') {
                continue;
            }
            foreach ($skip as $s) {
                if ($rel === $s || strpos($rel, $s) === 0) {
                    continue 2;
                }
            }
            $target = $this->doc . '/' . $rel;
            $dir = dirname($target);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $data = $zip->getFromIndex($i);
            if ($data === false) {
                continue;
            }
            if (@file_put_contents($target, $data) !== false) {
                $files++;
            }
        }
        $zip->close();
        @unlink($tmp);

        /* обновляем версию */
        $cfg = $this->readIni($this->osInfo);
        $cfg['version'] = $latest['version'];
        $cfg['subversion'] = $latest['version'];
        $cfg['revision'] = $latest['file'] !== '' ? $latest['file'] : $latest['version'];
        /* кодовое имя берём из релиза, если оно там указано */
        if (!empty($latest['codename'])) {
            $cfg['codename'] = (string)$latest['codename'];
        }
        $this->writeIni($this->osInfo, $cfg);
        @chmod($this->osInfo, 0666);

        return array(
            'ok' => true,
            'installed' => $latest['version'],
            'files' => $files,
            'current' => $this->current(),
        );
    }
}
