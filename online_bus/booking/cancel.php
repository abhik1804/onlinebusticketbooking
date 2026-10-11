<?php
/**
 * Ticket Cancellation Handler
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$baseUrl}/user/my_bookings.php");
    exit;
}

requireCsrfToken();

$pnr = cleanInput($_POST['pnr'] ?? '');
$cancelReason = cleanInput($_POST['cancel_reason'] ?? 'Passenger request');

if (empty($pnr)) {
    die("PNR is required.");
}

// Fetch booking with schedule details
$stmt = $db->prepare("SELECT b.*, s.departure_date, s.departure_time 
                      FROM bookings b
                      JOIN schedules s ON b.schedule_id = s.id
                      WHERE b.pnr = ? LIMIT 1");
$stmt->execute([$pnr]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found.");
}

// Ownership check: logged in user or matching contact
if (isUserLoggedIn()) {
    if ($booking['user_id'] != $_SESSION['user_id'] && !isAdminLoggedIn()) {
        die("Unauthorized access to this booking.");
    }
}

if ($booking['booking_status'] === 'cancelled') {
    die("This booking is already cancelled.");
}

// Calculate hours to departure
$depDateTimeStr = $booking['departure_date'] . ' ' . $booking['departure_time'];
$depTimestamp = strtotime($depDateTimeStr);
$nowTimestamp = time();
$hoursDiff = ($depTimestamp - $nowTimestamp) / 3600;

// Cancellation Policy Refund Calculation
$refundPercent = 0;
if ($hoursDiff > 24) {
    $refundPercent = (float)getSetting('cancellation_policy_24h', 90);
} elseif ($hoursDiff >= 12) {
    $refundPercent = (float)getSetting('cancellation_policy_12h', 70);
} else {
    $refundPercent = (float)getSetting('cancellation_policy_0h', 0);
}

$baseAmount = (float)$booking['base_amount'];
$refundAmount = round(($baseAmount * ($refundPercent / 100)), 2);

try {
    $db->beginTransaction();

    // 1. Update Booking
    $updStmt = $db->prepare("UPDATE bookings 
                            SET booking_status = 'cancelled', 
                                refund_amount = ?, 
                                cancellation_reason = ?, 
                                cancelled_at = NOW(),
                                updated_at = NOW() 
                            WHERE id = ?");
    $updStmt->execute([$refundAmount, $cancelReason, $booking['id']]);

    // 2. Update Payment
    $payStmt = $db->prepare("UPDATE payments SET status = 'refunded', updated_at = NOW() WHERE booking_id = ?");
    $payStmt->execute([$booking['id']]);

    $db->commit();

    header("Location: {$baseUrl}/user/my_bookings.php?cancelled=1&refund=" . urlencode($refundAmount));
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Cancellation error: " . $e->getMessage());
    die("Failed to cancel booking. Please contact support.");
}
