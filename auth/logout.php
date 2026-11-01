<?php
/**
 * User Sign Out Script
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!verify_csrf()) {
    http_response_code(403);
    exit('Invalid security token.');
}

logout_user();
set_flash('info', 'You have been successfully signed out.');
header('Location: ' . base_url('auth/login.php'));
exit;
