<?php
/**
 * Admin Logout
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/auth.php';

$appConfig = require __DIR__ . '/../config/app.php';
logoutAdmin();

header("Location: " . $appConfig['admin_url'] . "/login.php");
exit;
