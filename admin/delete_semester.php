<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$semester_id = (int)($_GET['id'] ?? 0);

if ($semester_id <= 0) {
    http_response_code(400);
    exit('Invalid semester ID.');
}


/*
|--------------------------------------------------------------------------
| Check Semester Exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT id
     FROM semesters
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $semester_id
]);

if (!$stmt->fetch()) {

    header(
        'Location: semesters.php?error=not_found'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Student Registrations
|--------------------------------------------------------------------------
|
| IMPORTANT:
| This assumes course_registrations has a semester_id column.
|
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM course_registrations
     WHERE semester_id = :semester_id'
);

$stmt->execute([
    'semester_id' => $semester_id
]);

$registration_count =
    (int)$stmt->fetchColumn();


if ($registration_count > 0) {

    header(
        'Location: semesters.php?error=has_registrations'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'DELETE FROM semesters
     WHERE id = :id'
);

$stmt->execute([
    'id' => $semester_id
]);


header(
    'Location: semesters.php?deleted=1'
);

exit;