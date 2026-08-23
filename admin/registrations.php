<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');


/*
|--------------------------------------------------------------------------
| Get Course Registrations
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        cr.id,
        cr.status,
        cr.registered_at,

        s.registration_number,
        s.first_name,
        s.last_name,

        p.name AS program_name,
        p.code AS program_code,

        c.course_code,
        c.course_name,
        c.credit_hours,

        sem.name AS semester_name,
        sem.academic_year

     FROM course_registrations cr

     INNER JOIN students s
        ON cr.student_id = s.id

     INNER JOIN programs p
        ON s.program_id = p.id

     INNER JOIN courses c
        ON cr.course_id = c.id

     INNER JOIN semesters sem
        ON cr.semester_id = sem.id

     ORDER BY cr.registered_at DESC'
);

$registrations = $stmt->fetchAll();

?>


<?php require __DIR__ . '/../includes/admin_header.php'; ?>


<h1 class="page-title">
    Course Registrations
</h1>


<div class="card">

    <h2>
        Student Course Registrations
    </h2>

    <p>
        View all courses registered by students.
    </p>

</div>


<div class="table-container">

    <?php if (empty($registrations)): ?>

        <p>
            No course registrations have been made yet.
        </p>

    <?php else: ?>

        <table>

            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Student
                    </th>

                    <th>
                        Registration Number
                    </th>

                    <th>
                        Program
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
                        Semester
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

                <?php foreach ($registrations as $index => $registration): ?>
                    <tr>

                        <td>

                         <?= $index + 1 ?>

                        </td>


                        <td>

                            <strong>

                                <?= e(
                                    $registration['first_name']
                                ) ?>

                                <?= e(
                                    $registration['last_name']
                                ) ?>

                            </strong>

                        </td>


                        <td>

                            <?= e(
                                $registration['registration_number']
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $registration['program_code']
                            ) ?>

                            <br>

                            <small>

                                <?= e(
                                    $registration['program_name']
                                ) ?>

                            </small>

                        </td>


                        <td>

                            <strong>

                                <?= e(
                                    $registration['course_code']
                                ) ?>

                            </strong>

                        </td>


                        <td>

                            <?= e(
                                $registration['course_name']
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                (string)$registration['credit_hours']
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $registration['semester_name']
                            ) ?>

                            <br>

                            <small>

                                <?= e(
                                    $registration['academic_year']
                                ) ?>

                            </small>

                        </td>


                       <td>

    <?php if ($registration['status'] === 'registered'): ?>

        <span class="badge">
            Registered
        </span>

        <br><br>

        <a
            href="approve_registrations.php?id=<?= e((string)$registration['id']) ?>"
            class="btn btn-success"
            onclick="return confirm('Approve this course registration?');"
        >
            Approve
        </a>

    <?php elseif ($registration['status'] === 'approved'): ?>

        <span class="badge badge-green">
            Approved
        </span>

    <?php elseif ($registration['status'] === 'pending'): ?>

        <span class="badge badge-red">
            Pending
        </span>

    <?php else: ?>

        <span class="badge">
            <?= e(
                ucfirst(
                    (string)$registration['status']
                )
            ) ?>
        </span>

    <?php endif; ?>

</td>


                        <td>

                            <?= e(
                                date(
                                    'd M Y H:i',
                                    strtotime(
                                        $registration['registered_at']
                                    )
                                )
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php endif; ?>

</div>


<div class="card">

    <a
        href="dashboard.php"
        class="btn"
    >
        Back to Dashboard
    </a>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>