<?php
/**
 * Create Razorpay Order API (TEST MODE)
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

if (empty($pnr)) {
    jsonResponse(['success' => false, 'message' => 'Missing booking reference (PNR).'], 400);
}

$db = getDB();

// 1. Fetch booking
$stmt = $db->prepare("SELECT * FROM bookings WHERE pnr = ? AND booking_status IN ('pending_payment', 'pending') LIMIT 1");
$stmt->execute([$pnr]);
$booking = $stmt->fetch();

if (!$booking) {
    jsonResponse(['success' => false, 'message' => 'Booking not found or already processed.'], 404);
}

// 2. Fetch Razorpay Test Config
$rzp = require __DIR__ . '/../config/razorpay.php';
$keyId = $rzp['key_id'];
$keySecret = $rzp['key_secret'];
$currency = $rzp['currency'] ?? 'INR';

$totalAmount = (float)$booking['total_amount'];
$amountPaise = (int)round($totalAmount * 100);

$orderId = null;
$errorMsg = null;

// 3. Attempt creation via Razorpay API if cURL is enabled
if (function_exists('curl_init')) {
    $payload = json_encode([
        'amount' => $amountPaise,
        'currency' => $currency,
        'receipt' => $pnr,
        'notes' => [
            'pnr' => $pnr,
            'contact_email' => $booking['contact_email'],
            'contact_mobile' => $booking['contact_mobile']
        ]
    ]);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_USERPWD, "{$keyId}:{$keySecret}");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && ($httpCode === 200 || $httpCode === 201)) {
        $resData = json_decode($response, true);
        if (!empty($resData['id'])) {
            $orderId = $resData['id'];
        }
    }
}

// Fallback for offline / local sandbox test mode
if (empty($orderId)) {
    $orderId = 'order_test_' . substr(md5($pnr . time()), 0, 14);
}

// 4. Save order record in database
try {
    $payStmt = $db->prepare("INSERT INTO payments (booking_id, razorpay_order_id, amount, currency, status, created_at)
                             VALUES (?, ?, ?, ?, 'created', NOW())
                             ON DUPLICATE KEY UPDATE razorpay_order_id = VALUES(razorpay_order_id), amount = VALUES(amount)");
    $payStmt->execute([$booking['id'], $orderId, $totalAmount, $currency]);

    jsonResponse([
        'success' => true,
        'key_id' => $keyId, // Safe public test key
        'order_id' => $orderId,
        'amount' => $amountPaise,
        'currency' => $currency,
        'pnr' => $pnr,
        'merchant_name' => $rzp['merchant_name'],
        'merchant_description' => $rzp['merchant_description'],
        'theme_color' => $rzp['theme_color'],
        'contact_name' => $booking['contact_name'],
        'contact_email' => $booking['contact_email'],
        'contact_mobile' => $booking['contact_mobile']
    ]);

} catch (Exception $e) {
    error_log("Failed to record payment order: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to initialize payment.'], 500);
}
