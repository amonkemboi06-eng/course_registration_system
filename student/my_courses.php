
<?php

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
        students.id,
        students.registration_number,
        students.first_name,
        students.last_name,
        programs.name AS program_name,
        programs.code AS program_code,
        students.year_of_study
     FROM students
     INNER JOIN programs
        ON students.program_id = programs.id
     WHERE students.user_id = :user_id
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
| Get Current / Latest Semester
|--------------------------------------------------------------------------
|
| IMPORTANT:
| We DO NOT filter by registration_open here.
|
| This allows students to continue seeing their registered
| courses even after course registration has been closed.
|
*/

$stmt = $pdo->query(
    'SELECT
        id,
        name,
        academic_year,
        start_date,
        end_date,
        registration_open
     FROM semesters
     ORDER BY start_date DESC
     LIMIT 1'
);

$semester = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Registration Status
|--------------------------------------------------------------------------
*/

$registration_open = false;

if ($semester) {
    $registration_open = ((int)$semester['registration_open'] === 1);
}


/*
|--------------------------------------------------------------------------
| Get Registered Courses
|--------------------------------------------------------------------------
*/

$courses = [];

$total_credit_hours = 0;

if ($semester) {

    $stmt = $pdo->prepare(
        'SELECT
            c.id,
            c.course_code,
            c.course_name,
            c.description,
            c.credit_hours,
            cr.status,
            cr.registered_at
         FROM course_registrations cr
         INNER JOIN courses c
            ON cr.course_id = c.id
         WHERE cr.student_id = :student_id
         AND cr.semester_id = :semester_id
         ORDER BY c.course_code ASC'
    );

    $stmt->execute([
        'student_id' => $student['id'],
        'semester_id' => $semester['id']
    ]);

    $courses = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Calculate Total Credit Hours
    |--------------------------------------------------------------------------
    */

    foreach ($courses as $course) {

        $total_credit_hours +=
            (int)$course['credit_hours'];
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
        My Courses
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=3"
    >

</head>


<body>


<header class="topbar">

    <div class="logo">
        Course Registration System
    </div>


    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>


        <?php if ($registration_open): ?>

            <a href="register_courses.php">
                Register Courses
            </a>

        <?php endif; ?>


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


<main class="dashboard-container">


    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <section class="welcome-section">

        <h1>
            My Registered Courses
        </h1>

        <p>

            <?= e($student['first_name']) ?>

            <?= e($student['last_name']) ?>

            —

            <?= e($student['registration_number']) ?>

        </p>

    </section>


    <?php if (!$semester): ?>


        <!-- =================================================
             NO SEMESTER
             ================================================= -->

        <section class="dashboard-card">

            <div class="registration-closed">

                <h3>
                    No Semester Available
                </h3>

                <p>
                    There is currently no semester available
                    in the system.
                </p>

            </div>

        </section>


    <?php else: ?>


        <!-- =================================================
             SEMESTER INFORMATION
             ================================================= -->

        <section class="dashboard-card">

            <h2>
                Semester
            </h2>


            <div class="semester-info">

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


                <?php if ($registration_open): ?>

                    <p
                        style="
                            color:#198754;
                            font-weight:bold;
                        "
                    >
                        Course Registration is OPEN
                    </p>

                <?php else: ?>

                    <p
                        style="
                            color:#dc3545;
                            font-weight:bold;
                        "
                    >
                        Course Registration is CLOSED
                    </p>

                <?php endif; ?>

            </div>

        </section>


        <!-- =================================================
             COURSE SUMMARY
             ================================================= -->

        <section class="dashboard-card">

            <h2>
                Registration Summary
            </h2>


            <div class="info-grid">


                <div>

                    <span>
                        Registered Courses
                    </span>

                    <strong>
                        <?= e((string)count($courses)) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Total Credit Hours
                    </span>

                    <strong>
                        <?= e((string)$total_credit_hours) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Program
                    </span>

                    <strong>
                        <?= e($student['program_name']) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Year of Study
                    </span>

                    <strong>
                        Year
                        <?= e((string)$student['year_of_study']) ?>
                    </strong>

                </div>


            </div>

        </section>


        <!-- =================================================
             REGISTERED COURSES
             ================================================= -->

        <section class="dashboard-card">

            <h2>
                Registered Courses
            </h2>


            <?php if (empty($courses)): ?>


                <div class="empty-state">

                    <p>
                        You have not registered for any
                        courses for this semester.
                    </p>


                    <?php if ($registration_open): ?>

                        <a
                            href="register_courses.php"
                            class="btn btn-primary"
                        >
                            Register Courses
                        </a>

                    <?php else: ?>

                        <p
                            style="
                                color:#dc3545;
                                font-weight:bold;
                            "
                        >
                            Registration is currently closed.
                        </p>

                    <?php endif; ?>

                </div>


            <?php else: ?>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Course Code
                                </th>

                                <th>
                                    Course Name
                                </th>

                                <th>
                                    Credit Hours
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Registered At
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($courses as $index => $course): ?>


                            <tr>


                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>
                                    <?= e($course['course_code']) ?>
                                </td>


                                <td>
                                    <?= e($course['course_name']) ?>
                                </td>


                                <td>
                                    <?= e((string)$course['credit_hours']) ?>
                                </td>


                                <td>

                                    <?php

                                    $status = strtolower(
                                        (string)$course['status']
                                    );

                                    $status_color = '#198754';

                                    if ($status === 'pending') {
                                        $status_color = '#ffc107';
                                    }

                                    if ($status === 'dropped') {
                                        $status_color = '#dc3545';
                                    }

                                    if ($status === 'approved') {
                                        $status_color = '#198754';
                                    }

                                    if ($status === 'registered') {
                                        $status_color = '#198754';
                                    }

                                    ?>

                                    <span
                                        style="
                                            color:<?= e($status_color) ?>;
                                            font-weight:bold;
                                        "
                                    >
                                        <?= e(ucfirst($status)) ?>
                                    </span>

                                </td>


                                <td>

                                    <?php if (!empty($course['registered_at'])): ?>

                                        <?= e(
                                            date(
                                                'd M Y H:i',
                                                strtotime(
                                                    $course['registered_at']
                                                )
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                        <tfoot>

                            <tr>

                                <th colspan="3">
                                    Total
                                </th>

                                <th>
                                    <?= e(
                                        (string)$total_credit_hours
                                    ) ?>
                                </th>

                                <th colspan="2">
                                </th>

                            </tr>

                        </tfoot>


                    </table>

                </div>


                <?php if ($registration_open): ?>


                    <br>


                    <a
                        href="register_courses.php"
                        class="btn btn-primary"
                    >
                        Add More Courses
                    </a>


                <?php endif; ?>


            <?php endif; ?>


        </section>


    <?php endif; ?>


</main>


</body>

</html>
```
