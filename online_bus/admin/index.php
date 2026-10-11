<?php
/**
 * Admin Index Redirector
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/auth.php';

$appConfig = require __DIR__ . '/../config/app.php';

if (isAdminLoggedIn()) {
    header("Location: " . $appConfig['admin_url'] . "/dashboard.php");
} else {
    header("Location: " . $appConfig['admin_url'] . "/login.php");
}
exit;
