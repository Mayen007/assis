<?php

/**
 * Customer Profile & Product Linkage View
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$customerId = (int)($_GET['id'] ?? 0);
if ($customerId <= 0) {
    set_flash('danger', 'Invalid customer identifier.');
    header('Location: ' . base_url('customers/index.php'));
    exit;
}

$db = get_db();

// Fetch customer
$stmt = $db->prepare('SELECT * FROM CUSTOMER WHERE CustomerID = :id LIMIT 1');
$stmt->execute([':id' => $customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    set_flash('danger', 'Customer not found.');
    header('Location: ' . base_url('customers/index.php'));
    exit;
}

// Fetch linked products with active warranty check
$stmt = $db->prepare('
    SELECT p.*, w.WarrantyID, w.StartDate, w.EndDate, w.Terms
    FROM PRODUCT p
    LEFT JOIN WARRANTY w ON p.ProductID = w.ProductID
    WHERE p.CustomerID = :cid
    ORDER BY p.ProductID DESC
');
$stmt->execute([':cid' => $customerId]);
$products = $stmt->fetchAll();

// Fetch service requests logged for this customer
$stmt = $db->prepare('
    SELECT sr.*, p.ProductName, t.FullName AS TechnicianName
    FROM SERVICE_REQUEST sr
    JOIN PRODUCT p ON sr.ProductID = p.ProductID
    LEFT JOIN TECHNICIAN t ON sr.TechnicianID = t.TechnicianID
    WHERE sr.CustomerID = :cid
    ORDER BY sr.DateLogged DESC
');
$stmt->execute([':cid' => $customerId]);
$requests = $stmt->fetchAll();

$pageTitle = 'Customer Profile: ' . $customer['FullName'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1"><?= e($customer['FullName']) ?></h3>
        <p class="text-muted small mb-0">Customer ID: #CUST-<?= str_pad((string)$customer['CustomerID'], 4, '0', STR_PAD_LEFT) ?> &bull; Registered on <?= date('d M Y', strtotime($customer['DateRegistered'])) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap flex-md-nowrap w-100 w-md-auto align-flex-end">
        <a href="<?= base_url('products/create.php?customer_id=' . $customer['CustomerID']) ?>" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0 text-nowrap">
            <i class="bi bi-plus-circle"></i>
            <span class="d-none d-md-inline">Register Equipment</span>
            <span class="d-md-none">New Equipment</span>
        </a>
        <a href="<?= base_url('customers/index.php') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0 text-nowrap">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-md-inline">Back to Directory</span>
            <span class="d-md-none">Back</span>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
        <!-- Contact Information Card -->
        <div class="card card-assis h-100">
            <div class="card-assis-header">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2"></i> Contact Details</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="text-muted small d-block">Phone Number</span>
                    <span class="fw-semibold fs-6"><?= e($customer['PhoneNumber']) ?></span>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Email Address</span>
                    <span><?= e($customer['Email'] ?: 'Not Provided') ?></span>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Physical Address</span>
                    <span><?= e($customer['PhysicalAddress'] ?: 'Not Provided') ?></span>
                </div>
                <hr>
                <a href="<?= base_url('customers/edit.php?id=' . $customer['CustomerID']) ?>" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="bi bi-pencil me-1"></i> Edit Customer
                </a>
                <a href="<?= base_url('requests/create.php?customer_id=' . $customer['CustomerID']) ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-wrench me-1"></i> Log Service Request
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <!-- Products Owned Card -->
        <div class="card card-assis h-100">
            <div class="card-assis-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-boxes me-2"></i> Registered Equipment & Warranties</h6>
                <span class="badge bg-light text-dark border"><?= count($products) ?> Total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-assis table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Model</th>
                            <th>Serial Number</th>
                            <th>Purchase Date</th>
                            <th>Warranty Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No equipment registered yet for this client.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <?php
                                $hasWarranty = !empty($p['EndDate']);
                                $isWarrantyActive = $hasWarranty && (strtotime($p['EndDate']) >= time());
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($p['ProductName']) ?></td>
                                    <td><?= e($p['Model']) ?></td>
                                    <td><code><?= e($p['SerialNumber']) ?></code></td>
                                    <td><?= $p['PurchaseDate'] ? date('d M Y', strtotime($p['PurchaseDate'])) : 'â€”' ?></td>
                                    <td>
                                        <?php if (!$hasWarranty): ?>
                                            <span class="badge bg-secondary">No Warranty</span>
                                        <?php elseif ($isWarrantyActive): ?>
                                            <span class="badge bg-success" title="Expires: <?= e($p['EndDate']) ?>">Active (to <?= date('d/m/Y', strtotime($p['EndDate'])) ?>)</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger" title="Expired: <?= e($p['EndDate']) ?>">Expired</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url('products/edit.php?id=' . $p['ProductID']) ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Service History -->
<div class="card card-assis">
    <div class="card-assis-header">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i> Service Request History</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-assis table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Product</th>
                    <th>Issue Description</th>
                    <th>Assigned Tech</th>
                    <th>Date Logged</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No service history for this client.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td class="fw-bold">#SR<?= str_pad((string)$r['RequestID'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td><?= e($r['ProductName']) ?></td>
                            <td class="text-truncate" style="max-width: 280px;"><?= e($r['IssueDescription']) ?></td>
                            <td><?= e($r['TechnicianName'] ?? 'Unassigned') ?></td>
                            <td class="small text-muted"><?= date('d M Y, H:i', strtotime($r['DateLogged'])) ?></td>
                            <td><span class="badge badge-status badge-status-<?= strtolower(str_replace(' ', '-', $r['Status'])) ?>"><?= e($r['Status']) ?></span></td>
                            <td class="text-end">
                                <a href="<?= base_url('requests/view.php?id=' . $r['RequestID']) ?>" class="btn btn-sm btn-light border">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>