<?php
/**
 * Register New Staff User
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

$db = get_db();
$error = '';

// Fetch all technicians for linking option
$technicians = $db->query('SELECT * FROM TECHNICIAN ORDER BY FullName ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Security validation failed (invalid CSRF token). Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $techOption = trim($_POST['tech_option'] ?? 'existing');
        $existingTechId = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
        $newTechSpec = trim($_POST['new_tech_specialization'] ?? '');
        $newTechPhone = trim($_POST['new_tech_phone'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($fullName === '' || $username === '' || $role === '' || $password === '') {
            $error = 'All required fields marked with an asterisk (*) must be completed.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,30}$/', $username)) {
            $error = 'Username must be 3-30 characters long and contain only lowercase letters, numbers, dots, dashes, or underscores.';
        } elseif (!in_array($role, ['Admin', 'FrontDesk', 'Technician'])) {
            $error = 'Invalid role selected.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters in length.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Check username uniqueness
            $stmt = $db->prepare('SELECT COUNT(*) FROM USERS WHERE LOWER(Username) = :u');
            $stmt->execute([':u' => $username]);
            if ((int)$stmt->fetchColumn() > 0) {
                $error = 'The username "' . e($username) . '" is already taken. Please choose another username.';
            } else {
                $relatedTechId = null;
                try {
                    $db->beginTransaction();

                    if ($role === 'Technician') {
                        if ($techOption === 'new') {
                            $spec = $newTechSpec !== '' ? $newTechSpec : 'General Diagnostics & Repair';
                            $tStmt = $db->prepare('
                                INSERT INTO TECHNICIAN (FullName, Specialization, Phone, Availability)
                                VALUES (:name, :spec, :phone, \'Available\')
                            ');
                            $tStmt->execute([
                                ':name'  => $fullName,
                                ':spec'  => $spec,
                                ':phone' => $newTechPhone ?: null,
                            ]);
                            $relatedTechId = (int)$db->lastInsertId();
                        } elseif ($techOption === 'existing' && $existingTechId) {
                            $tStmt = $db->prepare('SELECT TechnicianID FROM TECHNICIAN WHERE TechnicianID = :id FOR UPDATE');
                            $tStmt->execute([':id' => $existingTechId]);
                            $relatedTechId = $tStmt->fetchColumn();

                            if (!$relatedTechId) {
                                throw new RuntimeException('Select a valid technician profile for this account.');
                            }
                        } else {
                            throw new RuntimeException('A technician account must be linked to an existing or new technician profile.');
                        }
                    }

                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $insStmt = $db->prepare('
                        INSERT INTO USERS (Username, PasswordHash, FullName, Role, RelatedTechnicianID, IsActive)
                        VALUES (:username, :hash, :name, :role, :tech_id, :active)
                    ');
                    $insStmt->execute([
                        ':username' => $username,
                        ':hash'     => $passwordHash,
                        ':name'     => $fullName,
                        ':role'     => $role,
                        ':tech_id'  => $relatedTechId,
                        ':active'   => $isActive,
                    ]);

                    $db->commit();
                    set_flash('success', 'Staff account for "' . e($fullName) . '" (@' . e($username) . ') created successfully.');
                    header('Location: ' . base_url('users/index.php'));
                    exit;
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $error = 'Account creation error: ' . $e->getMessage();
                }
            }
        }
    }
}

$pageTitle = 'Register New Staff User';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Register New Staff User</h3>
                <p class="text-muted small mb-0">Create internal staff credentials with role-based access permissions.</p>
            </div>
            <div>
                <a href="<?= base_url('users/index.php') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-md-inline">Back to Staff Accounts</span>
                    <span class="d-md-none">Back</span>
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="card card-assis p-4">
            <form method="POST">
                <?= csrf_field() ?>

                <div class="row g-3">
                    <div class="col-12 col-md-7">
                        <label class="form-label" for="full_name">Staff Member Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g. Samuel Karanja" value="<?= e($_POST['full_name'] ?? '') ?>" required>
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label" for="username">Login Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">@</span>
                            <input type="text" name="username" id="username" class="form-control" placeholder="e.g. samuel.k" value="<?= e($_POST['username'] ?? '') ?>" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Lowercase letters, numbers, dot, or dash.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="role">System Access Role <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select" required onchange="toggleTechnicianSection()">
                            <option value="">-- Choose Access Role --</option>
                            <option value="Admin" <?= ($_POST['role'] ?? '') === 'Admin' ? 'selected' : '' ?>>Admin (Service Manager & System Administrator)</option>
                            <option value="FrontDesk" <?= ($_POST['role'] ?? '') === 'FrontDesk' ? 'selected' : '' ?>>Front Desk (Intake, Client & Ticket Registration)</option>
                            <option value="Technician" <?= ($_POST['role'] ?? '') === 'Technician' ? 'selected' : '' ?>>Technician (Repair Workbench & Job Logging)</option>
                        </select>
                    </div>

                    <!-- Technician Profile Link Section (Conditional) -->
                    <div id="techLinkSection" class="col-12 p-3 bg-light rounded-3 border" style="display: none;">
                        <h6 class="fw-bold mb-2 text-primary">
                            <i class="bi bi-wrench-adjustable me-1"></i> Specialist Profile Link
                        </h6>
                        <p class="small text-muted mb-3">Service technicians require an associated specialist profile to receive assigned jobs.</p>

                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tech_option" id="tech_opt_existing" value="existing" checked onchange="toggleTechSubOptions()">
                                <label class="form-check-label" for="tech_opt_existing">Link to existing specialist</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tech_option" id="tech_opt_new" value="new" onchange="toggleTechSubOptions()">
                                <label class="form-check-label" for="tech_opt_new">Create new specialist profile</label>
                            </div>
                        </div>

                        <div id="existingTechDiv">
                            <label class="form-label small" for="technician_id">Select Existing Technician Profile</label>
                            <select name="technician_id" id="technician_id" class="form-select">
                                <option value="">-- Select Specialist --</option>
                                <?php foreach ($technicians as $t): ?>
                                    <option value="<?= $t['TechnicianID'] ?>">
                                        <?= e($t['FullName']) ?> (<?= e($t['Specialization']) ?>) &bull; <?= e($t['Availability']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="newTechDiv" style="display: none;">
                            <div class="row g-2">
                                <div class="col-12 col-md-7">
                                    <label class="form-label small" for="new_tech_specialization">Area of Specialization</label>
                                    <input type="text" name="new_tech_specialization" id="new_tech_specialization" class="form-control form-control-sm" placeholder="e.g. Refrigeration & Cooling, Audio-Visual">
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label small" for="new_tech_phone">Contact Phone</label>
                                    <input type="text" name="new_tech_phone" id="new_tech_phone" class="form-control form-control-sm" placeholder="e.g. 0712345678">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="password">Initial Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required minlength="6">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                            <label class="form-check-label fw-medium" for="is_active">Activate account immediately upon creation</label>
                        </div>
                    </div>

                    <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-end">
                        <a href="<?= base_url('users/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-person-check-fill me-1"></i> Create Staff Account
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTechnicianSection() {
    const roleSelect = document.getElementById('role');
    const techSection = document.getElementById('techLinkSection');
    if (roleSelect.value === 'Technician') {
        techSection.style.display = 'block';
    } else {
        techSection.style.display = 'none';
    }
}

function toggleTechSubOptions() {
    const isNew = document.getElementById('tech_opt_new').checked;
    document.getElementById('newTechDiv').style.display = isNew ? 'block' : 'none';
    document.getElementById('existingTechDiv').style.display = isNew ? 'none' : 'block';
}

// Initialize on page load in case of postback
document.addEventListener('DOMContentLoaded', function() {
    toggleTechnicianSection();
    toggleTechSubOptions();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>