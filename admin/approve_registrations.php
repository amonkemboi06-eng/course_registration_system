<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$registration_id = (int)($_GET['id'] ?? 0);

if ($registration_id <= 0) {
 header('Location: /course_registration_system/admin/registrations.php');
    exit;
}

$stmt = $pdo->prepare(
    'UPDATE course_registrations
     SET status = :status
     WHERE id = :id'
);

$stmt->execute([
    'status' => 'approved',
    'id' => $registration_id
]);

header('Location: /course_registration_system/admin/registrations.php');
exit;