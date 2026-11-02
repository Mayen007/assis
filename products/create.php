<?php
/**
 * Product & Warranty Registration Interface
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$db = get_db();
$selectedCustomerId = (int)($_GET['customer_id'] ?? 0);
$returnToRequest = !empty($_GET['return_to_request']) || !empty($_POST['return_to_request']);
$returnRequestUrl = base_url('requests/create.php?' . http_build_query([
    'customer_id' => $selectedCustomerId,
    'return_from_product' => 1,
]));
$error = null;

// Fetch all customers for dropdown
$customers = $db->query('SELECT CustomerID, FullName, PhoneNumber FROM CUSTOMER ORDER BY FullName ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $productName = trim($_POST['product_name'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $serialNumber = trim($_POST['serial_number'] ?? '');
        $purchaseDate = trim($_POST['purchase_date'] ?? '') ?: null;

        // Warranty fields
        $hasWarranty = !empty($_POST['include_warranty']);
        $warrantyStart = trim($_POST['warranty_start'] ?? '');
        $warrantyEnd = trim($_POST['warranty_end'] ?? '');
        $warrantyTerms = trim($_POST['warranty_terms'] ?? '');

        if ($customerId <= 0 || $productName === '' || $model === '' || $serialNumber === '') {
            $error = 'Customer, Product Name, Model, and Serial Number are required.';
        } else {
            try {
                // Check serial number uniqueness
                $check = $db->prepare('SELECT ProductID FROM PRODUCT WHERE SerialNumber = :sn LIMIT 1');
                $check->execute([':sn' => $serialNumber]);
                if ($check->fetch()) {
                    $error = 'A product with this serial number is already registered in the system.';
                } else {
                    $db->beginTransaction();

                    $stmt = $db->prepare('
                        INSERT INTO PRODUCT (CustomerID, ProductName, Model, SerialNumber, PurchaseDate)
                        VALUES (:cid, :name, :model, :sn, :pdate)
                    ');
                    $stmt->execute([
                        ':cid'   => $customerId,
                        ':name'  => $productName,
                        ':model' => $model,
                        ':sn'    => $serialNumber,
                        ':pdate' => $purchaseDate,
                    ]);
                    $productId = $db->lastInsertId();

                    if ($hasWarranty && $warrantyStart !== '' && $warrantyEnd !== '') {
                        $wStmt = $db->prepare('
                            INSERT INTO WARRANTY (ProductID, StartDate, EndDate, Terms)
                            VALUES (:pid, :start, :end, :terms)
                        ');
                        $wStmt->execute([
                            ':pid'   => $productId,
                            ':start' => $warrantyStart,
                            ':end'   => $warrantyEnd,
                            ':terms' => $warrantyTerms ?: null,
                        ]);
                    }

                    $db->commit();
                    if ($returnToRequest) {
                        set_flash('success', 'Product registered. Continue creating the service request.');
                        $returnUrl = base_url('requests/create.php?' . http_build_query([
                            'customer_id' => $customerId,
                            'product_id' => $productId,
                            'return_from_product' => 1,
                        ]));
                        header('Location: ' . $returnUrl);
                        exit;
                    }

                    set_flash('success', 'Product registered and linked to customer successfully.');
                    header('Location: ' . base_url('customers/view.php?id=' . $customerId));
                    exit;
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'Registration error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Register Customer Equipment';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Register Equipment</h3>
                <p class="text-muted small mb-0">Associate product details and warranty coverage with an existing customer.</p>
            </div>
            <div>
                <a href="<?= e($returnToRequest ? $returnRequestUrl : base_url('products/index.php')) ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-md-inline"><?= $returnToRequest ? 'Back to Service Request' : 'Back to Equipment' ?></span>
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
                <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i> Product Information</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <?php if ($returnToRequest): ?>
                        <input type="hidden" name="return_to_request" value="1">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="customer_id">Select Customer <span class="text-danger">*</span></label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">-- Choose Customer --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['CustomerID'] ?>" <?= ($selectedCustomerId === (int)$c['CustomerID']) ? 'selected' : '' ?>>
                                    <?= e($c['FullName']) ?> (<?= e($c['PhoneNumber']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="product_name">Product Name / Type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="product_name" name="product_name" placeholder="e.g. Smart LED Television 55-inch" required value="<?= e($_POST['product_name'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="model">Model Number / Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="model" name="model" placeholder="e.g. LG-55UN7300" required value="<?= e($_POST['model'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="serial_number">Serial Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="serial_number" name="serial_number" placeholder="e.g. SN-LG55-88319" required value="<?= e($_POST['serial_number'] ?? '') ?>">
                            <div class="form-text">Must be unique for asset identification.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="purchase_date">Date of Purchase</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?= e($_POST['purchase_date'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Warranty Details Section -->
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="include_warranty" name="include_warranty" value="1" <?= !empty($_POST['include_warranty']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold" for="include_warranty">
                                <i class="bi bi-shield-check text-primary me-1"></i> Include Warranty Information
                            </label>
                        </div>

                        <div class="row g-3" id="warrantyFields">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="warranty_start">Warranty Start Date</label>
                                <input type="date" class="form-control" id="warranty_start" name="warranty_start" value="<?= e($_POST['warranty_start'] ?? date('Y-m-d')) ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="warranty_end">Warranty Expiry Date</label>
                                <input type="date" class="form-control" id="warranty_end" name="warranty_end" value="<?= e($_POST['warranty_end'] ?? date('Y-m-d', strtotime('+1 year'))) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="warranty_terms">Terms & Coverage Conditions</label>
                                <textarea class="form-control" id="warranty_terms" name="warranty_terms" rows="2" placeholder="e.g. 12-month parts and labour manufacturer warranty."><?= e($_POST['warranty_terms'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= e($returnToRequest ? $returnRequestUrl : base_url('customers/index.php')) ?>" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Register Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
