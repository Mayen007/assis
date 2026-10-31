<?php
/**
 * Initial Administrator Account Setup Interface
 * Reference: sameera.md (Chapter Five, User & Access Control)
 * 
 * This interface is strictly accessible only when 0 users exist in the system,
 * allowing the first system administrator to securely configure their credentials.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$db = get_db();
$userCount = (int)$db->query('SELECT COUNT(*) FROM USERS')->fetchColumn();

// If an administrator already exists, close self-registration
if ($userCount > 0) {
    set_flash('info', 'System already initialized. Additional staff accounts must be created by the Administrator.');
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if ($fullName === '' || $username === '' || $password === '' || $confirmPassword === '') {
            $error = 'All fields are required.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be between 3 and 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            $error = 'Username can only contain letters, numbers, periods, hyphens, and underscores.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare('INSERT INTO USERS (Username, PasswordHash, FullName, Role, RelatedTechnicianID, IsActive) VALUES (:username, :hash, :fullname, :role, NULL, 1)');
                $stmt->execute([
                    ':username' => $username,
                    ':hash'     => $hash,
                    ':fullname' => $fullName,
                    ':role'     => 'Admin'
                ]);

                $adminId = (int)$db->lastInsertId();
                $adminUser = [
                    'UserID'              => $adminId,
                    'Username'            => $username,
                    'FullName'            => $fullName,
                    'Role'                => 'Admin',
                    'RelatedTechnicianID' => null,
                ];

                login_user($adminUser);
                set_flash('success', 'Administrator account created successfully! Welcome to ' . APP_SHORT_NAME . '.');
                header('Location: ' . base_url('dashboard.php'));
                exit;
            } catch (Exception $e) {
                $error = 'Account creation error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Initial Administrator Setup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Administrator Setup | <?= APP_SHORT_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .setup-card {
            width: 100%;
            max-width: 480px;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
    </style>
</head>
<body>

<div class="setup-card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3" style="width: 56px; height: 56px;">
            <i class="bi bi-shield-lock fs-3"></i>
        </div>
        <h4 class="fw-bold mb-1">System Setup</h4>
        <p class="text-muted small mb-0">Create the primary Administrator account</p>
    </div>

    <div class="alert alert-info py-2 small mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-1"></i> No users detected. Set up your administrator credentials to initialize the system.
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label" for="full_name">Full Name</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-card-heading"></i></span>
                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g. System Administrator" required autofocus value="<?= e($_POST['full_name'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-text">Letters, numbers, underscores, and dots only.</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-check2-circle"></i></span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
            </div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg fs-6">
                <i class="bi bi-person-check me-1"></i> Create Administrator &amp; Sign In
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

