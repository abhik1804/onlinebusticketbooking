<?php
/**
 * User Logout Handler
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/auth.php';

$appConfig = require __DIR__ . '/../config/app.php';
logoutUser();

header("Location: " . $appConfig['base_url'] . "/index.php");
exit;
