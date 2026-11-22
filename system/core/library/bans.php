<?php
/**
 * Forest OS — простой fail2ban: счётчик неудачных входов по IP.
 *
 * 3 неудачи в окне 15 минут → IP блокируется на 15 минут. Повторные
 * блокировки того же IP растут: 15 мин → 1 час → 6 часов → 24 часа.
 * Успешный вход сбрасывает счётчик.
 *
 * За прокси (forest.cbrpnk.xyz → 127.0.0.1:48080) реальный адрес берётся из
 * X-Forwarded-For, но только если соединение пришло от локального/частного
 * адреса — иначе заголовок можно подделать.
 */
final class ForestBans
{
    public const MAX_FAILURES = 3;
    public const WINDOW = 900;        // 15 минут на «3 попытки»
    public const DURATIONS = array(900, 3600, 21600, 86400); // 15м, 1ч, 6ч, 24ч

    /* ---------------- адрес клиента ---------------- */

    public static function ip(): string
    {
        $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $xff = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff !== '' && self::isPrivate($remote)) {
            foreach (explode(',', $xff) as $part) {
                $cand = trim($part);
                if (filter_var($cand, FILTER_VALIDATE_IP)) {
                    return $cand;
                }
            }
        }
        return $remote;
    }

    private static function isPrivate(string $ip): bool
    {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if (in_array($ip, array('127.0.0.1', '::1'), true)) {
            return true;
        }
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /* ---------------- хранилище ---------------- */

    private static function db(): PDO
    {
        if (!defined('DB_DSN')) {
            require_once __DIR__ . '/bd_inc.php';
        }
        $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ));
        self::ensureTable($pdo);
        return $pdo;
    }

    private static function ensureTable(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS forestbans (
                ip VARCHAR(45) NOT NULL PRIMARY KEY,
                failures INT NOT NULL DEFAULT 0,
                first_at INT NOT NULL DEFAULT 0,
                last_at INT NOT NULL DEFAULT 0,
                banned_until INT NOT NULL DEFAULT 0,
                bans INT NOT NULL DEFAULT 0,
                last_login VARCHAR(150) NOT NULL DEFAULT \'\'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        } catch (Throwable $e) {
            /* таблицу создаст bootstrap */
        }
    }

    /** Состояние IP: забанен ли и сколько осталось. */
    public static function check(string $ip): array
    {
        $out = array('ip' => $ip, 'banned' => false, 'until' => 0, 'left' => 0,
            'failures' => 0, 'bans' => 0);
        if ($ip === '') {
            return $out;
        }
        try {
            $st = self::db()->prepare('SELECT * FROM forestbans WHERE ip = ?');
            $st->execute(array($ip));
            $row = $st->fetch();
        } catch (Throwable $e) {
            return $out;
        }
        if (!$row) {
            return $out;
        }
        $now = time();
        $until = (int)$row['banned_until'];
        $out['failures'] = (int)$row['failures'];
        $out['bans'] = (int)$row['bans'];
        $out['until'] = $until;
        if ($until > $now) {
            $out['banned'] = true;
            $out['left'] = $until - $now;
        }
        return $out;
    }

    /** Учесть неудачную попытку; при достижении порога — бан. */
    public static function fail(string $ip, string $login = ''): array
    {
        if ($ip === '') {
            return self::check($ip);
        }
        $now = time();
        try {
            $pdo = self::db();
            $st = $pdo->prepare('SELECT * FROM forestbans WHERE ip = ?');
            $st->execute(array($ip));
            $row = $st->fetch();

            if (!$row) {
                $pdo->prepare('INSERT INTO forestbans (ip, failures, first_at, last_at, banned_until, bans, last_login)
                               VALUES (?,?,?,?,?,?,?)')
                    ->execute(array($ip, 1, $now, $now, 0, 0, mb_substr($login, 0, 150, 'UTF-8')));
                return self::check($ip);
            }

            $failures = (int)$row['failures'];
            $first = (int)$row['first_at'];
            $bans = (int)$row['bans'];
            /* окно истекло — начинаем счёт заново */
            if ($now - $first > self::WINDOW && (int)$row['banned_until'] <= $now) {
                $failures = 0;
                $first = $now;
            }
            $failures++;

            $until = (int)$row['banned_until'];
            if ($failures >= self::MAX_FAILURES && $until <= $now) {
                $idx = min($bans, count(self::DURATIONS) - 1);
                $until = $now + self::DURATIONS[$idx];
                $bans++;
            }

            $pdo->prepare('UPDATE forestbans SET failures = ?, first_at = ?, last_at = ?, banned_until = ?, bans = ?, last_login = ?
                           WHERE ip = ?')
                ->execute(array($failures, $first, $now, $until, $bans, mb_substr($login, 0, 150, 'UTF-8'), $ip));
        } catch (Throwable $e) {
        }
        return self::check($ip);
    }

    /** Успешный вход: снимаем счётчик (но не историю банов). */
    public static function succeed(string $ip): void
    {
        if ($ip === '') {
            return;
        }
        try {
            $st = self::db()->prepare('UPDATE forestbans SET failures = 0, first_at = 0, banned_until = 0 WHERE ip = ?');
            $st->execute(array($ip));
        } catch (Throwable $e) {
        }
    }

    /** Все, кого стоит показать в настройках: активные баны и накопленные попытки. */
    public static function active(): array
    {
        try {
            $st = self::db()->query('SELECT * FROM forestbans WHERE banned_until > 0 OR failures > 0 ORDER BY banned_until DESC, last_at DESC LIMIT 200');
            $rows = $st->fetchAll();
        } catch (Throwable $e) {
            return array();
        }
        $now = time();
        $out = array();
        foreach ((array)$rows as $r) {
            $until = (int)$r['banned_until'];
            $out[] = array(
                'ip' => (string)$r['ip'],
                'failures' => (int)$r['failures'],
                'bans' => (int)$r['bans'],
                'banned' => $until > $now,
                'until' => $until,
                'left' => max(0, $until - $now),
                'last_at' => (int)$r['last_at'],
                'last_login' => (string)$r['last_login'],
            );
        }
        return $out;
    }

    public static function unban(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }
        try {
            $st = self::db()->prepare('DELETE FROM forestbans WHERE ip = ?');
            $st->execute(array($ip));
            return $st->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function unbanAll(): int
    {
        try {
            return (int)self::db()->exec('DELETE FROM forestbans');
        } catch (Throwable $e) {
            return 0;
        }
    }

    /** «До 18:42» — человеческое время окончания бана. */
    public static function untilText(int $until): string
    {
        if ($until <= 0) {
            return '';
        }
        return date('H:i', $until) . (date('d.m', $until) !== date('d.m') ? ' (' . date('d.m', $until) . ')' : '');
    }
}
