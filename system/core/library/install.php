<?php
/**
 * Forest OS — установка и учётные записи.
 * Используется bootstrap-скриптом и экраном первого запуска.
 */

/** Подключение к базе: константы из bd_inc.php, иначе .env. */
function fo_db(): PDO
{
    $root = rtrim(getenv('FO_DOC') ?: dirname(__DIR__, 3), '/');
    $inc = $root . '/system/core/library/bd_inc.php';
    $dsn = defined('DB_DSN') ? DB_DSN : '';
    $user = defined('DB_USERNAME') ? DB_USERNAME : '';
    $pass = defined('DB_PASSWORD') ? DB_PASSWORD : '';

    if ($dsn === '' && is_file($inc)) {
        $src = (string)file_get_contents($inc);
        foreach (array('DB_DSN' => 'dsn', 'DB_USERNAME' => 'user', 'DB_PASSWORD' => 'pass') as $const => $var) {
            if (preg_match("/define\('" . $const . "',\s*'([^']*)'\)/", $src, $m)) {
                $$var = $m[1];
            }
        }
    }
    if ($dsn === '') {
        $cfg = is_file($root . '/.env') ? (array)@parse_ini_file($root . '/.env') : array();
        $host = $cfg['DB_HOST'] ?? (getenv('DB_HOST') ?: 'db');
        $name = $cfg['DB_NAME'] ?? (getenv('DB_NAME') ?: 'forestos');
        $user = $cfg['DB_USER'] ?? (getenv('DB_USER') ?: 'forest');
        $pass = $cfg['DB_PASSWORD'] ?? (getenv('DB_PASSWORD') ?: '');
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    }

    return new PDO($dsn, $user, $pass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
}

/** Есть ли хоть один суперпользователь (значит установка завершена). */
function fo_has_superuser(?PDO $pdo = null): bool
{
    try {
        $pdo = $pdo ?: fo_db();
        $n = (int)$pdo->query("SELECT COUNT(*) FROM forestusers WHERE status = 'superuser'")->fetchColumn();
        return $n > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/** Хеш пароля в историческом формате Forest OS. */
function fo_hash_password(string $password, string $salt): string
{
    $crypt = crypt($password, '$2a$10$' . md5($salt));
    $crypt = str_replace('$2a$10$', '', $crypt);
    return addslashes(strip_tags(htmlspecialchars($crypt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
}

/** Дерево каталогов и стартовые настройки пользователя. */
function fo_user_tree(string $root, string $login, string $lang = 'ru', string $tz = 'Europe/Moscow'): void
{
    $root = rtrim($root, '/');
    $userRoot = $root . '/system/users/' . $login;
    $dirs = array(
        '/desktop', '/documents/images', '/trash',
        '/settings/etc', '/settings/notifications', '/settings/Dock/A',
    );
    foreach ($dirs as $d) {
        if (!is_dir($userRoot . $d)) {
            @mkdir($userRoot . $d, 0777, true);
        }
        @chmod($userRoot . $d, 0777);
    }

    $files = array(
        '/settings/language.foc' => $lang,
        '/settings/timezone.foc' => $tz,
        '/settings/autorun.foc' => '',
        '/settings/state.hdf' => '',
        '/settings/display.foc' => '',
        '/settings/AppWindow.foc' => "WindowSize=8\nControlMode=1\n",
        '/settings/notifications/MainNotificationFile.hdf' => '',
    );
    foreach ($files as $f => $content) {
        $path = $userRoot . $f;
        if (!is_file($path)) {
            @file_put_contents($path, $content);
        }
        @chmod($path, 0666);
    }

    foreach (array('Explorer', 'Settings', 'Apps_House') as $app) {
        $p = $userRoot . '/settings/Dock/A/' . $app;
        if (!is_file($p)) {
            @file_put_contents($p, '');
        }
        @chmod($p, 0666);
    }

    /* тема по умолчанию, если своя ещё не выбрана */
    $themeTarget = $userRoot . '/settings/etc/theme.fth';
    if (!is_file($themeTarget)) {
        $source = $root . '/system/core/design/themes/BlackMamba.fth';
        if (is_file($source)) {
            @copy($source, $themeTarget);
            @chmod($themeTarget, 0666);
        }
    }
}

/**
 * Создать (или обновить) суперпользователя и его дерево.
 * @return array{ok:bool,created?:bool,error?:string}
 */
function fo_create_user(PDO $pdo, string $login, string $password, string $lang = 'ru', string $tz = 'Europe/Moscow', string $root = ''): array
{
    $login = strtolower(str_replace(' ', '_', trim($login)));
    if (!preg_match('/^[a-z0-9_\-\.]{3,64}$/', $login)) {
        return array('ok' => false, 'error' => 'bad_login');
    }
    if (strlen($password) < 8) {
        return array('ok' => false, 'error' => 'short_password');
    }
    $root = $root !== '' ? $root : rtrim(getenv('FO_DOC') ?: dirname(__DIR__, 3), '/');
    $hash = fo_hash_password($password, $login);

    $stmt = $pdo->prepare('SELECT login FROM forestusers WHERE login = ?');
    $stmt->execute(array($login));
    $exists = (bool)$stmt->fetch();

    if ($exists) {
        $pdo->prepare('UPDATE forestusers SET password = ?, TempKey = NULL WHERE login = ?')->execute(array($hash, $login));
    } else {
        $fuid = strtoupper(hash('sha256', $login . '|' . $hash . '|' . bin2hex(random_bytes(16))));
        $pdo->prepare('INSERT INTO forestusers (login, password, fuid, status, TempKey) VALUES (?, ?, ?, ?, NULL)')
            ->execute(array($login, $hash, $fuid, 'superuser'));
    }

    fo_user_tree($root, $login, $lang, $tz);
    return array('ok' => true, 'created' => !$exists);
}
