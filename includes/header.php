<?php

/**
 * Shared Application Header & Navigation Bar
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$currentUser = current_user();
$pageTitle = $pageTitle ?? 'After-Sales Service Information System';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | ASSIS</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Application CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>

<body>
    <?php if ($currentUser): ?><nav class="navbar navbar-expand-lg navbar-dark navbar-assis no-print">
            <div class="container-fluid px-3 py-2">
                <a class="navbar-brand d-flex align-items-center" href="<?= base_url('dashboard.php') ?>">
                    <i class="bi bi-tools me-2"></i>
                    <span>ASSIS</span>
                </a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#assisNav" aria-controls="assisNav" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <?php
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                $isDashboard = str_contains($scriptName, 'dashboard.php');
                $isRequests = str_contains($scriptName, '/requests/');
                $isWorkbench = str_contains($scriptName, '/workbench.php');
                $isClients = str_contains($scriptName, '/customers/') || str_contains($scriptName, '/products/');
                $isOps = str_contains($scriptName, '/technicians/') || str_contains($scriptName, '/inventory/') || str_contains($scriptName, '/billing/') || str_contains($scriptName, '/feedback/') || str_contains($scriptName, '/users/');
                $isReports = str_contains($scriptName, '/reports/');
                ?>
                <div class="offcanvas-lg offcanvas-start navbar-drawer" tabindex="-1" id="assisNav" aria-labelledby="assisNavLabel">
                    <div class="offcanvas-header border-bottom d-lg-none">
                        <div class="d-flex align-items-center">
                            <div class="drawer-icon-wrap me-2 bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                <i class="bi bi-tools"></i>
                            </div>
                            <div>
                                <h6 class="offcanvas-title fw-bold mb-0 text-dark" id="assisNavLabel">ASSIS Menu</h6>
                                <small class="text-muted" style="font-size: 0.75rem;">Service Management</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#assisNav" aria-label="Close"></button>
                    </div>

                    <div class="offcanvas-body d-flex flex-column flex-lg-row">
                        <!-- Mobile User Summary Card -->
                        <div class="drawer-user-card d-lg-none mb-3 p-3 rounded-3 bg-light border">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-2 bg-primary text-white d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi bi-person-fill fs-5"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-semibold text-dark text-truncate"><?= e($currentUser['full_name']) ?></div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.75rem;"><?= e($currentUser['role']) ?></span>
                                        <span class="text-muted small text-truncate">@<?= e($currentUser['username']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link <?= $isDashboard ? 'active' : '' ?>" href="<?= base_url('dashboard.php') ?>">
                                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                                </a>
                            </li>

                            <?php if (has_role('Technician')): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= $isWorkbench ? 'active' : '' ?>" href="<?= base_url('technicians/workbench.php') ?>">
                                        <i class="bi bi-wrench-adjustable me-2"></i> My Assigned Jobs
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <a class="nav-link <?= $isRequests ? 'active' : '' ?>" href="<?= base_url('requests/index.php') ?>">
                                    <i class="bi bi-card-checklist me-2"></i> Service Requests
                                </a>
                            </li>

                            <?php if (has_role(['Admin', 'FrontDesk'])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle <?= $isClients ? 'active' : '' ?>" href="#" id="clientsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-people me-2"></i> Clients & Assets
                                    </a>
                                    <ul class="dropdown-menu shadow-sm" aria-labelledby="clientsDropdown">
                                        <li>
                                            <a class="dropdown-item" href="<?= base_url('customers/index.php') ?>">
                                                <i class="bi bi-person-lines-fill me-2 text-primary"></i> Customer Directory
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= base_url('products/index.php') ?>">
                                                <i class="bi bi-box-seam me-2 text-primary"></i> Equipment Registry
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item small" href="<?= base_url('customers/create.php') ?>">
                                                <i class="bi bi-person-plus me-2 text-muted"></i> Register New Customer
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small" href="<?= base_url('products/create.php') ?>">
                                                <i class="bi bi-plus-circle me-2 text-muted"></i> Register New Equipment
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle <?= $isOps ? 'active' : '' ?>" href="#" id="opsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-gear-wide-connected me-2"></i> Operations
                                    </a>
                                    <ul class="dropdown-menu shadow-sm" aria-labelledby="opsDropdown">
                                        <?php if (has_role('Admin')): ?>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('inventory/index.php') ?>">
                                                    <i class="bi bi-boxes me-2 text-warning"></i> Spare Parts Inventory
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('technicians/index.php') ?>">
                                                    <i class="bi bi-person-badge me-2 text-info"></i> Technicians Management
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('users/index.php') ?>">
                                                    <i class="bi bi-people-fill me-2 text-primary"></i> Staff User Accounts
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                        <?php endif; ?>

                                        <li>
                                            <a class="dropdown-item" href="<?= base_url('billing/index.php') ?>">
                                                <i class="bi bi-receipt me-2 text-success"></i> Invoices & Billing
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= base_url('feedback/index.php') ?>">
                                                <i class="bi bi-star-half me-2 text-warning"></i> Customer Feedback
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <?php if (has_role('Admin')): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= $isReports ? 'active' : '' ?>" href="<?= base_url('reports/index.php') ?>">
                                        <i class="bi bi-graph-up-arrow me-2"></i> Reports
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <!-- Desktop User Menu -->
                        <ul class="navbar-nav ms-auto align-items-center d-none d-lg-flex">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle fs-5 me-2"></i>
                                    <span><?= e($currentUser['full_name']) ?></span>
                                    <span class="badge bg-light text-dark ms-2"><?= e($currentUser['role']) ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
                                    <li><span class="dropdown-item-text text-muted small">Signed in as <strong><?= e($currentUser['username']) ?></strong></span></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <form method="POST" action="<?= base_url('auth/logout.php') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <!-- Mobile Drawer Sign Out -->
                        <div class="drawer-footer d-lg-none mt-auto pt-3 border-top">
                            <form method="POST" action="<?= base_url('auth/logout.php') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <main class="main-wrapper">
        <div class="container-fluid px-4">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
