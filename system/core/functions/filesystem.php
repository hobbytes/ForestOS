<?
/**
 * Копирование / перенос файлов (legacy-эндпоинт Explorer).
 * Источник и приёмник теперь обязаны лежать внутри DOCUMENT_ROOT,
 * а вызывать это может только вошедший пользователь.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/etc/security.php';
require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/filesystem.php';

$security = new security;
if (!isset($_SESSION)) {
    session_start();
}
$security->appprepare();

$doc = realpath($_SERVER['DOCUMENT_ROOT']);

function fo_inside_doc($path, $doc)
{
    if ($doc === false) {
        return null;
    }
    $real = realpath((string)$path);
    if ($real === false || strpos($real, $doc . '/') !== 0) {
        return null;
    }
    return $real;
}

$getFile  = (string)($_POST['f'] ?? '');
$newPlace = (string)($_POST['n'] ?? '');
$action   = (string)($_POST['a'] ?? '');

$src = fo_inside_doc($getFile, $doc);
$dst = fo_inside_doc($newPlace, $doc);
if ($src === null || $dst === null) {
    http_response_code(400);
    exit;
}
if (!preg_match('#/(os|login|makeprocess)\.php$#', $src)) {
    $fileaction = new fileaction;
    $fileaction->rcopy($src, $dst, 1);
    if ($action === 'cut') {
        if (is_dir($src)) {
            $fileaction->deleteDir($src);
        } else {
            @unlink($src);
        }
    }
}
