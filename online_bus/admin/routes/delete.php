<?php
/**
 * Admin - Delete Route
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdminLogin();
$appConfig = require __DIR__ . '/../../config/app.php';
$adminUrl = $appConfig['admin_url'];
$db = getDB();

$routeId = (int)($_GET['id'] ?? 0);
if ($routeId <= 0) {
    header("Location: {$adminUrl}/routes/index.php");
    exit;
}

// Check if schedules exist on this route
$chk = $db->prepare("SELECT COUNT(*) FROM schedules WHERE route_id = ?");
$chk->execute([$routeId]);
$schedCount = (int)$chk->fetchColumn();

if ($schedCount > 0) {
    // Deactivate instead
    $db->prepare("UPDATE routes SET is_active = 0 WHERE id = ?")->execute([$routeId]);
    header("Location: {$adminUrl}/routes/index.php?deleted=1");
    exit;
}

$db->prepare("DELETE FROM routes WHERE id = ?")->execute([$routeId]);
header("Location: {$adminUrl}/routes/index.php?deleted=1");
exit;
