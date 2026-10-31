<?php
/**
 * Spare Parts Inventory & Stock Control
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('Admin');

$db = get_db();
$error = null;

// Handle New Part Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_part'])) {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $partName = trim($_POST['part_name'] ?? '');
        $qty = (int)($_POST['quantity_in_stock'] ?? 0);
        $reorder = (int)($_POST['reorder_level'] ?? 5);
        $price = (float)($_POST['unit_price'] ?? 0.0);

        if ($partName === '' || $qty < 0 || $price <= 0) {
            $error = 'Please provide a valid part name, quantity, and unit price.';
        } else {
            $stmt = $db->prepare('
                INSERT INTO SPARE_PART (PartName, QuantityInStock, ReorderLevel, UnitPrice)
                VALUES (:name, :qty, :reorder, :price)
            ');
            $stmt->execute([
                ':name'    => $partName,
                ':qty'     => $qty,
                ':reorder' => $reorder,
                ':price'   => $price,
            ]);
            set_flash('success', 'Spare part added to catalogue.');
            header('Location: ' . base_url('inventory/index.php'));
            exit;
        }
    }
}

// Handle Stock Replenishment (Stock-In)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_in'])) {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $partId = (int)$_POST['part_id'];
        $addQty = (int)$_POST['add_quantity'];

        if ($partId > 0 && $addQty > 0) {
            $stmt = $db->prepare('UPDATE SPARE_PART SET QuantityInStock = QuantityInStock + :qty WHERE PartID = :pid');
            $stmt->execute([':qty' => $addQty, ':pid' => $partId]);
            set_flash('success', 'Stock quantity updated successfully.');
            header('Location: ' . base_url('inventory/index.php'));
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? '');
$filterLowStock = !empty($_GET['low_stock']);

$sql = 'SELECT * FROM SPARE_PART WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND PartName LIKE :q ';
    $params[':q'] = "%$search%";
}

if ($filterLowStock) {
    $sql .= ' AND QuantityInStock <= ReorderLevel ';
}

$sql .= ' ORDER BY (QuantityInStock <= ReorderLevel) DESC, PartName ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$parts = $stmt->fetchAll();

$pageTitle = 'Spare Parts Inventory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Spare Parts Inventory</h3>
        <p class="text-muted small mb-0">Track warehouse stock, trigger reorder alerts, and record inbound inventory replenishment.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 w-100 w-md-auto justify-content-center" data-bs-toggle="modal" data-bs-target="#newPartModal">
            <i class="bi bi-plus-circle"></i>
            <span class="d-none d-md-inline">Add Spare Part</span>
            <span class="d-md-none">Add Part</span>
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<!-- Filter & Search Controls -->
<div class="card card-assis mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by component name..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <a href="<?= base_url('inventory/index.php?low_stock=1') ?>" class="btn btn-outline-danger w-100 <?= $filterLowStock ? 'active' : '' ?>">
                    <i class="bi bi-bell me-1"></i> Low Stock Only
                </a>
                <?php if ($search !== '' || $filterLowStock): ?>
                    <a href="<?= base_url('inventory/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card card-assis">
    <div class="table-responsive">
        <table class="table table-assis table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Part Code</th>
                    <th>Component Name</th>
                    <th>Unit Price</th>
                    <th class="text-center">Stock Level</th>
                    <th class="text-center">Reorder Threshold</th>
                    <th>Inventory Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($parts)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No spare parts found.</td></tr>
                <?php else: ?>
                    <?php foreach ($parts as $p): ?>
                        <?php $isLowStock = ($p['QuantityInStock'] <= $p['ReorderLevel']); ?>
                        <tr class="<?= $isLowStock ? 'table-warning bg-opacity-25' : '' ?>">
                            <td class="fw-bold text-muted">#PART-<?= str_pad((string)$p['PartID'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td class="fw-semibold"><?= e($p['PartName']) ?></td>
                            <td><?= format_money($p['UnitPrice']) ?></td>
                            <td class="text-center fw-bold fs-6 <?= $isLowStock ? 'text-danger' : 'text-dark' ?>">
                                <?= $p['QuantityInStock'] ?>
                            </td>
                            <td class="text-center text-muted"><?= $p['ReorderLevel'] ?></td>
                            <td>
                                <?php if ($p['QuantityInStock'] == 0): ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php elseif ($isLowStock): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i> Reorder Level</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Adequate</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stockInModal" data-partid="<?= $p['PartID'] ?>" data-partname="<?= e($p['PartName']) ?>" onclick="prepareStockIn(this)">
                                    <i class="bi bi-box-arrow-in-down me-1"></i> Stock In
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add New Spare Part -->
<div class="modal fade" id="newPartModal" tabindex="-1" aria-labelledby="newPartModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="add_part" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="newPartModalLabel">Add Spare Part to Catalogue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="part_name">Part / Component Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="part_name" name="part_name" placeholder="e.g. LED Backlight Strip Array" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="quantity_in_stock">Initial Stock Quantity</label>
                            <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock" value="10" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="reorder_level">Reorder Level Threshold</label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" value="5" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="unit_price">Unit Selling Price (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" placeholder="e.g. 2500.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Spare Part</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Stock In / Restock -->
<div class="modal fade" id="stockInModal" tabindex="-1" aria-labelledby="stockInModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="stock_in" value="1">
                <input type="hidden" name="part_id" id="modal_stock_part_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="stockInModalLabel">Stock Inbound Inflow</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Restocking component: <strong id="modal_stock_part_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label" for="add_quantity">Quantity Delivered / Inbound <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_quantity" name="add_quantity" min="1" value="5" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Stock Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepareStockIn(btn) {
    document.getElementById('modal_stock_part_id').value = btn.getAttribute('data-partid');
    document.getElementById('modal_stock_part_name').innerText = btn.getAttribute('data-partname');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

