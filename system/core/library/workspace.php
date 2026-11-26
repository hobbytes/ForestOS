<?php
/**
 * Forest OS — «рабочее пространство»: какие окна открыты и где они стоят.
 *
 * Хранится per-user в system/users/<login>/settings/workspace.json.
 * Запись атомарная (tmp + rename), предыдущая версия остаётся в .bak,
 * у файла есть номер ревизии — старый браузер не затрёт новое состояние
 * молча (получит conflict и переспросит).
 */
final class ForestWorkspace
{
    public const MAX_WINDOWS = 24;
    public const MAX_BYTES = 262144;

    public static function file(string $login): string
    {
        $doc = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
        return $doc . '/system/users/' . $login . '/settings/workspace.json';
    }

    public static function load(string $login): array
    {
        $empty = array('rev' => 0, 'updated_at' => 0, 'active' => '', 'windows' => array());
        $file = self::file($login);
        if (!is_file($file)) {
            return $empty;
        }
        $raw = (string)@file_get_contents($file);
        $data = json_decode($raw, true);
        if (!is_array($data) && is_file($file . '.bak')) {
            /* основной файл побился — пробуем резервную копию */
            $data = json_decode((string)@file_get_contents($file . '.bak'), true);
        }
        if (!is_array($data)) {
            return $empty;
        }
        return self::normalize($data);
    }

    /** Приводит входные данные к безопасному виду. */
    public static function normalize(array $data): array
    {
        $out = array(
            'rev' => max(0, (int)($data['rev'] ?? 0)),
            'updated_at' => max(0, (int)($data['updated_at'] ?? 0)),
            'active' => self::cleanApp((string)($data['active'] ?? '')),
            'windows' => array(),
        );
        $windows = is_array($data['windows'] ?? null) ? $data['windows'] : array();
        foreach ($windows as $w) {
            if (!is_array($w)) {
                continue;
            }
            $dest = (string)($w['dest'] ?? '');
            if (!preg_match('#^system/apps/[A-Za-z0-9_.\-]+/main\.php$#', $dest)) {
                continue;
            }
            $doc = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
            if (!is_file($doc . '/' . $dest)) {
                /* приложение удалили — не тянем его в новую сессию */
                continue;
            }
            $name = preg_replace('/[^\p{L}\p{N}_ .\-]/u', '', (string)($w['name'] ?? ''));
            $param = (string)($w['param'] ?? '');
            $key = (string)($w['key'] ?? '');
            if (strlen($param) > 400) {
                $param = '';
            }
            if (strlen($key) > 120) {
                $key = '';
            }
            $geom = function ($v, $min, $max, $def) {
                $n = is_numeric($v) ? (int)round($v) : $def;
                return max($min, min($max, $n));
            };
            /* геометрия отдельно для десктопа и телефона: на телефоне окно
               полноэкранное, и его размеры не должны «ужимать» окно на ПК */
            $box = function (array $g) use ($geom) {
                return array(
                    'x' => $geom($g['x'] ?? null, -4000, 20000, 120),
                    'y' => $geom($g['y'] ?? null, -4000, 20000, 90),
                    'w' => $geom($g['w'] ?? null, 240, 8000, 900),
                    'h' => $geom($g['h'] ?? null, 160, 8000, 600),
                    'max' => !empty($g['max']),
                    'shaded' => !empty($g['shaded']),
                );
            };
            /* старый формат (плоские x/y/w/h) считаем десктопным */
            $desk = is_array($w['desk'] ?? null) ? $w['desk'] : $w;
            $mob = is_array($w['mob'] ?? null) ? $w['mob'] : array();
            /* «вид» приложения: последняя загруженная страница (для legacy-приложений,
               которые навигируют через $().load) и папка Проводника */
            $view = (string)($w['view'] ?? '');
            if ($view !== '' && (!preg_match('#^system/apps/[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+\.php(\?[^\s\'"<>]*)?$#', $view) || strlen($view) > 400)) {
                $view = '';
            }
            $dir = (string)($w['dir'] ?? '');
            if (strpos($dir, '..') !== false || strlen($dir) > 400) {
                $dir = '';
            }
            $out['windows'][] = array(
                'dest' => $dest,
                'name' => mb_substr(trim($name) !== '' ? trim($name) : 'App', 0, 60, 'UTF-8'),
                'param' => $param,
                'key' => $key,
                'view' => $view,
                'dir' => $dir,
                'desk' => $box($desk),
                'mob' => $box($mob),
            );
            if (count($out['windows']) >= self::MAX_WINDOWS) {
                break;
            }
        }
        return $out;
    }

