<?php
/**
 * Applications ask for "file associations" here: the first app that opens a
 * file type becomes its default handler (extconfiguration.foc).
 *
 * The file is rewritten as a whole, so repeated calls cannot accumulate
 * duplicate keys (the old implementation checked substrings and appended).
 */
class PermissionRequest
{
    public function fileassociate($array, $appdestination, $requestname, $appname)
    {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/system/core/extconfiguration.foc';

        $raw = is_file($file) ? (string)@file_get_contents($file) : '';
        $ext = array();
        $keys = array();
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '[' || $line[0] === ';' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $k = trim(substr($line, 0, $pos));
            $v = trim(substr($line, $pos + 1));
            if ($k === '') {
                continue;
            }
            if (substr($k, -4) === '_key') {
                $keys[$k] = $v;
            } else {
                $ext[$k] = $v;
            }
        }

        $added = array();
        foreach ((array)$array as $e) {
            $e = strtolower(trim((string)$e));
            $e = ltrim($e, '.');
            if ($e === '') {
                continue;
            }
            if (!isset($ext[$e]) || $ext[$e] !== $appdestination) {
                $added[] = $e;
            }
            $ext[$e] = $appdestination;
            $keys[$e . '_key'] = $requestname;
        }
        if (!$added && !$ext) {
            return;
        }

        ksort($ext);
        ksort($keys);
        $out = "[AppExt]\n";
        foreach ($ext as $k => $v) {
            $out .= $k . '=' . $v . "\n";
        }
        $out .= "\n[AppKeys]\n";
        foreach ($keys as $k => $v) {
            $out .= $k . '=' . $v . "\n";
        }

        $dir = dirname($file);
        if (is_dir($dir) && is_writable($dir)) {
            @file_put_contents($file, $out);
            @chmod($file, 0666);
        }

        if (!empty($added)) {
            global $object, $language;
            if (is_object($object) && method_exists($object, 'newnotification')) {
                $pubname = str_replace('_', ' ', (string)$appname);
                $list = '';
                foreach ($added as $e) {
                    $list .= ' <b>.' . htmlspecialchars($e) . '</b>';
                }
                $object->newnotification(
                    (string)$appname,
                    'Ассоциация файлов',
                    'Приложение <b>' . htmlspecialchars($pubname) . '</b> стало обработчиком:' . $list
                );
            }
        }
    }
}
