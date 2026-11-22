<?php
/**
 * Forest OS — сборка и публикация релиза обновления.
 *
 * Берёт код из GitHub (zip ветки), перепаковывает в forest-os-<версия>.zip,
 * считает контрольную сумму и кладёт рядом update.json, который читает
 * приложение «Обновление».
 *
 * CLI:  php bin/release.php --version=4.4 --notes="Что нового"
 * API:  POST system/core/functions/osupdate.php?action=release (суперпользователь)
 */

declare(strict_types=1);

function rl_env(string $key, string $default = ''): string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : (string)$v;
}

function rl_root(): string
{
    $root = getenv('FO_DOC');
    if ($root) {
        return rtrim($root, '/');
    }
    return rtrim((string)realpath(__DIR__ . '/..'), '/');
}

/**
 * @return array{ok:bool,error?:string,version?:string,file?:string,size?:int,sha256?:string,url?:string,files?:int}
 */
function rl_build(string $version, string $notes, string $codename = '', array $opts = array()): array
{
    $doc = rl_root();
    $owner = $opts['owner'] ?? rl_env('FOREST_REPO_OWNER', 'hobbytes');
    $repo = $opts['repo'] ?? rl_env('FOREST_REPO_NAME', 'ForestOS');
    $branch = $opts['branch'] ?? rl_env('FOREST_REPO_BRANCH', 'master');
    $releases = $doc . '/releases';

    if (!preg_match('/^\d+(\.\d+){0,3}(-[a-z0-9.]+)?$/i', $version)) {
        return array('ok' => false, 'error' => 'bad_version');
    }
    if (!is_dir($releases) && !@mkdir($releases, 0775, true)) {
        return array('ok' => false, 'error' => 'mkdir');
    }
    if (!class_exists('ZipArchive')) {
        return array('ok' => false, 'error' => 'no_zip_ext');
    }

    /* 1. скачиваем исходники из GitHub */
    $srcUrl = "https://github.com/{$owner}/{$repo}/archive/refs/heads/{$branch}.zip";
    $tmp = tempnam(sys_get_temp_dir(), 'forestsrc') . '.zip';
    $fp = @fopen($tmp, 'wb');
    if (!$fp) {
        return array('ok' => false, 'error' => 'tmp');
    }
    $ch = curl_init($srcUrl);
    curl_setopt_array($ch, array(
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'ForestOS-Release',
    ));
    $ok = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    if ($ok === false || $code >= 400 || !is_file($tmp) || filesize($tmp) < 1024) {
        @unlink($tmp);
        return array('ok' => false, 'error' => 'source_download', 'detail' => (string)$code);
    }

    $src = new ZipArchive();
    if ($src->open($tmp) !== true) {
        @unlink($tmp);
        return array('ok' => false, 'error' => 'source_zip');
    }

    /* префикс внутри архива GitHub: <repo>-<branch>/ */
    $prefix = '';
    for ($i = 0; $i < $src->numFiles; $i++) {
        $n = (string)$src->getNameIndex($i);
        if (preg_match('#^([^/]+)/#', $n, $m)) {
            $prefix = $m[1] . '/';
            break;
        }
    }

    $skip = array('.git/', '.github/', 'data/', 'system/users/', 'docs/screenshots/');
    $skipFiles = array('.env', 'system/instance.foc', 'system/core/library/bd_inc.php', 'system/core/journal.mcj');

    $outName = 'forest-os-' . $version . '.zip';
    $outPath = $releases . '/' . $outName;
    @unlink($outPath);
    $dst = new ZipArchive();
    if ($dst->open($outPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        $src->close();
        @unlink($tmp);
        return array('ok' => false, 'error' => 'dest_zip');
    }

    $files = 0;
    for ($i = 0; $i < $src->numFiles; $i++) {
        $name = (string)$src->getNameIndex($i);
        if ($prefix !== '' && strpos($name, $prefix) === 0) {
            $rel = substr($name, strlen($prefix));
        } else {
            $rel = $name;
        }
        if ($rel === '' || substr($rel, -1) === '/') {
            continue;
        }
        $skipIt = false;
        foreach ($skip as $s) {
            if (strpos($rel, $s) === 0) {
                $skipIt = true;
                break;
            }
        }
        if (!$skipIt && in_array($rel, $skipFiles, true)) {
            $skipIt = true;
        }
        if (!$skipIt && preg_match('/\.bak-|\.bak$/', $rel)) {
            $skipIt = true;
        }
        if ($skipIt) {
            continue;
        }
        $data = $src->getFromIndex($i);
        if ($data === false) {
            continue;
        }
        if ($dst->addFromString($rel, $data)) {
            $files++;
        }
    }
    /* пустая папка пользователей, чтобы монтирование работало */
    $dst->addEmptyDir('system/users');
    $dst->close();
    $src->close();
    @unlink($tmp);

    $size = (int)@filesize($outPath);
    $sha = (string)@hash_file('sha256', $outPath);

    /* 2. адрес, по которому этот сервер отдаёт архив */
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $port = rl_env('APP_PORT', '');
    if ($host === '') {
        /* CLI: берём публичный адрес из .env, иначе из окружения */
        $envFile = $doc . '/.env';
        $cfg = is_file($envFile) ? (array)@parse_ini_file($envFile) : array();
        $host = (string)($cfg['FOREST_PUBLIC_HOST'] ?? rl_env('FOREST_PUBLIC_HOST', 'localhost'));
        $port = (string)($cfg['APP_PORT'] ?? $port);
    }
    if ($port !== '' && $port !== '80' && $port !== '443' && strpos($host, ':') === false) {
        $host .= ':' . $port;
    }
    $base = rtrim(rl_env('FOREST_RELEASES_URL', $scheme . '://' . $host . '/releases'), '/');

    $codename = trim($codename) !== '' ? trim($codename) : (string)rl_env('FOREST_CODENAME', '');
    $payload = array(
        'version' => $version,
        'name' => 'Forest OS ' . $version,
        'codename' => $codename,
        'notes' => $notes,
        'published_at' => date('Y-m-d H:i'),
        'file' => $outName,
        'size' => $size,
        'sha256' => $sha,
        'url' => $base . '/' . $outName,
        'os_min' => '2.0',
        'source' => $srcUrl,
        'codename' => $codename,
    );
    @file_put_contents($releases . '/update.json', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    @chmod($releases . '/update.json', 0666);
    @chmod($outPath, 0644);

    /* 3. чистим старые архивы (оставляем 5 последних) */
    $old = glob($releases . '/forest-os-*.zip');
    if (is_array($old) && count($old) > 5) {
        usort($old, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        foreach (array_slice($old, 0, count($old) - 5) as $f) {
            @unlink($f);
        }
    }

    return array(
        'ok' => true,
        'version' => $version,
        'file' => $outName,
        'size' => $size,
        'sha256' => $sha,
        'url' => $payload['url'],
        'files' => $files,
    );
}

/* ---------- CLI ---------- */
if (PHP_SAPI === 'cli' && isset($argv) && basename((string)$argv[0]) === 'release.php') {
    $version = '';
    $notes = '';
    $codename = '';
    foreach ($argv as $arg) {
        if (strpos($arg, '--version=') === 0) {
            $version = substr($arg, 10);
        } elseif (strpos($arg, '--notes=') === 0) {
            $notes = substr($arg, 8);
        } elseif (strpos($arg, '--codename=') === 0) {
            $codename = substr($arg, 11);
        }
    }
    if ($version === '') {
        fwrite(STDERR, "usage: php bin/release.php --version=2.0 --notes=\"Что нового\" [--codename=\"Wet Stone\"]\n");
        exit(1);
    }
    $res = rl_build($version, $notes, $codename);
    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
    exit($res['ok'] ? 0 : 1);
}
