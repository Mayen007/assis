<?php
/**
 * Product & Equipment Registry
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$db = get_db();
$search = trim($_GET['search'] ?? '');

$sql = '
    SELECT p.*, c.FullName AS CustomerName, c.PhoneNumber,
           w.WarrantyID, w.StartDate, w.EndDate
    FROM PRODUCT p
    JOIN CUSTOMER c ON p.CustomerID = c.CustomerID
    LEFT JOIN WARRANTY w ON p.ProductID = w.ProductID
';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE p.ProductName LIKE :q OR p.Model LIKE :q OR p.SerialNumber LIKE :q OR c.FullName LIKE :q ';
    $params[':q'] = "%$search%";
}

$sql .= ' ORDER BY p.ProductID DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Product & Equipment Catalogue';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Equipment Registry</h3>
        <p class="text-muted small mb-0">Registered customer products, models, serial numbers, and warranty coverage.</p>
    </div>
    <div>
        <a href="<?= base_url('products/create.php') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center">
            <i class="bi bi-plus-circle"></i>
            <span class="d-none d-md-inline">Register Equipment</span>
            <span class="d-md-none">New Equipment</span>
        </a>
    </div>
</div>

<div class="card card-assis mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-12 col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by product name, model, serial number, or customer..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter"></i> Filter</button>
                <?php if ($search !== ''): ?>
                    <a href="<?= base_url('products/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Serial Number</th>
                    <th>Product Name</th>
                    <th>Model</th>
                    <th>Customer (Owner)</th>
                    <th>Purchase Date</th>
                    <th>Warranty Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No equipment records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                        $hasWarranty = !empty($p['EndDate']);
                        $isActive = $hasWarranty && (strtotime($p['EndDate']) >= time());
                        ?>
                        <tr>
                            <td><code><?= e($p['SerialNumber']) ?></code></td>
                            <td class="fw-semibold"><?= e($p['ProductName']) ?></td>
                            <td><?= e($p['Model']) ?></td>
                            <td>
                                <a href="<?= base_url('customers/view.php?id=' . $p['CustomerID']) ?>" class="text-decoration-none">
                                    <?= e($p['CustomerName']) ?>
                                </a>
                            </td>
                            <td><?= $p['PurchaseDate'] ? date('d M Y', strtotime($p['PurchaseDate'])) : 'â€”' ?></td>
                            <td>
                                <?php if (!$hasWarranty): ?>
                                    <span class="badge bg-secondary">None</span>
                                <?php elseif ($isActive): ?>
                                    <span class="badge bg-success">Active (to <?= date('d/m/Y', strtotime($p['EndDate'])) ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('requests/create.php?customer_id=' . $p['CustomerID'] . '&product_id=' . $p['ProductID']) ?>" class="btn btn-sm btn-outline-primary" title="Log Service Request">
                                    <i class="bi bi-wrench"></i> Log Repair
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

