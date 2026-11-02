<?php
/**
 * Spare Parts Inventory & Stock Valuation Report
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

$db = get_db();
$currentUser = current_user();

$startDateInput = $_GET['start_date'] ?? date('Y-m-01');
$endDateInput = $_GET['end_date'] ?? date('Y-m-d');
$startDate = is_string($startDateInput) ? trim($startDateInput) : '';
$endDate = is_string($endDateInput) ? trim($endDateInput) : '';
$startDateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
$endDateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);
$dateRangeError = null;

if (
    !$startDateValue || $startDateValue->format('Y-m-d') !== $startDate ||
    !$endDateValue || $endDateValue->format('Y-m-d') !== $endDate ||
    $startDate > $endDate
) {
    $dateRangeError = 'Enter a valid date range with the start date on or before the end date.';
    $inventory = [];
} else {
    $stmt = $db->prepare('
        SELECT sp.*,
               COALESCE(SUM(
                   CASE
                       WHEN rec.CompletionDate BETWEEN :start_date AND :end_date
                       THEN pu.QuantityUsed
                       ELSE 0
                   END
               ), 0) AS TotalConsumedQuantity
        FROM SPARE_PART sp
        LEFT JOIN PARTS_USED pu ON sp.PartID = pu.PartID
        LEFT JOIN SERVICE_RECORD rec ON pu.RecordID = rec.RecordID
        GROUP BY sp.PartID
        ORDER BY (sp.QuantityInStock <= sp.ReorderLevel) DESC, sp.PartName ASC
    ');
    $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $inventory = $stmt->fetchAll();
}

$pageTitle = 'Inventory Valuation & Stock Report';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-1">Spare Parts Inventory & Stock Report</h3>
        <p class="text-muted small mb-0">Filter parts consumed by repair completion date; stock balances and valuation show current inventory.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-print-sheet">
            <i class="bi bi-printer me-1"></i> Print Stock Report
        </button>
        <a href="<?= base_url('reports/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Reports
        </a>
    </div>
</div>

<div class="card card-assis mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label" for="start_date">Consumption period start</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDate) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="end_date">Consumption period end</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($endDate) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-secondary w-100">Apply date range</button>
            </div>
        </form>
        <?php if ($dateRangeError): ?>
            <div class="alert alert-danger mt-3 mb-0" role="alert"><?= e($dateRangeError) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-assis print-sheet p-4 p-md-5">
    <div class="text-center mb-4 border-bottom pb-3">
        <h4 class="fw-bold text-uppercase mb-1">AFTER-SALES SERVICE INFORMATION SYSTEM</h4>
        <h5 class="fw-bold text-primary mb-2">SPARE PARTS INVENTORY & REORDER REPORT</h5>
        <div class="text-muted small">Generated on <?= date('d F Y, H:i') ?> by <?= e($currentUser['full_name']) ?></div>
        <div class="text-muted small">Consumption period <?= e($startDate) ?> to <?= e($endDate) ?></div>
        <div class="text-muted small">Stock on hand and valuation reflect current inventory.</div>
    </div>

    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Part Code</th>
                    <th>Component Name</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-center">Stock on Hand</th>
                    <th class="text-center">Reorder Threshold</th>
                    <th class="text-center">Consumed in Period</th>
                    <th class="text-end">Stock Value</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventory)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No inventory records found.</td></tr>
                <?php else: ?>
                    <?php
                    $totalStockValue = 0.0;
                    $totalUnitsOnHand = 0;
                    foreach ($inventory as $part):
                        $val = $part['QuantityInStock'] * $part['UnitPrice'];
                        $totalStockValue += $val;
                        $totalUnitsOnHand += $part['QuantityInStock'];
                        $isLow = ($part['QuantityInStock'] <= $part['ReorderLevel']);
                    ?>
                        <tr class="<?= $isLow ? 'table-warning bg-opacity-25' : '' ?>">
                            <td class="font-monospace text-muted">#PART-<?= str_pad((string)$part['PartID'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td class="fw-semibold"><?= e($part['PartName']) ?></td>
                            <td class="text-end"><?= format_money($part['UnitPrice']) ?></td>
                            <td class="text-center fw-bold <?= $isLow ? 'text-danger' : '' ?>"><?= $part['QuantityInStock'] ?></td>
                            <td class="text-center text-muted"><?= $part['ReorderLevel'] ?></td>
                            <td class="text-center"><?= $part['TotalConsumedQuantity'] ?> units</td>
                            <td class="text-end fw-semibold"><?= format_money($val) ?></td>
                            <td class="text-center">
                                <?php if ($part['QuantityInStock'] == 0): ?>
                                    <span class="badge bg-danger">Critical: Out</span>
                                <?php elseif ($isLow): ?>
                                    <span class="badge bg-warning text-dark">Reorder Alert</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Normal</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end">Warehouse Aggregate Valuation:</td>
                        <td class="text-center text-primary"><?= $totalUnitsOnHand ?> Units</td>
                        <td colspan="2"></td>
                        <td class="text-end fs-6 text-primary"><?= format_money($totalStockValue) ?></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="row pt-5 mt-4 border-top">
        <div class="col-6">
            <div class="small">
                <strong>Inventory Officer:</strong> <?= e($currentUser['full_name']) ?>
            </div>
        </div>
        <div class="col-6 text-end">
            <div class="small" style="display: inline-block; text-align: left;">
                <strong>Store Manager Signature:</strong> ____________________________
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
