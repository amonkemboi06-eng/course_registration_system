<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');


/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$total_students = (int)$pdo->query(
    "SELECT COUNT(*) FROM students"
)->fetchColumn();

$total_courses = (int)$pdo->query(
    "SELECT COUNT(*) FROM courses"
)->fetchColumn();

$total_semesters = (int)$pdo->query(
    "SELECT COUNT(*) FROM semesters"
)->fetchColumn();

$total_registrations = (int)$pdo->query(
    "SELECT COUNT(*) FROM course_registrations"
)->fetchColumn();


/*
|--------------------------------------------------------------------------
| Current Semester
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        name,
        academic_year,
        start_date,
        end_date,
        registration_open
     FROM semesters
     ORDER BY start_date DESC
     LIMIT 1"
);

$current_semester = $stmt->fetch();

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>


<h1 class="page-title">
    Admin Dashboard
</h1>


<div class="card">

    <h2>
        Welcome,
        <?= e($_SESSION['username'] ?? 'Administrator') ?>
    </h2>

    <p>
        Administrator Dashboard
    </p>

</div>


<!-- =====================================================
     SYSTEM SUMMARY
     ===================================================== -->

<div class="table-container">

    <h2>
        System Summary
    </h2>

    <table>

        <thead>

            <tr>
                <th>Category</th>
                <th>Total</th>
                <th>Action</th>
            </tr>

        </thead>


        <tbody>

            <tr>

                <td>
                    <strong>Students</strong>
                </td>

                <td>
                    <?= $total_students ?>
                </td>

                <td>

                    <a
                        href="students.php"
                        class="btn"
                    >
                        View Students
                    </a>

                </td>

            </tr>


            <tr>

                <td>
                    <strong>Courses</strong>
                </td>

                <td>
                    <?= $total_courses ?>
                </td>

                <td>

                    <a
                        href="courses.php"
                        class="btn"
                    >
                        Manage Courses
                    </a>

                </td>

            </tr>


            <tr>

                <td>
                    <strong>Semesters</strong>
                </td>

                <td>
                    <?= $total_semesters ?>
                </td>

                <td>

                    <a
                        href="semesters.php"
                        class="btn"
                    >
                        Manage Semesters
                    </a>

                </td>

            </tr>


            <tr>

                <td>
                    <strong>Course Registrations</strong>
                </td>

                <td>
                    <?= $total_registrations ?>
                </td>

                <td>

                    <a
                        href="registrations.php"
                        class="btn"
                    >
                        View Registrations
                    </a>

                </td>

            </tr>

        </tbody>

    </table>

</div>


<!-- =====================================================
     CURRENT SEMESTER
     ===================================================== -->

<div class="table-container">

    <h2>
        Current Semester
    </h2>


    <?php if ($current_semester): ?>

        <table>

            <tbody>

                <tr>

                    <th>
                        Semester
                    </th>

                    <td>
                        <?= e($current_semester['name']) ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Academic Year
                    </th>

                    <td>
                        <?= e($current_semester['academic_year']) ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Start Date
                    </th>

                    <td>
                        <?= e($current_semester['start_date']) ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        End Date
                    </th>

                    <td>
                        <?= e($current_semester['end_date']) ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Registration Status
                    </th>

                    <td>

                        <?php if (
                            (int)$current_semester['registration_open'] === 1
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

                </tr>

            </tbody>

        </table>


        <br>

        <a
            href="semesters.php"
            class="btn btn-success"
        >
            Manage Semester
        </a>


    <?php else: ?>

        <p>
            No semester has been created yet.
        </p>

        <a
            href="semesters.php"
            class="btn btn-success"
        >
            Create Semester
        </a>

    <?php endif; ?>

</div>


<!-- =====================================================
     QUICK ACTIONS
     ===================================================== -->

<div class="card">

    <h2>
        Quick Actions
    </h2>


    <p>
        <a
            href="courses.php"
            class="btn btn-success"
        >
            Add Course
        </a>


        <a
            href="semesters.php"
            class="btn"
        >
            Manage Semesters
        </a>


        <a
            href="students.php"
            class="btn"
        >
            View Students
        </a>


        <a
            href="registrations.php"
            class="btn"
        >
            View Registrations
        </a>
    </p>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>