<?php
/**
 * Booking Confirmation Page with Animated Success State
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

$pnr = cleanInput($_GET['pnr'] ?? '');
if (empty($pnr)) {
    header("Location: {$baseUrl}/index.php");
    exit;
}

// Fetch confirmed booking
$stmt = $db->prepare("SELECT b.*, 
                             s.departure_date, s.departure_time, s.arrival_date, s.arrival_time,
                             bus.bus_name, bus.bus_type, bus.bus_number,
                             fc.name as from_city, tc.name as to_city,
                             p.razorpay_payment_id, p.payment_method
                      FROM bookings b
                      JOIN schedules s ON b.schedule_id = s.id
                      JOIN buses bus ON s.bus_id = bus.id
                      JOIN routes r ON s.route_id = r.id
                      JOIN indian_cities fc ON r.from_city_id = fc.id
                      JOIN indian_cities tc ON r.to_city_id = tc.id
                      LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'captured'
                      WHERE b.pnr = ? LIMIT 1");
$stmt->execute([$pnr]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking reference not found. <a href='{$baseUrl}/index.php'>Return to Homepage</a>");
}

// Fetch Passengers
$pStmt = $db->prepare("SELECT * FROM booking_passengers WHERE booking_id = ? ORDER BY seat_number ASC");
$pStmt->execute([$booking['id']]);
$passengers = $pStmt->fetchAll();

$pageTitle = "Booking Confirmed! PNR: " . e($pnr) . " – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <!-- Animated Success Banner -->
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow-lg mb-3" style="width: 80px; height: 80px; font-size: 38px;">
          ✓
        </div>
        <h2 class="fw-bold text-dark mb-1">Booking Confirmed!</h2>
        <p class="text-muted">Your tickets have been issued and payment verified. A copy of your e-ticket has been sent to <strong><?= e($booking['contact_email']) ?></strong>.</p>
      </div>

      <!-- Ticket Card Preview -->
      <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-3 mb-3">
          <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">
              <i class="fas fa-check-circle me-1"></i> Confirmed & Paid
            </span>
            <div class="fs-4 fw-bold">PNR: <span class="text-primary"><?= e($booking['pnr']) ?></span></div>
          </div>
          <div class="text-end">
            <span class="text-muted small d-block">Total Paid</span>
            <span class="fs-4 fw-bold text-success"><?= formatPrice($booking['total_amount']) ?></span>
          </div>
        </div>

        <!-- Journey Summary -->
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="text-muted small">Route</div>
            <div class="fw-bold fs-5 text-dark"><?= e($booking['from_city']) ?> → <?= e($booking['to_city']) ?></div>
            <div class="text-secondary small"><?= e($booking['bus_name']) ?> (<?= e($booking['bus_type']) ?>)</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small">Journey Date</div>
            <div class="fw-bold text-dark"><?= formatDateIndian($booking['journey_date']) ?></div>
            <div class="text-secondary small">Departure Time: <?= formatTime($booking['departure_time']) ?></div>
          </div>
        </div>

        <!-- Boarding & Dropping Points -->
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="p-3 bg-light rounded-3">
              <div class="text-muted small fw-bold text-uppercase"><i class="fas fa-map-marker-alt text-primary me-1"></i> Boarding Point</div>
              <div class="fw-bold text-dark mt-1"><?= e($booking['boarding_point']) ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 bg-light rounded-3">
              <div class="text-muted small fw-bold text-uppercase"><i class="fas fa-map-pin text-danger me-1"></i> Dropping Point</div>
              <div class="fw-bold text-dark mt-1"><?= e($booking['dropping_point']) ?></div>
            </div>
          </div>
        </div>

        <!-- Passengers List -->
        <h6 class="fw-bold text-dark mb-2">Booked Passengers & Seats</h6>
        <div class="table-responsive mb-3">
          <table class="table table-bordered table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>Seat No</th>
                <th>Passenger Name</th>
                <th>Age / Gender</th>
                <th class="text-end">Fare</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($passengers as $p): ?>
                <tr>
                  <td><span class="badge bg-primary fs-6"><?= e($p['seat_number']) ?></span></td>
                  <td class="fw-bold"><?= e($p['passenger_name']) ?></td>
                  <td class="text-capitalize"><?= e($p['age']) ?> Yrs • <?= e($p['gender']) ?></td>
                  <td class="text-end"><?= formatPrice($p['seat_fare']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Payment ID Reference -->
        <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center flex-wrap gap-2 small text-muted">
          <span><strong>Razorpay Transaction ID:</strong> <?= e($booking['razorpay_payment_id'] ?? 'Verified Online') ?></span>
          <span><strong>Booking Date:</strong> <?= date('d M Y, h:i A', strtotime($booking['created_at'])) ?></span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="<?= $baseUrl ?>/user/ticket.php?pnr=<?= urlencode($booking['pnr']) ?>" target="_blank" class="btn btn-st-gold btn-lg">
          <i class="fas fa-print me-2"></i> View & Print E-Ticket
        </a>
        <a href="<?= $baseUrl ?>/user/my_bookings.php" class="btn btn-outline-primary btn-lg">
          <i class="fas fa-ticket-alt me-2"></i> My Bookings
        </a>
        <a href="<?= $baseUrl ?>/index.php" class="btn btn-st-outline btn-lg">
          <i class="fas fa-home me-2"></i> Return to Homepage
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
