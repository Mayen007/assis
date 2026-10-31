<?php
/**
 * User Sign Out Script
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();
set_flash('info', 'You have been successfully signed out.');
header('Location: ' . base_url('auth/login.php'));
exit;
