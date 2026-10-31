<?php
/**
 * Service Request Status Report (Direct implementation of Figure 5.5)
 * Reference: sameera.md (Chapter Five, Figure 5.5)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

$db = get_db();
$currentUser = current_user();

$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

$stmt = $db->prepare('
    SELECT sr.RequestID, sr.DateLogged, sr.Status, sr.Priority,
           c.FullName AS CustomerName,
           p.ProductName
    FROM SERVICE_REQUEST sr
    JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
    JOIN PRODUCT p ON sr.ProductID = p.ProductID
    WHERE DATE(sr.DateLogged) BETWEEN :start AND :end
    ORDER BY sr.DateLogged ASC
');
$stmt->execute([':start' => $startDate, ':end' => $endDate]);
$records = $stmt->fetchAll();

// Calculate Summary Counts matching Figure 5.5 wireframe
$totalCount = count($records);
$completedCount = 0;
$pendingCount = 0;
$inProgressCount = 0;
$closedCount = 0;

foreach ($records as $r) {
    if ($r['Status'] === 'Completed') $completedCount++;
    elseif ($r['Status'] === 'Pending') $pendingCount++;
    elseif ($r['Status'] === 'In Progress') $inProgressCount++;
    elseif ($r['Status'] === 'Closed') $closedCount++;
}

$pageTitle = 'Service Request Status Report';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-1">Service Request Status Report</h3>
        <p class="text-muted small mb-0">Official operational status report corresponding to Figure 5.5 of the system design.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-print-sheet">
            <i class="bi bi-printer me-1"></i> Print / Export PDF
        </button>
        <a href="<?= base_url('reports/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Reports
        </a>
    </div>
</div>

<!-- Date Filter Form (Screen Only) -->
<div class="card card-assis mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label" for="start_date">Report Period Start</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDate) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="end_date">Report Period End</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($endDate) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-secondary w-100">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh Report Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Printable Formal Report Document -->
<div class="card card-assis print-sheet p-4 p-md-5">
    <div class="text-center mb-4 border-bottom pb-3">
        <h4 class="fw-bold text-uppercase mb-1">AFTER-SALES SERVICE INFORMATION SYSTEM</h4>
        <h5 class="fw-bold text-primary mb-2">SERVICE REQUEST STATUS REPORT</h5>
        <div class="d-flex justify-content-between text-muted small mt-3 px-2">
            <span><strong>Report Period:</strong> <?= date('d/m/Y', strtotime($startDate)) ?> to <?= date('d/m/Y', strtotime($endDate)) ?></span>
            <span><strong>Date Generated:</strong> <?= date('d/m/Y H:i') ?></span>
        </div>
    </div>

    <!-- Table matching Wireframe Fig 5.5 -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 130px;">Request ID</th>
                    <th>Customer Name</th>
                    <th>Product / Equipment</th>
                    <th style="width: 140px;">Date Logged</th>
                    <th style="width: 140px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No service requests found for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td class="fw-bold font-monospace">SR<?= str_pad((string)$row['RequestID'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= e($row['CustomerName']) ?></td>
                            <td><?= e($row['ProductName']) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['DateLogged'])) ?></td>
                            <td>
                                <span class="badge badge-status badge-status-<?= strtolower(str_replace(' ', '-', $row['Status'])) ?>">
                                    <?= e($row['Status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- SUMMARY Section (from Fig 5.5 wireframe) -->
    <div class="border rounded p-3 bg-light mb-5">
        <h6 class="fw-bold text-uppercase mb-3 small text-muted">Summary Statistics</h6>
        <div class="row text-center g-2">
            <div class="col-3">
                <span class="text-muted small d-block">Total Requests:</span>
                <span class="fw-bold fs-5"><?= $totalCount ?></span>
            </div>
            <div class="col-3">
                <span class="text-muted small d-block">Completed:</span>
                <span class="fw-bold fs-5 text-success"><?= $completedCount + $closedCount ?></span>
            </div>
            <div class="col-3">
                <span class="text-muted small d-block">Pending:</span>
                <span class="fw-bold fs-5 text-warning"><?= $pendingCount ?></span>
            </div>
            <div class="col-3">
                <span class="text-muted small d-block">In Progress:</span>
                <span class="fw-bold fs-5 text-info"><?= $inProgressCount ?></span>
            </div>
        </div>
    </div>

    <!-- Signature Footer (from Fig 5.5 wireframe) -->
    <div class="row pt-4 mt-4 border-top">
        <div class="col-6">
            <div class="small">
                <strong>Generated By:</strong> <?= e($currentUser['full_name']) ?> (<?= e($currentUser['role']) ?>)
            </div>
        </div>
        <div class="col-6 text-end">
            <div class="small" style="display: inline-block; text-align: left;">
                <strong>Signature:</strong> ____________________________________
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
