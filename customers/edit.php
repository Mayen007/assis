<?php
/**
 * Customer Details Editor
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$customerIdInput = $_GET['id'] ?? $_POST['customer_id'] ?? 0;
$customerId = is_scalar($customerIdInput) ? (int)$customerIdInput : 0;
$db = get_db();
$stmt = $db->prepare('SELECT * FROM CUSTOMER WHERE CustomerID = :id LIMIT 1');
$stmt->execute([':id' => $customerId]);
$customer = $stmt->fetch();

if ($customerId <= 0 || !$customer) {
    set_flash('danger', 'Customer not found.');
    header('Location: ' . base_url('customers/index.php'));
    exit;
}

$error = null;
$fullName = $customer['FullName'];
$phoneNumber = $customer['PhoneNumber'];
$email = $customer['Email'] ?? '';
$physicalAddress = $customer['PhysicalAddress'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $fullName = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
        $phoneNumber = is_string($_POST['phone_number'] ?? null) ? trim($_POST['phone_number']) : '';
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $physicalAddress = is_string($_POST['physical_address'] ?? null) ? trim($_POST['physical_address']) : '';

        if ($fullName === '' || $phoneNumber === '') {
            $error = 'Full Name and Phone Number are required.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } else {
            $check = $db->prepare('
                SELECT CustomerID FROM CUSTOMER
                WHERE PhoneNumber = :phone AND CustomerID <> :id
                LIMIT 1
            ');
            $check->execute([':phone' => $phoneNumber, ':id' => $customerId]);

            if ($check->fetch()) {
                $error = 'Another customer is already registered with this phone number.';
            } else {
                try {
                    $update = $db->prepare('
                        UPDATE CUSTOMER
                        SET FullName = :full_name,
                            PhoneNumber = :phone,
                            Email = :email,
                            PhysicalAddress = :address
                        WHERE CustomerID = :id
                    ');
                    $update->execute([
                        ':full_name' => $fullName,
                        ':phone' => $phoneNumber,
                        ':email' => $email ?: null,
                        ':address' => $physicalAddress ?: null,
                        ':id' => $customerId,
                    ]);

                    set_flash('success', 'Customer details updated successfully.');
                    header('Location: ' . base_url('customers/view.php?id=' . $customerId));
                    exit;
                } catch (PDOException $e) {
                    error_log('Customer update failed: ' . $e->getMessage());
                    $error = 'Unable to update customer details because of a database error.';
                }
            }
        }
    }
}

$pageTitle = 'Edit Customer';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Edit Customer</h3>
                <p class="text-muted small mb-0">Update contact details for <?= e($customer['FullName']) ?>.</p>
            </div>
            <a href="<?= base_url('customers/view.php?id=' . $customerId) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Profile
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="card card-assis">
            <div class="card-assis-header">
                <h5 class="fw-bold mb-0"><i class="bi bi-person-lines-fill me-2"></i> Customer Information</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="customer_id" value="<?= $customerId ?>">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="full_name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" maxlength="100" required value="<?= e($fullName) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="phone_number">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" maxlength="20" required value="<?= e($phoneNumber) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= e($email) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="physical_address">Physical Address / Location</label>
                            <input type="text" class="form-control" id="physical_address" name="physical_address" maxlength="150" value="<?= e($physicalAddress) ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('customers/view.php?id=' . $customerId) ?>" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
