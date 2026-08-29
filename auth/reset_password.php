<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$errors = [];
$success = '';

/*
|--------------------------------------------------------------------------
| Get Reset Token
|--------------------------------------------------------------------------
*/

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    exit('Invalid or missing password reset token.');
}


/*
|--------------------------------------------------------------------------
| Hash Token
|--------------------------------------------------------------------------
*/

$token_hash = hash(
    'sha256',
    $token
);


/*
|--------------------------------------------------------------------------
| Find Reset Request
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        email,
        reset_token_expires_at
     FROM users
     WHERE reset_token_hash = :token_hash
     AND is_active = 1
     LIMIT 1'
);

$stmt->execute([
    'token_hash' => $token_hash
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Validate Reset Token Expiry
|--------------------------------------------------------------------------
*/

if (!$user) {
    exit('This password reset link is invalid.');
}

$expires_at = strtotime(
    $user['reset_token_expires_at']
);

if ($expires_at === false || $expires_at < time()) {
    exit('This password reset link has expired.');
}

/*
|--------------------------------------------------------------------------
| Handle Password Reset
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Validate CSRF Token
    |--------------------------------------------------------------------------
    */

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token.';
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Password Validation
    |--------------------------------------------------------------------------
    */

    if ($password === '') {

        $errors[] = 'New password is required.';

    } elseif (strlen($password) < 8) {

        $errors[] =
            'Password must be at least 8 characters long.';
    }

    if ($confirm_password === '') {

        $errors[] = 'Please confirm your new password.';

    } elseif ($password !== $confirm_password) {

        $errors[] = 'Passwords do not match.';
    }


    /*
    |--------------------------------------------------------------------------
    | Update Password
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare(
            'UPDATE users
             SET
                password_hash = :password_hash,
                reset_token_hash = NULL,
                reset_token_expires_at = NULL
             WHERE id = :id'
        );

        $stmt->execute([
            'password_hash' => $password_hash,
            'id' => $user['id']
        ]);

        $success =
            'Your password has been reset successfully.';
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

```
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Reset Password | Course Registration System
</title>

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

    .reset-container {

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

    .reset-button {

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

    .reset-button:hover {

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

    @media (max-width: 500px) {

        .reset-container {

            padding: 25px;
        }

        .logo h1 {

            font-size: 23px;
        }

    }

</style>
```

</head>

<body>

<div class="reset-container">

```
<div class="logo">

    <h1>
        Reset Password
    </h1>

    <p>
        Create a new secure password
    </p>

</div>


<?php foreach ($errors as $error): ?>

    <div class="alert alert-error">

        <?= e($error) ?>

    </div>

<?php endforeach; ?>


<?php if ($success !== ''): ?>

    <div class="alert alert-success">

        <?= e($success) ?>

    </div>

    <div class="links">

        <a href="login.php">
            ← Back to Login
        </a>

    </div>

<?php else: ?>


    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >


        <div class="form-group">

            <label for="password">
                New Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

        </div>


        <div class="form-group">

            <label for="confirm_password">
                Confirm New Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

        </div>


        <button
            type="submit"
            class="reset-button"
        >
            Reset Password
        </button>

    </form>


    <div class="links">

        <a href="login.php">
            ← Back to Login
        </a>

    </div>

<?php endif; ?>
```

</div>

</body>

</html>
