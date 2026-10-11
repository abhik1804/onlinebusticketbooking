<?php
/**
 * Razorpay Webhook Handler
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$rzp = require __DIR__ . '/../config/razorpay.php';
$webhookSecret = $rzp['webhook_secret'] ?? '';

$rawBody = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

// Validate Webhook Signature
if (!empty($webhookSecret) && !empty($signatureHeader)) {
    $expectedSignature = hash_hmac('sha256', $rawBody, $webhookSecret);
    if (!hash_equals($expectedSignature, $signatureHeader)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid webhook signature']);
        exit;
    }
}

$eventData = json_decode($rawBody, true);
if (!$eventData || empty($eventData['event'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
    exit;
}

$eventId = $eventData['id'] ?? ('evt_' . time());
$eventType = $eventData['event'];

$db = getDB();

// Duplicate-event protection / Idempotency check
$checkStmt = $db->prepare("SELECT COUNT(*) FROM payment_webhook_events WHERE event_id = ?");
$checkStmt->execute([$eventId]);
if ($checkStmt->fetchColumn() > 0) {
    echo json_encode(['status' => 'ignored', 'message' => 'Event already processed']);
    exit;
}

// Log event
$insStmt = $db->prepare("INSERT INTO payment_webhook_events (event_id, event_type, payload, processed, created_at) VALUES (?, ?, ?, 0, NOW())");
$insStmt->execute([$eventId, $eventType, $rawBody]);

// Process Event
if ($eventType === 'payment.captured' || $eventType === 'order.paid') {
    $paymentEntity = $eventData['payload']['payment']['entity'] ?? [];
    $orderId = $paymentEntity['order_id'] ?? null;
    $paymentId = $paymentEntity['id'] ?? null;

    if ($orderId) {
        $pStmt = $db->prepare("SELECT booking_id FROM payments WHERE razorpay_order_id = ? LIMIT 1");
        $pStmt->execute([$orderId]);
        $bookingId = $pStmt->fetchColumn();

        if ($bookingId) {
            $db->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid', updated_at = NOW() WHERE id = ?")->execute([$bookingId]);
            $db->prepare("UPDATE payments SET status = 'captured', razorpay_payment_id = ?, updated_at = NOW() WHERE booking_id = ?")->execute([$paymentId, $bookingId]);
        }
    }
} elseif ($eventType === 'payment.failed') {
    $paymentEntity = $eventData['payload']['payment']['entity'] ?? [];
    $orderId = $paymentEntity['order_id'] ?? null;
    $errorDesc = $paymentEntity['error_description'] ?? 'Payment failed';

    if ($orderId) {
        $db->prepare("UPDATE payments SET status = 'failed', error_description = ?, updated_at = NOW() WHERE razorpay_order_id = ?")->execute([$errorDesc, $orderId]);
    }
}

// Mark processed
$db->prepare("UPDATE payment_webhook_events SET processed = 1 WHERE event_id = ?")->execute([$eventId]);

echo json_encode(['status' => 'success']);
