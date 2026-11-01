<?php
/**
 * Invoicing & Billing Directory
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$db = get_db();
$paymentStatus = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = '
    SELECT i.*, sr.RequestID, sr.DateLogged,
           c.FullName AS CustomerName, c.PhoneNumber,
           p.ProductName, p.Model
    FROM INVOICE i
    JOIN SERVICE_REQUEST sr ON i.RequestID = sr.RequestID
    JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
    JOIN PRODUCT p ON sr.ProductID = p.ProductID
    WHERE 1=1
';
$params = [];

if ($paymentStatus !== '') {
    $sql .= ' AND i.PaymentStatus = :status ';
    $params[':status'] = $paymentStatus;
}

if ($search !== '') {
    $sql .= ' AND (c.FullName LIKE :customer_name OR c.PhoneNumber LIKE :phone OR p.ProductName LIKE :product_name) ';
    $searchTerm = "%$search%";
    $params[':customer_name'] = $searchTerm;
    $params[':phone'] = $searchTerm;
    $params[':product_name'] = $searchTerm;
}

$sql .= ' ORDER BY i.DateIssued DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

$pageTitle = 'Billing & Invoices';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-1">Billing & Invoices</h3>
        <p class="text-muted small mb-0">Monitor service billing statements, labour fees, parts expenses, and payment collection status.</p>
    </div>
</div>

<div class="card card-assis mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Search by customer name, phone, or equipment..." value="<?= e($search) ?>">
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- All Payments --</option>
                    <option value="Unpaid" <?= $paymentStatus === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    <option value="Paid" <?= $paymentStatus === 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Partially Paid" <?= $paymentStatus === 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel"></i> Filter</button>
                <?php if ($search !== '' || $paymentStatus !== ''): ?>
                    <a href="<?= base_url('billing/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Invoice ID</th>
                    <th>Ticket Ref</th>
                    <th>Customer</th>
                    <th>Equipment</th>
                    <th>Date Issued</th>
                    <th>Total Amount</th>
                    <th>Payment Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No billing records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td class="fw-bold">#INV-<?= str_pad((string)$inv['InvoiceID'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <a href="<?= base_url('requests/view.php?id=' . $inv['RequestID']) ?>" class="text-decoration-none">
                                    #SR<?= str_pad((string)$inv['RequestID'], 4, '0', STR_PAD_LEFT) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($inv['CustomerName']) ?></div>
                                <div class="text-muted small"><?= e($inv['PhoneNumber']) ?></div>
                            </td>
                            <td><?= e($inv['ProductName']) ?></td>
                            <td class="small text-muted"><?= date('d M Y', strtotime($inv['DateIssued'])) ?></td>
                            <td class="fw-bold fs-6 text-primary"><?= format_money($inv['AmountDue']) ?></td>
                            <td>
                                <span class="badge badge-payment-<?= strtolower(str_replace(' ', '', $inv['PaymentStatus'])) ?>">
                                    <?= e($inv['PaymentStatus']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('billing/view.php?id=' . $inv['InvoiceID']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text me-1"></i> View Bill
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
