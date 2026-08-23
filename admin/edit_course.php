<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$errors = [];

$course_id = (int)($_GET['id'] ?? 0);

if ($course_id <= 0) {
    http_response_code(400);
    exit('Invalid course ID.');
}


/*
|--------------------------------------------------------------------------
| Get Course
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        course_code,
        course_name,
        description,
        credit_hours,
        program_id
     FROM courses
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $course_id
]);

$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    exit('Course not found.');
}


/*
|--------------------------------------------------------------------------
| Update Course
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token.';
    }

    $course_code = strtoupper(
        trim($_POST['course_code'] ?? '')
    );

    $course_name = trim(
        $_POST['course_name'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $credit_hours = (int)(
        $_POST['credit_hours'] ?? 0
    );

    $program_id = (int)(
        $_POST['program_id'] ?? 0
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($course_code === '') {
        $errors[] = 'Course code is required.';
    }

    if ($course_name === '') {
        $errors[] = 'Course name is required.';
    }

    if ($credit_hours < 1 || $credit_hours > 20) {
        $errors[] =
            'Credit hours must be between 1 and 20.';
    }

    if ($program_id <= 0) {
        $errors[] = 'Please select a program.';
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Program
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            'SELECT id
             FROM programs
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $program_id
        ]);

        if (!$stmt->fetch()) {
            $errors[] =
                'Selected program does not exist.';
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
                'UPDATE courses
                 SET
                    course_code = :course_code,
                    course_name = :course_name,
                    description = :description,
                    credit_hours = :credit_hours,
                    program_id = :program_id
                 WHERE id = :id'
            );

            $stmt->execute([
                'course_code' => $course_code,
                'course_name' => $course_name,
                'description' =>
                    $description !== ''
                        ? $description
                        : null,
                'credit_hours' => $credit_hours,
                'program_id' => $program_id,
                'id' => $course_id
            ]);

            header(
                'Location: courses.php?updated=1'
            );

            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] =
                    'That course code already exists.';

            } else {

                error_log($e->getMessage());

                $errors[] =
                    'Unable to update course.';
            }
        }
    }
}


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
        departments.name AS department_name
     FROM programs
     INNER JOIN departments
        ON programs.department_id = departments.id
     ORDER BY programs.name'
);

$programs = $stmt->fetchAll();

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>

<h1 class="page-title">
    Edit Course
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

            <label for="course_code">
                Course Code
            </label>

            <input
                type="text"
                id="course_code"
                name="course_code"
                maxlength="30"
                value="<?= e($course['course_code']) ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="course_name">
                Course Name
            </label>

            <input
                type="text"
                id="course_name"
                name="course_name"
                maxlength="150"
                value="<?= e($course['course_name']) ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="description">
                Course Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="5"
            ><?= e($course['description']) ?></textarea>

        </div>


        <div class="form-group">

            <label for="credit_hours">
                Credit Hours
            </label>

            <input
                type="number"
                id="credit_hours"
                name="credit_hours"
                min="1"
                max="20"
                value="<?= e((string)$course['credit_hours']) ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="program_id">
                Program
            </label>

            <select
                id="program_id"
                name="program_id"
                required
            >

                <?php foreach ($programs as $program): ?>

                    <option
                        value="<?= e((string)$program['id']) ?>"
                        <?= (int)$course['program_id'] ===
                            (int)$program['id']
                            ? 'selected'
                            : '' ?>
                    >

                        <?= e($program['name']) ?>

                        (<?= e($program['code']) ?>)

                        -
                        <?= e($program['department_name']) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <button
            type="submit"
            class="btn btn-success"
        >
            Save Changes
        </button>


        <a
            href="courses.php"
            class="btn"
        >
            Cancel
        </a>

    </form>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>