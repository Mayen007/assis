<?php
/**
 * Technician Workload & Performance Report
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
    $workloads = [];
} else {
    $stmt = $db->prepare('
        SELECT t.*,
               COUNT(sr.RequestID) AS TotalAssigned,
               COUNT(CASE WHEN sr.Status = \'Pending\' THEN 1 END) AS PendingJobs,
               COUNT(CASE WHEN sr.Status = \'In Progress\' THEN 1 END) AS InProgressJobs,
               COUNT(CASE WHEN sr.Status IN (\'Completed\', \'Closed\') THEN 1 END) AS CompletedJobs,
               COALESCE(SUM(i.AmountDue), 0) AS TotalBilled
        FROM TECHNICIAN t
        LEFT JOIN SERVICE_REQUEST sr
            ON t.TechnicianID = sr.TechnicianID
            AND sr.DateLogged >= :start_date
            AND sr.DateLogged < DATE_ADD(:end_date, INTERVAL 1 DAY)
        LEFT JOIN INVOICE i ON sr.RequestID = i.RequestID
        GROUP BY t.TechnicianID
        ORDER BY TotalAssigned DESC, t.FullName ASC
    ');
    $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $workloads = $stmt->fetchAll();
}

$pageTitle = 'Technician Workload Report';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-1">Technician Workload & Performance Report</h3>
        <p class="text-muted small mb-0">Requests are grouped by logged date. Total billed uses invoice amounts; warranty-covered repairs count as KSh 0.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-print-sheet">
            <i class="bi bi-printer me-1"></i> Print Report
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
                <label class="form-label" for="start_date">Request period start</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDate) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="end_date">Request period end</label>
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
        <h5 class="fw-bold text-primary mb-2">TECHNICIAN WORKLOAD & PERFORMANCE SUMMARY</h5>
        <div class="text-muted small">Generated on <?= date('d F Y, H:i') ?> by <?= e($currentUser['full_name']) ?></div>
        <div class="text-muted small">Requests logged <?= e($startDate) ?> to <?= e($endDate) ?></div>
    </div>

    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Technician Specialist</th>
                    <th>Area of Specialization</th>
                    <th class="text-center">Total Assigned</th>
                    <th class="text-center">Pending</th>
                    <th class="text-center">In Progress</th>
                    <th class="text-center">Completed</th>
                    <th class="text-end">Total Billed</th>
                    <th class="text-center">Current Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($workloads)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No technician records found.</td></tr>
                <?php else: ?>
                    <?php
                    $grandTotal = 0;
                    $grandCompleted = 0;
                    $grandBilled = 0.0;
                    foreach ($workloads as $w):
                        $grandTotal += $w['TotalAssigned'];
                        $grandCompleted += $w['CompletedJobs'];
                        $grandBilled += (float)$w['TotalBilled'];
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= e($w['FullName']) ?></td>
                            <td><?= e($w['Specialization']) ?></td>
                            <td class="text-center"><?= $w['TotalAssigned'] ?></td>
                            <td class="text-center text-warning fw-semibold"><?= $w['PendingJobs'] ?></td>
                            <td class="text-center text-info fw-semibold"><?= $w['InProgressJobs'] ?></td>
                            <td class="text-center text-success fw-semibold"><?= $w['CompletedJobs'] ?></td>
                            <td class="text-end fw-semibold"><?= format_money($w['TotalBilled']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?= e($w['Availability']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-light fw-bold">
                        <td colspan="2" class="text-end">Department Totals:</td>
                        <td class="text-center"><?= $grandTotal ?></td>
                        <td colspan="2"></td>
                        <td class="text-center text-success"><?= $grandCompleted ?></td>
                        <td class="text-end text-primary"><?= format_money($grandBilled) ?></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="row pt-5 mt-4 border-top">
        <div class="col-6">
            <div class="small">
                <strong>Supervising Authority:</strong> <?= e($currentUser['full_name']) ?>
            </div>
        </div>
        <div class="col-6 text-end">
            <div class="small" style="display: inline-block; text-align: left;">
                <strong>Sign-off:</strong> ____________________________________
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
