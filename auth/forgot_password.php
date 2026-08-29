<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$errors = [];
$success = '';

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
| Handle Forgot Password
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

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | Validate Email
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        $errors[] = 'Email address is required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = 'Please enter a valid email address.';
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
                email
             FROM users
             WHERE email = :email
             AND is_active = 1
             LIMIT 1'
        );

        $stmt->execute([
            'email' => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        /*
        |--------------------------------------------------------------------------
        | Generate Reset Token
        |--------------------------------------------------------------------------
        */

        if ($user) {

            $reset_token = bin2hex(
                random_bytes(32)
            );

            $reset_token_hash = hash(
                'sha256',
                $reset_token
            );

            $expires_at = date(
                'Y-m-d H:i:s',
                time() + 3600
            );


            /*
            |--------------------------------------------------------------------------
            | Store Hashed Token
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET
                    reset_token_hash = :token_hash,
                    reset_token_expires_at = :expires_at
                 WHERE id = :id'
            );

            $stmt->execute([
                'token_hash' => $reset_token_hash,
                'expires_at' => $expires_at,
                'id' => $user['id']
            ]);


            /*
            |--------------------------------------------------------------------------
            | Development Reset Link
            |--------------------------------------------------------------------------
            */

            $reset_link =
                'reset_password.php?token=' .
                urlencode($reset_token);

            $success =
                'If an account with that email exists, '
                . 'a password reset link has been generated.'
                . '<br><br>'
                . '<strong>Development Reset Link:</strong><br>'
                . '<a href="' . e($reset_link) . '">'
                . e($reset_link)
                . '</a>';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Do Not Reveal Whether Email Exists
            |--------------------------------------------------------------------------
            */

            $success =
                'If an account with that email exists, '
                . 'a password reset link has been generated.';
        }
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
    Forgot Password | Course Registration System
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

    .forgot-container {

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

        line-height: 1.5;
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

        .forgot-container {

            padding: 25px;
        }

        .logo h1 {

            font-size: 23px;
        }

    }
    .reset-link {
    display: block;
    margin-top: 8px;
    padding: 10px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 6px;
    color: #1976d2;
    text-decoration: none;
    word-break: break-all;
    overflow-wrap: anywhere;
    line-height: 1.5;
}

.reset-link:hover {
    color: #168a45;
    text-decoration: underline;
}

</style>
```

</head>

<body>

<div class="forgot-container">

```
<div class="logo">

    <h1>
        Forgot Password
    </h1>

    <p>
        Course Registration System
    </p>

</div>


<?php if (!empty($errors)): ?>

    <?php foreach ($errors as $error): ?>

        <div class="alert alert-error">

            <?= e($error) ?>

        </div>

    <?php endforeach; ?>

<?php endif; ?>


<?php if ($success !== ''): ?>

    <div class="alert alert-success">

        <?= $success ?>

    </div>

<?php endif; ?>


<?php if ($success === ''): ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >


        <div class="form-group">

            <label for="email">

                Email Address

            </label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                value="<?= e($_POST['email'] ?? '') ?>"
                autocomplete="email"
                required
            >

        </div>


        <button
            type="submit"
            class="reset-button"
        >

            Send Reset Link

        </button>

    </form>

<?php endif; ?>


<div class="links">

    <a href="login.php">

        ← Back to Login

    </a>

</div>
```

</div>

</body>

</html>
