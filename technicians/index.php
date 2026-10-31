<?php
/**
 * Technician Directory & Workload Overview
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

$db = get_db();

// Handle new technician addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_technician'])) {
    if (verify_csrf()) {
        $fullName = trim($_POST['full_name'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($fullName !== '' && $specialization !== '') {
            $stmt = $db->prepare('
                INSERT INTO TECHNICIAN (FullName, Specialization, Phone, Availability)
                VALUES (:name, :spec, :phone, \'Available\')
            ');
            $stmt->execute([
                ':name'  => $fullName,
                ':spec'  => $specialization,
                ':phone' => $phone ?: null,
            ]);
            set_flash('success', 'Technician record created.');
            header('Location: ' . base_url('technicians/index.php'));
            exit;
        }
    }
}

// Fetch technicians with active workload count
$stmt = $db->query('
    SELECT t.*,
           COUNT(CASE WHEN sr.Status IN (\'Pending\', \'In Progress\') THEN 1 END) AS ActiveJobsCount,
           COUNT(CASE WHEN sr.Status IN (\'Completed\', \'Closed\') THEN 1 END) AS CompletedJobsCount
    FROM TECHNICIAN t
    LEFT JOIN SERVICE_REQUEST sr ON t.TechnicianID = sr.TechnicianID
    GROUP BY t.TechnicianID
    ORDER BY t.FullName ASC
');
$technicians = $stmt->fetchAll();

$pageTitle = 'Technician Management';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Technician Management</h3>
        <p class="text-muted small mb-0">Monitor service specialist workload, areas of specialization, and availability status.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center" data-bs-toggle="modal" data-bs-target="#newTechModal">
            <i class="bi bi-person-plus"></i>
            <span class="d-none d-md-inline">Add Specialist</span>
            <span class="d-md-none">New Specialist</span>
        </button>
    </div>
</div>

<div class="card card-assis">
    <div class="table-responsive">
        <table class="table table-assis table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Specialization</th>
                    <th>Phone</th>
                    <th>Availability</th>
                    <th>Active Workload</th>
                    <th>Completed Repairs</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($technicians)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No technicians registered.</td></tr>
                <?php else: ?>
                    <?php foreach ($technicians as $t): ?>
                        <tr>
                            <td class="fw-bold text-muted">#TECH-<?= str_pad((string)$t['TechnicianID'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td class="fw-semibold"><?= e($t['FullName']) ?></td>
                            <td><?= e($t['Specialization']) ?></td>
                            <td><?= e($t['Phone'] ?: 'â€”') ?></td>
                            <td>
                                <?php if ($t['Availability'] === 'Available'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Available</span>
                                <?php elseif ($t['Availability'] === 'Busy'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Busy</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">On Leave</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= $t['ActiveJobsCount'] ?> Active Work Order(s)
                                </span>
                            </td>
                            <td class="text-muted"><?= $t['CompletedJobsCount'] ?> Jobs</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add New Technician -->
<div class="modal fade" id="newTechModal" tabindex="-1" aria-labelledby="newTechModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="add_technician" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="newTechModalLabel">Add Technician Specialist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="full_name">Technician Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g. David Ochieng" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="specialization">Specialization / Expertise <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="specialization" name="specialization" placeholder="e.g. Refrigeration & Cooling" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g. 0711000101">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Technician</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

