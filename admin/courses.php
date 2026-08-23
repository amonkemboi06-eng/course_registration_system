<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$errors = [];
$success = '';
if (isset($_GET['updated'])) {
    $success = 'Course updated successfully.';
}

if (isset($_GET['deleted'])) {
    $success = 'Course deleted successfully.';
}

if (
    isset($_GET['error']) &&
    $_GET['error'] === 'has_registrations'
) {
    $errors[] =
        'This course cannot be deleted because students have already registered for it.';
}

if (
    isset($_GET['error']) &&
    $_GET['error'] === 'not_found'
) {
    $errors[] = 'Course not found.';
}
/*
|--------------------------------------------------------------------------
| ADD COURSE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token.';
    }

    $course_code = strtoupper(trim($_POST['course_code'] ?? ''));
    $course_name = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $credit_hours = (int)($_POST['credit_hours'] ?? 0);
    $program_id = (int)($_POST['program_id'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($course_code === '') {
        $errors[] = 'Course code is required.';
    }

    if ($course_name === '') {
        $errors[] = 'Course name is required.';
    }

    if ($credit_hours < 1 || $credit_hours > 20) {
        $errors[] = 'Credit hours must be between 1 and 20.';
    }

    if ($program_id <= 0) {
        $errors[] = 'Please select a program.';
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY PROGRAM
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
            $errors[] = 'Selected program does not exist.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT COURSE
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                'INSERT INTO courses
                (
                    course_code,
                    course_name,
                    description,
                    credit_hours,
                    program_id
                )
                VALUES
                (
                    :course_code,
                    :course_name,
                    :description,
                    :credit_hours,
                    :program_id
                )'
            );

            $stmt->execute([
                'course_code' => $course_code,
                'course_name' => $course_name,
                'description' => $description !== ''
                    ? $description
                    : null,
                'credit_hours' => $credit_hours,
                'program_id' => $program_id
            ]);

            $success = 'Course added successfully.';

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] =
                    'That course code already exists.';

            } else {

                error_log($e->getMessage());

                $errors[] =
                    'Unable to add course.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET PROGRAMS
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


/*
|--------------------------------------------------------------------------
| GET COURSES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        courses.id,
        courses.course_code,
        courses.course_name,
        courses.description,
        courses.credit_hours,
        programs.name AS program_name,
        programs.code AS program_code,
        departments.name AS department_name,
        courses.created_at
     FROM courses
     INNER JOIN programs
        ON courses.program_id = programs.id
     INNER JOIN departments
        ON programs.department_id = departments.id
     ORDER BY courses.course_code'
);

$courses = $stmt->fetchAll();

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>


<h1 class="page-title">
    Course Management
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
     ADD COURSE
     ===================================================== -->

<div class="card">

    <h2>Add New Course</h2>

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
                placeholder="e.g. BIT 4101"
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
                placeholder="e.g. Web Application Development"
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
                rows="4"
                placeholder="Enter course description..."
            ></textarea>

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
                value="3"
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

                <option value="">
                    -- Select Program --
                </option>

                <?php foreach ($programs as $program): ?>

                    <option
                        value="<?= e((string)$program['id']) ?>"
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
            Add Course
        </button>

    </form>

</div>


<!-- =====================================================
     COURSE LIST
     ===================================================== -->

<div class="table-container">

    <h2>Available Courses</h2>

    <table>

        <thead>

            <tr>

                <th>ID</th>

                <th>Course Code</th>

                <th>Course Name</th>

                <th>Program</th>

                <th>Department</th>

                <th>Credits</th>

                <th>Actions</th>

            </tr>

        </thead>


        <tbody>

        <?php if (empty($courses)): ?>

            <tr>

                <td colspan="7">
                    No courses have been added yet.
                </td>

            </tr>

        <?php else: ?>

            <?php foreach ($courses as $course): ?>

                <tr>

                    <td>
                        <?= e((string)$course['id']) ?>
                    </td>


                    <td>

                        <span class="badge badge-gold">

                            <?= e($course['course_code']) ?>

                        </span>

                    </td>


                    <td>

                        <strong>
                            <?= e($course['course_name']) ?>
                        </strong>

                        <?php if (!empty($course['description'])): ?>

                            <br>

                            <small>
                                <?= e($course['description']) ?>
                            </small>

                        <?php endif; ?>

                    </td>


                    <td>

                        <?= e($course['program_name']) ?>

                        <br>

                        <small>
                            <?= e($course['program_code']) ?>
                        </small>

                    </td>


                    <td>
                        <?= e($course['department_name']) ?>
                    </td>


                    <td>
                        <?= e((string)$course['credit_hours']) ?>
                    </td>


                    <td>

                        <a
                            href="edit_course.php?id=<?= e((string)$course['id']) ?>"
                            class="btn"
                        >
                            Edit
                        </a>

                        <a
                            href="delete_course.php?id=<?= e((string)$course['id']) ?>"
                            class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete this course?');"
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