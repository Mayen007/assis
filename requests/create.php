<?php

/**
 * New Service Request Logging Interface
 * Reference: sameera.md (Chapter Five, Figure 5.4)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$db = get_db();
$error = null;

$selectedCustomerId = (int)($_GET['customer_id'] ?? 0);
$selectedProductId = (int)($_GET['product_id'] ?? 0);
$returnFromProduct = !empty($_GET['return_from_product']);
$registerProductQuery = ['return_to_request' => 1];
if ($selectedCustomerId > 0) {
    $registerProductQuery['customer_id'] = $selectedCustomerId;
}
$registerProductUrl = base_url('products/create.php?' . http_build_query($registerProductQuery));

// Fetch customers
$customers = $db->query('SELECT CustomerID, FullName, PhoneNumber, Email FROM CUSTOMER ORDER BY FullName ASC')->fetchAll();

// Fetch products
$products = $db->query('
    SELECT p.ProductID, p.CustomerID, p.ProductName, p.Model, p.SerialNumber,
           c.FullName AS CustomerName
    FROM PRODUCT p
    JOIN CUSTOMER c ON p.CustomerID = c.CustomerID
    ORDER BY p.ProductName ASC
')->fetchAll();

// Fetch available technicians (Available status and under active capacity)
$technicians = $db->query("
    SELECT t.TechnicianID, t.FullName, t.Specialization, t.Availability,
           COUNT(CASE WHEN sr.Status IN ('Pending', 'In Progress') THEN 1 END) AS ActiveJobs
    FROM TECHNICIAN t
    LEFT JOIN SERVICE_REQUEST sr ON t.TechnicianID = sr.TechnicianID
    WHERE t.Availability = 'Available'
    GROUP BY t.TechnicianID
    HAVING ActiveJobs < " . MAX_TECHNICIAN_ACTIVE_JOBS . "
    ORDER BY t.FullName ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $technicianId = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
        $priority = trim($_POST['priority'] ?? 'Medium');
        $issueDescription = trim($_POST['issue_description'] ?? '');

        if ($customerId <= 0 || $productId <= 0 || $issueDescription === '') {
            $error = 'Please select a Customer, select a Product, and provide the Problem Description.';
        } else {
            // Verify that the product exists and belongs to the selected customer
            $prodCheck = $db->prepare('SELECT ProductID FROM PRODUCT WHERE ProductID = :pid AND CustomerID = :cid LIMIT 1');
            $prodCheck->execute([':pid' => $productId, ':cid' => $customerId]);
            $productRow = $prodCheck->fetch();

            if (!$productRow) {
                $error = 'The selected product does not exist or is not registered to the selected customer.';
            } else {
                try {
                    // The technician is locked before the capacity check so the
                    // check and the INSERT that consumes a slot are atomic.
                    begin_assignment_transaction($db);

                    $assignmentError = null;
                    if ($technicianId) {
                        $assignmentError = validate_technician_assignment($db, $technicianId, $productId, $issueDescription);
                    }

                    if ($assignmentError !== null) {
                        $db->rollBack();
                        $error = $assignmentError;
                    } else {
                        // If technician assigned, status can start as 'In Progress', else 'Pending'
                        $initialStatus = $technicianId ? 'In Progress' : 'Pending';

                        $stmt = $db->prepare('
                            INSERT INTO SERVICE_REQUEST (CustomerID, ProductID, TechnicianID, DateLogged, IssueDescription, Priority, Status)
                            VALUES (:cid, :pid, :tid, NOW(), :desc, :priority, :status)
                        ');
                        $stmt->execute([
                            ':cid'      => $customerId,
                            ':pid'      => $productId,
                            ':tid'      => $technicianId,
                            ':desc'     => $issueDescription,
                            ':priority' => $priority,
                            ':status'   => $initialStatus,
                        ]);

                        $requestId = (int)$db->lastInsertId();

                        if ($technicianId) {
                            sync_technician_availability($db, $technicianId);
                        }

                        $db->commit();
                        set_flash('success', 'Service Request #SR' . str_pad((string)$requestId, 4, '0', STR_PAD_LEFT) . ' logged successfully.');
                        header('Location: ' . base_url('requests/view.php?id=' . $requestId));
                        exit;
                    }
                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $error = 'Failed to create service request: ' . $e->getMessage();
                }
            }
        }
    }
}

$pageTitle = 'Log Service Request';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Log New Service Request</h3>
                <p class="text-muted small mb-0">Record customer defect complaint, priority rating, and assign a specialist technician.</p>
            </div>
            <div>
                <a href="<?= base_url('requests/index.php') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-md-inline">Back to Requests</span>
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
                <h5 class="fw-bold mb-0"><i class="bi bi-card-text me-2"></i> Service Request Ticket</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <?= csrf_field() ?>

                    <!-- 1. Customer Selection -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2">1. Customer Information</h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="customer_id" name="customer_id" required onchange="filterProductsByCustomer(this.value)">
                                    <option value="">-- Select Registered Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['CustomerID'] ?>" data-phone="<?= e($c['PhoneNumber']) ?>" data-email="<?= e($c['Email']) ?>" <?= ($selectedCustomerId === (int)$c['CustomerID']) ? 'selected' : '' ?>>
                                            <?= e($c['FullName']) ?> (<?= e($c['PhoneNumber']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 d-flex align-items-end">
                                <a href="<?= base_url('customers/create.php') ?>" class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="bi bi-person-plus me-1"></i> Quick Register
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Product Selection -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2">2. Product / Equipment Information</h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-9">
                                <label class="form-label" for="product_id">Select Product <span class="text-danger">*</span></label>
                                <select class="form-select" id="product_id" name="product_id" required>
                                    <option value="">-- Choose Equipment --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['ProductID'] ?>" data-customer="<?= $p['CustomerID'] ?>" <?= ($selectedProductId === (int)$p['ProductID']) ? 'selected' : '' ?>>
                                            <?= e($p['ProductName']) ?> &bull; Model: <?= e($p['Model']) ?> &bull; SN: <?= e($p['SerialNumber']) ?> (Owner: <?= e($p['CustomerName']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 d-flex align-items-end">
                                <a
                                    href="<?= e($registerProductUrl) ?>"
                                    id="register-product-link"
                                    data-create-url="<?= e(base_url('products/create.php')) ?>"
                                    class="btn btn-outline-primary w-100"
                                >
                                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Register equipment
                                </a>
                            </div>
                            <div class="col-12">
                                <div class="form-text">Choose the specific item requiring repair.</div>
                                <div class="form-text" id="register-product-help">Register equipment for the selected customer, or choose one during registration.</div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Service Details -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2">3. Service & Defect Details</h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="priority">Service Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High (Urgent)</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="technician_id">Assign Technician (Optional)</label>
                                <select class="form-select" id="technician_id" name="technician_id">
                                    <option value="">-- Leave Unassigned (Triage Later) --</option>
                                    <?php foreach ($technicians as $t): ?>
                                        <option value="<?= $t['TechnicianID'] ?>">
                                            <?= e($t['FullName']) ?> (<?= e($t['Specialization']) ?>) &bull; <?= $t['ActiveJobs'] ?>/<?= MAX_TECHNICIAN_ACTIVE_JOBS ?> active
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="issue_description">Problem Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="issue_description" name="issue_description" rows="4" placeholder="Describe the fault symptoms, customer notes, or observed defects in detail..." required><?= e($_POST['issue_description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('requests/index.php') ?>" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Submit Service Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const customerSelect = document.getElementById('customer_id');
    const productSelect = document.getElementById('product_id');
    const registerProductLink = document.getElementById('register-product-link');
    const registerProductHelp = document.getElementById('register-product-help');

    function filterProductsByCustomer(customerId) {
        const options = productSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return; // keep default placeholder
            const optCustomer = opt.getAttribute('data-customer');
            if (!customerId || optCustomer === customerId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        if (customerId) {
            // Find if selected product belongs to this customer, otherwise reset
            const currentSelected = productSelect.selectedOptions[0];
            if (currentSelected && currentSelected.getAttribute('data-customer') !== customerId) {
                productSelect.value = '';
            }
        }
    }

    function updateRegisterProductLink() {
        const customerId = customerSelect.value;
        const query = new URLSearchParams({return_to_request: '1'});
        if (customerId) {
            query.set('customer_id', customerId);
        }
        registerProductLink.href = `${registerProductLink.dataset.createUrl}?${query}`;
        registerProductHelp.textContent = 'Register equipment and return to this request.';
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (<?= $returnFromProduct ? 'true' : 'false' ?>) {
            const draft = JSON.parse(sessionStorage.getItem('serviceRequestDraft') || 'null');
            if (draft) {
                document.getElementById('priority').value = draft.priority;
                document.getElementById('technician_id').value = draft.technicianId;
                document.getElementById('issue_description').value = draft.issueDescription;
                sessionStorage.removeItem('serviceRequestDraft');
            }
        }

        filterProductsByCustomer(customerSelect.value);
        updateRegisterProductLink();
        customerSelect.addEventListener('change', updateRegisterProductLink);
        registerProductLink.addEventListener('click', function(event) {
            sessionStorage.setItem('serviceRequestDraft', JSON.stringify({
                priority: document.getElementById('priority').value,
                technicianId: document.getElementById('technician_id').value,
                issueDescription: document.getElementById('issue_description').value,
            }));
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>