<?php
/**
 * Customer Feedback Capture Interface
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['Admin', 'FrontDesk']);

$db = get_db();
$requestId = (int)($_GET['request_id'] ?? 0);
$error = null;

if ($requestId <= 0) {
    set_flash('danger', 'Invalid service ticket ID.');
    header('Location: ' . base_url('requests/index.php'));
    exit;
}

// Fetch request details
$stmt = $db->prepare('
    SELECT sr.*, c.FullName AS CustomerName, p.ProductName
    FROM SERVICE_REQUEST sr
    JOIN CUSTOMER c ON sr.CustomerID = c.CustomerID
    JOIN PRODUCT p ON sr.ProductID = p.ProductID
    WHERE sr.RequestID = :id
    LIMIT 1
');
$stmt->execute([':id' => $requestId]);
$req = $stmt->fetch();

if (!$req) {
    set_flash('danger', 'Service ticket not found.');
    header('Location: ' . base_url('requests/index.php'));
    exit;
}

if (!in_array($req['Status'], ['Completed', 'Closed'], true)) {
    set_flash('warning', 'Feedback can only be recorded for completed service tickets.');
    header('Location: ' . base_url('requests/view.php?id=' . $requestId));
    exit;
}

// Check if feedback already submitted
$check = $db->prepare('SELECT FeedbackID FROM FEEDBACK WHERE RequestID = :rid LIMIT 1');
$check->execute([':rid' => $requestId]);
if ($check->fetch()) {
    set_flash('warning', 'Feedback for this service ticket has already been recorded.');
    header('Location: ' . base_url('requests/view.php?id=' . $requestId));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comments = trim($_POST['comments'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a valid rating between 1 and 5 stars.';
        } else {
            try {
                $stmt = $db->prepare('
                    INSERT INTO FEEDBACK (RequestID, Rating, Comments, FeedbackDate)
                    VALUES (:rid, :rating, :comments, NOW())
                ');
                $stmt->execute([
                    ':rid'      => $requestId,
                    ':rating'   => $rating,
                    ':comments' => $comments ?: null,
                ]);

                set_flash('success', 'Customer feedback captured successfully.');
                header('Location: ' . base_url('requests/view.php?id=' . $requestId));
                exit;
            } catch (Exception $e) {
                $error = 'Failed to submit feedback: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Capture Feedback: Ticket #SR' . str_pad((string)$req['RequestID'], 4, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold mb-1">Customer Feedback</h3>
                <p class="text-muted small mb-0">Record post-service rating and feedback for Ticket #SR<?= str_pad((string)$req['RequestID'], 4, '0', STR_PAD_LEFT) ?>.</p>
            </div>
            <a href="<?= base_url('requests/view.php?id=' . $req['RequestID']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="card card-assis">
            <div class="card-assis-header">
                <h5 class="fw-bold mb-0"><i class="bi bi-star me-2"></i> Service Evaluation</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3 p-3 bg-light rounded border">
                    <div class="small text-muted">Customer: <strong class="text-dark"><?= e($req['CustomerName']) ?></strong></div>
                    <div class="small text-muted">Equipment: <strong class="text-dark"><?= e($req['ProductName']) ?></strong></div>
                </div>

                <form method="POST" action="">
                    <?= csrf_field() ?>

                    <div class="mb-4 text-center">
                        <label class="form-label d-block fw-bold mb-2">Overall Service Satisfaction Rating (1 to 5 Stars)</label>
                        <div class="btn-group" role="group" aria-label="Rating Selection">
                            <?php for ($r = 1; $r <= 5; $r++): ?>
                                <input type="radio" class="btn-check" name="rating" id="rating<?= $r ?>" value="<?= $r ?>" autocomplete="off" <?= $r === 1 ? 'required' : '' ?>>
                                <label class="btn btn-outline-warning text-dark px-3 py-2" for="rating<?= $r ?>">
                                    <span class="fs-5 fw-bold"><?= $r ?> <i class="bi bi-star-fill text-warning"></i></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="comments">Customer Comments / Remarks (Optional)</label>
                        <textarea class="form-control" id="comments" name="comments" rows="3" placeholder="Enter any qualitative remarks or compliments provided by the client..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= base_url('requests/view.php?id=' . $req['RequestID']) ?>" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save Customer Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
