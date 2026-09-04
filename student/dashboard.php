<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student');


// ---------------------------------------------------------
// Get Logged-In User
// ---------------------------------------------------------

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header('Location: ../auth/login.php');
    exit;
}


// ---------------------------------------------------------
// Get Student Information
// ---------------------------------------------------------

$stmt = $pdo->prepare(
    'SELECT
        s.id,
        s.registration_number,
        s.first_name,
        s.last_name,
        s.year_of_study,
        u.profile_photo,
        p.name AS program_name,
        p.code AS program_code
     FROM students s
     INNER JOIN users u
        ON s.user_id = u.id
     INNER JOIN programs p
        ON s.program_id = p.id
     WHERE s.user_id = :user_id
     LIMIT 1'
);

$stmt->execute([
    'user_id' => $user_id
]);

$student = $stmt->fetch();


// ---------------------------------------------------------
// Student Profile Not Found
// ---------------------------------------------------------

if (!$student) {

    session_unset();
    session_destroy();

    header(
        'Location: ../auth/login.php?error=student_profile'
    );

    exit;
}


// ---------------------------------------------------------
// Get Current Open Semester
// ---------------------------------------------------------

$stmt = $pdo->query(
    'SELECT
        id,
        name,
        academic_year,
        start_date,
        end_date,
        registration_open
     FROM semesters
     WHERE registration_open = 1
     ORDER BY start_date DESC
     LIMIT 1'
);

$current_semester = $stmt->fetch();


// ---------------------------------------------------------
// Count Registered Courses
// ---------------------------------------------------------

$registered_count = 0;

if ($current_semester) {

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM course_registrations
         WHERE student_id = :student_id
         AND semester_id = :semester_id'
    );

    $stmt->execute([
        'student_id' => $student['id'],
        'semester_id' => $current_semester['id']
    ]);

    $registered_count = (int)$stmt->fetchColumn();
}


// ---------------------------------------------------------
// Get Registered Courses
// ---------------------------------------------------------

$registered_courses = [];

if ($current_semester) {

    $stmt = $pdo->prepare(
        'SELECT
            c.course_code,
            c.course_name,
            c.credit_hours,
            cr.status
         FROM course_registrations cr

         INNER JOIN courses c
            ON cr.course_id = c.id

         WHERE cr.student_id = :student_id
         AND cr.semester_id = :semester_id

         ORDER BY c.course_code ASC'
    );

    $stmt->execute([
        'student_id' => $student['id'],
        'semester_id' => $current_semester['id']
    ]);

    $registered_courses = $stmt->fetchAll();
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
        Student Dashboard | Course Registration System
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=4"
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

<a href="profile.php">
    My Profile
</a>
    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>


        <?php if ($current_semester): ?>

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


<!-- =====================================================
     MAIN CONTENT
     ===================================================== -->

<main class="dashboard-container">


    <!-- =================================================
         WELCOME
         ================================================= -->

    <section class="welcome-section">

    <div class="welcome-profile">

        <?php if (!empty($student['profile_photo'])): ?>

            <img
                src="../uploads/profile_photos/<?= e($student['profile_photo']) ?>"
                alt="Student Profile Photo"
                class="dashboard-profile-photo"
            >

        <?php else: ?>

            <div class="dashboard-default-avatar">

                <?= e(
                    strtoupper(
                        substr(
                            $student['first_name'] ?? 'S',
                            0,
                            1
                        )
                    )
                ) ?>

            </div>

        <?php endif; ?>


        <div class="welcome-details">

            <h1>

                Welcome,

                <?= e($student['first_name']) ?>

                <?= e($student['last_name']) ?>

            </h1>


            <p>

                Registration Number:

                <strong>
                    <?= e($student['registration_number']) ?>
                </strong>

            </p>

        </div>

    </div>

</section>


    <!-- =================================================
         STUDENT INFORMATION
         ================================================= -->

    <section class="dashboard-card">

        <h2>
            Student Information
        </h2>


        <div class="info-grid">


            <div>

                <span>
                    Full Name
                </span>

                <strong>

                    <?= e($student['first_name']) ?>

                    <?= e($student['last_name']) ?>

                </strong>

            </div>


            <div>

                <span>
                    Registration Number
                </span>

                <strong>

                    <?= e(
                        $student['registration_number']
                    ) ?>

                </strong>

            </div>


            <div>

                <span>
                    Program
                </span>

                <strong>

                    <?= e($student['program_name']) ?>

                    (<?= e($student['program_code']) ?>)

                </strong>

            </div>


            <div>

                <span>
                    Year of Study
                </span>

                <strong>

                    Year
                    <?= e(
                        (string)$student['year_of_study']
                    ) ?>

                </strong>

            </div>


        </div>

    </section>


    <!-- =================================================
         CURRENT SEMESTER
         ================================================= -->

    <section class="dashboard-card">

        <h2>
            Current Registration
        </h2>


        <?php if ($current_semester): ?>


            <div class="semester-info">

                <h3>

                    <?= e(
                        $current_semester['name']
                    ) ?>

                    -

                    <?= e(
                        $current_semester['academic_year']
                    ) ?>

                </h3>


                <p>

                    Semester Period:

                    <?= e(
                        $current_semester['start_date']
                    ) ?>

                    to

                    <?= e(
                        $current_semester['end_date']
                    ) ?>

                </p>


                <p class="registration-open">

                    Course Registration is OPEN

                </p>


                <p>

                    Courses Registered:

                    <strong>
                        <?= $registered_count ?>
                    </strong>

                </p>


                <a
                    href="register_courses.php"
                    class="btn btn-primary"
                >
                    Register Courses
                </a>

            </div>


        <?php else: ?>


            <div class="registration-closed">

                <h3>
                    Registration Closed
                </h3>


                <p>

                    There is currently no semester
                    open for course registration.

                </p>

            </div>


        <?php endif; ?>


    </section>


    <!-- =================================================
         REGISTERED COURSES
         ================================================= -->

    <?php if ($current_semester): ?>


        <section class="dashboard-card">

            <h2>
                My Registered Courses
            </h2>


            <?php if (!empty($registered_courses)): ?>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

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

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $registered_courses
                                as $course
                            ): ?>

                                <tr>

                                    <td>

                                        <?= e(
                                            $course['course_code']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $course['course_name']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            (string)$course['credit_hours']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            ucfirst(
                                                (string)$course['status']
                                            )
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty-state">

                    <p>

                        You have not registered for
                        any courses for the current
                        semester.

                    </p>


                    <a
                        href="register_courses.php"
                        class="btn btn-primary"
                    >
                        Register Courses
                    </a>

                </div>


            <?php endif; ?>


        </section>


    <?php endif; ?>


</main>


</body>

</html>