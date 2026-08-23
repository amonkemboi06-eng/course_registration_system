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
| Close Other Open Semesters
|--------------------------------------------------------------------------
*/

$pdo->beginTransaction();

try {

    $stmt = $pdo->prepare(
        'UPDATE semesters
         SET registration_open = 0
         WHERE registration_open = 1
         AND id != :id'
    );

    $stmt->execute([
        'id' => $semester_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Open Selected Semester
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        'UPDATE semesters
         SET registration_open = 1
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $semester_id
    ]);


    $pdo->commit();

    header(
        'Location: semesters.php?opened=1'
    );

    exit;

} catch (Throwable $e) {

    $pdo->rollBack();

    error_log($e->getMessage());

    exit('Unable to open registration.');
}