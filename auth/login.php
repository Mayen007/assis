<?php

/**
 * User Login Interface
 * Reference: sameera.md (Chapter Five, Figure 5.2)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$db = get_db();
$userCount = (int)$db->query('SELECT COUNT(*) FROM USERS')->fetchColumn();
if ($userCount === 0) {
    header('Location: ' . base_url('auth/register.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $usernameInput = $_POST['username'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $username = is_string($usernameInput) ? trim($usernameInput) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } elseif (strlen($username) > 50 || strlen($password) > 1024) {
            $error = 'Invalid username or password.';
        } else {
            try {
                $stmt = $db->prepare('SELECT * FROM USERS WHERE LOWER(Username) = LOWER(:username) AND IsActive = 1 LIMIT 1');
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch();
                $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
                $passwordMatches = password_verify($password, $user['PasswordHash'] ?? $dummyHash);

                if ($user && $passwordMatches) {
                    login_user($user);
                    set_flash('success', 'Welcome back, ' . $user['FullName'] . '!');
                    header('Location: ' . base_url('dashboard.php'));
                    exit;
                }

                $error = 'Invalid username or password.';
            } catch (Throwable $e) {
                error_log('Login authentication error: ' . $e->getMessage());
                $error = 'Authentication is temporarily unavailable. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | ASSIS</title>
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

        .login-card {
            width: 100%;
            max-width: 440px;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
    </style>
</head>

<body>

    <div class="login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3" style="width: 56px; height: 56px;">
                <i class="bi bi-tools fs-3"></i>
            </div>
            <h4 class="fw-bold mb-1">Welcome Back</h4>
            <p class="text-muted small mb-0">After-Sales Service Information System (ASSIS)</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" maxlength="50" placeholder="Enter your username" required autofocus value="<?= e(is_string($_POST['username'] ?? null) ? $_POST['username'] : '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" maxlength="1024" placeholder="Enter your password" required>
                </div>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg fs-6">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </div>
        </form>

        <div class="mt-4 pt-3 border-top text-center">
            <p class="text-muted small mb-0">Don't have an account? <span class="text-dark fw-medium">Contact Administrator</span></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>