<?php
/**
 * Admin - Booking Management
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdminLogin();
$appConfig = require __DIR__ . '/../../config/app.php';
$baseUrl = $appConfig['base_url'];
$adminUrl = $appConfig['admin_url'];
$db = getDB();

$search = cleanInput($_GET['q'] ?? '');
$statusFilter = cleanInput($_GET['status'] ?? '');
$dateFilter = cleanInput($_GET['date'] ?? '');

$sql = "SELECT b.*, 
               s.departure_date, s.departure_time,
               bus.bus_name, bus.bus_number,
               fc.name as from_city, tc.name as to_city,
               p.razorpay_payment_id
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
    $where[] = "(b.pnr LIKE ? OR b.contact_name LIKE ? OR b.contact_mobile LIKE ? OR b.contact_email LIKE ?)";
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
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

$sql .= " ORDER BY b.id DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$adminPageTitle = "Booking Management – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">Booking Management</h4>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= $adminUrl ?>/bookings/export.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" class="btn btn-outline-success btn-sm">
        <i class="fas fa-file-csv me-1"></i> Export to CSV
      </a>
    </div>
  </header>

  <div class="admin-content-body">
    <div class="admin-table-card">
      <div class="admin-table-header">
        <form action="<?= $adminUrl ?>/bookings/index.php" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="PNR, Name, Mobile..." value="<?= e($search) ?>" style="width: 200px;">
          
          <select name="status" class="form-select form-select-sm w-auto">
            <option value="">All Statuses</option>
            <option value="confirmed" <?= ($statusFilter === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
            <option value="pending_payment" <?= ($statusFilter === 'pending_payment') ? 'selected' : '' ?>>Pending</option>
            <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
          </select>

          <input type="date" name="date" class="form-control form-control-sm w-auto" value="<?= e($dateFilter) ?>">

          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Filter</button>
          <a href="<?= $adminUrl ?>/bookings/index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>

        <span class="small text-muted fw-bold">Records: <?= count($bookings) ?></span>
      </div>

      <div class="table-responsive">
        <table class="table admin-table align-middle">
          <thead>
            <tr>
              <th>PNR / Ref</th>
              <th>Passenger</th>
              <th>Journey Details</th>
              <th>Seats</th>
              <th>Amount</th>
              <th>Payment Ref</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">No booking records found.</td></tr>
            <?php else: ?>
              <?php foreach ($bookings as $b): ?>
                <tr>
                  <td>
                    <span class="fw-bold text-primary"><?= e($b['pnr']) ?></span>
                    <div class="text-muted small"><?= date('d M Y', strtotime($b['created_at'])) ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= e($b['contact_name']) ?></div>
                    <small class="text-muted">+91 <?= e($b['contact_mobile']) ?></small>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= e($b['from_city']) ?> → <?= e($b['to_city']) ?></div>
                    <small class="text-muted"><?= formatDateIndian($b['journey_date']) ?> (<?= formatTime($b['departure_time']) ?>)</small>
                  </td>
                  <td>
                    <span class="badge bg-primary fs-6"><?= $b['total_seats'] ?> seats</span>
                  </td>
                  <td>
                    <div class="fw-bold"><?= formatPrice($b['total_amount']) ?></div>
                    <?php if ($b['refund_amount'] > 0): ?>
                      <small class="text-danger">Refund: <?= formatPrice($b['refund_amount']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <small class="text-secondary font-monospace"><?= e($b['razorpay_payment_id'] ?? 'Pending') ?></small>
                  </td>
                  <td>
                    <?php if ($b['booking_status'] === 'confirmed'): ?>
                      <span class="badge badge-confirmed">Confirmed</span>
                    <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                      <span class="badge badge-cancelled">Cancelled</span>
                    <?php else: ?>
                      <span class="badge badge-pending">Pending</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <a href="<?= $adminUrl ?>/bookings/view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-info" title="View Full Details">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?= $baseUrl ?>/user/ticket.php?pnr=<?= urlencode($b['pnr']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Print Ticket">
                      <i class="fas fa-print"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
