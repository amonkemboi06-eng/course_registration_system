<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student');

$errors = [];
$success = '';

$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Get Student Information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        id,
        full_name,
        registration_number,
        username,
        email,
        phone,
        profile_photo
     FROM users
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $user_id
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    exit('Student account not found.');
}


/*
|--------------------------------------------------------------------------
| Handle Photo Upload
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token.';
    }


    if (
        !isset($_FILES['profile_photo']) ||
        $_FILES['profile_photo']['error'] === UPLOAD_ERR_NO_FILE
    ) {

        $errors[] = 'Please select a photo to upload.';

    } elseif (
        $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK
    ) {

        $errors[] = 'Photo upload failed. Please try again.';

    } else {

        $file = $_FILES['profile_photo'];

        /*
        |--------------------------------------------------------------------------
        | Maximum File Size: 3MB
        |--------------------------------------------------------------------------
        */

        if ($file['size'] > 3 * 1024 * 1024) {

            $errors[] =
                'Photo must not be larger than 3MB.';
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Real MIME Type
        |--------------------------------------------------------------------------
        */

        $allowed_types = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $mime_type = $finfo->file(
            $file['tmp_name']
        );

        if (
            !array_key_exists(
                $mime_type,
                $allowed_types
            )
        ) {

            $errors[] =
                'Only JPG, PNG and WebP images are allowed.';
        }


        /*
        |--------------------------------------------------------------------------
        | Save Photo
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            $extension =
                $allowed_types[$mime_type];

            $filename =
                'student_' .
                $user_id .
                '_' .
                bin2hex(random_bytes(8)) .
                '.' .
                $extension;

            $upload_directory =
                __DIR__ .
                '/../uploads/profile_photos/';

            /*
            |--------------------------------------------------------------------------
            | Create Directory If Missing
            |--------------------------------------------------------------------------
            */

            if (!is_dir($upload_directory)) {

                mkdir(
                    $upload_directory,
                    0755,
                    true
                );
            }

            $destination =
                $upload_directory .
                $filename;


            if (
                move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )
            ) {

                /*
                |--------------------------------------------------------------------------
                | Delete Old Photo
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $student['profile_photo']
                    )
                ) {

                    $old_photo =
                        $upload_directory .
                        basename(
                            $student['profile_photo']
                        );

                    if (
                        is_file($old_photo)
                    ) {

                        unlink($old_photo);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Update Database
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET profile_photo = :photo
                     WHERE id = :id'
                );

                $stmt->execute([
                    'photo' => $filename,
                    'id' => $user_id
                ]);

                $student['profile_photo'] =
                    $filename;

                $success =
                    'Profile photo updated successfully.';

            } else {

                $errors[] =
                    'Unable to save the uploaded photo.';
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

>

<title>My Profile | Course Registration System</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f5f5;
    color: #222;
}

.profile-container {
    max-width: 750px;
    margin: 40px auto;
    padding: 20px;
}

.profile-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    border-top: 6px solid #d4af37;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.profile-header {
    text-align: center;
    margin-bottom: 25px;
}

.profile-header h1 {
    margin: 0;
    color: #111;
}

.profile-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
    margin: 20px auto;
    border: 4px solid #d4af37;
}

.default-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: #222;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 20px auto;
    font-size: 55px;
    font-weight: bold;
    border: 4px solid #d4af37;
}

.student-info {
    margin: 25px 0;
}

.student-info p {
    padding: 12px;
    background: #f8f8f8;
    border-left: 4px solid #d4af37;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.btn {
    display: inline-block;
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    background: #1976d2;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
    font-size: 15px;
}

.btn:hover {
    background: #168a45;
}

.btn-secondary {
    background: #555;
}

.btn-secondary:hover {
    background: #222;
}

.alert {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 6px;
}

.alert-error {
    background: #ffeaea;
    color: #b71c1c;
    border-left: 4px solid #c62828;
}

.alert-success {
    background: #eaf8ef;
    color: #146c36;
    border-left: 4px solid #168a45;
}

@media (max-width: 600px) {

    .profile-container {
        margin: 20px auto;
    }

    .profile-card {
        padding: 20px;
    }

}

</style>

</head>

<body>

<div class="profile-container">

<div class="profile-card">

<div class="profile-header">

<h1>My Profile</h1>

<?php if (!empty($student['profile_photo'])): ?>

```
<img
    src="../uploads/profile_photos/<?= e($student['profile_photo']) ?>"
    alt="Profile Photo"
    class="profile-photo"
>
```

<?php else: ?>

```
<div class="default-avatar">

    <?= e(
        strtoupper(
            substr(
                $student['full_name'] ?? 'S',
                0,
                1
            )
        )
    ) ?>

</div>
```

<?php endif; ?>

</div>

<?php foreach ($errors as $error): ?>

<div class="alert alert-error">
    <?= e($error) ?>
</div>

<?php endforeach; ?>

<?php if ($success !== ''): ?>

<div class="alert alert-success">
    <?= e($success) ?>
</div>

<?php endif; ?>

<div class="student-info">

<p>
    <strong>Name:</strong>
    <?= e($student['full_name']) ?>
</p>

<p>
    <strong>Registration Number:</strong>
    <?= e($student['registration_number']) ?>
</p>

<p>
    <strong>Email:</strong>
    <?= e($student['email']) ?>
</p>

<p>
    <strong>Username:</strong>
    <?= e($student['username']) ?>
</p>

</div>

<form
    method="POST"
    enctype="multipart/form-data"
>

<input
type="hidden"
name="csrf_token"
value="<?= e(csrf_token()) ?>"

>

<div class="form-group">

<label for="profile_photo">
    Upload Profile Photo
</label>

<input
type="file"
id="profile_photo"
name="profile_photo"
accept=".jpg,.jpeg,.png,.webp"
required

>

</div>

<button
type="submit"
class="btn"

>

```
Upload Photo
```

</button>

<a
href="dashboard.php"
class="btn btn-secondary"

>

```
Back to Dashboard
```

</a>

</form>

</div>

</div>

</body>

</html>
