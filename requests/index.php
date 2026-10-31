<?php
/**
 * Service Request Management & Tracking
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$db = get_db();
$currentUser = current_user();

$statusFilter = trim($_GET['status'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = '
    SELECT sr.*, c.FullName AS CustomerName, c.PhoneNumber,
           p.ProductName, p.Model, p.SerialNumber,
           t.FullName AS TechnicianName
    FROM SERVICE_REQUEST sr
    JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
    JOIN PRODUCT p ON sr.ProductID = p.ProductID
    LEFT JOIN TECHNICIAN t ON sr.TechnicianID = t.TechnicianID
    WHERE 1=1
';
$params = [];

if ($currentUser['role'] === 'Technician' && !empty($currentUser['technician_id'])) {
    $sql .= ' AND sr.TechnicianID = :tech_id ';
    $params[':tech_id'] = $currentUser['technician_id'];
}

if ($statusFilter !== '') {
    $sql .= ' AND sr.Status = :status ';
    $params[':status'] = $statusFilter;
}

if ($priorityFilter !== '') {
    $sql .= ' AND sr.Priority = :priority ';
    $params[':priority'] = $priorityFilter;
}

if ($search !== '') {
    $sql .= ' AND (c.FullName LIKE :q OR c.PhoneNumber LIKE :q OR p.ProductName LIKE :q OR p.SerialNumber LIKE :q OR sr.IssueDescription LIKE :q) ';
    $params[':q'] = "%$search%";
}

$sql .= ' ORDER BY sr.DateLogged DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'Service Requests';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Service Requests</h3>
        <p class="text-muted small mb-0">Track customer repair tickets across the service lifecycle.</p>
    </div>
    <?php if (has_role(['Admin', 'FrontDesk'])): ?>
    <div>
        <a href="<?= base_url('requests/create.php') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center">
            <i class="bi bi-plus-circle"></i>
            <span class="d-none d-md-inline">New Service Request</span>
            <span class="d-md-none">New Request</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card card-assis">
    <div class="table-responsive">
        <table class="table table-assis table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Priority</th>
                    <th>Assigned Tech</th>
                    <th>Status</th>
                    <th>Date Logged</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No service requests match the specified criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td class="fw-bold">#SR<?= str_pad((string)$r['RequestID'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div><?= e($r['CustomerName']) ?></div>
                                <div class="text-muted small"><?= e($r['PhoneNumber']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($r['ProductName']) ?></div>
                                <div class="text-muted small font-monospace"><?= e($r['SerialNumber']) ?></div>
                            </td>
                            <td><span class="badge badge-priority-<?= strtolower($r['Priority']) ?>"><?= e($r['Priority']) ?></span></td>
                            <td>
                                <?php if ($r['TechnicianName']): ?>
                                    <span class="text-dark"><i class="bi bi-person me-1"></i><?= e($r['TechnicianName']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-status badge-status-<?= strtolower(str_replace(' ', '-', $r['Status'])) ?>"><?= e($r['Status']) ?></span></td>
                            <td class="small text-muted"><?= date('d M Y, H:i', strtotime($r['DateLogged'])) ?></td>
                            <td class="text-end">
                                <a href="<?= base_url('requests/view.php?id=' . $r['RequestID']) ?>" class="btn btn-sm btn-outline-primary">
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

