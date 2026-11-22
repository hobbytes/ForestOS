<?php
/**
 * Forest OS — смена пароля пользователя.
 *
 * Зачем: bootstrap создаёт суперпользователя один раз. Если инсталляция уже есть,
 * смена FOREST_ADMIN_PASSWORD в .env сама по себе пароль не меняет — база живёт
 * в томе и хранит старый хеш. Этот скрипт обновляет пароль в базе.
 *
 * Использование:
 *   php bin/forest-password.php                       # логин и пароль из .env
 *   php bin/forest-password.php --login=admin --password='новый пароль'
 *   php bin/forest-password.php --login=admin         # пароль спросит интерактивно
 */

declare(strict_types=1);

function pw_env(string $key, string $default = ''): string
{
    static $cfg = null;
    if ($cfg === null) {
        $root = getenv('FO_DOC') ?: dirname(__DIR__);
        $file = rtrim($root, '/') . '/.env';
        $cfg = is_file($file) ? (array)@parse_ini_file($file) : array();
    }
    $v = $cfg[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : (string)$v;
}

function pw_root(): string
{
    return rtrim(getenv('FO_DOC') ?: dirname(__DIR__), '/');
}

/** Хеш совместим с историческим форматом Forest OS. */
function pw_hash(string $password, string $salt): string
{
    $crypt = crypt($password, '$2a$10$' . md5($salt));
    $crypt = str_replace('$2a$10$', '', $crypt);
    return addslashes(strip_tags(htmlspecialchars($crypt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
}

/* ---------- аргументы ---------- */
$login = '';
$password = '';
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--login=') === 0) {
        $login = substr($arg, 8);
    } elseif (strpos($arg, '--password=') === 0) {
        $password = substr($arg, 11);
    }
}
if ($login === '') {
    $login = pw_env('FOREST_ADMIN_LOGIN', 'admin');
}
$login = strtolower(str_replace(' ', '_', $login));

if ($password === '') {
    $password = pw_env('FOREST_ADMIN_PASSWORD', '');
}

/* если пароля нет ни в аргументах, ни в .env — спрашиваем */
if ($password === '' && PHP_SAPI === 'cli' && function_exists('readline')) {
    $password = (string)readline('Новый пароль: ');
}

if ($password === '' || strlen($password) < 8) {
    fwrite(STDERR, "Пароль не задан или короче 8 символов.\n");
    fwrite(STDERR, "Укажите FOREST_ADMIN_PASSWORD в .env или передайте --password='...'\n");
    exit(1);
}
if (!preg_match('/^[a-z0-9_\-\.]{3,64}$/', $login)) {
    fwrite(STDERR, "Некорректный логин: {$login}\n");
    exit(1);
}

/* ---------- база ---------- */
$dbHost = pw_env('DB_HOST', 'db');
$dbName = pw_env('DB_NAME', 'forestos');
$dbUser = pw_env('DB_USER', 'forest');
$dbPass = pw_env('DB_PASSWORD', '');

$inc = pw_root() . '/system/core/library/bd_inc.php';
if (is_file($inc)) {
    $src = (string)file_get_contents($inc);
    foreach (array('DB_DSN' => 'dsn', 'DB_USERNAME' => 'dbUser', 'DB_PASSWORD' => 'dbPass') as $const => $var) {
        if ($var === 'dbPass' && $dbPass !== '') {
            continue;
        }
        if (preg_match("/define\('" . $const . "',\s*'([^']*)'\)/", $src, $m)) {
            if ($var === 'dsn') {
                if (preg_match('/dbname=([^;]+)/', $m[1], $mm)) {
                    $dbName = $mm[1];
                }
                if (preg_match('/host=([^;]+)/', $m[1], $mm)) {
                    $dbHost = $mm[1];
                }
            } else {
                $$var = $m[1];
            }
        }
    }
}

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
} catch (Throwable $e) {
    fwrite(STDERR, 'Не удалось подключиться к базе: ' . $e->getMessage() . "\n");
    fwrite(STDERR, "Проверьте DB_HOST/DB_NAME/DB_USER/DB_PASSWORD в .env\n");
    exit(1);
}

$stmt = $pdo->prepare('SELECT login FROM forestusers WHERE login = ?');
$stmt->execute(array($login));
if (!$stmt->fetch()) {
    fwrite(STDERR, "Пользователь {$login} не найден.\n");
    $all = $pdo->query('SELECT login FROM forestusers ORDER BY login')->fetchAll();
    if ($all) {
        fwrite(STDERR, 'Есть: ' . implode(', ', array_column($all, 'login')) . "\n");
    }
    exit(1);
}

$pdo->prepare('UPDATE forestusers SET password = ?, TempKey = NULL WHERE login = ?')
    ->execute(array(pw_hash($password, $login), $login));

echo "Пароль пользователя {$login} обновлён.\n";
echo "Старые ключи доступа удалены.\n";
