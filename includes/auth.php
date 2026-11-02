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

function revoke_login_session(string $message, string $type = 'warning'): void {
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
    $_SESSION['last_activity'] = time();
}

/**
 * Returns a validation message for a password that violates the bcrypt policy.
 */
function password_policy_error(string $password): ?string {
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }

    if (strlen($password) > 72) {
        return 'Password must not exceed 72 bytes.';
    }

    return null;
}

/**
 * Starts a serialized login attempt and returns its locked rate-limit keys.
 * A null result means at least one key is currently locked.
 */
function begin_login_attempt(PDO $db, string $username, string $ipAddress): ?array {
    $keys = [
        hash('sha256', 'username:' . strtolower(trim($username))),
        hash('sha256', 'ip:' . $ipAddress),
    ];
    sort($keys, SORT_STRING);

    $db->exec("
        DELETE FROM LOGIN_ATTEMPTS
        WHERE WindowStartedAt < UTC_TIMESTAMP() - INTERVAL 1 DAY
          AND (LockedUntil IS NULL OR LockedUntil < UTC_TIMESTAMP())
    ");
    $db->beginTransaction();

    try {
        $insert = $db->prepare("
            INSERT INTO LOGIN_ATTEMPTS (AttemptKey, FailedAttempts, WindowStartedAt, LockedUntil)
            VALUES (:attempt_key, 0, UTC_TIMESTAMP(), NULL)
            ON DUPLICATE KEY UPDATE AttemptKey = VALUES(AttemptKey)
        ");
        $select = $db->prepare("
            SELECT FailedAttempts,
                   WindowStartedAt < UTC_TIMESTAMP() - INTERVAL 15 MINUTE AS WindowExpired,
                   LockedUntil > UTC_TIMESTAMP() AS IsLocked
            FROM LOGIN_ATTEMPTS
            WHERE AttemptKey = :attempt_key
            FOR UPDATE
        ");
        $reset = $db->prepare("
            UPDATE LOGIN_ATTEMPTS
            SET FailedAttempts = 0, WindowStartedAt = UTC_TIMESTAMP(), LockedUntil = NULL
            WHERE AttemptKey = :attempt_key
        ");

        foreach ($keys as $key) {
            $insert->execute([':attempt_key' => $key]);
            $select->execute([':attempt_key' => $key]);
            $attempt = $select->fetch();

            if ((int)$attempt['IsLocked'] === 1) {
                $db->rollBack();
                return null;
            }

            if ((int)$attempt['WindowExpired'] === 1) {
                $reset->execute([':attempt_key' => $key]);
            }
        }

        return $keys;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

/**
 * Commits a failed login count or clears the counters after successful login.
 */
function finish_login_attempt(PDO $db, array $keys, bool $isSuccessful): void {
    if ($isSuccessful) {
        $delete = $db->prepare('DELETE FROM LOGIN_ATTEMPTS WHERE AttemptKey = :attempt_key');
        foreach ($keys as $key) {
            $delete->execute([':attempt_key' => $key]);
        }
    } else {
        $select = $db->prepare('SELECT FailedAttempts FROM LOGIN_ATTEMPTS WHERE AttemptKey = :attempt_key FOR UPDATE');
        $update = $db->prepare("
            UPDATE LOGIN_ATTEMPTS
            SET FailedAttempts = :failed_attempts,
                LockedUntil = IF(:should_lock = 1, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE), NULL)
            WHERE AttemptKey = :attempt_key
        ");
        foreach ($keys as $key) {
            $select->execute([':attempt_key' => $key]);
            $failedAttempts = (int)$select->fetchColumn() + 1;
            $update->execute([
                ':failed_attempts' => $failedAttempts,
                ':should_lock' => $failedAttempts >= 5 ? 1 : 0,
                ':attempt_key' => $key,
            ]);
        }
    }

    $db->commit();
}

/**
 * Terminates user session.
 */
function logout_user(): void {
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
