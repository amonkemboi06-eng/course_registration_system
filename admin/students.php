<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');


/*
|--------------------------------------------------------------------------
| Get All Students
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    'SELECT
        s.id,
        s.registration_number,
        s.first_name,
        s.last_name,
        s.year_of_study,
        p.name AS program_name,
        p.code AS program_code,
        u.username,
        u.email,
        u.phone,
        u.is_active
     FROM students s

     INNER JOIN users u
        ON s.user_id = u.id

     INNER JOIN programs p
        ON s.program_id = p.id

     ORDER BY s.id DESC'
);

$students = $stmt->fetchAll();

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>


<h1 class="page-title">
    Students
</h1>


<div class="card">

    <h2>
        Registered Students
    </h2>

    <p>
        View all students registered in the system.
    </p>

</div>


<div class="table-container">

    <?php if (empty($students)): ?>

        <p>
            No students have been registered yet.
        </p>

    <?php else: ?>

        <table>

            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Full Name
                    </th>

                    <th>
                        Registration Number
                    </th>

                    <th>
                        Username
                    </th>

                    <th>
                        Email
                    </th>

                    <th>
                        Phone
                    </th>

                    <th>
                        Program
                    </th>

                    <th>
                        Year
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php foreach ($students as $student): ?>

                    <tr>

                        <td>
                            <?= e((string)$student['id']) ?>
                        </td>


                        <td>

                            <strong>

                                <?= e($student['first_name']) ?>

                                <?= e($student['last_name']) ?>

                            </strong>

                        </td>


                        <td>

                            <?= e(
                                $student['registration_number']
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $student['username']
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $student['email']
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $student['phone'] ?? ''
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $student['program_name']
                            ) ?>

                            <br>

                            <small>

                                <?= e(
                                    $student['program_code']
                                ) ?>

                            </small>

                        </td>


                        <td>

                            Year
                            <?= e(
                                (string)$student['year_of_study']
                            ) ?>

                        </td>


                        <td>

                            <?php if (
                                (int)$student['is_active'] === 1
                            ): ?>

                                <span class="badge badge-green">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="badge badge-red">
                                    Disabled
                                </span>

                            <?php endif; ?>

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