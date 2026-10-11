<?php
/**
 * Admin - Export Bookings to CSV
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdminLogin();
$db = getDB();

$search = cleanInput($_GET['q'] ?? '');
$statusFilter = cleanInput($_GET['status'] ?? '');
$dateFilter = cleanInput($_GET['date'] ?? '');

$sql = "SELECT b.pnr, b.contact_name, b.contact_email, b.contact_mobile, 
               fc.name as from_city, tc.name as to_city, b.journey_date, s.departure_time,
               bus.bus_name, bus.bus_number, b.total_seats, b.total_amount, b.booking_status, b.payment_status,
               p.razorpay_payment_id, b.created_at
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bus ON s.bus_id = bus.id
        JOIN routes r ON s.route_id = r.id
        JOIN indian_cities fc ON r.from_city_id = fc.id
        JOIN indian_cities tc ON r.to_city_id = tc.id
        LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'captured'";

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(b.pnr LIKE ? OR b.contact_name LIKE ? OR b.contact_mobile LIKE ?)";
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
}

if (!empty($statusFilter)) {
    $where[] = "b.booking_status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    $where[] = "b.journey_date = ?";
    $params[] = $dateFilter;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY b.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="shreenathji_bookings_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');
// Write UTF-8 BOM for Excel
fputs($output, "\xEF\xBB\xBF");

// Header row
fputcsv($output, ['PNR', 'Passenger Name', 'Email', 'Mobile', 'From City', 'To City', 'Journey Date', 'Dep Time', 'Bus Name', 'Bus Number', 'Seats Count', 'Total Fare', 'Booking Status', 'Payment Status', 'Payment ID', 'Booked At']);

foreach ($rows as $r) {
    fputcsv($output, [
        $r['pnr'],
        $r['contact_name'],
        $r['contact_email'],
        $r['contact_mobile'],
        $r['from_city'],
        $r['to_city'],
        $r['journey_date'],
        $r['departure_time'],
        $r['bus_name'],
        $r['bus_number'],
        $r['total_seats'],
        $r['total_amount'],
        $r['booking_status'],
        $r['payment_status'],
        $r['razorpay_payment_id'] ?? 'N/A',
        $r['created_at']
    ]);
}

fclose($output);
exit;
