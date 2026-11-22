<?php
/**
 * Renders the desktop icons for the current user (HTML fragment).
 * Used by the Explorer to refresh the desktop right after a shortcut is created.
 */
if (!isset($_SESSION)) {
    session_start();
}

ini_set('display_errors', 'Off');

if (empty($_SESSION['loginuser']) || !isset($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
    http_response_code(401);
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$DOC = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');

require_once $DOC . '/system/core/library/etc.php';
require_once $DOC . '/system/core/library/filesystem.php';
require_once $DOC . '/system/core/library/prepare.php';

$hashfile = new fileaction();
$prepare  = new prepare();
$login    = (string)$_SESSION['loginuser'];
$click    = 'dblclick';
$maxwidth = '100%';
$top      = '0';
$left     = '0';

// the renderer resolves icons through relative paths
chdir($DOC);

$prepare->desktop('linkdiv');
