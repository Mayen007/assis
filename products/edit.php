<?php
/**
 * Product & Warranty Editor
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$productIdInput = $_GET['id'] ?? $_POST['product_id'] ?? 0;
$productId = is_scalar($productIdInput) ? (int)$productIdInput : 0;
$db = get_db();
$stmt = $db->prepare('
    SELECT p.*, w.StartDate AS WarrantyStart, w.EndDate AS WarrantyEnd, w.Terms AS WarrantyTerms
    FROM PRODUCT p
    LEFT JOIN WARRANTY w ON p.ProductID = w.ProductID
    WHERE p.ProductID = :id
    ORDER BY w.WarrantyID DESC
    LIMIT 1
');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if ($productId <= 0 || !$product) {
    set_flash('danger', 'Product not found.');
    header('Location: ' . base_url('products/index.php'));
    exit;
}

$customers = $db->query('SELECT CustomerID, FullName, PhoneNumber FROM CUSTOMER ORDER BY FullName ASC')->fetchAll();
$error = null;
$customerId = (int)$product['CustomerID'];
$productName = $product['ProductName'];
$model = $product['Model'];
$serialNumber = $product['SerialNumber'];
$purchaseDate = $product['PurchaseDate'] ?? '';
$includeWarranty = $product['WarrantyStart'] !== null;
$warrantyStart = $product['WarrantyStart'] ?? '';
$warrantyEnd = $product['WarrantyEnd'] ?? '';
$warrantyTerms = $product['WarrantyTerms'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $customerIdInput = $_POST['customer_id'] ?? 0;
        $customerId = is_scalar($customerIdInput) ? (int)$customerIdInput : 0;
        $productName = is_string($_POST['product_name'] ?? null) ? trim($_POST['product_name']) : '';
        $model = is_string($_POST['model'] ?? null) ? trim($_POST['model']) : '';
        $serialNumber = is_string($_POST['serial_number'] ?? null) ? trim($_POST['serial_number']) : '';
        $purchaseDate = is_string($_POST['purchase_date'] ?? null) ? trim($_POST['purchase_date']) : '';
        $includeWarranty = !empty($_POST['include_warranty']);
        $warrantyStart = is_string($_POST['warranty_start'] ?? null) ? trim($_POST['warranty_start']) : '';
        $warrantyEnd = is_string($_POST['warranty_end'] ?? null) ? trim($_POST['warranty_end']) : '';
        $warrantyTerms = is_string($_POST['warranty_terms'] ?? null) ? trim($_POST['warranty_terms']) : '';

        if ($customerId <= 0 || $productName === '' || $model === '' || $serialNumber === '') {
            $error = 'Customer, Product Name, Model, and Serial Number are required.';
        } elseif (!array_filter($customers, static fn($customer) => (int)$customer['CustomerID'] === $customerId)) {
            $error = 'Select a valid customer.';
        } elseif ($purchaseDate !== '' && !is_valid_date($purchaseDate)) {
            $error = 'Enter a valid purchase date.';
        } elseif (
            $includeWarranty &&
            (!is_valid_date($warrantyStart) || !is_valid_date($warrantyEnd) || $warrantyEnd < $warrantyStart)
        ) {
            $error = 'Enter valid warranty dates, with the expiry date on or after the start date.';
        } else {
            $check = $db->prepare('
                SELECT ProductID FROM PRODUCT
                WHERE SerialNumber = :serial_number AND ProductID <> :id
                LIMIT 1
            ');
            $check->execute([':serial_number' => $serialNumber, ':id' => $productId]);

            if ($check->fetch()) {
                $error = 'Another product is already registered with this serial number.';
            } else {
                try {
                    $db->beginTransaction();
                    $update = $db->prepare('
                        UPDATE PRODUCT
                        SET CustomerID = :customer_id,
                            ProductName = :product_name,
                            Model = :model,
                            SerialNumber = :serial_number,
                            PurchaseDate = :purchase_date
                        WHERE ProductID = :id
                    ');
                    $update->execute([
                        ':customer_id' => $customerId,
                        ':product_name' => $productName,
                        ':model' => $model,
                        ':serial_number' => $serialNumber,
                        ':purchase_date' => $purchaseDate ?: null,
                        ':id' => $productId,
                    ]);

                    if ($includeWarranty) {
                        $existingWarranty = $db->prepare('
                            SELECT WarrantyID FROM WARRANTY
                            WHERE ProductID = :product_id
                            LIMIT 1
                        ');
                        $existingWarranty->execute([':product_id' => $productId]);
                        $hasExistingWarranty = (bool)$existingWarranty->fetchColumn();
                        $warrantyUpdate = $db->prepare('
                            UPDATE WARRANTY
                            SET StartDate = :start_date, EndDate = :end_date, Terms = :terms
                            WHERE ProductID = :product_id
                        ');
                        $warrantyUpdate->execute([
                            ':start_date' => $warrantyStart,
                            ':end_date' => $warrantyEnd,
                            ':terms' => $warrantyTerms ?: null,
                            ':product_id' => $productId,
                        ]);
                        if (!$hasExistingWarranty) {
                            $warrantyInsert = $db->prepare('
                                INSERT INTO WARRANTY (ProductID, StartDate, EndDate, Terms)
                                VALUES (:product_id, :start_date, :end_date, :terms)
                            ');
                            $warrantyInsert->execute([
                                ':product_id' => $productId,
                                ':start_date' => $warrantyStart,
                                ':end_date' => $warrantyEnd,
                                ':terms' => $warrantyTerms ?: null,
                            ]);
                        }
                    } else {
                        $warrantyDelete = $db->prepare('DELETE FROM WARRANTY WHERE ProductID = :product_id');
                        $warrantyDelete->execute([':product_id' => $productId]);
                    }

                    $db->commit();
                    set_flash('success', 'Product details updated successfully.');
                    header('Location: ' . base_url('customers/view.php?id=' . $customerId));
                    exit;
                } catch (PDOException $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('Product update failed: ' . $e->getMessage());
                    $error = 'Unable to update product details because of a database error.';
                }
            }
        }
    }
}

function is_valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

$pageTitle = 'Edit Product';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Edit Equipment</h3>
                <p class="text-muted small mb-0">Update product, owner, and warranty details.</p>
            </div>
            <a href="<?= base_url('customers/view.php?id=' . $product['CustomerID']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Customer
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="card card-assis">
            <div class="card-assis-header">
                <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i> Equipment Information</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= $productId ?>">

                    <div class="mb-3">
                        <label class="form-label" for="customer_id">Customer / Owner <span class="text-danger">*</span></label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">-- Choose Customer --</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['CustomerID'] ?>" <?= $customerId === (int)$customer['CustomerID'] ? 'selected' : '' ?>>
                                    <?= e($customer['FullName']) ?> (<?= e($customer['PhoneNumber']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Changing the owner also updates the customer linked to this product’s service requests.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="product_name">Product Name / Type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="product_name" name="product_name" maxlength="100" required value="<?= e($productName) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="model">Model Number / Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="model" name="model" maxlength="100" required value="<?= e($model) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="serial_number">Serial Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="serial_number" name="serial_number" maxlength="100" required value="<?= e($serialNumber) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="purchase_date">Date of Purchase</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?= e($purchaseDate) ?>">
                        </div>
                    </div>

                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="include_warranty" name="include_warranty" value="1" <?= $includeWarranty ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold" for="include_warranty">Include Warranty Information</label>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="warranty_start">Warranty Start Date</label>
                                <input type="date" class="form-control" id="warranty_start" name="warranty_start" value="<?= e($warrantyStart) ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="warranty_end">Warranty Expiry Date</label>
                                <input type="date" class="form-control" id="warranty_end" name="warranty_end" value="<?= e($warrantyEnd) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="warranty_terms">Terms & Coverage Conditions</label>
                                <textarea class="form-control" id="warranty_terms" name="warranty_terms" rows="2"><?= e($warrantyTerms) ?></textarea>
                            </div>
                        </div>
                        <div class="form-text">Turning warranty information off removes the warranty from this product.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('customers/view.php?id=' . $product['CustomerID']) ?>" class="btn btn-light border">Cancel</a>
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
