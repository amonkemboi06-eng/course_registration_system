<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$errors = [];
$success = '';
if (isset($_GET['updated'])) {
    $success = 'Program updated successfully.';
}

if (isset($_GET['deleted'])) {
    $success = 'Program deleted successfully.';
}

if (isset($_GET['error']) && $_GET['error'] === 'has_courses') {
    $errors[] = 'This program cannot be deleted because it has courses assigned to it.';
}
/*
|--------------------------------------------------------------------------
| Add Program
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
    | Verify Department Exists
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
    | Insert Program
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                'INSERT INTO programs
                (department_id, name, code, duration_years)
                VALUES
                (:department_id, :name, :code, :duration_years)'
            );

            $stmt->execute([
                'department_id' => $department_id,
                'name' => $name,
                'code' => $code,
                'duration_years' => $duration_years
            ]);

            $success = 'Program added successfully.';

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] =
                    'The program code already exists.';

            } else {

                error_log($e->getMessage());

                $errors[] =
                    'Unable to add program.';
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


/*
|--------------------------------------------------------------------------
| Get Programs
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        programs.id,
        programs.name,
        programs.code,
        programs.duration_years,
        departments.name AS department_name,
        departments.code AS department_code,
        programs.created_at
     FROM programs
     INNER JOIN departments
        ON programs.department_id = departments.id
     ORDER BY programs.name'
);

$programs = $stmt->fetchAll();

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>


<h1 class="page-title">
    Program Management
</h1>


<?php if ($success): ?>

    <div class="alert alert-success">
        <?= e($success) ?>
    </div>

<?php endif; ?>


<?php foreach ($errors as $error): ?>

    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>

<?php endforeach; ?>


<!-- =====================================================
     ADD PROGRAM
     ===================================================== -->

<div class="card">

    <h2>Add Academic Program</h2>

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

                <option value="">
                    -- Select Department --
                </option>

                <?php foreach ($departments as $department): ?>

                    <option
                        value="<?= e((string)$department['id']) ?>"
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
                placeholder="e.g. Bachelor of Information Technology"
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
                placeholder="e.g. BIT"
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
                required
            >

        </div>


        <button
            type="submit"
            class="btn btn-success"
        >
            Add Program
        </button>

    </form>

</div>


<!-- =====================================================
     PROGRAM LIST
     ===================================================== -->

<div class="table-container">

    <h2>Available Programs</h2>

    <table>

        <thead>

            <tr>

                <th>ID</th>
                <th>Program</th>
                <th>Code</th>
                <th>Department</th>
                <th>Duration</th>
                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

        <?php if (empty($programs)): ?>

            <tr>
                <td colspan="6">
                    No programs have been added yet.
                </td>
            </tr>

        <?php else: ?>

            <?php foreach ($programs as $program): ?>

                <tr>

                    <td>
                        <?= e((string)$program['id']) ?>
                    </td>

                    <td>
                        <?= e($program['name']) ?>
                    </td>

                    <td>
                        <span class="badge badge-gold">
                            <?= e($program['code']) ?>
                        </span>
                    </td>

                    <td>

                        <?= e($program['department_name']) ?>

                        <small>
                            (<?= e($program['department_code']) ?>)
                        </small>

                    </td>

                    <td>
                        <?= e((string)$program['duration_years']) ?>
                        year(s)
                    </td>

                    <td>

                        <a
                            href="edit_program.php?id=<?= e((string)$program['id']) ?>"
                            class="btn"
                        >
                            Edit
                        </a>

                        <a
                            href="delete_program.php?id=<?= e((string)$program['id']) ?>"
                            class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete this program?');"
                        >
                            Delete
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>