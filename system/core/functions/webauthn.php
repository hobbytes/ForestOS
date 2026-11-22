<?php
/**
 * Forest OS — JSON-эндпоинты WebAuthn (Face ID / отпечаток).
 *
 * action=status          — доступна ли фича на этом origin и для этого логина
 * action=begin-register  — начать привязку ключа (нужна сессия)
 * action=finish-register — сохранить ключ
 * action=list            — ключи текущего пользователя
 * action=delete          — отозвать ключ
 * action=begin-login     — начать вход по ключу (без сессии)
 * action=finish-login    — проверить подпись и открыть сессию
 */
ini_set('display_errors', 'Off');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION)) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/webauthn.php';

function wa_out(array $d, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function wa_in(): array
{
    $raw = (string)file_get_contents('php://input');
    $j = json_decode($raw, true);
    if (is_array($j)) {
        return $j;
    }
    return is_array($_POST) ? $_POST : array();
}

$action = (string)($_GET['action'] ?? ($_POST['action'] ?? ''));
$me = (string)($_SESSION['loginuser'] ?? '');
$authed = !empty($_SESSION['is_authuser']) && $me !== '';

switch ($action) {
    case 'status': {
        $login = isset($_GET['login']) ? strtolower(trim((string)$_GET['login'])) : $me;
        $allowed = $login !== '' && ForestWebAuthn::allowed($login);
        wa_out(array(
            'ok' => true,
            'available' => ForestWebAuthn::available(),
            'rp_id' => ForestWebAuthn::rpId(),
            'origin' => ForestWebAuthn::origin(),
            'policy' => ForestWebAuthn::policy(),
            'login' => $login,
            'allowed' => $allowed,
            'has_keys' => $allowed ? ForestWebAuthn::hasKeys($login) : false,
            'authed' => $authed,
            'you' => $me,
        ));
    }

    case 'begin-register': {
        if (!$authed) {
            wa_out(array('ok' => false, 'error' => 'auth'), 401);
        }
        $in = wa_in();
        $res = ForestWebAuthn::beginRegister($me, (string)($in['label'] ?? ''));
        wa_out($res, $res['ok'] ? 200 : 400);
    }

    case 'finish-register': {
        if (!$authed) {
            wa_out(array('ok' => false, 'error' => 'auth'), 401);
        }
        $in = wa_in();
        $cred = is_array($in['credential'] ?? null) ? $in['credential'] : $in;
        if (isset($in['label']) && is_string($in['label']) && $in['label'] !== '') {
            $cred['label'] = $in['label'];
        }
        try {
            $res = ForestWebAuthn::finishRegister($me, $cred);
        } catch (Throwable $e) {
            $res = array('ok' => false, 'error' => 'server_error');
        }
        wa_out($res, $res['ok'] ? 200 : 400);
    }

    case 'list': {
        if (!$authed) {
            wa_out(array('ok' => false, 'error' => 'auth'), 401);
        }
        $keys = array();
        foreach (ForestWebAuthn::listKeys($me) as $k) {
            $keys[] = array(
                'id' => (int)$k['id'],
                'label' => (string)$k['label'],
                'transports' => (string)$k['transports'],
                'created_at' => (string)$k['created_at'],
                'last_used_at' => (string)($k['last_used_at'] ?? ''),
            );
        }
        wa_out(array('ok' => true, 'keys' => $keys, 'available' => ForestWebAuthn::available(),
            'allowed' => ForestWebAuthn::allowed($me), 'policy' => ForestWebAuthn::policy()));
    }

    case 'delete': {
        if (!$authed) {
            wa_out(array('ok' => false, 'error' => 'auth'), 401);
        }
        $in = wa_in();
        $id = (int)($in['id'] ?? 0);
        $ok = ForestWebAuthn::deleteKey($me, $id);
        wa_out(array('ok' => $ok, 'error' => $ok ? '' : 'not_found'));
    }

    case 'begin-login': {
        $in = wa_in();
        $login = strtolower(trim((string)($in['login'] ?? '')));
        if ($login === '' || !preg_match('/^[a-z0-9_\-\.]{3,64}$/', $login)) {
            wa_out(array('ok' => false, 'error' => 'bad_login'), 400);
        }
        $res = ForestWebAuthn::beginAuth($login);
        wa_out($res, $res['ok'] ? 200 : 400);
    }

    case 'finish-login': {
        $in = wa_in();
        $login = strtolower(trim((string)($in['login'] ?? '')));
        $cred = is_array($in['credential'] ?? null) ? $in['credential'] : $in;
        if ($login === '' || !preg_match('/^[a-z0-9_\-\.]{3,64}$/', $login)) {
            wa_out(array('ok' => false, 'error' => 'bad_login'), 400);
        }
        try {
            $res = ForestWebAuthn::finishAuth($login, $cred);
        } catch (Throwable $e) {
            $res = array('ok' => false, 'error' => 'server_error');
        }
        if (empty($res['ok'])) {
            wa_out($res, 401);
        }
        /* сессия — как после пароля (см. AuthClassUser::auth) */
        $_SESSION['is_authuser'] = true;
        $_SESSION['loginuser'] = $login;
        /* новая сессия после входа — защита от фиксации */
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $xff = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        $_SESSION['CookieIsMine'] = md5($login . (string)($_SERVER['HTTP_USER_AGENT'] ?? '')
            . (string)($_SERVER['REMOTE_ADDR'] ?? '') . $xff);
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/etc.php';
            $infob = new info();
            $infob->writestat('Success Login (Face ID) -> ' . $login, 'system/core/journal.mcj');
        } catch (Throwable $e) {
        }
        session_write_close();
        wa_out(array('ok' => true, 'login' => $login, 'redirect' => 'os.php'));
    }

    default:
        wa_out(array('ok' => false, 'error' => 'unknown_action'), 404);
}
