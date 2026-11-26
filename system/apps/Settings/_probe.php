<?php
header('Content-Type: text/plain; charset=utf-8');
echo 'METHOD=' . $_SERVER['REQUEST_METHOD'] . "\n";
echo 'POST=' . json_encode($_POST, JSON_UNESCAPED_UNICODE) . "\n";
echo 'RAW=' . file_get_contents('php://input') . "\n";
echo 'CT=' . ($_SERVER['CONTENT_TYPE'] ?? '') . "\n";
echo 'CL=' . ($_SERVER['CONTENT_LENGTH'] ?? '') . "\n";
echo 'COOKIE=' . json_encode(array_keys($_COOKIE)) . "\n";
