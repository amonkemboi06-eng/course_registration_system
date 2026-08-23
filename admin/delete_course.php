<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$course_id = (int)($_GET['id'] ?? 0);

if ($course_id <= 0) {
    http_response_code(400);
    exit('Invalid course ID.');
}


/*
|--------------------------------------------------------------------------
| Check Course Exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT id
     FROM courses
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $course_id
]);

if (!$stmt->fetch()) {

    header(
        'Location: courses.php?error=not_found'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Course Registrations
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM course_registrations
     WHERE course_id = :course_id'
);

$stmt->execute([
    'course_id' => $course_id
]);

$registration_count =
    (int)$stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Prevent Deletion of Registered Course
|--------------------------------------------------------------------------
*/

if ($registration_count > 0) {

    header(
        'Location: courses.php?error=has_registrations'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Delete Course
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'DELETE FROM courses
     WHERE id = :id'
);

$stmt->execute([
    'id' => $course_id
]);


header(
    'Location: courses.php?deleted=1'
);

exit;