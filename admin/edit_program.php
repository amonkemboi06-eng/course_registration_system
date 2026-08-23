<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$errors = [];
$success = '';

$program_id = (int)($_GET['id'] ?? 0);

if ($program_id <= 0) {
    http_response_code(400);
    exit('Invalid program ID.');
}


/*
|--------------------------------------------------------------------------
| Get Program
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT id, department_id, name, code, duration_years
     FROM programs
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $program_id
]);

$program = $stmt->fetch();

if (!$program) {
    http_response_code(404);
    exit('Program not found.');
}


/*
|--------------------------------------------------------------------------
| Update Program
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token.';
    }

    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $department_id = (int)($_POST['department_id'] ?? 0);
    $duration_years = (int)($_POST['duration_years'] ?? 0);

    if ($name === '') {
        $errors[] = 'Program name is required.';
    }

    if ($code === '') {
        $errors[] = 'Program code is required.';
    }

    if ($department_id <= 0) {
        $errors[] = 'Please select a department.';
    }

    if ($duration_years < 1 || $duration_years > 10) {
        $errors[] = 'Program duration must be between 1 and 10 years.';
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Department
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            'SELECT id
             FROM departments
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $department_id
        ]);

        if (!$stmt->fetch()) {
            $errors[] = 'Selected department does not exist.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update Database
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                'UPDATE programs
                 SET
                    department_id = :department_id,
                    name = :name,
                    code = :code,
                    duration_years = :duration_years
                 WHERE id = :id'
            );

            $stmt->execute([
                'department_id' => $department_id,
                'name' => $name,
                'code' => $code,
                'duration_years' => $duration_years,
                'id' => $program_id
            ]);

            header(
                'Location: programs.php?updated=1'
            );

            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] =
                    'That program code already exists.';

            } else {

                error_log($e->getMessage());

                $errors[] =
                    'Unable to update program.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Departments
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT id, name, code
     FROM departments
     ORDER BY name'
);

$departments = $stmt->fetchAll();

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>

<h1 class="page-title">
    Edit Program
</h1>


<?php foreach ($errors as $error): ?>

    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>

<?php endforeach; ?>


<div class="card">

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >


        <div class="form-group">

            <label for="department_id">
                Department
            </label>

            <select
                id="department_id"
                name="department_id"
                required
            >

                <?php foreach ($departments as $department): ?>

                    <option
                        value="<?= e((string)$department['id']) ?>"
                        <?= (int)$program['department_id'] === (int)$department['id']
                            ? 'selected'
                            : '' ?>
                    >

                        <?= e($department['name']) ?>

                        (<?= e($department['code']) ?>)

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="form-group">

            <label for="name">
                Program Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                maxlength="150"
                value="<?= e($program['name']) ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="code">
                Program Code
            </label>

            <input
                type="text"
                id="code"
                name="code"
                maxlength="30"
                value="<?= e($program['code']) ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="duration_years">
                Duration (Years)
            </label>

            <input
                type="number"
                id="duration_years"
                name="duration_years"
                min="1"
                max="10"
                value="<?= e((string)$program['duration_years']) ?>"
                required
            >

        </div>


        <button
            type="submit"
            class="btn btn-success"
        >
            Save Changes
        </button>

        <a
            href="programs.php"
            class="btn"
        >
            Cancel
        </a>

    </form>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>
