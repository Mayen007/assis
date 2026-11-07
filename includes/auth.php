<?php

/**
 * Authentication & Role-Based Access Control (RBAC) Module
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Checks if a user is currently authenticated.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Retrieves the currently logged-in user details.
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id'            => $_SESSION['user_id'] ?? null,
        'user_id'       => $_SESSION['user_id'] ?? null,
        'username'      => $_SESSION['username'] ?? '',
        'full_name'     => $_SESSION['full_name'] ?? '',
        'role'          => $_SESSION['role'] ?? '',
        'technician_id' => $_SESSION['technician_id'] ?? null,
    ];
}

/**
 * Ensures the user is logged in, otherwise redirects to the login screen.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access this page.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }

    $now = time();
    if (isset($_SESSION['last_activity']) && $now - (int)$_SESSION['last_activity'] >= SESSION_IDLE_TIMEOUT) {
        revoke_login_session('Your session expired due to inactivity. Please sign in again.');
    }

    $db = get_db();
    $stmt = $db->prepare('
        SELECT Username, FullName, Role, RelatedTechnicianID, IsActive
        FROM USERS
        WHERE UserID = :user_id
        LIMIT 1
    ');
    $stmt->execute([':user_id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !(bool)$user['IsActive']) {
        revoke_login_session('Your account is inactive or no longer available. Please contact an administrator.');
    }

    if ($user['Role'] === 'Technician' && empty($user['RelatedTechnicianID'])) {
        revoke_login_session('Your technician account is not linked to a technician profile. Contact an administrator.', 'danger');
    }

    $_SESSION['username'] = $user['Username'];
    $_SESSION['full_name'] = $user['FullName'];
    $_SESSION['role'] = $user['Role'];
    $_SESSION['technician_id'] = $user['RelatedTechnicianID'];
    $_SESSION['last_activity'] = $now;
}

function revoke_login_session(string $message, string $type = 'warning'): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    set_flash($type, $message);
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

/**
 * Checks if the current user possesses one of the allowed roles.
 * @param string|array $allowedRoles
 */
function has_role(string|array $allowedRoles): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $currentRole = $_SESSION['role'] ?? '';
    if (is_array($allowedRoles)) {
        return in_array($currentRole, $allowedRoles, true);
    }

    return $currentRole === $allowedRoles;
}

/**
 * Enforces role restriction. Halts with 403 Forbidden if user lacks required role.
 * @param string|array $allowedRoles
 */
function require_role(string|array $allowedRoles): void
{
    require_login();

    if (!has_role($allowedRoles)) {
        http_response_code(403);
        set_flash('danger', 'Access denied. You do not have permission to view this resource.');
        header('Location: ' . base_url('dashboard.php'));
        exit;
    }
}

/**
 * Logs in a verified user by populating session variables.
 */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['UserID'];
    $_SESSION['username']      = $user['Username'];
    $_SESSION['full_name']     = $user['FullName'];
    $_SESSION['role']          = $user['Role'];
    $_SESSION['technician_id'] = $user['RelatedTechnicianID'] ?? null;
    $_SESSION['last_activity'] = time();
}

/**
 * Returns a validation message for a password that violates the bcrypt policy.
 */
function password_policy_error(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }

    if (strlen($password) > 72) {
        return 'Password must not exceed 72 bytes.';
    }

    return null;
}

/**
 * Terminates user session.
 */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}
