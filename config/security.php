<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Secure Session Configuration
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false, // Change to true when using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| Session Timeout
|--------------------------------------------------------------------------
*/

$session_timeout = 1800; // 30 minutes

if (isset($_SESSION['last_activity'])) {

    if (time() - $_SESSION['last_activity'] > $session_timeout) {

        session_unset();
        session_destroy();

        header('Location: /course_registration_system/auth/login.php?timeout=1');
        exit;
    }
}

$_SESSION['last_activity'] = time();


/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| Generate CSRF Token
|--------------------------------------------------------------------------
*/

function csrf_token(): string
{
    return $_SESSION['csrf_token'];
}


/*
|--------------------------------------------------------------------------
| Verify CSRF Token
|--------------------------------------------------------------------------
*/

function verify_csrf_token(?string $token): bool
{
    if (!$token) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}


/*
|--------------------------------------------------------------------------
| Escape HTML Output
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}