<?php
/**
 * Forest OS — одна активная сессия на пользователя.
 *
 * При входе генерируется токен и сохраняется в forestusers.session_token.
 * Каждый запрос оболочки сверяет токен из сессии с сохранённым: если он
 * сменился (вошли на другом устройстве), старая сессия завершается и
 * пользователь видит экран входа с объяснением.
 *
 * Это же защищает рабочее пространство: выкинутая вкладка больше не может
 * сохранить своё состояние поверх нового (плюс проверка ревизии на сервере).
 */
final class ForestSession
{
    private static function db(): PDO
    {
        if (!defined('DB_DSN')) {
            require_once __DIR__ . '/bd_inc.php';
        }
        return new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ));
    }

    /** Колонка появляется на существующей базе при первом входе. */
    public static function ensureColumn(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $pdo = self::db();
            $check = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $check->execute(array('forestusers', 'session_token'));
            if ((int)$check->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE forestusers ADD session_token VARCHAR(64) NULL');
            }
        } catch (Throwable $e) {
            /* нет прав на ALTER — работаем без проверки */
        }
    }

    /** Новый вход: выдаём токен и делаем его единственным активным. */
    public static function begin(string $login): string
    {
        if ($login === '') {
            return '';
        }
        $token = bin2hex(random_bytes(16));
        try {
            self::ensureColumn();
            $st = self::db()->prepare('UPDATE forestusers SET session_token = ? WHERE login = ?');
            $st->execute(array($token, $login));
        } catch (Throwable $e) {
            return '';
        }
        return $token;
    }

    /** Совпадает ли сессия с текущей активной. */
    public static function validate(string $login, string $token): bool
    {
        if ($login === '') {
            return false;
        }
        try {
            self::ensureColumn();
            $st = self::db()->prepare('SELECT session_token FROM forestusers WHERE login = ?');
            $st->execute(array($login));
            $stored = (string)$st->fetchColumn();
        } catch (Throwable $e) {
            /* база недоступна — не выкидываем пользователя из-за этого */
            return true;
        }
        if ($stored === '') {
            /* сессия до появления механизма — не трогаем */
            return true;
        }
        return $token !== '' && hash_equals($stored, $token);
    }

    /** Выход: токен больше никому не действителен. */
    public static function end(string $login): void
    {
        if ($login === '') {
            return;
        }
        try {
            self::ensureColumn();
            self::db()->prepare('UPDATE forestusers SET session_token = NULL WHERE login = ?')
                ->execute(array($login));
        } catch (Throwable $e) {
        }
    }
}
