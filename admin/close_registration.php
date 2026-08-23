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
| Close Registration
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'UPDATE semesters
     SET registration_open = 0
     WHERE id = :id'
);

$stmt->execute([
    'id' => $semester_id
]);


header(
    'Location: semesters.php?closed=1'
);

exit;