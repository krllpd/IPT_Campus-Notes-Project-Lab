<?php
declare(strict_types=1);

/** Start the session with hardened cookie and ID settings. */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');   // reject uninitialised (attacker-chosen) IDs
    ini_set('session.use_only_cookies', '1');  // never accept IDs from the URL
    ini_set('session.use_trans_sid', '0');

    session_name('campus_notes_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,   // true automatically once served over HTTPS
        'httponly' => true,     // not readable from JavaScript
        'samesite' => 'Lax',    // not sent on cross-site POST/fetch
    ]);
    session_start();

    // Idle timeout.
    $now = time();
    $idle = app_config()['session_idle_seconds'];
    if (isset($_SESSION['last_seen']) && ($now - (int)$_SESSION['last_seen']) > $idle) {
        logout_user();
        session_start();
    }
    $_SESSION['last_seen'] = $now;
}

/** Called only after the password has been verified. */
function login_user(array $user): void
{
    session_regenerate_id(true);           // prevent session fixation
    $_SESSION = [
        'user_id'   => (int)$user['id'],
        'role'      => (string)$user['role'],  // taken from the database, never from the request
        'last_seen' => time(),
    ];
    csrf_rotate();
}

/** Fully end the session: clear data, expire the cookie, destroy server-side. */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
    }
    session_destroy();
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_role(): ?string
{
    return isset($_SESSION['role']) ? (string)$_SESSION['role'] : null;
}

/** One-shot status message stored server-side (replaces the reflected ?message= parameter). */
function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}
