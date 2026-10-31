<?php

/**
 * Database Setup & Initialization Utility
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/config/database.php';

$status = [];
$tableReports = [];
$error = null;
$action = $_GET['action'] ?? '';

try {
    // 1. Check Server Connection
    $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $status[] = ['title' => 'MySQL Server Connection', 'state' => 'success', 'message' => 'Connected successfully to MySQL server.'];

    // 2. Create Database if not exists
    $serverPdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', DB_NAME));
    $status[] = ['title' => 'Database Creation', 'state' => 'success', 'message' => "Database '" . DB_NAME . "' is ready."];

    // 3. Connect to assis_db
    $db = get_db();

    // 4. Initialize Schema or Clear Data if requested
    if ($action === 'reset' || $action === 'init_schema') {
        $schemaFile = __DIR__ . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            execute_sql_file($db, $schemaFile);
        }

        $status[] = ['title' => 'Schema Initialization', 'state' => 'success', 'message' => 'Relational database schema initialized. Ready for administrator account creation.'];
    } elseif ($action === 'clear') {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $tablesToClear = [
            "FEEDBACK", "INVOICE", "PARTS_USED", "SERVICE_RECORD",
            "SERVICE_REQUEST", "WARRANTY", "PRODUCT", "CUSTOMER", "SPARE_PART", "TECHNICIAN"
        ];
        foreach ($tablesToClear as $table) {
            $db->exec("TRUNCATE TABLE `$table`");
        }
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $status[] = ['title' => 'Data Cleanup', 'state' => 'success', 'message' => 'All transactional records removed. System is clean for end-to-end testing.'];
    } elseif ($action === 'wipe_users') {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $db->exec("TRUNCATE TABLE `USERS`");
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $status[] = ['title' => 'Users Reset', 'state' => 'success', 'message' => 'All user accounts wiped. You can now create a fresh Administrator account.'];
    }

    // 5. Verify Table Counts
    $expectedTables = [
        'TECHNICIAN',
        'USERS',
        'CUSTOMER',
        'PRODUCT',
        'WARRANTY',
        'SERVICE_REQUEST',
        'SERVICE_RECORD',
        'SPARE_PART',
        'PARTS_USED',
        'INVOICE',
        'FEEDBACK'
    ];

    $tableReports = [];
    foreach ($expectedTables as $t) {
        try {
            $count = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $tableReports[$t] = $count;
        } catch (Exception $e) {
            $tableReports[$t] = 'Missing';
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup & Verification | ASSIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f1f5f9;
            padding: 2rem 1rem;
        }

        .setup-card {
            max-width: 720px;
            margin: 0 auto;
        }
    </style>
</head>

<body>

    <div class="setup-card">
        <div class="card card-assis p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-database-check fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1">ASSIS Database Setup & Verification</h3>
                <p class="text-muted small mb-0">Automatic configuration and status check for <code><?= DB_NAME ?></code></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Database Error</h6>
                    <p class="mb-0 small"><?= e($error) ?></p>
                </div>
            <?php else: ?>
                <div class="list-group mb-4">
                    <?php foreach ($status as $s): ?>
                        <?php
                        $iconClass = 'bi-hdd-network text-primary';
                        if (str_contains($s['title'], 'Database')) $iconClass = 'bi-database-check text-success';
                        elseif (str_contains($s['title'], 'Schema')) $iconClass = 'bi-file-earmark-code text-info';
                        ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-light me-3" style="width: 42px; height: 42px; border-radius: 8px;">
                                    <i class="bi <?= $iconClass ?> fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= e($s['title']) ?></div>
                                    <div class="text-muted small"><?= e($s['message']) ?></div>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                                <i class="bi bi-check-circle-fill me-1"></i> Verified
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (($tableReports['USERS'] ?? 0) === 0): ?>
                    <div class="alert alert-warning d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="fw-bold"><i class="bi bi-shield-exclamation me-1"></i> No Administrator Account Found</div>
                            <div class="small text-muted">Create the primary administrator account to start managing the system.</div>
                        </div>
                        <a href="auth/register.php" class="btn btn-primary btn-sm text-nowrap">
                            <i class="bi bi-person-plus me-1"></i> Create Administrator
                        </a>
                    </div>
                <?php endif; ?>

                <h6 class="fw-bold mb-2 small text-uppercase text-muted">
                    <i class="bi bi-collection me-1"></i> Verified Table Records
                </h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><i class="bi bi-table me-1"></i> Table Name</th>
                                <th class="text-center" style="width: 220px;"><i class="bi bi-check2-circle me-1"></i> Status / Row Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableReports as $tbl => $cnt): ?>
                                <tr>
                                    <td class="font-monospace fw-semibold">
                                        <i class="bi bi-diagram-3 text-primary me-2"></i><?= e($tbl) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($cnt === 'Missing'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="bi bi-x-circle-fill me-1"></i> Not Created
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-circle-fill me-1"></i> <?= $cnt ?> record(s)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-3 border-top">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="setup.php?action=clear" class="btn btn-outline-danger" onclick="return confirm('Wipe all transactional data for clean end-to-end testing?');">
                            <i class="bi bi-trash3 me-1"></i> Clear Data
                        </a>
                        <a href="setup.php?action=wipe_users" class="btn btn-outline-warning" onclick="return confirm('Wipe all user accounts to allow creating a new Administrator from scratch?');">
                            <i class="bi bi-people me-1"></i> Wipe Users
                        </a>
                        <a href="setup.php?action=reset" class="btn btn-outline-secondary" onclick="return confirm('Re-initialize database schema?');">
                            <i class="bi bi-arrow-repeat me-1"></i> Reset Schema
                        </a>
                    </div>
                    <?php if (($tableReports['USERS'] ?? 0) === 0): ?>
                        <a href="auth/register.php" class="btn btn-primary btn-lg fs-6 px-4">
                            <i class="bi bi-shield-plus me-1"></i> Setup Administrator
                        </a>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-primary btn-lg fs-6 px-4">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Go to ASSIS Login
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>