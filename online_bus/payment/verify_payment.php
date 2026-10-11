<?php
/**
 * Razorpay Payment Signature Verification API (TEST MODE)
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$pnr = cleanInput($input['pnr'] ?? '');
$orderId = cleanInput($input['razorpay_order_id'] ?? '');
$paymentId = cleanInput($input['razorpay_payment_id'] ?? '');
$signature = cleanInput($input['razorpay_signature'] ?? '');
$method = cleanInput($input['payment_method'] ?? 'card');

if (empty($pnr) || empty($orderId) || empty($paymentId)) {
    jsonResponse(['success' => false, 'message' => 'Incomplete payment payload.'], 400);
}

$rzp = require __DIR__ . '/../config/razorpay.php';
$keySecret = $rzp['key_secret'];

// Verify HMAC signature
$expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
$isSignatureValid = hash_equals($expectedSignature, $signature);

// Allow test mode simulated signature if enabled
if (!$isSignatureValid && $rzp['mode'] === 'test' && ($signature === 'simulated_test_sig' || str_starts_with($paymentId, 'pay_test_') || str_starts_with($paymentId, 'pay_demo_'))) {
    $isSignatureValid = true;
}

if (!$isSignatureValid) {
    error_log("Razorpay signature mismatch for order {$orderId}");
    jsonResponse(['success' => false, 'message' => 'Payment verification failed: Invalid cryptographic signature.'], 400);
}

$db = getDB();

try {
    $db->beginTransaction();

    // 1. Fetch booking with locking
    $bStmt = $db->prepare("SELECT * FROM bookings WHERE pnr = ? FOR UPDATE");
    $bStmt->execute([$pnr]);
    $booking = $bStmt->fetch();

    if (!$booking) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Booking not found.'], 404);
    }

    if ($booking['booking_status'] === 'confirmed') {
        $db->commit();
        $app = require __DIR__ . '/../config/app.php';
        jsonResponse([
            'success' => true,
            'message' => 'Booking is already confirmed.',
            'redirect' => $app['base_url'] . '/booking/confirmation.php?pnr=' . urlencode($pnr)
        ]);
    }

    // 2. Update Payment record
    $payStmt = $db->prepare("UPDATE payments 
                             SET razorpay_payment_id = ?, razorpay_signature = ?, payment_method = ?, status = 'captured', updated_at = NOW(),
                                 raw_response = ?
                             WHERE booking_id = ?");
    $rawJson = json_encode(['payment_id' => $paymentId, 'order_id' => $orderId, 'verified_at' => date('Y-m-d H:i:s')]);
    $payStmt->execute([$paymentId, $signature, $method, $rawJson, $booking['id']]);

    // 3. Update Booking to Confirmed
    $updBStmt = $db->prepare("UPDATE bookings 
                              SET booking_status = 'confirmed', payment_status = 'paid', payment_method = 'razorpay', updated_at = NOW() 
                              WHERE id = ?");
    $updBStmt->execute([$booking['id']]);

    // 4. Release temporary seat locks for this booking
    $schedId = $booking['schedule_id'];
    $sessId = session_id();
    $delLocksStmt = $db->prepare("DELETE FROM seat_locks WHERE schedule_id = ? AND (session_id = ? OR user_id = ?)");
    $delLocksStmt->execute([$schedId, $sessId, $booking['user_id']]);

    unset($_SESSION['active_seat_lock']);

    $db->commit();

    $app = require __DIR__ . '/../config/app.php';
    jsonResponse([
        'success' => true,
        'message' => 'Payment verified successfully! Booking confirmed.',
        'pnr' => $pnr,
        'redirect' => $app['base_url'] . '/booking/confirmation.php?pnr=' . urlencode($pnr)
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Payment verification error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Error finalizing booking after payment.'], 500);
}
