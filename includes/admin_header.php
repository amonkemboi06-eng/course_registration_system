<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

require_role('admin');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Course Registration System</title>

  <link rel="stylesheet" href="/course_registration_system/assets/css/style.css?v=2">

</head>

<body>


<!-- =====================================================
     ADMIN HEADER
     ===================================================== -->

<header class="admin-navbar">

    <div class="admin-logo">
        COURSE REGISTRATION SYSTEM
    </div>

    <div class="admin-user">

        <span class="admin-welcome">
            Welcome,
            <?= e($_SESSION['username'] ?? 'Administrator') ?>
        </span>

        <a
            href="/course_registration_system/auth/logout.php"
            class="admin-logout"
        >
            Logout
        </a>

    </div>

</header>


<!-- =====================================================
     ADMIN SIDEBAR
     ===================================================== -->

<aside class="admin-sidebar">

    <a
        href="/course_registration_system/admin/dashboard.php"
        class="admin-nav-btn"
    >
        Dashboard
    </a>

    <a
        href="/course_registration_system/admin/departments.php"
        class="admin-nav-btn"
    >
        Departments
    </a>

    <a
        href="/course_registration_system/admin/programs.php"
        class="admin-nav-btn"
    >
        Programs
    </a>

    <a
        href="/course_registration_system/admin/courses.php"
        class="admin-nav-btn"
    >
        Courses
    </a>

    <a
        href="/course_registration_system/admin/semesters.php"
        class="admin-nav-btn"
    >
        Semesters
    </a>

    <a
        href="/course_registration_system/admin/registrations.php"
        class="admin-nav-btn"
    >
        Registrations
    </a>

</aside>


<!-- =====================================================
     MAIN CONTENT
     ===================================================== -->

<main class="admin-main-content">
