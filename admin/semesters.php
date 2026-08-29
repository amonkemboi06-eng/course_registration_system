<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$errors = [];
$success = '';

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

if (isset($_GET['updated'])) {
    $success = 'Semester updated successfully.';
}

if (isset($_GET['deleted'])) {
    $success = 'Semester deleted successfully.';
}

if (isset($_GET['opened'])) {
    $success = 'Course registration has been opened.';
}

if (isset($_GET['closed'])) {
    $success = 'Course registration has been closed.';
}

if (
    isset($_GET['error']) &&
    $_GET['error'] === 'has_registrations'
) {
    $errors[] =
        'This semester cannot be deleted because students have registrations associated with it.';
}

if (
    isset($_GET['error']) &&
    $_GET['error'] === 'not_found'
) {
    $errors[] = 'Semester not found.';
}
/*
|--------------------------------------------------------------------------
| Add Semester
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


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {
        $errors[] = 'Semester name is required.';
    }

    if ($academic_year === '') {
        $errors[] = 'Academic year is required.';
    }

    if ($start_date === '') {
        $errors[] = 'Semester start date is required.';
    }

    if ($end_date === '') {
        $errors[] = 'Semester end date is required.';
    }


    /*
    |--------------------------------------------------------------------------
    | Date Validation
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        $start_date >= $end_date
    ) {

        $errors[] =
            'Semester end date must be after the start date.';
    }


    /*
    |--------------------------------------------------------------------------
    | Insert Semester
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                'INSERT INTO semesters
                (
                    name,
                    academic_year,
                    start_date,
                    end_date
                )
                VALUES
                (
                    :name,
                    :academic_year,
                    :start_date,
                    :end_date
                )'
            );

            $stmt->execute([
                'name' => $name,
                'academic_year' => $academic_year,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

            $success =
                'Semester added successfully.';

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] =
                    'That semester already exists for this academic year.';

            } else {

                error_log($e->getMessage());

                $errors[] =
                    'Unable to add semester.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Semesters
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        id,
        name,
        academic_year,
        start_date,
        end_date,
        registration_open,
        created_at
     FROM semesters
     ORDER BY start_date DESC'
);

$semesters = $stmt->fetchAll();

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>


<h1 class="page-title">
    Semester Management
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
     ADD SEMESTER
     ===================================================== -->

<div class="card">

    <h2>Add Semester</h2>

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

                <option value="">
                    -- Select Semester --
                </option>

                <option value="Semester 1">
                    Semester 1
                </option>

                <option value="Semester 2">
                    Semester 2
                </option>

                <option value="Semester 3">
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
                maxlength="20"
                placeholder="e.g. 2026/2027"
                required
            >

        </div>


        <div class="form-group">

            <label for="start_date">
                Semester Start Date
            </label>

            <input
                type="date"
                id="start_date"
                name="start_date"
                required
            >

        </div>


        <div class="form-group">

            <label for="end_date">
                Semester End Date
            </label>

            <input
                type="date"
                id="end_date"
                name="end_date"
                required
            >

        </div>


        <button
            type="submit"
            class="btn btn-success"
        >
            Add Semester
        </button>

    </form>

</div>


<!-- =====================================================
     SEMESTER LIST
     ===================================================== -->

<div class="table-container">

    <h2>Semesters</h2>

    <table>

        <thead>

            <tr>

                <th>Semester</th>

                <th>Academic Year</th>

                <th>Start Date</th>

                <th>End Date</th>

                <th>Status</th>

                <th>Actions</th>

            </tr>

        </thead>


        <tbody>

        <?php if (empty($semesters)): ?>

            <tr>

                <td colspan="6">
                    No semesters have been created yet.
                </td>

            </tr>

        <?php else: ?>

            <?php foreach ($semesters as $semester): ?>

                <tr>

                    <td>
                        <?= e($semester['name']) ?>
                    </td>


                    <td>
                        <?= e($semester['academic_year']) ?>
                    </td>


                    <td>
                        <?= e($semester['start_date']) ?>
                    </td>


                    <td>
                        <?= e($semester['end_date']) ?>
                    </td>


                    <td>

                        <?php if (
                            (int)$semester['registration_open'] === 1
                        ): ?>

                            <span class="badge badge-green">
                                OPEN
                            </span>

                        <?php else: ?>

                            <span class="badge badge-red">
                                CLOSED
                            </span>

                        <?php endif; ?>

                    </td>


                    <td>

                        <a
                            href="edit_semester.php?id=<?= e((string)$semester['id']) ?>"
                            class="btn"
                        >
                            Edit
                        </a>


                        <?php if (
                            (int)$semester['registration_open'] === 1
                        ): ?>

                            <a
                                href="close_registration.php?id=<?= e((string)$semester['id']) ?>"
                                class="btn btn-danger"
                                onclick="return confirm('Close course registration for this semester?');"
                            >
                                Close
                            </a>

                        <?php else: ?>

                            <a
                                href="open_registration.php?id=<?= e((string)$semester['id']) ?>"
                                class="btn btn-success"
                                onclick="return confirm('Open course registration for this semester?');"
                            >
                                Open
                            </a>

                        <?php endif; ?>


                        <a
                            href="delete_semester.php?id=<?= e((string)$semester['id']) ?>"
                            class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete this semester?');"
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