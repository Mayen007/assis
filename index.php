<?php
/**
 * Application Entry Point Router
 * After-Sales Service Information System (ASSIS)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . base_url('dashboard.php'));
} else {
    header('Location: ' . base_url('auth/login.php'));
}
exit;
