<?php
/**
 * Customer Management Directory
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$db = get_db();
$search = trim($_GET['search'] ?? '');

$sql = '
    SELECT c.*, COUNT(p.ProductID) AS ProductCount
    FROM CUSTOMER c
    LEFT JOIN PRODUCT p ON c.CustomerID = p.CustomerID
';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE c.FullName LIKE :name OR c.PhoneNumber LIKE :phone OR c.Email LIKE :email ';
    $searchTerm = "%$search%";
    $params[':name'] = $searchTerm;
    $params[':phone'] = $searchTerm;
    $params[':email'] = $searchTerm;
}

$sql .= ' GROUP BY c.CustomerID ORDER BY c.DateRegistered DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customer Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Customer Directory</h3>
        <p class="text-muted small mb-0">Manage registered clients and view associated equipment profiles.</p>
    </div>
    <div>
        <a href="<?= base_url('customers/create.php') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center">
            <i class="bi bi-person-plus"></i>
            <span class="d-none d-md-inline">Register New Customer</span>
            <span class="d-md-none">New Customer</span>
        </a>
    </div>
</div>

<div class="card card-assis mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-12 col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, phone number, or email..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter"></i> Filter</button>
                <?php if ($search !== ''): ?>
                    <a href="<?= base_url('customers/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Customer ID</th>
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Email Address</th>
                    <th>Physical Address</th>
                    <th>Registered Products</th>
                    <th>Date Registered</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No customer records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="fw-bold text-muted">#CUST-<?= str_pad((string)$c['CustomerID'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td class="fw-semibold"><?= e($c['FullName']) ?></td>
                            <td><?= e($c['PhoneNumber']) ?></td>
                            <td><?= e($c['Email'] ?: 'â€”') ?></td>
                            <td><?= e($c['PhysicalAddress'] ?: 'â€”') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-box-seam me-1"></i> <?= $c['ProductCount'] ?> Product(s)
                                </span>
                            </td>
                            <td class="small text-muted"><?= date('d M Y', strtotime($c['DateRegistered'])) ?></td>
                            <td class="text-end">
                                <a href="<?= base_url('customers/edit.php?id=' . $c['CustomerID']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit Customer">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="<?= base_url('customers/view.php?id=' . $c['CustomerID']) ?>" class="btn btn-sm btn-outline-primary" title="View Profile">
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
