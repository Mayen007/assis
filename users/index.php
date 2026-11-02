<?php
/**
 * Staff User Account Management
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

$db = get_db();
$currentUser = current_user();

// Handle Password Reset POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    if (!verify_csrf()) {
        set_flash('danger', 'Security validation failed (invalid CSRF token). Please try again.');
        header('Location: ' . base_url('users/index.php'));
        exit;
    }

    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';

    if (($passwordError = password_policy_error($newPassword)) !== null) {
        set_flash('danger', $passwordError);
        header('Location: ' . base_url('users/index.php'));
        exit;
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE USERS SET PasswordHash = :hash WHERE UserID = :uid');
    $stmt->execute([':hash' => $hash, ':uid' => $targetUserId]);

    set_flash('success', 'Password successfully reset for the selected user account.');
    header('Location: ' . base_url('users/index.php'));
    exit;
}

// Handle Toggle Active/Inactive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!verify_csrf()) {
        set_flash('danger', 'Security validation failed (invalid CSRF token).');
        header('Location: ' . base_url('users/index.php'));
        exit;
    }

    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if ($targetUserId === (int)($currentUser['id'] ?? 0)) {
        set_flash('warning', 'You cannot deactivate your own active session account.');
        header('Location: ' . base_url('users/index.php'));
        exit;
    }

    $stmt = $db->prepare('UPDATE USERS SET IsActive = NOT IsActive WHERE UserID = :uid');
    $stmt->execute([':uid' => $targetUserId]);

    set_flash('info', 'User account active status updated.');
    header('Location: ' . base_url('users/index.php'));
    exit;
}

// Filters & Search
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$sql = '
    SELECT u.*, t.FullName AS TechnicianName, t.Specialization AS TechnicianSpec
    FROM USERS u
    LEFT JOIN TECHNICIAN t ON u.RelatedTechnicianID = t.TechnicianID
    WHERE 1=1
';
$params = [];

if ($search !== '') {
    $sql .= ' AND (u.FullName LIKE :full_name OR u.Username LIKE :username) ';
    $searchTerm = "%$search%";
    $params[':full_name'] = $searchTerm;
    $params[':username'] = $searchTerm;
}

if ($roleFilter !== '' && in_array($roleFilter, ['Admin', 'FrontDesk', 'Technician'])) {
    $sql .= ' AND u.Role = :role ';
    $params[':role'] = $roleFilter;
}

$sql .= ' ORDER BY u.UserID ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$pageTitle = 'Staff User Accounts';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Staff User Accounts</h3>
        <p class="text-muted small mb-0">Manage internal system users, authentication credentials, and role assignments.</p>
    </div>
    <div>
        <a href="<?= base_url('users/create.php') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center">
            <i class="bi bi-person-plus-fill"></i>
            <span class="d-none d-md-inline">Register New Staff User</span>
            <span class="d-md-none">New Staff</span>
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card card-assis mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name or username..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <select name="role" class="form-select">
                    <option value="">All Access Roles</option>
                    <option value="Admin" <?= $roleFilter === 'Admin' ? 'selected' : '' ?>>Admin (Manager)</option>
                    <option value="FrontDesk" <?= $roleFilter === 'FrontDesk' ? 'selected' : '' ?>>Front Desk Reception</option>
                    <option value="Technician" <?= $roleFilter === 'Technician' ? 'selected' : '' ?>>Service Technician</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter"></i> Filter</button>
                <?php if ($search !== '' || $roleFilter !== ''): ?>
                    <a href="<?= base_url('users/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card card-assis">
    <div class="table-responsive">
        <table class="table table-assis table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Linked Profile</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usersList)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No staff user accounts matching criteria found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td class="font-monospace fw-bold text-muted">#USR-<?= str_pad((string)$u['UserID'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td class="fw-semibold">
                                <?= e($u['FullName']) ?>
                                <?php if ($u['UserID'] === (int)($currentUser['id'] ?? 0)): ?>
                                    <span class="badge bg-light text-primary border ms-1 small">You</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($u['Username']) ?></code></td>
                            <td>
                                <?php if ($u['Role'] === 'Admin'): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1">
                                        <i class="bi bi-shield-lock me-1"></i> Admin
                                    </span>
                                <?php elseif ($u['Role'] === 'FrontDesk'): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle px-2 py-1">
                                        <i class="bi bi-headset me-1"></i> Front Desk
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                                        <i class="bi bi-wrench me-1"></i> Technician
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['Role'] === 'Technician'): ?>
                                    <?php if ($u['TechnicianName']): ?>
                                        <span class="small text-dark fw-medium">
                                            <i class="bi bi-link-45deg text-success"></i> <?= e($u['TechnicianName']) ?>
                                        </span>
                                        <div class="small text-muted" style="font-size: 0.75rem;"><?= e($u['TechnicianSpec']) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">
                                            <i class="bi bi-exclamation-circle me-1"></i> Unlinked
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['IsActive']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="bi bi-dash-circle me-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('d M Y', strtotime($u['CreatedAt'])) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#resetModal<?= $u['UserID'] ?>" title="Reset Password">
                                        <i class="bi bi-key"></i> <span class="d-none d-lg-inline">Password</span>
                                    </button>

                                    <?php if ($u['UserID'] !== (int)($currentUser['id'] ?? 0)): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Change status for user <?= e($u['Username']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $u['UserID'] ?>">
                                            <?php if ($u['IsActive']): ?>
                                                <button type="submit" class="btn btn-outline-warning" title="Deactivate Account">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-outline-success" title="Activate Account">
                                                    <i class="bi bi-person-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <!-- Password Reset Modal -->
                                <div class="modal fade text-start" id="resetModal<?= $u['UserID'] ?>" tabindex="-1" aria-labelledby="resetModalLabel<?= $u['UserID'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="user_id" value="<?= $u['UserID'] ?>">

                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold" id="resetModalLabel<?= $u['UserID'] ?>">
                                                        <i class="bi bi-key me-1 text-primary"></i> Reset Password for <?= e($u['Username']) ?>
                                                    </h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-3">Set a new password for staff member <strong><?= e($u['FullName']) ?></strong>.</p>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="new_password_<?= $u['UserID'] ?>">New Password</label>
                                                        <input type="password" name="new_password" id="new_password_<?= $u['UserID'] ?>" class="form-control" required minlength="8" maxlength="72" placeholder="Minimum 8 characters">
                                                        <div class="form-text">Use 8 or more characters; maximum 72 bytes.</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Save New Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>