<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$program_id = (int)($_GET['id'] ?? 0);

if ($program_id <= 0) {
    http_response_code(400);
    exit('Invalid program ID.');
}


/*
|--------------------------------------------------------------------------
| Check Whether Program Has Courses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM courses
     WHERE program_id = :program_id'
);

$stmt->execute([
    'program_id' => $program_id
]);

$course_count = (int)$stmt->fetchColumn();

if ($course_count > 0) {

    header(
        'Location: programs.php?error=has_courses'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Delete Program
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'DELETE FROM programs
     WHERE id = :id'
);

$stmt->execute([
    'id' => $program_id
]);


header(
    'Location: programs.php?deleted=1'
);

exit;