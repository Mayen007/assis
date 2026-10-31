<?php
/**
 * Management Reporting Engine Hub
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

$db = get_db();

// Quick summary stats for manager
$totalRequests = (int)$db->query('SELECT COUNT(*) FROM SERVICE_REQUEST')->fetchColumn();
$totalRevenue = (float)$db->query('SELECT COALESCE(SUM(AmountDue), 0) FROM INVOICE WHERE PaymentStatus = \'Paid\'')->fetchColumn();
$lowStockAlerts = (int)$db->query('SELECT COUNT(*) FROM SPARE_PART WHERE QuantityInStock <= ReorderLevel')->fetchColumn();
$avgRating = (float)$db->query('SELECT COALESCE(AVG(Rating), 0) FROM FEEDBACK')->fetchColumn();

$pageTitle = 'Management Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Management Reporting Engine</h3>
        <p class="text-muted small mb-0">Consolidated operational intelligence, service turnaround, inventory alerts, and technician productivity.</p>
    </div>
</div>

<!-- Executive Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="text-muted small text-uppercase fw-semibold">Lifetime Work Orders</span>
            <h3 class="fw-bold mb-0 mt-1"><?= $totalRequests ?></h3>
            <span class="small text-muted">All service tickets</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="text-muted small text-uppercase fw-semibold">Settled Revenue</span>
            <h3 class="fw-bold mb-0 mt-1 text-success"><?= format_money($totalRevenue) ?></h3>
            <span class="small text-muted">Paid invoices to date</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="text-muted small text-uppercase fw-semibold">Inventory Critical</span>
            <h3 class="fw-bold mb-0 mt-1 <?= $lowStockAlerts > 0 ? 'text-danger' : 'text-success' ?>"><?= $lowStockAlerts ?></h3>
            <span class="small text-muted">Items below reorder level</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="text-muted small text-uppercase fw-semibold">Customer CSAT</span>
            <h3 class="fw-bold mb-0 mt-1 text-warning"><?= number_format($avgRating, 1) ?> / 5.0</h3>
            <span class="small text-muted">Average client satisfaction</span>
        </div>
    </div>
</div>

<!-- Report Navigation Matrix -->
<div class="row g-4">
    <div class="col-12 col-md-4">
        <div class="card card-assis h-100">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-file-earmark-check fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Service Request Status Report</h5>
                    <p class="text-muted small mb-4">
                        Comprehensive summary of tickets across Pending, In Progress, Completed, and Closed statuses (Figure 5.5 wireframe).
                    </p>
                </div>
                <a href="<?= base_url('reports/status.php') ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-printer me-1"></i> Generate Status Report
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card card-assis h-100">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Technician Workload Report</h5>
                    <p class="text-muted small mb-4">
                        Analysis of specialist workload distribution, completed maintenance jobs, and labor revenue generated per technician.
                    </p>
                </div>
                <a href="<?= base_url('reports/workload.php') ?>" class="btn btn-outline-info w-100">
                    <i class="bi bi-bar-chart me-1"></i> Generate Workload Report
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card card-assis h-100">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-boxes fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Spare Parts & Inventory Report</h5>
                    <p class="text-muted small mb-4">
                        Inventory valuation, stock balance monitoring, parts consumption summary, and low-stock reorder thresholds.
                    </p>
                </div>
                <a href="<?= base_url('reports/inventory.php') ?>" class="btn btn-outline-warning text-dark w-100">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Generate Stock Report
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
