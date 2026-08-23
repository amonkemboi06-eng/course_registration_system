<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token.';
    }

    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));

    if ($name === '') {
        $errors[] = 'Department name is required.';
    }

    if ($code === '') {
        $errors[] = 'Department code is required.';
    }

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                'INSERT INTO departments (name, code)
                 VALUES (:name, :code)'
            );

            $stmt->execute([
                'name' => $name,
                'code' => $code
            ]);

            $success = 'Department added successfully.';

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] =
                    'Department name or code already exists.';

            } else {

                error_log($e->getMessage());

                $errors[] =
                    'Unable to add department.';
            }
        }
    }
}

$stmt = $pdo->query(
    'SELECT id, name, code, created_at
     FROM departments
     ORDER BY name'
);

$departments = $stmt->fetchAll();

?>

<?php require __DIR__ . '/../includes/admin_header.php'; ?>

<h1 class="page-title">
    Department Management
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


<div class="card">

    <h2>Add Department</h2>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <div class="form-group">

            <label for="name">
                Department Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                required
            >

        </div>


        <div class="form-group">

            <label for="code">
                Department Code
            </label>

            <input
                type="text"
                id="code"
                name="code"
                maxlength="20"
                required
            >

        </div>


        <button
            type="submit"
            class="btn btn-success"
        >
            Add Department
        </button>

    </form>

</div>


<div class="table-container">

    <h2>Existing Departments</h2>

    <table>

        <thead>

            <tr>

                <th>ID</th>

                <th>Department</th>

                <th>Code</th>

                <th>Created</th>

            </tr>

        </thead>

        <tbody>

        <?php if (empty($departments)): ?>

            <tr>

                <td colspan="4">
                    No departments found.
                </td>

            </tr>

        <?php else: ?>

            <?php foreach ($departments as $department): ?>

                <tr>

                    <td>
                        <?= e((string)$department['id']) ?>
                    </td>

                    <td>
                        <?= e($department['name']) ?>
                    </td>

                    <td>

                        <span class="badge badge-gold">

                            <?= e($department['code']) ?>

                        </span>

                    </td>

                    <td>
                        <?= e($department['created_at']) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>