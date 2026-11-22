<?php
/**
 * Forest OS — WebAuthn (Face ID / отпечаток / Windows Hello).
 *
 * Пароль остаётся основным способом входа; ключ WebAuthn — дополнительный.
 * Сервер хранит только публичный ключ: биометрия не покидает устройство.
 *
 * Политика по умолчанию: только суперпользователь (админ). Расширяется файлом
 * system/core/webauthn.foc:
 *   enabled=1
 *   all_users=0
 */
final class ForestWebAuthn
{
    public const CHALLENGE_TTL = 120;
    public const MAX_ATTEMPTS = 12;      // за окно
    public const ATTEMPT_WINDOW = 300;   // секунд

    /* ---------------- окружение и политика ---------------- */

    public static function doc(): string
    {
        return rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
    }

    public static function policy(): array
    {
        $f = self::doc() . '/system/core/webauthn.foc';
        $ini = is_file($f) ? (@parse_ini_file($f) ?: array()) : array();
        return array(
            'enabled' => (string)($ini['enabled'] ?? '1') !== '0',
            'all_users' => (string)($ini['all_users'] ?? '0') === '1',
        );
    }

    public static function isAdmin(string $login): bool
    {
        if ($login === '') {
            return false;
        }
        try {
            $st = self::db()->prepare('SELECT status FROM forestusers WHERE login = ?');
            $st->execute(array($login));
            return (string)$st->fetchColumn() === 'superuser';
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function allowed(string $login): bool
    {
        $p = self::policy();
        if (!$p['enabled']) {
            return false;
        }
        return $p['all_users'] || self::isAdmin($login);
    }

    /** HTTPS или localhost — иначе браузер не даст вызвать Face ID. */
    public static function available(): bool
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            || (string)($_SERVER['SERVER_PORT'] ?? '') === '443';
        return $https || in_array(self::rpId(), array('localhost', '127.0.0.1', '::1'), true);
    }

    public static function rpId(): string
    {
        $h = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        return (string)preg_replace('/:\d+$/', '', $h);
    }

    public static function origin(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            || (string)($_SERVER['SERVER_PORT'] ?? '') === '443';
        return ($https ? 'https' : 'http') . '://' . (string)($_SERVER['HTTP_HOST'] ?? self::rpId());
    }

    /* ---------------- хранилище ---------------- */

    private static function db(): PDO
    {
        if (!defined('DB_DSN')) {
            require_once self::doc() . '/system/core/library/bd_inc.php';
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
            $pdo->exec('CREATE TABLE IF NOT EXISTS forestwebauthn (
                id INT AUTO_INCREMENT PRIMARY KEY,
                login VARCHAR(150) NOT NULL,
                cred_id VARCHAR(255) NOT NULL,
                pubkey_pem TEXT NOT NULL,
                alg INT NOT NULL DEFAULT -7,
                sign_count BIGINT NOT NULL DEFAULT 0,
                uv TINYINT NOT NULL DEFAULT 1,
                label VARCHAR(120) NOT NULL DEFAULT \'\',
                transports VARCHAR(120) NOT NULL DEFAULT \'\',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_used_at TIMESTAMP NULL,
                UNIQUE KEY uniq_cred (cred_id),
                KEY idx_login (login)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        } catch (Throwable $e) {
            /* нет прав на CREATE — таблицу создаст bootstrap */
        }
    }

    public static function listKeys(string $login): array
    {
        try {
            $st = self::db()->prepare('SELECT id, cred_id, label, transports, sign_count, created_at, last_used_at
                                       FROM forestwebauthn WHERE login = ? ORDER BY id DESC');
            $st->execute(array($login));
            $rows = $st->fetchAll();
        } catch (Throwable $e) {
            return array();
        }
        return is_array($rows) ? $rows : array();
    }

    public static function hasKeys(string $login): bool
    {
        try {
            $st = self::db()->prepare('SELECT COUNT(*) FROM forestwebauthn WHERE login = ?');
            $st->execute(array($login));
            return (int)$st->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function deleteKey(string $login, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $st = self::db()->prepare('DELETE FROM forestwebauthn WHERE id = ? AND login = ?');
        $st->execute(array($id, $login));
        return $st->rowCount() > 0;
    }

    /* ---------------- challenge ---------------- */

    private static function putChallenge(string $kind, string $login): string
    {
        $ch = self::b64url(random_bytes(32));
        $_SESSION['fo_wa'] = array(
            'ch' => $ch, 'kind' => $kind, 'login' => $login, 'exp' => time() + self::CHALLENGE_TTL,
        );
        return $ch;
    }

    private static function takeChallenge(string $kind, string $login): string
    {
        $s = $_SESSION['fo_wa'] ?? null;
        unset($_SESSION['fo_wa']);
        if (!is_array($s) || (string)($s['kind'] ?? '') !== $kind || (string)($s['login'] ?? '') !== $login) {
            return '';
        }
        if ((int)($s['exp'] ?? 0) < time()) {
            return '';
        }
        return (string)($s['ch'] ?? '');
    }

    public static function throttleOk(): bool
    {
        $now = time();
        $a = $_SESSION['fo_wa_att'] ?? array();
        $a = array_values(array_filter((array)$a, function ($t) use ($now) {
            return is_int($t) && $t > $now - self::ATTEMPT_WINDOW;
        }));
        $a[] = $now;
        $_SESSION['fo_wa_att'] = $a;
        return count($a) <= self::MAX_ATTEMPTS;
    }

    /* ---------------- регистрация ключа ---------------- */

    public static function beginRegister(string $login, string $label): array
    {
        if (!self::allowed($login)) {
            return array('ok' => false, 'error' => 'not_allowed');
        }
        if (!self::available()) {
            return array('ok' => false, 'error' => 'insecure_context');
        }
        $ch = self::putChallenge('reg', $login);
        $exclude = array();
        foreach (self::listKeys($login) as $k) {
            $exclude[] = array('type' => 'public-key', 'id' => (string)$k['cred_id'],
                'transports' => array_values(array_filter(explode(',', (string)$k['transports']))));
        }
        $label = trim($label) !== '' ? mb_substr(trim($label), 0, 60, 'UTF-8') : 'Устройство';
        return array('ok' => true, 'publicKey' => array(
            'challenge' => $ch,
            'rp' => array('name' => 'Forest OS', 'id' => self::rpId()),
            'user' => array(
                'id' => self::b64url(substr(hash('sha256', 'forestos|' . $login, true), 0, 16)),
                'name' => $login,
                'displayName' => $login,
            ),
            'pubKeyCredParams' => array(array('type' => 'public-key', 'alg' => -7)),
            'timeout' => 60000,
            'authenticatorSelection' => array(
                'authenticatorAttachment' => 'platform',
                'userVerification' => 'required',
                'residentKey' => 'preferred',
            ),
            'excludeCredentials' => $exclude,
            'attestation' => 'none',
        ), 'label' => $label);
    }

    public static function finishRegister(string $login, array $in): array
    {
        if (!self::allowed($login)) {
            return array('ok' => false, 'error' => 'not_allowed');
        }
        $ch = self::takeChallenge('reg', $login);
        if ($ch === '') {
            return array('ok' => false, 'error' => 'challenge_expired');
        }
        $clientData = self::b64urlDecode((string)($in['clientDataJSON'] ?? ''));
        $attObjRaw = self::b64urlDecode((string)($in['attestationObject'] ?? ''));
        if ($clientData === '' || $attObjRaw === '') {
            return array('ok' => false, 'error' => 'bad_input');
        }
        $cd = json_decode($clientData, true);
        $cdErr = self::checkClientData($cd, 'webauthn.create', $ch);
        if ($cdErr !== '') {
            return array('ok' => false, 'error' => $cdErr);
        }

        $o = 0;
        try {
            $att = self::cborDecode($attObjRaw, $o);
        } catch (Throwable $e) {
            return array('ok' => false, 'error' => 'bad_attestation');
        }
        if (!is_array($att) || !isset($att['authData']) || !is_string($att['authData'])) {
            return array('ok' => false, 'error' => 'bad_attestation');
        }
        $authData = $att['authData'];
        $parsed = self::parseAuthData($authData);
        if (!is_array($parsed)) {
            return array('ok' => false, 'error' => 'bad_authdata');
        }
        if (!hash_equals(hash('sha256', self::rpId(), true), $parsed['rpIdHash'])) {
            return array('ok' => false, 'error' => 'bad_rpid');
        }
        if (($parsed['flags'] & 0x05) !== 0x05) { // UP + UV
            return array('ok' => false, 'error' => 'uv_required');
        }
        $pem = self::coseToPem($parsed['cose']);
        if ($pem === null) {
            return array('ok' => false, 'error' => 'unsupported_key');
        }
        $credId = self::b64url($parsed['credId']);
        $label = trim((string)($in['label'] ?? '')) !== '' ? mb_substr(trim((string)$in['label']), 0, 60, 'UTF-8') : 'Устройство';
        $transports = array();
        foreach ((array)($in['transports'] ?? array()) as $t) {
            if (is_string($t) && preg_match('/^[a-z]{3,20}$/', $t)) {
                $transports[] = $t;
            }
        }
        try {
            $pdo = self::db();
            $st = $pdo->prepare('INSERT INTO forestwebauthn
                (login, cred_id, pubkey_pem, alg, sign_count, uv, label, transports)
                VALUES (?,?,?,?,?,?,?,?)');
            $st->execute(array($login, $credId, $pem, (int)($parsed['alg'] ?? -7),
                (int)$parsed['signCount'], 1, $label, implode(',', $transports)));
            $newId = (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            return array('ok' => false, 'error' => 'already_registered');
        }
        return array('ok' => true, 'id' => $newId, 'label' => $label);
    }

    /* ---------------- вход ---------------- */

    public static function beginAuth(string $login): array
    {
        if (!self::throttleOk()) {
            return array('ok' => false, 'error' => 'too_many_attempts');
        }
        if (!self::allowed($login) || !self::hasKeys($login)) {
            return array('ok' => false, 'error' => 'no_keys');
        }
        if (!self::available()) {
            return array('ok' => false, 'error' => 'insecure_context');
        }
        $ch = self::putChallenge('auth', $login);
        $allow = array();
        foreach (self::listKeys($login) as $k) {
            $allow[] = array('type' => 'public-key', 'id' => (string)$k['cred_id'],
                'transports' => array_values(array_filter(explode(',', (string)$k['transports']))));
        }
        return array('ok' => true, 'publicKey' => array(
            'challenge' => $ch,
            'rpId' => self::rpId(),
            'timeout' => 60000,
            'userVerification' => 'required',
            'allowCredentials' => $allow,
        ));
    }

    public static function finishAuth(string $login, array $in): array
    {
        if (!self::throttleOk()) {
            return array('ok' => false, 'error' => 'too_many_attempts');
        }
        $ch = self::takeChallenge('auth', $login);
        if ($ch === '') {
            return array('ok' => false, 'error' => 'challenge_expired');
        }
        $credId = (string)($in['id'] ?? '');
        $clientData = self::b64urlDecode((string)($in['clientDataJSON'] ?? ''));
        $authData = self::b64urlDecode((string)($in['authenticatorData'] ?? ''));
        $signature = self::b64urlDecode((string)($in['signature'] ?? ''));
        if ($credId === '' || $clientData === '' || $authData === '' || $signature === '') {
            return array('ok' => false, 'error' => 'bad_input');
        }
        $cd = json_decode($clientData, true);
        $cdErr = self::checkClientData($cd, 'webauthn.get', $ch);
        if ($cdErr !== '') {
            return array('ok' => false, 'error' => $cdErr);
        }
        $st = self::db()->prepare('SELECT * FROM forestwebauthn WHERE cred_id = ? AND login = ?');
        $st->execute(array($credId, $login));
        $row = $st->fetch();
        if (!$row) {
            return array('ok' => false, 'error' => 'unknown_key');
        }
        $parsed = self::parseAuthData($authData);
        if (!is_array($parsed) || isset($parsed['cose'])) {
            return array('ok' => false, 'error' => 'bad_authdata');
        }
        if (!hash_equals(hash('sha256', self::rpId(), true), $parsed['rpIdHash'])) {
            return array('ok' => false, 'error' => 'bad_rpid');
        }
        if (($parsed['flags'] & 0x05) !== 0x05) {
            return array('ok' => false, 'error' => 'uv_required');
        }
        $signed = $authData . hash('sha256', $clientData, true);
        $ok = openssl_verify($signed, $signature, (string)$row['pubkey_pem'], OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return array('ok' => false, 'error' => 'bad_signature');
        }
        $stored = (int)$row['sign_count'];
        $seen = (int)$parsed['signCount'];
        if ($stored > 0 && $seen > 0 && $seen <= $stored) {
            return array('ok' => false, 'error' => 'replay');
        }
        $up = self::db()->prepare('UPDATE forestwebauthn SET sign_count = ?, last_used_at = NOW() WHERE id = ?');
        $up->execute(array(max($stored, $seen), (int)$row['id']));
        return array('ok' => true, 'key' => (string)$row['label']);
    }

    /* ---------------- проверка данных клиента ---------------- */

    private static function checkClientData(?array $cd, string $type, string $challenge): string
    {
        if (!is_array($cd)) {
            return 'bad_clientdata';
        }
        if ((string)($cd['type'] ?? '') !== $type) {
            return 'bad_type';
        }
        if (!hash_equals($challenge, (string)($cd['challenge'] ?? ''))) {
            return 'bad_challenge';
        }
        if ((string)($cd['origin'] ?? '') !== self::origin()) {
            return 'bad_origin';
        }
        return '';
    }

    private static function parseAuthData(string $ad): ?array
    {
        if (strlen($ad) < 37) {
            return null;
        }
        $out = array(
            'rpIdHash' => substr($ad, 0, 32),
            'flags' => ord($ad[32]),
            'signCount' => (int)unpack('N', substr($ad, 33, 4))[1],
        );
        if (($out['flags'] & 0x40) === 0) {
            return $out;
        }
        $off = 37;
        if (strlen($ad) < $off + 18) {
            return null;
        }
        $off += 16; // AAGUID
        $len = (int)unpack('n', substr($ad, $off, 2))[1];
        $off += 2;
        if ($len < 16 || strlen($ad) < $off + $len) {
            return null;
        }
        $out['credId'] = substr($ad, $off, $len);
        $off += $len;
        $o = $off;
        try {
            $cose = self::cborDecode($ad, $o);
        } catch (Throwable $e) {
            return null;
        }
        if (!is_array($cose)) {
            return null;
        }
        $out['cose'] = $cose;
        $out['alg'] = (int)($cose[3] ?? -7);
        return $out;
    }

    /* ---------------- COSE → PEM (ES256) ---------------- */

    private static function coseToPem(array $cose): ?string
    {
        if ((int)($cose[1] ?? 0) !== 2 || (int)($cose[3] ?? 0) !== -7 || (int)($cose[-1] ?? 0) !== 1) {
            return null;
        }
        $x = (string)($cose[-2] ?? '');
        $y = (string)($cose[-3] ?? '');
        if (strlen($x) !== 32 || strlen($y) !== 32) {
            return null;
        }
        $algId = self::derSeq(self::derOid(hex2bin('2a8648ce3d0201')), self::derOid(hex2bin('2a8648ce3d030107')));
        $spki = self::derSeq($algId, self::derBitString("\x04" . $x . $y));
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function derLen(int $n): string
    {
        if ($n < 0x80) {
            return chr($n);
        }
        $b = ltrim(pack('N', $n), "\x00");
        return chr(0x80 | strlen($b)) . $b;
    }

    private static function derSeq(string $a, string $b = ''): string
    {
        $body = $a . $b;
        return "\x30" . self::derLen(strlen($body)) . $body;
    }

    private static function derOid(string $body): string
    {
        return "\x06" . self::derLen(strlen($body)) . $body;
    }

    private static function derBitString(string $bits): string
    {
        return "\x03" . self::derLen(strlen($bits) + 1) . "\x00" . $bits;
    }

    /* ---------------- CBOR (минимальный, definite-length) ---------------- */

    private static function cborDecode(string $s, int &$o)
    {
        if ($o >= strlen($s)) {
            throw new RuntimeException('cbor_eof');
        }
        $ib = ord($s[$o++]);
        $major = $ib >> 5;
        $ai = $ib & 0x1f;
        if ($ai < 24) {
            $val = $ai;
        } elseif ($ai === 24) {
            $val = ord($s[$o]);
            $o += 1;
        } elseif ($ai === 25) {
            $val = (int)unpack('n', substr($s, $o, 2))[1];
            $o += 2;
        } elseif ($ai === 26) {
            $val = (int)unpack('N', substr($s, $o, 4))[1];
            $o += 4;
        } elseif ($ai === 27) {
            $hi = (int)unpack('N', substr($s, $o, 4))[1];
            $lo = (int)unpack('N', substr($s, $o + 4, 4))[1];
            $val = $hi * 4294967296 + $lo;
            $o += 8;
        } else {
            throw new RuntimeException('cbor_indefinite');
        }
        switch ($major) {
            case 0:
                return $val;
            case 1:
                return -1 - $val;
            case 2:
                $b = substr($s, $o, $val);
                $o += $val;
                return $b;
            case 3:
                $b = substr($s, $o, $val);
                $o += $val;
                return $b;
            case 4:
                $arr = array();
                for ($i = 0; $i < $val; $i++) {
                    $arr[] = self::cborDecode($s, $o);
                }
                return $arr;
            case 5:
                $m = array();
                for ($i = 0; $i < $val; $i++) {
                    $k = self::cborDecode($s, $o);
                    $m[is_int($k) || is_string($k) ? $k : (string)$k] = self::cborDecode($s, $o);
                }
                return $m;
            case 6:
                return self::cborDecode($s, $o); // tag — значение без обёртки
            default:
                if ($ai === 20) { return false; }
                if ($ai === 21) { return true; }
                if ($ai === 22) { return null; }
                return null;
        }
    }

    /* ---------------- base64url ---------------- */

    public static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $txt): string
    {
        $txt = trim($txt);
        if ($txt === '') {
            return '';
        }
        $pad = strlen($txt) % 4;
        if ($pad) {
            $txt .= str_repeat('=', 4 - $pad);
        }
        $bin = base64_decode(strtr($txt, '-_', '+/'), true);
        return $bin === false ? '' : $bin;
    }
}
