<?php
/**
 * Admin - View Booking Details & Manage Manifest
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdminLogin();
$appConfig = require __DIR__ . '/../../config/app.php';
$baseUrl = $appConfig['base_url'];
$adminUrl = $appConfig['admin_url'];
$db = getDB();

$bookingId = (int)($_GET['id'] ?? 0);
if ($bookingId <= 0) {
    header("Location: {$adminUrl}/bookings/index.php");
    exit;
}

// Fetch booking
$stmt = $db->prepare("SELECT b.*, 
                             s.departure_date, s.departure_time, s.arrival_date, s.arrival_time,
                             bus.bus_name, bus.bus_type, bus.bus_number,
                             fc.name as from_city, tc.name as to_city,
                             p.razorpay_payment_id, p.payment_method, p.raw_response
                      FROM bookings b
                      JOIN schedules s ON b.schedule_id = s.id
                      JOIN buses bus ON s.bus_id = bus.id
                      JOIN routes r ON s.route_id = r.id
                      JOIN indian_cities fc ON r.from_city_id = fc.id
                      JOIN indian_cities tc ON r.to_city_id = tc.id
                      LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'captured'
                      WHERE b.id = ? LIMIT 1");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found.");
}

// Fetch Passengers
$pStmt = $db->prepare("SELECT * FROM booking_passengers WHERE booking_id = ? ORDER BY seat_number ASC");
$pStmt->execute([$bookingId]);
$passengers = $pStmt->fetchAll();

// Handle Admin Cancellation or Status Update
$actionMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_status'])) {
    requireCsrfToken();
    $newStatus = cleanInput($_POST['booking_status'] ?? '');
    $refundAmt = (float)($_POST['refund_amount'] ?? 0);
    $adminNotes = cleanInput($_POST['admin_notes'] ?? '');

    $upd = $db->prepare("UPDATE bookings SET booking_status = ?, refund_amount = ?, cancellation_reason = ?, updated_at = NOW() WHERE id = ?");
    $upd->execute([$newStatus, $refundAmt, $adminNotes, $bookingId]);
    logAdminActivity($currentAdmin['id'], 'admin_update_booking', "Admin updated booking #{$bookingId} ({$booking['pnr']}) to {$newStatus}");
    $actionMsg = "Booking updated successfully.";

    // Refresh
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
}

$adminPageTitle = "Booking #{$booking['pnr']} – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= $adminUrl ?>/bookings/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0">Booking Manifest: <?= e($booking['pnr']) ?></h4>
    </div>
    <div>
      <a href="<?= $baseUrl ?>/user/ticket.php?pnr=<?= urlencode($booking['pnr']) ?>" target="_blank" class="btn btn-primary btn-sm">
        <i class="fas fa-print me-1"></i> Print Customer Ticket
      </a>
    </div>
  </header>

  <div class="admin-content-body">
    <?php if (!empty($actionMsg)): ?>
      <div class="alert alert-success py-2 small"><?= e($actionMsg) ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Left: Booking & Passenger Manifest -->
      <div class="col-lg-8">
        <div class="admin-table-card p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
            <div>
              <span class="text-muted small">PNR Reference</span>
              <h4 class="fw-bold text-primary mb-0"><?= e($booking['pnr']) ?></h4>
            </div>
            <div>
              <?php if ($booking['booking_status'] === 'confirmed'): ?>
                <span class="badge badge-confirmed fs-6">Confirmed</span>
              <?php elseif ($booking['booking_status'] === 'cancelled'): ?>
                <span class="badge badge-cancelled fs-6">Cancelled</span>
              <?php else: ?>
                <span class="badge badge-pending fs-6">Pending Payment</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <div class="small text-muted">Route</div>
              <div class="fw-bold fs-5"><?= e($booking['from_city']) ?> → <?= e($booking['to_city']) ?></div>
              <small class="text-muted"><?= e($booking['bus_name']) ?> (<?= e($booking['bus_number']) ?>)</small>
            </div>
            <div class="col-md-6">
              <div class="small text-muted">Journey Date & Time</div>
              <div class="fw-bold"><?= formatDateIndian($booking['journey_date']) ?></div>
              <small class="text-primary fw-semibold">Departs: <?= formatTime($booking['departure_time']) ?></small>
            </div>
          </div>

          <div class="p-3 bg-light rounded-3 small mb-4">
            <div class="mb-1"><strong>Boarding Point:</strong> <?= e($booking['boarding_point']) ?></div>
            <div><strong>Dropping Point:</strong> <?= e($booking['dropping_point']) ?></div>
          </div>

          <h5 class="fw-bold mb-3">Passenger List (<?= count($passengers) ?>)</h5>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>Seat</th>
                  <th>Passenger Name</th>
                  <th>Age</th>
                  <th>Gender</th>
                  <th class="text-end">Seat Fare</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($passengers as $p): ?>
                  <tr>
                    <td><span class="badge bg-primary fs-6"><?= e($p['seat_number']) ?></span></td>
                    <td class="fw-bold"><?= e($p['passenger_name']) ?></td>
                    <td><?= e($p['age']) ?> Years</td>
                    <td class="text-capitalize"><?= e($p['gender']) ?></td>
                    <td class="text-end fw-semibold"><?= formatPrice($p['seat_fare']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right: Contact, Payment & Admin Actions -->
      <div class="col-lg-4">
        <!-- Contact Details -->
        <div class="admin-table-card p-4 mb-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-address-card text-primary me-2"></i>Contact Details</h5>
          <div class="mb-2"><strong>Name:</strong> <?= e($booking['contact_name']) ?></div>
          <div class="mb-2"><strong>Email:</strong> <?= e($booking['contact_email']) ?></div>
          <div class="mb-2"><strong>Mobile:</strong> +91 <?= e($booking['contact_mobile']) ?></div>
          <div class="small text-muted mt-2 border-top pt-2">Booked On: <?= date('d M Y, h:i A', strtotime($booking['created_at'])) ?></div>
        </div>

        <!-- Payment Details -->
        <div class="admin-table-card p-4 mb-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-credit-card text-success me-2"></i>Payment Details</h5>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Base Fare</span>
            <span><?= formatPrice($booking['base_amount']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">GST Tax</span>
            <span><?= formatPrice($booking['tax_amount']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Convenience Fee</span>
            <span><?= formatPrice($booking['convenience_fee']) ?></span>
          </div>
          <hr>
          <div class="d-flex justify-content-between fw-bold fs-5 text-dark mb-3">
            <span>Total Amount</span>
            <span class="text-primary"><?= formatPrice($booking['total_amount']) ?></span>
          </div>
          <div class="small text-muted">
            <div><strong>Razorpay Payment ID:</strong> <?= e($booking['razorpay_payment_id'] ?? 'Pending') ?></div>
            <div><strong>Payment Method:</strong> <?= strtoupper(e($booking['payment_method'] ?? 'Online')) ?></div>
          </div>
        </div>

        <!-- Admin Actions / Status Override -->
        <div class="admin-table-card p-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-cog text-danger me-2"></i>Admin Override</h5>
          <form method="POST">
            <?= getCsrfField() ?>
            <input type="hidden" name="action_update_status" value="1">

            <div class="mb-3">
              <label class="form-label small fw-bold">Booking Status</label>
              <select name="booking_status" class="form-select form-select-sm">
                <option value="confirmed" <?= ($booking['booking_status'] === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                <option value="pending_payment" <?= ($booking['booking_status'] === 'pending_payment') ? 'selected' : '' ?>>Pending Payment</option>
                <option value="cancelled" <?= ($booking['booking_status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Refund Amount (₹)</label>
              <input type="number" step="0.01" name="refund_amount" class="form-control form-control-sm" value="<?= $booking['refund_amount'] ?>">
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Admin Notes</label>
              <textarea name="admin_notes" class="form-control form-control-sm" rows="2"><?= e($booking['cancellation_reason'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-warning btn-sm w-100">Update Booking</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
