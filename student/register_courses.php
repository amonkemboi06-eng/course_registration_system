<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student');

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header('Location: ../login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Student
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        registration_number,
        first_name,
        last_name,
        program_id,
        year_of_study
     FROM students
     WHERE user_id = :user_id
     LIMIT 1'
);

$stmt->execute([
    'user_id' => $user_id
]);

$student = $stmt->fetch();

if (!$student) {
    session_destroy();

    header('Location: ../login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Current Open Semester
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        id,
        name,
        academic_year,
        start_date,
        end_date
     FROM semesters
     WHERE registration_open = 1
     ORDER BY start_date DESC
     LIMIT 1'
);

$semester = $stmt->fetch();

if (!$semester) {
    die('Course registration is currently closed.');
}


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';


/*
|--------------------------------------------------------------------------
| Register Selected Courses
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $message = 'Invalid security token. Please refresh the page and try again.';
        $message_type = 'error';

    } else {

        $selected_courses = $_POST['courses'] ?? [];

        if (!is_array($selected_courses) || empty($selected_courses)) {

            $message = 'Please select at least one course.';
            $message_type = 'error';

        } else {

            $registered = 0;
            $already_registered = 0;

            try {

                $pdo->beginTransaction();

                foreach ($selected_courses as $course_id) {

                    $course_id = (int)$course_id;

                    if ($course_id <= 0) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Check Course Belongs To Student Program
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare(
                        'SELECT id
                         FROM courses
                         WHERE id = :course_id
                         AND program_id = :program_id
                         LIMIT 1'
                    );

                    $stmt->execute([
                        'course_id' => $course_id,
                        'program_id' => $student['program_id']
                    ]);

                    $course = $stmt->fetch();

                    if (!$course) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Check Existing Registration
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare(
                        'SELECT id
                         FROM course_registrations
                         WHERE student_id = :student_id
                         AND course_id = :course_id
                         AND semester_id = :semester_id
                         LIMIT 1'
                    );

                    $stmt->execute([
                        'student_id' => $student['id'],
                        'course_id' => $course_id,
                        'semester_id' => $semester['id']
                    ]);

                    if ($stmt->fetch()) {

                        $already_registered++;

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Insert Registration
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare(
                        'INSERT INTO course_registrations
                        (
                            student_id,
                            course_id,
                            semester_id,
                            status,
                            registered_at
                        )
                        VALUES
                        (
                            :student_id,
                            :course_id,
                            :semester_id,
                            :status,
                            NOW()
                        )'
                    );

                    $stmt->execute([
                        'student_id' => $student['id'],
                        'course_id' => $course_id,
                        'semester_id' => $semester['id'],
                        'status' => 'registered'
                    ]);

                    $registered++;
                }

                $pdo->commit();


                /*
                |--------------------------------------------------------------------------
                | Result Message
                |--------------------------------------------------------------------------
                */

                if ($registered > 0) {

                    $message = $registered . ' course(s) registered successfully.';

                    if ($already_registered > 0) {
                        $message .=
                            ' ' . $already_registered .
                            ' course(s) were already registered.';
                    }

                    $message_type = 'success';

                } elseif ($already_registered > 0) {

                    $message =
                        'The selected course(s) are already registered.';

                    $message_type = 'error';

                } else {

                    $message =
                        'No courses were registered.';

                    $message_type = 'error';
                }

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if (
                    isset($e->errorInfo[1]) &&
                    (int)$e->errorInfo[1] === 1062
                ) {

                    $message =
                        'One or more selected courses are already registered.';

                } else {

                    $message =
                        'An unexpected error occurred. Please try again.';
                }

                $message_type = 'error';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get All Courses For Student's Program
|--------------------------------------------------------------------------
| Registered courses remain visible and are marked as checked.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        c.id,
        c.course_code,
        c.course_name,
        c.description,
        c.credit_hours,

        CASE
            WHEN cr.id IS NOT NULL THEN 1
            ELSE 0
        END AS is_registered

     FROM courses c

     LEFT JOIN course_registrations cr
        ON cr.course_id = c.id
        AND cr.student_id = :student_id
        AND cr.semester_id = :semester_id

     WHERE c.program_id = :program_id

     ORDER BY c.course_code ASC'
);

$stmt->execute([
    'student_id' => $student['id'],
    'semester_id' => $semester['id'],
    'program_id' => $student['program_id']
]);

$courses = $stmt->fetchAll();
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
        Register Courses
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>


<body>


<!-- =====================================================
     NAVIGATION
     ===================================================== -->

<header class="topbar">

    <div class="logo">
        Course Registration System
    </div>

    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="register_courses.php">
            Register Courses
        </a>

        <a href="my_courses.php">
            My Courses
        </a>

        <a
            href="../auth/logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </nav>

</header>


<!-- =====================================================
     MAIN CONTENT
     ===================================================== -->

<main class="dashboard-container">


    <section class="welcome-section">

        <h1>
            Register Courses
        </h1>

        <p>

            <?= e($student['first_name']) ?>
            <?= e($student['last_name']) ?>

            —

            <?= e($student['registration_number']) ?>

        </p>

    </section>


    <!-- =================================================
         SEMESTER INFORMATION
         ================================================= -->

    <section class="dashboard-card">

        <h2>
            Current Semester
        </h2>

        <h3>

            <?= e($semester['name']) ?>

            -

            <?= e($semester['academic_year']) ?>

        </h3>

        <p>

            Semester Period:

            <?= e($semester['start_date']) ?>

            to

            <?= e($semester['end_date']) ?>

        </p>

    </section>


    <!-- =================================================
         MESSAGE
         ================================================= -->

    <?php if ($message): ?>

        <div class="alert <?= e($message_type) ?>">

            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         AVAILABLE COURSES
         ================================================= -->

    <section class="dashboard-card">

        <h2>
            Available Courses
        </h2>


        <?php if (empty($courses)): ?>

            <p>
                You have registered for all available courses
                for the current semester.
            </p>

        <?php else: ?>


            <form
                method="POST"
                onsubmit="return confirm('Are you sure you want to register for the selected courses?');"
            >


                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >


                <div class="course-table-container">

                    <table class="course-table">

                        <thead>

                            <tr>

                                <th>
                                    Select
                                </th>

                                <th>
                                    Course Code
                                </th>

                                <th>
                                    Course Name
                                </th>

                                <th>
                                    Description
                                </th>

                                <th>
                                    Credit Hours
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($courses as $course): ?>

                                <tr>

                                    <td>

                                      <?php if ((int)$course['is_registered'] === 1): ?>

    <span class="registered-check">
        ✓
    </span>

<?php else: ?>

    <input
        type="checkbox"
        name="courses[]"
        value="<?= e((string)$course['id']) ?>"
        class="course-checkbox"
    >

<?php endif; ?>

                                    </td>


                                    <td>

                                        <strong>
                                            <?= e($course['course_code']) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= e($course['course_name']) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $course['description']
                                            ?: 'No description available.'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            (string)$course['credit_hours']
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     REGISTER BUTTON
                     ================================================= -->

                <div class="register-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Register Selected Courses
                    </button>

                </div>


            </form>


        <?php endif; ?>


    </section>


</main>


</body>

</html>