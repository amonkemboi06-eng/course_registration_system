<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$errors = [];

$semester_id = (int)($_GET['id'] ?? 0);

if ($semester_id <= 0) {
    http_response_code(400);
    exit('Invalid semester ID.');
}


/*
|--------------------------------------------------------------------------
| Get Semester
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT *
     FROM semesters
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $semester_id
]);

$semester = $stmt->fetch();

if (!$semester) {
    http_response_code(404);
    exit('Semester not found.');
}


/*
|--------------------------------------------------------------------------
| Update Semester
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token.';
    }

    $name = trim($_POST['name'] ?? '');

    $academic_year = trim(
        $_POST['academic_year'] ?? ''
    );

    $start_date = $_POST['start_date'] ?? '';

    $end_date = $_POST['end_date'] ?? '';


    if ($name === '') {
        $errors[] = 'Semester name is required.';
    }

    if ($academic_year === '') {
        $errors[] = 'Academic year is required.';
    }

    if ($start_date === '') {
        $errors[] = 'Start date is required.';
    }

    if ($end_date === '') {
        $errors[] = 'End date is required.';
    }

    if (
        empty($errors) &&
        $start_date >= $end_date
    ) {
        $errors[] =
            'End date must be after the start date.';
    }


    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                'UPDATE semesters
                 SET
                    name = :name,
                    academic_year = :academic_year,
                    start_date = :start_date,
                    end_date = :end_date
                 WHERE id = :id'
            );

            $stmt->execute([
                'name' => $name,
                'academic_year' => $academic_year,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'id' => $semester_id
            ]);

            header(
                'Location: semesters.php?updated=1'
            );

            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] =
                    'That semester already exists for this academic year.';

            } else {

                error_log($e->getMessage());

                $errors[] =
                    'Unable to update semester.';
            }
        }
    }
}

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>


<h1 class="page-title">
    Edit Semester
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

            <label for="name">
                Semester
            </label>

            <select
                id="name"
                name="name"
                required
            >

                <option
                    value="Semester 1"
                    <?= $semester['name'] === 'Semester 1'
                        ? 'selected'
                        : '' ?>
                >
                    Semester 1
                </option>

                <option
                    value="Semester 2"
                    <?= $semester['name'] === 'Semester 2'
                        ? 'selected'
                        : '' ?>
                >
                    Semester 2
                </option>

                <option
                    value="Semester 3"
                    <?= $semester['name'] === 'Semester 3'
                        ? 'selected'
                        : '' ?>
                >
                    Semester 3
                </option>

            </select>

        </div>


        <div class="form-group">

            <label for="academic_year">
                Academic Year
            </label>

            <input
                type="text"
                id="academic_year"
                name="academic_year"
                value="<?= e($semester['academic_year']) ?>"
                maxlength="20"
                required
            >

        </div>


        <div class="form-group">

            <label for="start_date">
                Start Date
            </label>

            <input
                type="date"
                id="start_date"
                name="start_date"
                value="<?= e($semester['start_date']) ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="end_date">
                End Date
            </label>

            <input
                type="date"
                id="end_date"
                name="end_date"
                value="<?= e($semester['end_date']) ?>"
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
            href="semesters.php"
            class="btn"
        >
            Cancel
        </a>

    </form>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>