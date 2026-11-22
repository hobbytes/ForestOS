<?php
/**
 * Forest OS bootstrap for Docker/local revival.
 * Run inside container:
 *   php bin/forest-bootstrap.php
 */

declare(strict_types=1);

function envv(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function ensure_dir(string $dir): void {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create directory: {$dir}");
    }
}

function write_if_missing(string $file, string $content): void {
    if (!file_exists($file)) {
        ensure_dir(dirname($file));
        file_put_contents($file, $content);
    }
}

/**
 * Печатает данные для первого входа: их видно в docker compose logs.
 */
function forest_banner(string $login, string $password, bool $isDefault): void
{
    $port = (string)envv('APP_PORT', '8080');
    $line = str_repeat('=', 54);
    echo "\n{$line}\n";
    echo " Forest OS готова к работе\n";
    echo " Адрес:  http://localhost:{$port}\n";
    echo " Логин:  {$login}\n";
    echo " Пароль: {$password}\n";
    echo "{$line}\n";
    if ($isDefault && in_array(strtolower($password), array('change_me_admin_password', 'admin12345', 'admin', 'password'), true)) {
        echo " ! Это пароль-заглушка. Смените его: Настройки → Безопасность\n";
        echo "{$line}\n";
    }
    echo "\n";
}

function legacy_crypt_s(string $string, string $salt): string {
    $crypt = crypt($string, '$2a$10$' . md5($salt));
    $crypt = str_replace('$2a$10$', '', $crypt);
    return addslashes(strip_tags(htmlspecialchars($crypt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
}

function generate_fuid(string $login, string $passwordHash): string {
    $cloudEnabled = filter_var(envv('FOREST_CLOUD_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
    $cloudUrl = rtrim((string)envv('FOREST_CLOUD_URL', ''), '/');
    $cloudToken = (string)envv('FOREST_CLOUD_TOKEN', '');

    if ($cloudEnabled && $cloudUrl !== '') {
        $payload = json_encode(['login' => $login], JSON_UNESCAPED_UNICODE);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$cloudToken}\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($cloudUrl . '/api/v1/users/reserve', false, $ctx);
        $json = $response ? json_decode($response, true) : null;
        if (is_array($json) && !empty($json['allowed']) && !empty($json['fuid'])) {
            return strtoupper((string)$json['fuid']);
        }
        fwrite(STDERR, "Cloud FUID reserve failed, falling back to local FUID.\n");
    }

    return strtoupper(hash('sha256', $login . '|' . $passwordHash . '|' . bin2hex(random_bytes(16))));
}

require_once (realpath(__DIR__ . '/..') ?: getcwd()) . '/system/core/library/install.php';

$root = realpath(__DIR__ . '/..') ?: getcwd();
chdir($root);

$dbHost = envv('DB_HOST', 'db');
$dbName = envv('DB_NAME', 'forestos');
$dbUser = envv('DB_USER', 'forest');
$dbPass = (string)envv('DB_PASSWORD', '');
$login = strtolower(str_replace(' ', '_', envv('FOREST_ADMIN_LOGIN', 'admin')));
$password = (string)envv('FOREST_ADMIN_PASSWORD', '');
$lang = envv('FOREST_ADMIN_LANGUAGE', 'ru');
$tz = envv('FOREST_ADMIN_TIMEZONE', envv('APP_TIMEZONE', 'Europe/Moscow'));

if (!preg_match('/^[a-z0-9_\-\.]{3,64}$/', $login)) {
    throw new InvalidArgumentException('FOREST_ADMIN_LOGIN must be 3-64 chars: a-z, 0-9, _, -, .');
}

$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sql = file_get_contents($root . '/sql/001_forestusers.sql');
$pdo->exec($sql);

$resetWanted = filter_var(envv('FOREST_ADMIN_PASSWORD_RESET', 'false'), FILTER_VALIDATE_BOOLEAN);

if (fo_has_superuser($pdo) && !$resetWanted) {
    /* система уже настроена: пароль из .env сам по себе не применяется */
    echo "\n  Учётная запись уже создана — пароль не менялся.\n";
    echo "  Сменить пароль:  php bin/forest-password.php\n";
    echo "  или поставить FOREST_ADMIN_PASSWORD_RESET=1 в .env и перезапустить контейнер.\n\n";
} elseif ($password === '' || strlen($password) < 8) {
    /* пароль не задан — его создадут в браузере при первом запуске */
    echo "\n  Учётная запись администратора ещё не создана.\n";
    echo "  Откройте http://localhost:" . (string)envv('APP_PORT', '8080') . " и задайте логин и пароль.\n\n";
} else {
    $res = fo_create_user($pdo, $login, $password, $lang, $tz, $root);
    if (!empty($res['ok'])) {
        echo ($res['created'] ? "Created superuser: " : "Password updated: ") . $login . "\n";
        forest_banner($login, $password, !$resetWanted);
    } else {
        fwrite(STDERR, "Не удалось создать администратора: " . ($res['error'] ?? 'unknown') . "\n");
        exit(1);
    }
}

// Legacy DB include expected by system/core/library/bd.php
write_if_missing('system/core/library/bd_inc.php', "<?php\ndefine('DB_DSN', 'mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4');\ndefine('DB_USERNAME', '{$dbUser}');\ndefine('DB_PASSWORD', '{$dbPass}');\n?>\n");

// Core generated files
write_if_missing('system/core/osinfo.foc', "[forestos]\n\nversion=2\n\nsubversion=2.0-docker\n\nrevision=local\n\ncodename=Revival\n");
write_if_missing('system/core/extconfiguration.foc', "[AppExt]\n\n[AppKeys]\n");
write_if_missing('system/core/journal.mcj', '');

// User tree
ensure_dir('system/users');
@chmod($root . '/system/users', 0777);
$userRoot = "system/users/{$login}";
ensure_dir($userRoot . '/desktop');
ensure_dir($userRoot . '/documents/images');
ensure_dir($userRoot . '/trash');
ensure_dir($userRoot . '/settings/etc');
ensure_dir($userRoot . '/settings/notifications');
ensure_dir($userRoot . '/settings/Dock/A');

write_if_missing($userRoot . '/settings/language.foc', $lang);
write_if_missing($userRoot . '/settings/timezone.foc', $tz);
write_if_missing($userRoot . '/settings/autorun.foc', '');
write_if_missing($userRoot . '/settings/state.hdf', '');
write_if_missing($userRoot . '/settings/display.foc', '');
write_if_missing($userRoot . '/settings/AppWindow.foc', "WindowSize=8\nControlMode=1\n");
write_if_missing($userRoot . '/settings/notifications/MainNotificationFile.hdf', '');

// Dock defaults
write_if_missing($userRoot . '/settings/Dock/A/Explorer', '');
write_if_missing($userRoot . '/settings/Dock/A/Settings', '');
write_if_missing($userRoot . '/settings/Dock/A/Apps_House', '');

// Theme fallback if old design theme is absent
$themeSource = 'system/core/design/themes/BlackMamba.fth';
$themeTarget = $userRoot . '/settings/etc/theme.fth';
if (!file_exists($themeTarget)) {
    if (file_exists($themeSource)) {
        copy($themeSource, $themeTarget);
    } else {
        file_put_contents($themeTarget, "topbarbackcolor=rgba(35,35,35,0.85);\ntopbarfontcolor=#ffffff;\ndraggablebackcolor=rgba(55,55,55,0.95);\ndraggablefontcolor=#ffffff;\nmenubackcolor=rgba(40,40,40,0.95);\nmenufontcolor=#ffffff;\nbackgroundcolor=#171717;\nbackgroundfontcolor=#ffffff;\ncustomCSS=\n");
    }
}

// Wallpaper fallback
if (!file_exists($userRoot . '/settings/etc/wall.jpg')) {
    foreach (['system/core/design/walls/forestoswall.jpg', 'system/core/design/images/webwall.jpg', 'system/core/design/images/forestosicon.png'] as $candidate) {
        if (file_exists($candidate)) {
            copy($candidate, $userRoot . '/settings/etc/wall.jpg');
            break;
        }
    }
}

$docRoot = '/var/www/html';
$trashLink = "[link]\ndestination=system/apps/Explorer/\nfile=main\nkey=dir\nparam={$docRoot}/system/users/{$login}/trash\nname=Explorer\nlinkname=Корзина\nicon=system/apps/Explorer/assets/trashicon.png\n";
write_if_missing($userRoot . '/desktop/trash.link', $trashLink);

// Install PHP 8 security compatibility patch if present
$patch = 'patches/system/core/library/etc/security.php';
$target = 'system/core/library/etc/security.php';
if (file_exists($patch) && file_exists($target)) {
    if (!file_exists($target . '.legacy-mcrypt.bak')) {
        copy($target, $target . '.legacy-mcrypt.bak');
    }
    copy($patch, $target);
    echo "Applied PHP 8 security compatibility patch.\n";
}

echo "Forest OS bootstrap complete. Open http://localhost:" . envv('APP_PORT', '8080') . "\n";
