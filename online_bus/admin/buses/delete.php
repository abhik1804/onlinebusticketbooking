<?php
/**
 * Admin - Delete Bus (with Historical Integrity Check)
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

$busId = (int)($_GET['id'] ?? 0);
if ($busId <= 0) {
    header("Location: {$adminUrl}/buses/index.php");
    exit;
}

// Check for existing bookings on this bus
$chkStmt = $db->prepare("SELECT COUNT(*) FROM bookings b 
                         JOIN schedules s ON b.schedule_id = s.id 
                         WHERE s.bus_id = ?");
$chkStmt->execute([$busId]);
$bookingCount = (int)$chkStmt->fetchColumn();

if ($bookingCount > 0) {
    // Preserve historical records: deactivate instead of delete
    $db->prepare("UPDATE buses SET is_active = 0 WHERE id = ?")->execute([$busId]);
    logAdminActivity($currentAdmin['id'], 'deactivate_bus', "Deactivated bus ID {$busId} instead of deleting because {$bookingCount} bookings exist.");
    header("Location: {$adminUrl}/buses/index.php?error=" . urlencode("Cannot permanently delete this bus because it has {$bookingCount} historical passenger bookings. The bus has been deactivated instead."));
    exit;
}

// Safe to delete
try {
    $db->beginTransaction();
    $db->prepare("DELETE FROM bus_seats WHERE bus_id = ?")->execute([$busId]);
    $db->prepare("DELETE FROM schedules WHERE bus_id = ?")->execute([$busId]);
    $db->prepare("DELETE FROM buses WHERE id = ?")->execute([$busId]);
    $db->commit();

    logAdminActivity($currentAdmin['id'], 'delete_bus', "Permanently deleted bus ID {$busId}");
    header("Location: {$adminUrl}/buses/index.php?deleted=1");
    exit;
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Bus deletion error: " . $e->getMessage());
    header("Location: {$adminUrl}/buses/index.php?error=" . urlencode("Failed to delete bus."));
    exit;
}
