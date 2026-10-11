<?php
/**
 * Admin - Export Payments to CSV
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdminLogin();
$db = getDB();

$statusFilter = cleanInput($_GET['status'] ?? '');
$search = cleanInput($_GET['q'] ?? '');

$sql = "SELECT p.id, b.pnr, p.razorpay_order_id, p.razorpay_payment_id, b.contact_name, b.contact_mobile,
               p.amount, p.currency, p.payment_method, p.status, p.created_at
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id";

$where = [];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(b.pnr LIKE ? OR p.razorpay_order_id LIKE ? OR p.razorpay_payment_id LIKE ?)";
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="shreenathji_payments_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');
fputs($output, "\xEF\xBB\xBF");

fputcsv($output, ['ID', 'PNR', 'Razorpay Order ID', 'Razorpay Payment ID', 'Customer Name', 'Mobile', 'Amount (INR)', 'Currency', 'Payment Method', 'Status', 'Date']);

foreach ($rows as $r) {
    fputcsv($output, [
        $r['id'],
        $r['pnr'],
        $r['razorpay_order_id'],
        $r['razorpay_payment_id'] ?? 'N/A',
        $r['contact_name'],
        $r['contact_mobile'],
        $r['amount'],
        $r['currency'],
        $r['payment_method'],
        $r['status'],
        $r['created_at']
    ]);
}

fclose($output);
exit;
