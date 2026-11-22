<?php
/**
 * Forest OS — снять блокировку IP (fail2ban) из командной строки.
 *
 *   php bin/forest-unban.php 1.2.3.4     — разблокировать адрес
 *   php bin/forest-unban.php --list      — показать блокировки
 *   php bin/forest-unban.php --all       — снять все
 *
 * Нужно, если администратор заблокировал сам себя и не может войти.
 */

$root = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $root;
require_once $root . '/system/core/library/bd.php';
require_once $root . '/system/core/library/bans.php';

$arg = $argv[1] ?? '';

if ($arg === '--list' || $arg === '') {
    $rows = ForestBans::active();
    if (!$rows) {
        echo "Активных блокировок нет.\n";
        exit(0);
    }
    foreach ($rows as $r) {
        printf("%-16s %s  неудач: %d  блокировок: %d  %s\n",
            $r['ip'],
            $r['banned'] ? 'ЗАБАНЕН до ' . ForestBans::untilText((int)$r['until']) : 'без бана     ',
            $r['failures'],
            $r['bans'],
            $r['last_login'] !== '' ? 'логин: ' . $r['last_login'] : ''
        );
    }
    exit(0);
}

if ($arg === '--all') {
    $n = ForestBans::unbanAll();
    echo "Снято блокировок: {$n}\n";
    exit(0);
}

if (!filter_var($arg, FILTER_VALIDATE_IP)) {
    fwrite(STDERR, "Укажите IP: php bin/forest-unban.php 1.2.3.4 | --list | --all\n");
    exit(1);
}

echo ForestBans::unban($arg) ? "Разблокирован: {$arg}\n" : "Нечего снимать: {$arg}\n";
