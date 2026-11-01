<?php
/**
 * Detailed Service Request Lifecycle View
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$requestId = (int)($_GET['id'] ?? 0);
if ($requestId <= 0) {
    set_flash('danger', 'Invalid service request ID.');
    header('Location: ' . base_url('requests/index.php'));
    exit;
}

$db = get_db();
$currentUser = current_user();

// Fetch complete Request details
$stmt = $db->prepare('
    SELECT sr.*,
           c.FullName AS CustomerName, c.PhoneNumber, c.Email, c.PhysicalAddress,
           p.ProductName, p.Model, p.SerialNumber, p.PurchaseDate,
           w.WarrantyID, w.StartDate AS WarrantyStart, w.EndDate AS WarrantyEnd, w.Terms AS WarrantyTerms,
           t.FullName AS TechnicianName, t.Specialization, t.Phone AS TechnicianPhone
    FROM SERVICE_REQUEST sr
    JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
    JOIN PRODUCT p ON sr.ProductID = p.ProductID
    LEFT JOIN WARRANTY w ON p.ProductID = w.ProductID
    LEFT JOIN TECHNICIAN t ON sr.TechnicianID = t.TechnicianID
    WHERE sr.RequestID = :id
    LIMIT 1
');
$stmt->execute([':id' => $requestId]);
$req = $stmt->fetch();

if (!$req) {
    set_flash('danger', 'Service request ticket not found.');
    header('Location: ' . base_url('requests/index.php'));
    exit;
}

if (
    $currentUser['role'] === 'Technician'
    && (int)$req['TechnicianID'] !== (int)$currentUser['technician_id']
) {
    set_flash('danger', 'Access denied. You are only authorized to view service requests assigned to you.');
    header('Location: ' . base_url('requests/index.php'));
    exit;
}

// Handle Technician Assignment submission (Supervisor / Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_technician'])) {
    require_role(['Admin', 'FrontDesk']);
    if (verify_csrf()) {
        $techId = (int)$_POST['technician_id'];
        if ($techId > 0) {
            $assignmentError = validate_technician_assignment($db, $techId, (int)$req['ProductID'], $req['IssueDescription'], $requestId);

            if ($assignmentError) {
                set_flash('danger', $assignmentError);
            } else {
                $previousTechId = (int)$req['TechnicianID'];
                $update = $db->prepare('
                    UPDATE SERVICE_REQUEST
                    SET TechnicianID = :tid, Status = CASE WHEN Status = \'Pending\' THEN \'In Progress\' ELSE Status END
                    WHERE RequestID = :rid
                ');
                $update->execute([':tid' => $techId, ':rid' => $requestId]);

                sync_technician_availability($db, $techId);
                if ($previousTechId && $previousTechId !== $techId) {
                    sync_technician_availability($db, $previousTechId);
                }

                set_flash('success', 'Technician assigned successfully.');
            }
            header('Location: ' . base_url('requests/view.php?id=' . $requestId));
            exit;
        }
    }
}

// Fetch Service Record if exists
$stmt = $db->prepare('SELECT * FROM SERVICE_RECORD WHERE RequestID = :rid LIMIT 1');
$stmt->execute([':rid' => $requestId]);
$serviceRecord = $stmt->fetch();

$partsUsed = [];
if ($serviceRecord) {
    $stmt = $db->prepare('
        SELECT pu.*, sp.PartName, sp.UnitPrice
        FROM PARTS_USED pu
        JOIN SPARE_PART sp ON pu.PartID = sp.PartID
        WHERE pu.RecordID = :rec_id
    ');
    $stmt->execute([':rec_id' => $serviceRecord['RecordID']]);
    $partsUsed = $stmt->fetchAll();
}

// Fetch Invoice if exists
$stmt = $db->prepare('SELECT * FROM INVOICE WHERE RequestID = :rid LIMIT 1');
$stmt->execute([':rid' => $requestId]);
$invoice = $stmt->fetch();

// Fetch Feedback if exists
$stmt = $db->prepare('SELECT * FROM FEEDBACK WHERE RequestID = :rid LIMIT 1');
$stmt->execute([':rid' => $requestId]);
$feedback = $stmt->fetch();

// Available technicians for assignment
$availableTechnicians = $db->query("
    SELECT t.TechnicianID, t.FullName, t.Specialization, t.Availability,
           COUNT(CASE WHEN sr.Status IN ('Pending', 'In Progress') AND sr.RequestID != {$requestId} THEN 1 END) AS ActiveJobs
    FROM TECHNICIAN t
    LEFT JOIN SERVICE_REQUEST sr ON t.TechnicianID = sr.TechnicianID
    WHERE t.Availability != 'On Leave'
    GROUP BY t.TechnicianID
    ORDER BY t.FullName ASC
")->fetchAll();

$isWarrantyActive = !empty($req['WarrantyEnd']) && (strtotime($req['WarrantyEnd']) >= time());

$pageTitle = 'Request #SR' . str_pad((string)$req['RequestID'], 4, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <h3 class="fw-bold mb-0">Ticket #SR<?= str_pad((string)$req['RequestID'], 4, '0', STR_PAD_LEFT) ?></h3>
            <span class="badge badge-status badge-status-<?= strtolower(str_replace(' ', '-', $req['Status'])) ?> fs-6">
                <?= e($req['Status']) ?>
            </span>
            <span class="badge badge-priority-<?= strtolower($req['Priority']) ?> fs-6">
                <?= e($req['Priority']) ?> Priority
            </span>
        </div>
        <p class="text-muted small mb-0">Logged on <?= date('d F Y, H:i', strtotime($req['DateLogged'])) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap flex-md-nowrap w-100 w-md-auto">
        <button type="button" class="btn btn-outline-secondary btn-print-sheet d-inline-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0 text-nowrap">
            <i class="bi bi-printer"></i>
            <span class="d-none d-md-inline">Print Ticket</span>
            <span class="d-md-none">Print</span>
        </button>
        <a href="<?= base_url('requests/index.php') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-2 flex-fill flex-md-grow-0 text-nowrap">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-md-inline">Back to Requests</span>
            <span class="d-md-none">Back</span>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Customer & Equipment Info -->
    <div class="col-12 col-lg-8">
        <div class="card card-assis mb-4">
            <div class="card-assis-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-laptop me-2"></i> Equipment & Customer Details</h6>
                <?php if ($req['WarrantyEnd']): ?>
                    <?php if ($isWarrantyActive): ?>
                        <span class="badge bg-success">Under Warranty (Valid to <?= date('d M Y', strtotime($req['WarrantyEnd'])) ?>)</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Warranty Expired</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <span class="text-muted small d-block">Customer Name</span>
                        <a href="<?= base_url('customers/view.php?id=' . $req['CustomerID']) ?>" class="fw-semibold text-decoration-none">
                            <?= e($req['CustomerName']) ?>
                        </a>
                    </div>
                    <div class="col-12 col-md-6">
                        <span class="text-muted small d-block">Phone Number</span>
                        <span><?= e($req['PhoneNumber']) ?></span>
                    </div>
                    <div class="col-12 col-md-6">
                        <span class="text-muted small d-block">Product / Equipment</span>
                        <span class="fw-semibold"><?= e($req['ProductName']) ?></span>
                    </div>
                    <div class="col-12 col-md-6">
                        <span class="text-muted small d-block">Model & Serial Number</span>
                        <span><?= e($req['Model']) ?> &bull; <code><?= e($req['SerialNumber']) ?></code></span>
                    </div>
                </div>

                <hr>

                <div>
                    <span class="text-muted small d-block mb-1">Reported Fault Description</span>
                    <div class="p-3 bg-light rounded border text-dark">
                        <?= nl2br(e($req['IssueDescription'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Execution / Repair Details -->
        <div class="card card-assis">
            <div class="card-assis-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-wrench-adjustable-circle me-2"></i> Technical Repair Record</h6>
                <?php if (in_array($req['Status'], ['Pending', 'In Progress'], true)): ?>
                    <?php if ($currentUser['role'] === 'Technician' || $currentUser['role'] === 'Admin'): ?>
                        <a href="<?= base_url('technicians/workbench.php?request_id=' . $req['RequestID']) ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil-square me-1"></i> Open Workbench / Complete Job
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$serviceRecord): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-tools fs-1 text-secondary opacity-50 d-block mb-2"></i>
                        No repair actions have been recorded yet. Work is pending or currently in progress.
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Actions Performed by Technician</span>
                        <div class="p-3 bg-light rounded border">
                            <?= nl2br(e($serviceRecord['ActionsTaken'])) ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <span class="text-muted small d-block">Completion Date</span>
                            <span class="fw-semibold"><?= date('d F Y', strtotime($serviceRecord['CompletionDate'])) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Labor Charge</span>
                            <span class="fw-semibold"><?= format_money($serviceRecord['LabourCost']) ?></span>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2 small text-uppercase text-muted">Spare Parts Consumed</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Part Description</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($partsUsed)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">No spare parts were consumed for this repair.</td></tr>
                                <?php else: ?>
                                    <?php
                                    $partsSubtotal = 0;
                                    foreach ($partsUsed as $pu):
                                        $sub = $pu['QuantityUsed'] * $pu['UnitPrice'];
                                        $partsSubtotal += $sub;
                                    ?>
                                        <tr>
                                            <td><?= e($pu['PartName']) ?></td>
                                            <td class="text-center"><?= $pu['QuantityUsed'] ?></td>
                                            <td class="text-end"><?= format_money($pu['UnitPrice']) ?></td>
                                            <td class="text-end fw-semibold"><?= format_money($sub) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-light fw-bold">
                                        <td colspan="3" class="text-end">Total Parts Cost:</td>
                                        <td class="text-end"><?= format_money($partsSubtotal) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: Assignment, Invoicing, Feedback -->
    <div class="col-12 col-lg-4">
        <!-- Assigned Technician Card -->
        <div class="card card-assis mb-4">
            <div class="card-assis-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-gear me-2"></i> Technician Assignment</h6>
            </div>
            <div class="card-body">
                <?php if ($req['TechnicianName']): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?= e($req['TechnicianName']) ?></div>
                            <div class="text-muted small"><?= e($req['Specialization']) ?></div>
                            <?php if ($req['TechnicianPhone']): ?>
                                <div class="small"><i class="bi bi-telephone me-1"></i><?= e($req['TechnicianPhone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="bi bi-exclamation-circle me-1"></i> No technician assigned to this ticket yet.
                    </div>
                <?php endif; ?>

                <?php if (has_role(['Admin', 'FrontDesk'])): ?>
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="assign_technician" value="1">
                        <label class="form-label small" for="technician_id"><?= $req['TechnicianName'] ? 'Reassign Technician' : 'Assign Specialist' ?></label>
                        <div class="input-group">
                            <select class="form-select form-select-sm" name="technician_id" id="technician_id" required>
                                <option value="">-- Choose Technician --</option>
                                <?php foreach ($availableTechnicians as $at): ?>
                                    <?php
                                    $isCurrent = ((int)$req['TechnicianID'] === (int)$at['TechnicianID']);
                                    $productTitle = $req['ProductName'] . ' ' . $req['Model'];
                                    $isCompatible = is_technician_specialization_compatible($at['Specialization'], $productTitle, $req['IssueDescription']);
                                    $isBusy = ($at['Availability'] === 'Busy' || (int)$at['ActiveJobs'] >= MAX_TECHNICIAN_ACTIVE_JOBS) && !$isCurrent;
                                    $tag = '';
                                    if ($isBusy) {
                                        $tag = ' [Busy - ' . $at['ActiveJobs'] . ' jobs]';
                                    } elseif ($isCompatible) {
                                        $tag = ' ★ [Recommended Specialist]';
                                    }
                                    ?>
                                    <option value="<?= $at['TechnicianID'] ?>" <?= $isCurrent ? 'selected' : '' ?> <?= $isBusy ? 'disabled' : '' ?>>
                                        <?= e($at['FullName']) ?> (<?= e($at['Specialization']) ?>)<?= $tag ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Assign</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Invoicing & Billing Card -->
        <div class="card card-assis mb-4">
            <div class="card-assis-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2"></i> Service Invoice</h6>
                <?php if ($invoice): ?>
                    <span class="badge badge-payment-<?= strtolower(str_replace(' ', '', $invoice['PaymentStatus'])) ?>">
                        <?= e($invoice['PaymentStatus']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$invoice): ?>
                    <p class="text-muted small mb-0">Invoice will be automatically generated upon technical completion of the service record.</p>
                <?php else: ?>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Invoice Number</span>
                        <span class="fw-bold fs-6">#INV-<?= str_pad((string)$invoice['InvoiceID'], 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Total Amount Due</span>
                        <span class="fw-bold fs-4 text-primary"><?= format_money($invoice['AmountDue']) ?></span>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('billing/view.php?id=' . $invoice['InvoiceID']) ?>" class="btn btn-outline-primary">
                            <i class="bi bi-file-earmark-text me-1"></i> View Printable Invoice
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer Feedback Card -->
        <div class="card card-assis">
            <div class="card-assis-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-star-half me-2"></i> Customer Feedback</h6>
            </div>
            <div class="card-body">
                <?php if ($feedback): ?>
                    <div class="rating-stars mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?= $i <= $feedback['Rating'] ? '-fill' : '' ?>"></i>
                        <?php endfor; ?>
                        <span class="text-dark small ms-1">(<?= $feedback['Rating'] ?>/5)</span>
                    </div>
                    <?php if ($feedback['Comments']): ?>
                        <div class="p-2 bg-light rounded small mb-2 fst-italic">
                            "<?= e($feedback['Comments']) ?>"
                        </div>
                    <?php endif; ?>
                    <div class="text-muted small">Recorded on <?= date('d M Y', strtotime($feedback['FeedbackDate'])) ?></div>
                <?php else: ?>
                    <p class="text-muted small mb-3">No customer feedback has been captured for this ticket.</p>
                    <?php if (in_array($req['Status'], ['Completed', 'Closed'], true) && has_role(['Admin', 'FrontDesk'])): ?>
                        <a href="<?= base_url('feedback/create.php?request_id=' . $req['RequestID']) ?>" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-chat-heart me-1"></i> Capture Feedback
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
