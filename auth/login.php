<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$errors = [];

/*
|--------------------------------------------------------------------------
| Redirect Already Logged-In Users
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'], $_SESSION['role'])) {

    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit;
    }

    if ($_SESSION['role'] === 'student') {
        header('Location: ../student/dashboard.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Handle Login
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validate CSRF Token
    |--------------------------------------------------------------------------
    */

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token.';
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Input
    |--------------------------------------------------------------------------
    */

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    /*
    |--------------------------------------------------------------------------
    | Find User
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            'SELECT
                id,
                username,
                password_hash,
                role,
                is_active
             FROM users
             WHERE username = :username
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $username
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        /*
        |--------------------------------------------------------------------------
        | Verify Credentials
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            $errors[] = 'Invalid username or password.';

        } elseif (!password_verify($password, $user['password_hash'])) {

            $errors[] = 'Invalid username or password.';

        } elseif ((int)$user['is_active'] !== 1) {

            $errors[] = 'Your account has been disabled.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Prevent Session Fixation
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            /*
            |--------------------------------------------------------------------------
            | Create Session
            |--------------------------------------------------------------------------
            */

            $_SESSION['user_id'] = (int)$user['id'];

            $_SESSION['username'] = $user['username'];

            $_SESSION['role'] = $user['role'];

            $_SESSION['last_activity'] = time();

            /*
            |--------------------------------------------------------------------------
            | Role-Based Redirect
            |--------------------------------------------------------------------------
            */

            if ($user['role'] === 'admin') {

                header('Location: ../admin/dashboard.php');
                exit;

            }

       if ($user['role'] === 'student') {

    header('Location: ../student/dashboard.php');
    exit;
}

            /*
            |--------------------------------------------------------------------------
            | Unknown Role
            |--------------------------------------------------------------------------
            */

            session_unset();
            session_destroy();

            $errors[] = 'Invalid account role. Please contact the administrator.';
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | Course Registration System</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #111 0%,
                    #111 50%,
                    #f5f5f5 50%,
                    #f5f5f5 100%
                );

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 20px;
        }

        .login-container {

            width: 100%;

            max-width: 430px;

            background: #fff;

            border-radius: 12px;

            padding: 35px;

            box-shadow:
                0 10px 35px
                rgba(0, 0, 0, 0.25);

            border-top: 6px solid #d4af37;
        }

        .logo {

            text-align: center;

            margin-bottom: 25px;
        }

        .logo h1 {

            margin: 0;

            color: #111;

            font-size: 28px;
        }

        .logo p {

            color: #666;

            margin-top: 8px;

            font-size: 14px;
        }

        .alert {

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 15px;

            font-size: 14px;
        }

        .alert-error {

            background: #ffeaea;

            color: #b71c1c;

            border-left:
                4px solid #c62828;
        }

        .alert-success {

            background: #eaf8ef;

            color: #146c36;

            border-left:
                4px solid #168a45;
        }

        .form-group {

            margin-bottom: 18px;
        }

        label {

            display: block;

            margin-bottom: 7px;

            font-weight: bold;

            color: #222;
        }

        input {

            width: 100%;

            padding: 12px;

            border: 1px solid #ccc;

            border-radius: 6px;

            font-size: 15px;

            outline: none;

            transition: 0.3s;
        }

        input:focus {

            border-color: #d4af37;

            box-shadow:
                0 0 0 3px
                rgba(212, 175, 55, 0.15);
        }

        .login-button {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 6px;

            background: #1976d2;

            color: #fff;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }

        .login-button:hover {

            background: #168a45;
        }

        .links {

            text-align: center;

            margin-top: 20px;
        }

        .links a {

            color: #1976d2;

            text-decoration: none;

            font-size: 14px;
        }

        .links a:hover {

            color: #168a45;

            text-decoration: underline;
        }

        .register-link {

            margin-top: 12px;
        }

        @media (max-width: 500px) {

            .login-container {
                padding: 25px;
            }

            .logo h1 {
                font-size: 23px;
            }

        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="logo">

        <h1>
            Course Registration System
        </h1>

        <p>
            Secure Student & Administrator Login
        </p>

    </div>


    <?php if (isset($_GET['registered'])): ?>

        <div class="alert alert-success">

            Account created successfully.

            You can now log in.

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['timeout'])): ?>

        <div class="alert alert-error">

            Your session expired.

            Please log in again.

        </div>

    <?php endif; ?>


    <?php if (!empty($errors)): ?>

        <?php foreach ($errors as $error): ?>

            <div class="alert alert-error">

                <?= e($error) ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>


    <form method="POST" autocomplete="off">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >


        <div class="form-group">

            <label for="username">
                Username
            </label>

            <input
                type="text"
                id="username"
                name="username"
                autocomplete="username"
                value="<?= e($_POST['username'] ?? '') ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

        </div>


        <button
            type="submit"
            class="login-button"
        >
            Login
        </button>

    </form>


    <div class="links">

    <a href="forgot_password.php">
        Forgot Password?
    </a>

    <div class="register-link">

        Don't have an account?

        <a href="register.php">
            Create Account
        </a>

    </div>

</div>

</div>

</body>

</html>