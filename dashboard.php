<?php

/**
 * Role-Based Dashboard Interface
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$user = current_user();
$db = get_db();

// Metrics queries
$totalRequests = 0;
$pendingRequests = 0;
$inProgressRequests = 0;
$completedRequests = 0;
$lowStockCount = 0;
$recentRequests = [];
$lowStockParts = [];
$myAssignedJobs = [];

try {
    // Global Request Metrics
    $totalRequests = (int)$db->query('SELECT COUNT(*) FROM SERVICE_REQUEST')->fetchColumn();
    $pendingRequests = (int)$db->query("SELECT COUNT(*) FROM SERVICE_REQUEST WHERE Status = 'Pending'")->fetchColumn();
    $inProgressRequests = (int)$db->query("SELECT COUNT(*) FROM SERVICE_REQUEST WHERE Status = 'In Progress'")->fetchColumn();
    $completedRequests = (int)$db->query("SELECT COUNT(*) FROM SERVICE_REQUEST WHERE Status IN ('Completed', 'Closed')")->fetchColumn();

    // Low-Stock Parts Count
    $lowStockCount = (int)$db->query('SELECT COUNT(*) FROM SPARE_PART WHERE QuantityInStock <= ReorderLevel')->fetchColumn();

    if ($lowStockCount > 0) {
        $stmt = $db->query('SELECT * FROM SPARE_PART WHERE QuantityInStock <= ReorderLevel ORDER BY QuantityInStock ASC LIMIT 5');
        $lowStockParts = $stmt->fetchAll();
    }

    // Role-specific records
    if ($user['role'] === 'Technician' && !empty($user['technician_id'])) {
        $stmt = $db->prepare('
            SELECT sr.*, c.FullName AS CustomerName, p.ProductName, p.Model
            FROM SERVICE_REQUEST sr
            JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
            JOIN PRODUCT p ON sr.ProductID = p.ProductID
            WHERE sr.TechnicianID = :tech_id AND sr.Status IN (\'Pending\', \'In Progress\')
            ORDER BY sr.DateLogged DESC
        ');
        $stmt->execute([':tech_id' => $user['technician_id']]);
        $myAssignedJobs = $stmt->fetchAll();
    } else {
        // Recent 5 requests for Admin and Front Desk
        $stmt = $db->query('
            SELECT sr.*, c.FullName AS CustomerName, p.ProductName, t.FullName AS TechnicianName
            FROM SERVICE_REQUEST sr
            JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
            JOIN PRODUCT p ON sr.ProductID = p.ProductID
            LEFT JOIN TECHNICIAN t ON sr.TechnicianID = t.TechnicianID
            ORDER BY sr.DateLogged DESC
            LIMIT 5
        ');
        $recentRequests = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Graceful fallback if tables are not yet migrated
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="col-md-8 col-12">
        <h3 class="fw-bold mb-1">Welcome, <?= e($user['full_name']) ?></h3>
        <p class="text-muted small mb-0">Operations Dashboard &bull; <?= date('l, d F Y') ?></p>
    </div>
    <div class="col-md-4 col-12">
        <?php if (has_role(['Admin', 'FrontDesk'])): ?>
            <a href="<?= base_url('requests/create.php') ?>" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0 text-nowrap">
                <i class="bi bi-plus-circle"></i>
                <span class="d-none d-md-inline">New Service Request</span>
                <span class="d-md-none">New Request</span>
            </a>
            <a href="<?= base_url('customers/create.php') ?>" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0 text-nowrap">
                <i class="bi bi-person-plus"></i>
                <span class="d-none d-md-inline">Register Customer</span>
                <span class="d-md-none">New Customer</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Metric KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small text-uppercase fw-semibold">Total Requests</span>
                <h3 class="fw-bold mb-0 mt-1"><?= $totalRequests ?></h3>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-folder2-open"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small text-uppercase fw-semibold">Pending Triage</span>
                <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $pendingRequests ?></h3>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small text-uppercase fw-semibold">In Progress</span>
                <h3 class="fw-bold mb-0 mt-1 text-info"><?= $inProgressRequests ?></h3>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-gear-wide-connected"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small text-uppercase fw-semibold">Low-Stock Parts</span>
                <h3 class="fw-bold mb-0 mt-1 <?= $lowStockCount > 0 ? 'text-danger' : 'text-success' ?>"><?= $lowStockCount ?></h3>
            </div>
            <div class="stat-icon <?= $lowStockCount > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success' ?>">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<?php if ($user['role'] === 'Technician'): ?>
    <!-- Technician Assigned Jobs Section -->
    <div class="card card-assis mb-4">
        <div class="card-assis-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="bi bi-wrench-adjustable me-2"></i> My Active Assigned Jobs</h5>
            <a href="<?= base_url('technicians/workbench.php') ?>" class="btn btn-sm btn-outline-primary">Open Workbench</a>
        </div>
        <div class="table-responsive">
            <table class="table table-assis table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myAssignedJobs)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No active service jobs currently assigned to you.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myAssignedJobs as $job): ?>
                            <tr>
                                <td class="fw-bold">#SR<?= str_pad((string)$job['RequestID'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= e($job['CustomerName']) ?></td>
                                <td><?= e($job['ProductName']) ?> <span class="text-muted small">(<?= e($job['Model']) ?>)</span></td>
                                <td><span class="badge badge-priority-<?= strtolower($job['Priority']) ?>"><?= e($job['Priority']) ?></span></td>
                                <td><span class="badge badge-status badge-status-<?= strtolower(str_replace(' ', '-', $job['Status'])) ?>"><?= e($job['Status']) ?></span></td>
                                <td>
                                    <a href="<?= base_url('technicians/workbench.php?request_id=' . $job['RequestID']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil-square me-1"></i> Log Repair
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <!-- Admin & Front Desk Views -->
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card card-assis">
                <div class="card-assis-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i> Recent Service Requests</h5>
                    <a href="<?= base_url('requests/index.php') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-assis table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Assigned Tech</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentRequests)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No service requests recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentRequests as $req): ?>
                                    <tr>
                                        <td class="fw-bold">#SR<?= str_pad((string)$req['RequestID'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= e($req['CustomerName']) ?></td>
                                        <td><?= e($req['ProductName']) ?></td>
                                        <td><?= e($req['TechnicianName'] ?? 'Unassigned') ?></td>
                                        <td><span class="badge badge-status badge-status-<?= strtolower(str_replace(' ', '-', $req['Status'])) ?>"><?= e($req['Status']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= base_url('requests/view.php?id=' . $req['RequestID']) ?>" class="btn btn-sm btn-light border">
                                                <i class="bi bi-eye"></i>
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

        <div class="col-12 col-lg-4">
            <!-- Low-Stock Alerts Card -->
            <div class="card card-assis mb-4">
                <div class="card-assis-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-bell-fill me-1"></i> Low Stock Alerts</h6>
                    <a href="<?= base_url('inventory/index.php') ?>" class="small text-muted">Manage</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($lowStockParts)): ?>
                        <div class="p-3 text-center text-muted small">
                            <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                            All spare parts are above reorder thresholds.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($lowStockParts as $part): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                    <div>
                                        <div class="fw-semibold small"><?= e($part['PartName']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Reorder at: <?= $part['ReorderLevel'] ?></div>
                                    </div>
                                    <span class="badge bg-danger rounded-pill"><?= $part['QuantityInStock'] ?> in stock</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Access Links -->
            <div class="card card-assis">
                <div class="card-assis-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-lightning-charge me-1"></i> Quick Operations</h6>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="<?= base_url('requests/create.php') ?>" class="btn btn-light border text-start">
                        <i class="bi bi-journal-plus text-primary me-2"></i> Log New Repair Request
                    </a>
                    <a href="<?= base_url('customers/create.php') ?>" class="btn btn-light border text-start">
                        <i class="bi bi-person-plus text-success me-2"></i> Register New Customer
                    </a>
                    <?php if (has_role('Admin')): ?>
                        <a href="<?= base_url('reports/status.php') ?>" class="btn btn-light border text-start">
                            <i class="bi bi-file-earmark-bar-graph text-info me-2"></i> Generate Status Report
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>