<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$errors = [];


// ---------------------------------------------------------
// Load Available Programs
// ---------------------------------------------------------

$stmt = $pdo->query(
    'SELECT id, name, code, duration_years
     FROM programs
     ORDER BY name ASC'
);

$programs = $stmt->fetchAll();


// ---------------------------------------------------------
// Handle Registration
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $registration_number = trim($_POST['registration_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $program_id = (int)($_POST['program_id'] ?? 0);
    $year_of_study = (int)($_POST['year_of_study'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    // -----------------------------------------------------
    // CSRF Validation
    // -----------------------------------------------------

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $errors[] = 'Invalid security token.';
    }


    // -----------------------------------------------------
    // Validate Full Name
    // -----------------------------------------------------

    if ($full_name === '') {

        $errors[] = 'Full name is required.';
    }


    // -----------------------------------------------------
    // Validate Registration Number
    // -----------------------------------------------------

    if ($registration_number === '') {

        $errors[] = 'Student registration number is required.';
    }


    // -----------------------------------------------------
    // Validate Username
    // -----------------------------------------------------

    if ($username === '') {

        $errors[] = 'Username is required.';
    }


    // -----------------------------------------------------
    // Validate Email
    // -----------------------------------------------------

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = 'Enter a valid email address.';
    }


    // -----------------------------------------------------
    // Validate Phone
    // -----------------------------------------------------

    if ($phone === '') {

        $errors[] = 'Phone number is required.';
    }


    // -----------------------------------------------------
    // Validate Program
    // -----------------------------------------------------

    if ($program_id <= 0) {

        $errors[] = 'Please select your program.';
    }


    // -----------------------------------------------------
    // Validate Year of Study
    // -----------------------------------------------------

    if ($year_of_study < 1 || $year_of_study > 8) {

        $errors[] = 'Please select a valid year of study.';
    }


    // -----------------------------------------------------
    // Validate Password
    // -----------------------------------------------------

    if (strlen($password) < 8) {

        $errors[] = 'Password must contain at least 8 characters.';
    }


    if ($password !== $confirm_password) {

        $errors[] = 'Passwords do not match.';
    }


    // -----------------------------------------------------
    // Split Full Name
    // -----------------------------------------------------

    $name_parts = preg_split(
        '/\s+/',
        $full_name,
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    $first_name = $name_parts[0] ?? '';

    $last_name = '';

    if (count($name_parts) > 1) {

        $last_name = implode(
            ' ',
            array_slice($name_parts, 1)
        );
    }


    if ($first_name === '') {

        $errors[] = 'Please enter your full name.';
    }


    if ($last_name === '') {

        $errors[] = 'Please enter both your first name and last name.';
    }


    // -----------------------------------------------------
    // Check Existing Account
    // -----------------------------------------------------

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            'SELECT id
             FROM users
             WHERE username = :username
                OR email = :email
                OR registration_number = :registration_number
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'registration_number' => $registration_number
        ]);

        if ($stmt->fetch()) {

            $errors[] =
                'Username, email, or registration number already exists.';
        }
    }


    // -----------------------------------------------------
    // Check Program Exists
    // -----------------------------------------------------

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            'SELECT id, duration_years
             FROM programs
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $program_id
        ]);

        $program = $stmt->fetch();

        if (!$program) {

            $errors[] = 'Selected program does not exist.';

        } elseif (
            $year_of_study > (int)$program['duration_years']
        ) {

            $errors[] =
                'The selected year is not valid for this program.';
        }
    }


    // -----------------------------------------------------
    // Create Account
    // -----------------------------------------------------

    if (empty($errors)) {

        try {

            // Start database transaction
            $pdo->beginTransaction();


            // -------------------------------------------------
            // Hash Password
            // -------------------------------------------------

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // -------------------------------------------------
            // Insert Into Users
            // -------------------------------------------------

            $stmt = $pdo->prepare(
                'INSERT INTO users
                (
                    full_name,
                    registration_number,
                    username,
                    email,
                    phone,
                    password_hash,
                    role,
                    is_active
                )
                VALUES
                (
                    :full_name,
                    :registration_number,
                    :username,
                    :email,
                    :phone,
                    :password_hash,
                    :role,
                    :is_active
                )'
            );

            $stmt->execute([

                'full_name' =>
                    $full_name,

                'registration_number' =>
                    $registration_number,

                'username' =>
                    $username,

                'email' =>
                    $email,

                'phone' =>
                    $phone,

                'password_hash' =>
                    $password_hash,

                'role' =>
                    'student',

                'is_active' =>
                    1
            ]);


            // -------------------------------------------------
            // Get Newly Created User ID
            // -------------------------------------------------

            $user_id = (int)$pdo->lastInsertId();


            // -------------------------------------------------
            // Insert Into Students
            // -------------------------------------------------

            $stmt = $pdo->prepare(
                'INSERT INTO students
                (
                    user_id,
                    registration_number,
                    first_name,
                    last_name,
                    program_id,
                    year_of_study
                )
                VALUES
                (
                    :user_id,
                    :registration_number,
                    :first_name,
                    :last_name,
                    :program_id,
                    :year_of_study
                )'
            );

            $stmt->execute([

                'user_id' =>
                    $user_id,

                'registration_number' =>
                    $registration_number,

                'first_name' =>
                    $first_name,

                'last_name' =>
                    $last_name,

                'program_id' =>
                    $program_id,

                'year_of_study' =>
                    $year_of_study
            ]);


            // -------------------------------------------------
            // Everything Successful
            // -------------------------------------------------

            $pdo->commit();


            // Send Student To Login
            header(
                'Location: login.php?registered=1'
            );

            exit;


        } catch (PDOException $e) {

            // Undo anything that was inserted
            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] =
                'Account could not be created. Please try again.';
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

    <title>
        Create Student Account
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=3"
    >

</head>


<body>


<div class="register-container">


    <h1>
        Create Student Account
    </h1>


    <?php if (!empty($errors)): ?>

        <div class="register-error">

            <?php foreach ($errors as $error): ?>

                <p>
                    <?= e($error) ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <div class="register-card">


        <h2>
            Student Registration
        </h2>


        <form method="POST">


            <!-- CSRF -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >


            <!-- Full Name -->

            <label for="full_name">
                Full Name
            </label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="100"
                placeholder="Enter your full name"
                value="<?= e($_POST['full_name'] ?? '') ?>"
                required
            >


            <!-- Registration Number -->

            <label for="registration_number">
                Student Registration Number
            </label>

            <input
                type="text"
                id="registration_number"
                name="registration_number"
                maxlength="50"
                placeholder="e.g. SCCJ/01581/2026"
                value="<?= e($_POST['registration_number'] ?? '') ?>"
                required
            >


            <!-- Username -->

            <label for="username">
                Username
            </label>

            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                placeholder="Choose a username"
                value="<?= e($_POST['username'] ?? '') ?>"
                required
            >


            <!-- Email -->

            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                placeholder="Enter your email address"
                value="<?= e($_POST['email'] ?? '') ?>"
                required
            >


            <!-- Phone -->

            <label for="phone">
                Phone Number
            </label>

            <input
                type="tel"
                id="phone"
                name="phone"
                maxlength="20"
                placeholder="e.g. 0712345678"
                value="<?= e($_POST['phone'] ?? '') ?>"
                required
            >


            <!-- Program -->

            <label for="program_id">
                Program
            </label>

            <select
                id="program_id"
                name="program_id"
                required
            >

                <option value="">
                    Select your program
                </option>

                <?php foreach ($programs as $program): ?>

                    <option
                        value="<?= e((string)$program['id']) ?>"
                        <?= (
                            (int)($_POST['program_id'] ?? 0)
                            === (int)$program['id']
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >

                        <?= e($program['name']) ?>

                        (<?= e($program['code']) ?>)

                    </option>

                <?php endforeach; ?>

            </select>


            <!-- Year -->

            <label for="year_of_study">
                Year of Study
            </label>

            <select
                id="year_of_study"
                name="year_of_study"
                required
            >

                <option value="">
                    Select your year
                </option>

                <?php for ($year = 1; $year <= 8; $year++): ?>

                    <option
                        value="<?= $year ?>"
                        <?= (
                            (int)($_POST['year_of_study'] ?? 0)
                            === $year
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Year <?= $year ?>

                    </option>

                <?php endfor; ?>

            </select>


            <!-- Password -->

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                placeholder="Create a password"
                required
            >


            <!-- Confirm Password -->

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                placeholder="Confirm your password"
                required
            >


            <!-- Submit -->

            <button type="submit">

                Create Account

            </button>


        </form>


        <p>

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </p>


    </div>


</div>


</body>

</html>