    private static function cleanApp(string $app): string
    {
        $app = preg_replace('/[^\p{L}\p{N}_ .\-]/u', '', $app);
        return mb_substr(trim((string)$app), 0, 60, 'UTF-8');
    }

    /**
     * Сохраняет состояние. Возвращает ['ok'=>bool, 'rev'=>int, 'conflict'=>bool, 'workspace'=>array].
     * Если клиент пришёл со старой ревизией — не пишем, отдаём серверное состояние.
     */
    public static function save(string $login, array $data): array
    {
        $current = self::load($login);
        $clientRev = max(0, (int)($data['rev'] ?? 0));
        if ($clientRev < $current['rev'] && !empty($data['force'])) {
            $clientRev = $current['rev'];
        }
        if ($clientRev < $current['rev']) {
            return array('ok' => false, 'conflict' => true, 'rev' => $current['rev'], 'workspace' => $current);
        }
        /* клиент присылает геометрию только для своего типа устройства:
           вторую (десктопную или мобильную) сохраняем из текущего состояния */
        $mode = ((string)($data['mode'] ?? 'desk')) === 'mob' ? 'mob' : 'desk';
        $byKey = array();
        foreach ($current['windows'] as $cw) {
            $byKey[$cw['dest'] . '|' . $cw['param'] . '|' . $cw['key'] . '|' . (string)($cw['dir'] ?? '')] = $cw;
        }
        $incoming = array();
        $seen = array();
        foreach ((array)($data['windows'] ?? array()) as $w) {
            if (!is_array($w)) {
                continue;
            }
            $k = ((string)($w['dest'] ?? '')) . '|' . ((string)($w['param'] ?? '')) . '|'
                . ((string)($w['key'] ?? '')) . '|' . ((string)($w['dir'] ?? ''));
            if (isset($seen[$k])) {
                /* два одинаковых окна (то же приложение, тот же параметр)
                   неразличимы между сессиями — оставляем одно */
                continue;
            }
            $seen[$k] = true;
            $entry = isset($byKey[$k]) ? $byKey[$k] : array(
                'dest' => (string)($w['dest'] ?? ''),
                'param' => (string)($w['param'] ?? ''),
                'key' => (string)($w['key'] ?? ''),
                'name' => (string)($w['name'] ?? 'App'),
            );
            $entry[$mode] = array(
                'x' => $w['x'] ?? null, 'y' => $w['y'] ?? null,
                'w' => $w['w'] ?? null, 'h' => $w['h'] ?? null,
                'max' => !empty($w['max']), 'shaded' => !empty($w['shaded']),
            );
            $entry['view'] = (string)($w['view'] ?? '');
            $entry['dir'] = (string)($w['dir'] ?? '');
            if (!isset($entry['desk']) || !is_array($entry['desk'])) {
                $entry['desk'] = array();
            }
            if (!isset($entry['mob']) || !is_array($entry['mob'])) {
                $entry['mob'] = array();
            }
            $incoming[] = $entry;
        }
        $data['windows'] = $incoming;
        $clean = self::normalize($data);
        $clean['rev'] = max($current['rev'], $clientRev) + 1;
        $clean['updated_at'] = time();
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || strlen($json) > self::MAX_BYTES) {
            return array('ok' => false, 'error' => 'too_large', 'rev' => $current['rev']);
        }
        if (!self::writeAtomic(self::file($login), $json)) {
            return array('ok' => false, 'error' => 'write_failed', 'rev' => $current['rev']);
        }
        return array('ok' => true, 'conflict' => false, 'rev' => $clean['rev'], 'workspace' => $clean);
    }

    public static function clear(string $login): bool
    {
        $file = self::file($login);
        $ok = true;
        if (is_file($file) && !@unlink($file)) {
            $ok = false;
        }
        @unlink($file . '.bak');
        return $ok;
    }

    private static function writeAtomic(string $file, string $json): bool
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (is_file($file)) {
            @copy($file, $file . '.bak');
        }
        $tmp = $file . '.tmp' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, $json) === false) {
            @unlink($tmp);
            return false;
        }
        @chmod($tmp, 0666);
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }
}
