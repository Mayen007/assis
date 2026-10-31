<?php
/**
 * Authentication & Role-Based Access Control (RBAC) Module
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Checks if a user is currently authenticated.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Retrieves the currently logged-in user details.
 */
function current_user(): ?array {
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
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access this page.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
}

/**
 * Checks if the current user possesses one of the allowed roles.
 * @param string|array $allowedRoles
 */
function has_role(string|array $allowedRoles): bool {
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
function require_role(string|array $allowedRoles): void {
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
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['UserID'];
    $_SESSION['username']      = $user['Username'];
    $_SESSION['full_name']     = $user['FullName'];
    $_SESSION['role']          = $user['Role'];
    $_SESSION['technician_id'] = $user['RelatedTechnicianID'] ?? null;
}

/**
 * Terminates user session.
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
