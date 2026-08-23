<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';


/*
|--------------------------------------------------------------------------
| Check if User is Logged In
|--------------------------------------------------------------------------
*/

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}


/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /course_registration_system/auth/login.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Specific Role
|--------------------------------------------------------------------------
*/

function require_role(string $role): void
{
    require_login();

    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== $role
    ) {
        http_response_code(403);
        exit('Access denied.');
    }
}


/*
|--------------------------------------------------------------------------
| Current User ID
|--------------------------------------------------------------------------
*/

function current_user_id(): ?int
{
    return isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : null;
}