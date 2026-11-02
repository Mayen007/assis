<?php
/**
 * Printable Invoice View & Payment Status Management
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$invoiceId = (int)($_GET['id'] ?? 0);
if ($invoiceId <= 0) {
    set_flash('danger', 'Invalid invoice identifier.');
    header('Location: ' . base_url('billing/index.php'));
    exit;
}

$db = get_db();

// Handle Payment Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'Security validation failed. Please try again.');
        header('Location: ' . base_url('billing/view.php?id=' . $invoiceId));
        exit;
    }

    $newStatus = trim($_POST['payment_status'] ?? '');
    if (!in_array($newStatus, ['Unpaid', 'Paid', 'Partially Paid'], true)) {
        set_flash('danger', 'Invalid payment status.');
        header('Location: ' . base_url('billing/view.php?id=' . $invoiceId));
        exit;
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('
            SELECT i.RequestID, i.WarrantyApplied, sr.Status AS RequestStatus
            FROM INVOICE i
            JOIN SERVICE_REQUEST sr ON sr.RequestID = i.RequestID
            WHERE i.InvoiceID = :id
            FOR UPDATE
        ');
        $stmt->execute([':id' => $invoiceId]);
        $invoiceStatus = $stmt->fetch();

        if (!$invoiceStatus) {
            $db->rollBack();
            set_flash('danger', 'Invoice not found.');
            header('Location: ' . base_url('billing/index.php'));
            exit;
        }

        if ((bool)$invoiceStatus['WarrantyApplied']) {
            $db->rollBack();
            set_flash('warning', 'This invoice is covered by warranty and has no amount due.');
            header('Location: ' . base_url('billing/view.php?id=' . $invoiceId));
            exit;
        }

        $stmt = $db->prepare('UPDATE INVOICE SET PaymentStatus = :status WHERE InvoiceID = :id');
        $stmt->execute([':status' => $newStatus, ':id' => $invoiceId]);

        if ($newStatus === 'Paid' && $invoiceStatus['RequestStatus'] === 'Completed') {
            $stmt = $db->prepare('UPDATE SERVICE_REQUEST SET Status = \'Closed\' WHERE RequestID = :rid');
            $stmt->execute([':rid' => $invoiceStatus['RequestID']]);
        } elseif ($newStatus !== 'Paid' && $invoiceStatus['RequestStatus'] === 'Closed') {
            $stmt = $db->prepare('UPDATE SERVICE_REQUEST SET Status = \'Completed\' WHERE RequestID = :rid');
            $stmt->execute([':rid' => $invoiceStatus['RequestID']]);
        }

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    set_flash('success', 'Payment status updated to: ' . $newStatus);
    header('Location: ' . base_url('billing/view.php?id=' . $invoiceId));
    exit;
}

// Fetch invoice and associated details
$stmt = $db->prepare('
    SELECT i.*,
           sr.RequestID, sr.DateLogged, sr.IssueDescription,
           c.FullName AS CustomerName, c.PhoneNumber, c.Email, c.PhysicalAddress,
           p.ProductName, p.Model, p.SerialNumber,
           rec.RecordID, rec.ActionsTaken, rec.CompletionDate, rec.LabourCost,
           t.FullName AS TechnicianName
    FROM INVOICE i
    JOIN SERVICE_REQUEST sr ON i.RequestID = sr.RequestID
    JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
    JOIN PRODUCT p ON sr.ProductID = p.ProductID
    LEFT JOIN SERVICE_RECORD rec ON sr.RequestID = rec.RequestID
    LEFT JOIN TECHNICIAN t ON sr.TechnicianID = t.TechnicianID
    WHERE i.InvoiceID = :id
    LIMIT 1
');
$stmt->execute([':id' => $invoiceId]);
$inv = $stmt->fetch();

if (!$inv) {
    set_flash('danger', 'Invoice not found.');
    header('Location: ' . base_url('billing/index.php'));
    exit;
}

// Fetch parts used on this record
$partsUsed = [];
if (!empty($inv['RecordID'])) {
    $stmt = $db->prepare('
        SELECT pu.*, sp.PartName, sp.UnitPrice
        FROM PARTS_USED pu
        JOIN SPARE_PART sp ON pu.PartID = sp.PartID
        WHERE pu.RecordID = :rec_id
    ');
    $stmt->execute([':rec_id' => $inv['RecordID']]);
    $partsUsed = $stmt->fetchAll();
}

$pageTitle = 'Invoice #INV-' . str_pad((string)$inv['InvoiceID'], 4, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-1">Invoice #INV-<?= str_pad((string)$inv['InvoiceID'], 4, '0', STR_PAD_LEFT) ?></h3>
        <p class="text-muted small mb-0">Issued on <?= date('d F Y, H:i', strtotime($inv['DateIssued'])) ?></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-print-sheet">
            <i class="bi bi-printer me-1"></i> Print Invoice
        </button>
        <a href="<?= base_url('billing/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Invoices
        </a>
    </div>
</div>

<!-- Payment Status Toggle Bar (Screen Only) -->
<?php if (!(bool)$inv['WarrantyApplied']): ?>
<div class="card card-assis mb-4 no-print">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Current Payment Status:</span>
            <span class="badge badge-payment-<?= strtolower(str_replace(' ', '', $inv['PaymentStatus'])) ?> fs-6">
                <?= e($inv['PaymentStatus']) ?>
            </span>
        </div>
        <form method="POST" action="" class="d-flex align-items-center gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="update_payment_status" value="1">
            <select name="payment_status" class="form-select form-select-sm" style="width: auto;">
                <option value="Unpaid" <?= $inv['PaymentStatus'] === 'Unpaid' ? 'selected' : '' ?>>Mark Unpaid</option>
                <option value="Paid" <?= $inv['PaymentStatus'] === 'Paid' ? 'selected' : '' ?>>Mark Paid</option>
                <option value="Partially Paid" <?= $inv['PaymentStatus'] === 'Partially Paid' ? 'selected' : '' ?>>Mark Partially Paid</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Update Status</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Printable Invoice Sheet -->
<div class="card card-assis print-sheet p-4 p-md-5">
    <!-- Invoice Header -->
    <div class="row align-items-center border-bottom pb-4 mb-4">
        <div class="col-6">
            <h4 class="fw-bold text-primary mb-1">AFTER-SALES SERVICE CENTRE</h4>
            <div class="text-muted small">
                Technical Maintenance & Repair Division<br>
                Official Service Invoice & Settlement Note
            </div>
        </div>
        <div class="col-6 text-end">
            <h3 class="fw-bold mb-0">INVOICE</h3>
            <div class="text-muted small">#INV-<?= str_pad((string)$inv['InvoiceID'], 4, '0', STR_PAD_LEFT) ?></div>
            <div class="small">Date: <?= date('d M Y', strtotime($inv['DateIssued'])) ?></div>
        </div>
    </div>

    <!-- Client & Job Info -->
    <div class="row g-4 mb-4">
        <div class="col-6">
            <span class="text-uppercase small text-muted fw-bold d-block mb-1">Bill To Client</span>
            <h6 class="fw-bold mb-1"><?= e($inv['CustomerName']) ?></h6>
            <div class="small text-muted">Phone: <?= e($inv['PhoneNumber']) ?></div>
            <?php if ($inv['Email']): ?>
                <div class="small text-muted">Email: <?= e($inv['Email']) ?></div>
            <?php endif; ?>
            <?php if ($inv['PhysicalAddress']): ?>
                <div class="small text-muted">Address: <?= e($inv['PhysicalAddress']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-6 text-end">
            <span class="text-uppercase small text-muted fw-bold d-block mb-1">Service Details</span>
            <div class="small"><strong>Work Order Ref:</strong> #SR<?= str_pad((string)$inv['RequestID'], 4, '0', STR_PAD_LEFT) ?></div>
            <div class="small"><strong>Equipment:</strong> <?= e($inv['ProductName']) ?></div>
            <div class="small"><strong>Model:</strong> <?= e($inv['Model']) ?></div>
            <div class="small"><strong>Serial Number:</strong> <code><?= e($inv['SerialNumber']) ?></code></div>
            <div class="small"><strong>Serviced By:</strong> <?= e($inv['TechnicianName'] ?? 'Assigned Specialist') ?></div>
        </div>
    </div>

    <!-- Actions Summary -->
    <?php if (!empty($inv['ActionsTaken'])): ?>
        <div class="mb-4">
            <span class="text-uppercase small text-muted fw-bold d-block mb-1">Technical Interventions Completed</span>
            <div class="p-3 bg-light rounded border small">
                <?= nl2br(e($inv['ActionsTaken'])) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((bool)$inv['WarrantyApplied']): ?>
        <div class="alert alert-success">
            <i class="bi bi-shield-check me-1"></i>
            <span class="badge badge-payment-nocharge me-1">No Charge</span>
            Warranty coverage applied. Labour and parts are covered; no payment is due.
        </div>
    <?php endif; ?>

    <!-- Itemized Table of Charges -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item Description</th>
                    <th class="text-center" style="width: 120px;">Quantity</th>
                    <th class="text-end" style="width: 150px;">Unit Price</th>
                    <th class="text-end" style="width: 160px;">Total (KES)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Labor Row -->
                <tr>
                    <td>
                        <strong>Technical Labour & Diagnostic Charges</strong>
                        <div class="text-muted small">Standard inspection, disassembly, servicing, and testing</div>
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-end"><?= format_money($inv['WarrantyApplied'] ? 0 : ($inv['LabourCost'] ?? 0)) ?></td>
                    <td class="text-end fw-semibold"><?= format_money($inv['WarrantyApplied'] ? 0 : ($inv['LabourCost'] ?? 0)) ?></td>
                </tr>

                <!-- Parts Rows -->
                <?php
                $partsTotal = 0;
                foreach ($partsUsed as $p):
                    $unitPrice = $inv['WarrantyApplied'] ? 0 : $p['UnitPrice'];
                    $sub = $p['QuantityUsed'] * $unitPrice;
                    $partsTotal += $sub;
                ?>
                    <tr>
                        <td>
                            <strong><?= e($p['PartName']) ?></strong>
                            <div class="text-muted small">Replacement hardware component</div>
                        </td>
                        <td class="text-center"><?= $p['QuantityUsed'] ?></td>
                        <td class="text-end"><?= format_money($unitPrice) ?></td>
                        <td class="text-end fw-semibold"><?= format_money($sub) ?></td>
                    </tr>
                <?php endforeach; ?>

                <!-- Totals -->
                <tr class="table-light">
                    <td colspan="3" class="text-end fw-bold">Grand Total Amount Due:</td>
                    <td class="text-end fw-bold fs-5 text-primary"><?= format_money($inv['AmountDue']) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Signatures & Footnotes -->
    <div class="row pt-5 mt-4 border-top">
        <div class="col-6">
            <div style="border-top: 1px dashed #94a3b8; width: 220px; padding-top: 0.5rem;" class="small text-muted">
                Customer Signature & Acceptance
            </div>
        </div>
        <div class="col-6 text-end">
            <div style="border-top: 1px dashed #94a3b8; width: 220px; margin-left: auto; padding-top: 0.5rem;" class="small text-muted">
                Authorized Service Officer
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
