<?php
/**
 * Customer Registration Interface
 * Reference: sameera.md (Chapter Five, Figure 5.3)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$error = null;
$fullName = '';
$phoneNumber = '';
$email = '';
$physicalAddress = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $physicalAddress = trim($_POST['physical_address'] ?? '');

        if ($fullName === '' || $phoneNumber === '') {
            $error = 'Full Name and Phone Number are required fields.';
        } else {
            try {
                $db = get_db();
                // Check if phone number already exists
                $check = $db->prepare('SELECT CustomerID FROM CUSTOMER WHERE PhoneNumber = :phone LIMIT 1');
                $check->execute([':phone' => $phoneNumber]);
                if ($check->fetch()) {
                    $error = 'A customer with this phone number is already registered.';
                } else {
                    $stmt = $db->prepare('
                        INSERT INTO CUSTOMER (FullName, PhoneNumber, Email, PhysicalAddress, DateRegistered)
                        VALUES (:full_name, :phone, :email, :address, NOW())
                    ');
                    $stmt->execute([
                        ':full_name' => $fullName,
                        ':phone'     => $phoneNumber,
                        ':email'     => $email ?: null,
                        ':address'   => $physicalAddress ?: null,
                    ]);

                    $newCustomerId = $db->lastInsertId();
                    set_flash('success', 'Customer record registered successfully.');
                    header('Location: ' . base_url('customers/view.php?id=' . $newCustomerId));
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Registration error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Register New Customer';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Customer Registration</h3>
                <p class="text-muted small mb-0">Record personal details to establish a new client account.</p>
            </div>
            <div>
                <a href="<?= base_url('customers/index.php') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-md-inline">Back to Directory</span>
                    <span class="d-md-none">Back</span>
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="card card-assis">
            <div class="card-assis-header">
                <h5 class="fw-bold mb-0"><i class="bi bi-person-lines-fill me-2"></i> Client Information</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="full_name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g. Grace Wanjiku" required value="<?= e($fullName) ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="phone_number">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="e.g. 0722123456" required value="<?= e($phoneNumber) ?>">
                            <div class="form-text">Must be unique per customer account.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email">Email Address (Optional)</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="e.g. grace.wanjiku@example.com" value="<?= e($email) ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="physical_address">Physical Address / Location</label>
                            <input type="text" class="form-control" id="physical_address" name="physical_address" placeholder="e.g. Kimathi Street, Nairobi" value="<?= e($physicalAddress) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('customers/index.php') ?>" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save Customer Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

