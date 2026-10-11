<?php
/**
 * PNR Status & Ticket Lookup
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

$searchedPnr = cleanInput($_GET['pnr'] ?? '');
$booking = null;
$passengers = [];
$error = '';

if (!empty($searchedPnr)) {
    $stmt = $db->prepare("SELECT b.*, 
                                 s.departure_date, s.departure_time, s.arrival_date, s.arrival_time,
                                 bus.bus_name, bus.bus_type, bus.bus_number,
                                 fc.name as from_city, tc.name as to_city
                          FROM bookings b
                          JOIN schedules s ON b.schedule_id = s.id
                          JOIN buses bus ON s.bus_id = bus.id
                          JOIN routes r ON s.route_id = r.id
                          JOIN indian_cities fc ON r.from_city_id = fc.id
                          JOIN indian_cities tc ON r.to_city_id = tc.id
                          WHERE b.pnr = ? LIMIT 1");
    $stmt->execute([$searchedPnr]);
    $booking = $stmt->fetch();

    if ($booking) {
        $pStmt = $db->prepare("SELECT * FROM booking_passengers WHERE booking_id = ?");
        $pStmt->execute([$booking['id']]);
        $passengers = $pStmt->fetchAll();
    } else {
        $error = "No booking found matching PNR reference '{$searchedPnr}'. Please verify and try again.";
    }
}

$pageTitle = "Track PNR & Bus Status – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <!-- Search Box -->
      <div class="bg-white p-4 p-md-5 rounded-4 border shadow-sm mb-4">
        <div class="text-center mb-4">
          <div class="fs-1 text-primary mb-2"><i class="fas fa-search-location"></i></div>
          <h3 class="fw-bold text-dark">Track PNR / Print Ticket</h3>
          <p class="text-muted small">Enter your 10-character booking PNR to view your live trip status and download your e-ticket.</p>
        </div>

        <form action="<?= $baseUrl ?>/user/pnr_status.php" method="GET">
          <div class="input-group input-group-lg mb-3">
            <span class="input-group-text bg-light"><i class="fas fa-ticket-alt text-primary"></i></span>
            <input type="text" name="pnr" class="form-control" placeholder="e.g. ST-26-XXXXXX" value="<?= e($searchedPnr) ?>" required style="text-transform: uppercase;">
            <button class="btn btn-st-gold px-4" type="submit">Track PNR</button>
          </div>
        </form>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small mt-3" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> <?= e($error) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Result Card -->
      <?php if ($booking): ?>
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
            <div>
              <span class="small text-muted d-block">Booking Reference</span>
              <span class="fs-4 fw-bold text-primary"><?= e($booking['pnr']) ?></span>
            </div>
            <div>
              <?php if ($booking['booking_status'] === 'confirmed'): ?>
                <span class="badge bg-success-subtle text-success fs-6"><i class="fas fa-check-circle me-1"></i> Confirmed & Paid</span>
              <?php elseif ($booking['booking_status'] === 'cancelled'): ?>
                <span class="badge bg-danger-subtle text-danger fs-6"><i class="fas fa-times-circle me-1"></i> Cancelled</span>
              <?php else: ?>
                <span class="badge bg-warning-subtle text-warning fs-6">Pending Payment</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <div class="text-muted small">Route</div>
              <div class="fw-bold fs-5"><?= e($booking['from_city']) ?> → <?= e($booking['to_city']) ?></div>
              <div class="text-secondary small"><?= e($booking['bus_name']) ?></div>
            </div>
            <div class="col-6 text-end">
              <div class="text-muted small">Journey Date</div>
              <div class="fw-bold"><?= formatDateIndian($booking['journey_date']) ?></div>
              <div class="text-primary fw-semibold">Departs: <?= formatTime($booking['departure_time']) ?></div>
            </div>
          </div>

          <div class="p-3 bg-light rounded-3 small mb-3">
            <div class="mb-1"><strong>Boarding Point:</strong> <?= e($booking['boarding_point']) ?></div>
            <div><strong>Dropping Point:</strong> <?= e($booking['dropping_point']) ?></div>
          </div>

          <h6 class="fw-bold mb-2">Passengers & Seats</h6>
          <ul class="list-group mb-3">
            <?php foreach ($passengers as $p): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong><?= e($p['passenger_name']) ?></strong> (<?= e($p['age']) ?> Yrs, <?= e($p['gender']) ?>)</span>
                <span class="badge bg-primary fs-6">Seat: <?= e($p['seat_number']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <span class="fw-bold fs-5">Total Paid: <?= formatPrice($booking['total_amount']) ?></span>
            <a href="<?= $baseUrl ?>/user/ticket.php?pnr=<?= urlencode($booking['pnr']) ?>" target="_blank" class="btn btn-st-primary">
              <i class="fas fa-print me-1"></i> View & Print E-Ticket
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
