<?php

/**
 * Technician Workbench & Service Action Logging
 * Handles repair completion, atomic spare parts stock deduction, and automated invoice calculation
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'Technician']);

$db = get_db();
$currentUser = current_user();
$error = null;

$requestId = (int)($_GET['request_id'] ?? 0);
$selectedRequest = null;

if ($requestId > 0) {
    $stmt = $db->prepare('
        SELECT sr.*, c.FullName AS CustomerName, c.PhoneNumber,
               p.ProductName, p.Model, p.SerialNumber
        FROM SERVICE_REQUEST sr
        JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
        JOIN PRODUCT p ON sr.ProductID = p.ProductID
        WHERE sr.RequestID = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => $requestId]);
    $selectedRequest = $stmt->fetch();
}

// Fetch available spare parts catalog for selection
$spareParts = $db->query('SELECT * FROM SPARE_PART WHERE QuantityInStock > 0 ORDER BY PartName ASC')->fetchAll();

// Handle Completion Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_repair'])) {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $reqId = (int)$_POST['request_id'];
        $actionsTaken = trim($_POST['actions_taken'] ?? '');
        $labourCost = (float)($_POST['labour_cost'] ?? 0.0);
        $partsSubmitted = $_POST['parts'] ?? []; // array of ['part_id' => ..., 'qty' => ...]

        if ($reqId <= 0 || $actionsTaken === '' || $labourCost < 0) {
            $error = 'Please provide the actions taken and a valid labour charge.';
        } else {
            try {
                $db->beginTransaction();

                // 1. Insert SERVICE_RECORD
                $stmt = $db->prepare('
                    INSERT INTO SERVICE_RECORD (RequestID, ActionsTaken, CompletionDate, LabourCost)
                    VALUES (:rid, :actions, CURDATE(), :labour)
                ');
                $stmt->execute([
                    ':rid'     => $reqId,
                    ':actions' => $actionsTaken,
                    ':labour'  => $labourCost,
                ]);
                $recordId = $db->lastInsertId();

                // 2. Process Spare Parts Used & Deduct Stock
                $totalPartsCost = 0.0;

                if (!empty($partsSubmitted) && is_array($partsSubmitted)) {
                    $insertPartUsed = $db->prepare('
                        INSERT INTO PARTS_USED (RecordID, PartID, QuantityUsed)
                        VALUES (:rec_id, :pid, :qty)
                    ');

                    $deductStock = $db->prepare('
                        UPDATE SPARE_PART
                        SET QuantityInStock = QuantityInStock - :qty
                        WHERE PartID = :pid AND QuantityInStock >= :qty
                    ');

                    $getPartPrice = $db->prepare('SELECT UnitPrice, QuantityInStock, PartName FROM SPARE_PART WHERE PartID = :pid FOR UPDATE');

                    foreach ($partsSubmitted as $item) {
                        $partId = (int)($item['part_id'] ?? 0);
                        $qty = (int)($item['qty'] ?? 0);

                        if ($partId > 0 && $qty > 0) {
                            $getPartPrice->execute([':pid' => $partId]);
                            $partRow = $getPartPrice->fetch();

                            if (!$partRow || $partRow['QuantityInStock'] < $qty) {
                                throw new Exception('Insufficient inventory for spare part: ' . ($partRow['PartName'] ?? "ID $partId"));
                            }

                            // Insert usage
                            $insertPartUsed->execute([
                                ':rec_id' => $recordId,
                                ':pid'    => $partId,
                                ':qty'    => $qty,
                            ]);

                            // Deduct from stock
                            $deductStock->execute([
                                ':qty' => $qty,
                                ':pid' => $partId,
                            ]);

                            $totalPartsCost += ((float)$partRow['UnitPrice'] * $qty);
                        }
                    }
                }

                // 3. Automated Invoicing: AmountDue = LabourCost + TotalPartsCost
                $totalAmountDue = $labourCost + $totalPartsCost;

                $stmt = $db->prepare('
                    INSERT INTO INVOICE (RequestID, AmountDue, PaymentStatus, DateIssued)
                    VALUES (:rid, :amount, \'Unpaid\', NOW())
                ');
                $stmt->execute([
                    ':rid'    => $reqId,
                    ':amount' => $totalAmountDue,
                ]);

                // 4. Update Request Status to 'Completed'
                $stmt = $db->prepare('UPDATE SERVICE_REQUEST SET Status = \'Completed\' WHERE RequestID = :rid');
                $stmt->execute([':rid' => $reqId]);

                $db->commit();
                set_flash('success', 'Repair completed successfully. Invoice #INV generated for ' . format_money($totalAmountDue) . '.');
                header('Location: ' . base_url('requests/view.php?id=' . $reqId));
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'Transaction failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch assigned jobs if no specific request is selected
$assignedJobs = [];
if (!$selectedRequest) {
    $sql = '
        SELECT sr.*, c.FullName AS CustomerName, p.ProductName, p.Model, p.SerialNumber
        FROM SERVICE_REQUEST sr
        JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
        JOIN PRODUCT p ON sr.ProductID = p.ProductID
        WHERE sr.Status IN (\'Pending\', \'In Progress\')
    ';
    $params = [];

    if ($currentUser['role'] === 'Technician' && !empty($currentUser['technician_id'])) {
        $sql .= ' AND sr.TechnicianID = :tid ';
        $params[':tid'] = $currentUser['technician_id'];
    }

    $sql .= ' ORDER BY sr.Priority = \'High\' DESC, sr.DateLogged ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $assignedJobs = $stmt->fetchAll();
}

$pageTitle = 'Technician Workbench';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap md:mt-2">
    <div>
        <h3 class="fw-bold mb-1">Technician Workbench</h3>
        <p class="text-muted small mb-0">Record repair actions taken, report consumed spare parts, and finalize service records.</p>
    </div>
    <?php if ($selectedRequest): ?>
        <a href="<?= base_url('technicians/workbench.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Queue
        </a>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if (!$selectedRequest): ?>
    <!-- Active Assigned Queue -->
    <div class="card card-assis">
        <div class="card-assis-header">
            <h5 class="fw-bold mb-0"><i class="bi bi-list-task me-2"></i> Work Orders Awaiting Service Execution</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-assis table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Customer</th>
                        <th>Equipment</th>
                        <th>Issue Reported</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignedJobs)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No pending or in-progress repair jobs found in your queue.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignedJobs as $j): ?>
                            <tr>
                                <td class="fw-bold">#SR<?= str_pad((string)$j['RequestID'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= e($j['CustomerName']) ?></td>
                                <td><?= e($j['ProductName']) ?> (<?= e($j['Model']) ?>)</td>
                                <td class="text-truncate" style="max-width: 250px;"><?= e($j['IssueDescription']) ?></td>
                                <td><span class="badge badge-priority-<?= strtolower($j['Priority']) ?>"><?= e($j['Priority']) ?></span></td>
                                <td><span class="badge badge-status badge-status-<?= strtolower(str_replace(' ', '-', $j['Status'])) ?>"><?= e($j['Status']) ?></span></td>
                                <td class="text-end">
                                    <a href="<?= base_url('technicians/workbench.php?request_id=' . $j['RequestID']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil-square me-1"></i> Record Repair
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
    <!-- Specific Repair Completion Form -->
    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card card-assis mb-4">
                <div class="card-assis-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2"></i> Work Order Specifications</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted small d-block">Ticket ID</span>
                        <span class="fw-bold fs-5">#SR<?= str_pad((string)$selectedRequest['RequestID'], 4, '0', STR_PAD_LEFT) ?></span>
                        <span class="badge badge-priority-<?= strtolower($selectedRequest['Priority']) ?> ms-2"><?= e($selectedRequest['Priority']) ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Customer & Contact</span>
                        <span class="fw-semibold"><?= e($selectedRequest['CustomerName']) ?></span>
                        <div class="small text-muted"><?= e($selectedRequest['PhoneNumber']) ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Product & Serial</span>
                        <span><?= e($selectedRequest['ProductName']) ?> (<?= e($selectedRequest['Model']) ?>)</span>
                        <div class="small font-monospace text-muted"><?= e($selectedRequest['SerialNumber']) ?></div>
                    </div>
                    <hr>
                    <div>
                        <span class="text-muted small d-block mb-1">Customer Fault Description</span>
                        <div class="p-3 bg-light rounded border small">
                            <?= nl2br(e($selectedRequest['IssueDescription'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card card-assis">
                <div class="card-assis-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clipboard-check me-2"></i> Technical Service Completion</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="complete_repair" value="1">
                        <input type="hidden" name="request_id" value="<?= $selectedRequest['RequestID'] ?>">

                        <div class="mb-3">
                            <label class="form-label" for="actions_taken">Repair Actions Performed <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="actions_taken" name="actions_taken" rows="4" placeholder="Detail the diagnostic procedures, technical interventions, and testing performed..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="labour_cost">Labour Charges (KES) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="labour_cost" name="labour_cost" placeholder="0.00" required value="1500.00">
                        </div>

                        <!-- Spare Parts Consumed Section -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0 fw-bold">Spare Parts Consumed</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPartRow()">
                                    <i class="bi bi-plus me-1"></i> Add Part
                                </button>
                            </div>
                            <div id="partsContainer">
                                <!-- Dynamic Rows -->
                            </div>
                            <div class="form-text">Parts selected will be automatically deducted from the warehouse inventory upon submission.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="<?= base_url('technicians/workbench.php') ?>" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-success" data-confirm="Are you sure you want to finalize this repair? An invoice will be automatically generated and inventory stock deducted.">
                                <i class="bi bi-check-circle me-1"></i> Finalize & Generate Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script to dynamically add/remove parts -->
    <script>
        const sparePartsList = <?= json_encode($spareParts) ?>;

        function addPartRow() {
            const container = document.getElementById('partsContainer');
            const index = container.children.length;

            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 align-items-center part-row';

            let optionsHtml = '<option value="">-- Select Spare Part --</option>';
            sparePartsList.forEach(part => {
                optionsHtml += `<option value="${part.PartID}" data-price="${part.UnitPrice}">
                ${part.PartName} (${part.QuantityInStock} in stock @ KES ${parseFloat(part.UnitPrice).toFixed(2)})
            </option>`;
            });

            row.innerHTML = `
            <div class="col-8">
                <select name="parts[${index}][part_id]" class="form-select form-select-sm" required>
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-3">
                <input type="number" name="parts[${index}][qty]" class="form-control form-select-sm" placeholder="Qty" min="1" value="1" required>
            </div>
            <div class="col-1 text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" title="Remove Part" onclick="this.closest('.part-row').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

            container.appendChild(row);
        }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>