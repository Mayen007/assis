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

$stmt = $db->query('
    SELECT t.*,
           COUNT(sr.RequestID) AS TotalAssigned,
           COUNT(CASE WHEN sr.Status = \'Pending\' THEN 1 END) AS PendingJobs,
           COUNT(CASE WHEN sr.Status = \'In Progress\' THEN 1 END) AS InProgressJobs,
           COUNT(CASE WHEN sr.Status IN (\'Completed\', \'Closed\') THEN 1 END) AS CompletedJobs,
           COALESCE(SUM(rec.LabourCost), 0) AS TotalLabourBilled
    FROM TECHNICIAN t
    LEFT JOIN SERVICE_REQUEST sr ON t.TechnicianID = sr.TechnicianID
    LEFT JOIN SERVICE_RECORD rec ON sr.RequestID = rec.RequestID
    GROUP BY t.TechnicianID
    ORDER BY TotalAssigned DESC, t.FullName ASC
');
$workloads = $stmt->fetchAll();

$pageTitle = 'Technician Workload Report';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-1">Technician Workload & Performance Report</h3>
        <p class="text-muted small mb-0">Analysis of technician assignment distribution, current queue, and repair throughput.</p>
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

<div class="card card-assis print-sheet p-4 p-md-5">
    <div class="text-center mb-4 border-bottom pb-3">
        <h4 class="fw-bold text-uppercase mb-1">AFTER-SALES SERVICE INFORMATION SYSTEM</h4>
        <h5 class="fw-bold text-primary mb-2">TECHNICIAN WORKLOAD & PERFORMANCE SUMMARY</h5>
        <div class="text-muted small">Generated on <?= date('d F Y, H:i') ?> by <?= e($currentUser['full_name']) ?></div>
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
                    <th class="text-end">Labour Billed</th>
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
                    $grandLabour = 0.0;
                    foreach ($workloads as $w):
                        $grandTotal += $w['TotalAssigned'];
                        $grandCompleted += $w['CompletedJobs'];
                        $grandLabour += (float)$w['TotalLabourBilled'];
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= e($w['FullName']) ?></td>
                            <td><?= e($w['Specialization']) ?></td>
                            <td class="text-center"><?= $w['TotalAssigned'] ?></td>
                            <td class="text-center text-warning fw-semibold"><?= $w['PendingJobs'] ?></td>
                            <td class="text-center text-info fw-semibold"><?= $w['InProgressJobs'] ?></td>
                            <td class="text-center text-success fw-semibold"><?= $w['CompletedJobs'] ?></td>
                            <td class="text-end fw-semibold"><?= format_money($w['TotalLabourBilled']) ?></td>
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
                        <td class="text-end text-primary"><?= format_money($grandLabour) ?></td>
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
