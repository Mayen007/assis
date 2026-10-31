<?php
/**
 * Customer Feedback Directory & Quality Metrics
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$db = get_db();

// Calculate feedback metrics
$avgRating = (float)$db->query('SELECT COALESCE(AVG(Rating), 0) FROM FEEDBACK')->fetchColumn();
$totalFeedback = (int)$db->query('SELECT COUNT(*) FROM FEEDBACK')->fetchColumn();

// Fetch feedback entries
$stmt = $db->query('
    SELECT f.*,
           sr.RequestID, sr.DateLogged,
           c.FullName AS CustomerName, c.PhoneNumber,
           p.ProductName,
           t.FullName AS TechnicianName
    FROM FEEDBACK f
    JOIN SERVICE_REQUEST sr ON f.RequestID = sr.RequestID
    JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
    JOIN PRODUCT p ON sr.ProductID = p.ProductID
    LEFT JOIN TECHNICIAN t ON sr.TechnicianID = t.TechnicianID
    ORDER BY f.FeedbackDate DESC
');
$feedbacks = $stmt->fetchAll();

$pageTitle = 'Customer Feedback';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-1">Customer Feedback</h3>
        <p class="text-muted small mb-0">Review post-service client satisfaction ratings and qualitative commentary.</p>
    </div>
</div>

<!-- Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small text-uppercase fw-semibold">Mean Customer Satisfaction</span>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <h3 class="fw-bold mb-0 text-warning"><?= number_format($avgRating, 1) ?> / 5.0</h3>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-star-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small text-uppercase fw-semibold">Total Responses Logged</span>
                <h3 class="fw-bold mb-0 mt-1"><?= $totalFeedback ?></h3>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-chat-heart"></i>
            </div>
        </div>
    </div>
</div>

<div class="card card-assis">
    <div class="table-responsive">
        <table class="table table-assis table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket Ref</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Servicing Technician</th>
                    <th>Rating</th>
                    <th>Client Comments</th>
                    <th>Date Logged</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbacks)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No customer feedback records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('requests/view.php?id=' . $fb['RequestID']) ?>" class="fw-bold text-decoration-none">
                                    #SR<?= str_pad((string)$fb['RequestID'], 4, '0', STR_PAD_LEFT) ?>
                                </a>
                            </td>
                            <td><?= e($fb['CustomerName']) ?></td>
                            <td><?= e($fb['ProductName']) ?></td>
                            <td><?= e($fb['TechnicianName'] ?? 'Unassigned') ?></td>
                            <td>
                                <div class="rating-stars text-nowrap">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= $fb['Rating'] ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="text-dark small ms-1">(<?= $fb['Rating'] ?>)</span>
                                </div>
                            </td>
                            <td><?= e($fb['Comments'] ?: '—') ?></td>
                            <td class="small text-muted"><?= date('d M Y', strtotime($fb['FeedbackDate'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
