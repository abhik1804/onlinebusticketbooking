<?php
/**
 * My Bookings - Passenger Trips History
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requireUserLogin();
$user = getCurrentUser();
$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

$filter = cleanInput($_GET['filter'] ?? 'all');
$todayStr = date('Y-m-d');

$query = "SELECT b.*, 
                 s.departure_date, s.departure_time, s.arrival_date, s.arrival_time,
                 bus.bus_name, bus.bus_type, bus.bus_number,
                 fc.name as from_city, tc.name as to_city
          FROM bookings b
          JOIN schedules s ON b.schedule_id = s.id
          JOIN buses bus ON s.bus_id = bus.id
          JOIN routes r ON s.route_id = r.id
          JOIN indian_cities fc ON r.from_city_id = fc.id
          JOIN indian_cities tc ON r.to_city_id = tc.id
          WHERE b.user_id = ?";

$params = [$user['id']];

if ($filter === 'upcoming') {
    $query .= " AND b.booking_status = 'confirmed' AND s.departure_date >= ?";
    $params[] = $todayStr;
} elseif ($filter === 'completed') {
    $query .= " AND b.booking_status = 'confirmed' AND s.departure_date < ?";
    $params[] = $todayStr;
} elseif ($filter === 'cancelled') {
    $query .= " AND b.booking_status = 'cancelled'";
}

$query .= " ORDER BY b.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = "My Bookings – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold mb-1">My Bookings</h3>
      <p class="text-muted small mb-0">View journey details, download printable tickets, or manage cancellations</p>
    </div>
    <a href="<?= $baseUrl ?>/buses/search.php" class="btn btn-st-primary">
      <i class="fas fa-plus me-1"></i> Book New Bus Ticket
    </a>
  </div>

  <?php if (!empty($_GET['cancelled'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
      <i class="fas fa-check-circle me-2"></i> Ticket cancelled successfully! Eligible refund of <strong><?= formatPrice($_GET['refund'] ?? 0) ?></strong> will be credited to your original payment method.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Filter Tabs -->
  <div class="deck-nav-pills mb-4">
    <a href="<?= $baseUrl ?>/user/my_bookings.php?filter=all" class="deck-pill-btn <?= ($filter === 'all') ? 'active' : '' ?>">
      All Trips
    </a>
    <a href="<?= $baseUrl ?>/user/my_bookings.php?filter=upcoming" class="deck-pill-btn <?= ($filter === 'upcoming') ? 'active' : '' ?>">
      Upcoming Trips
    </a>
    <a href="<?= $baseUrl ?>/user/my_bookings.php?filter=completed" class="deck-pill-btn <?= ($filter === 'completed') ? 'active' : '' ?>">
      Completed Trips
    </a>
    <a href="<?= $baseUrl ?>/user/my_bookings.php?filter=cancelled" class="deck-pill-btn <?= ($filter === 'cancelled') ? 'active' : '' ?>">
      Cancelled
    </a>
  </div>

  <?php if (empty($bookings)): ?>
    <div class="bg-white p-5 rounded-4 border text-center shadow-sm">
      <i class="fas fa-ticket-alt fs-1 text-muted mb-3 d-block"></i>
      <h5 class="fw-bold">No bookings found in this category</h5>
      <p class="text-muted small">You don't have any bookings matching this filter.</p>
      <a href="<?= $baseUrl ?>/buses/search.php" class="btn btn-st-gold btn-sm">Explore Routes & Book</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($bookings as $bk): ?>
        <?php
          // Fetch passengers for this booking
          $pStmt = $db->prepare("SELECT seat_number, passenger_name FROM booking_passengers WHERE booking_id = ?");
          $pStmt->execute([$bk['id']]);
          $passengers = $pStmt->fetchAll();
          $seatsStr = implode(', ', array_column($passengers, 'seat_number'));

          $isUpcoming = ($bk['booking_status'] === 'confirmed' && $bk['departure_date'] >= $todayStr);
        ?>
        <div class="col-lg-6">
          <div class="bg-white p-4 rounded-4 border shadow-sm h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold fs-5 text-primary">PNR: <?= e($bk['pnr']) ?></span>
                <?php if ($bk['booking_status'] === 'confirmed'): ?>
                  <span class="badge bg-success-subtle text-success">Confirmed</span>
                <?php elseif ($bk['booking_status'] === 'cancelled'): ?>
                  <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                <?php else: ?>
                  <span class="badge bg-warning-subtle text-warning">Payment Pending</span>
                <?php endif; ?>
              </div>

              <div class="fw-bold text-dark fs-5 mb-1"><?= e($bk['from_city']) ?> → <?= e($bk['to_city']) ?></div>
              <div class="text-secondary small mb-3"><?= e($bk['bus_name']) ?> (<?= e($bk['bus_type']) ?>)</div>

              <div class="row g-2 mb-3 bg-light p-3 rounded-3 small">
                <div class="col-6">
                  <span class="text-muted d-block">Journey Date:</span>
                  <strong><?= formatDateIndian($bk['journey_date']) ?></strong>
                </div>
                <div class="col-6">
                  <span class="text-muted d-block">Departure Time:</span>
                  <strong><?= formatTime($bk['departure_time']) ?></strong>
                </div>
                <div class="col-6 mt-2">
                  <span class="text-muted d-block">Seats (<?= count($passengers) ?>):</span>
                  <strong class="text-primary"><?= e($seatsStr) ?></strong>
                </div>
                <div class="col-6 mt-2">
                  <span class="text-muted d-block">Total Fare:</span>
                  <strong class="text-dark"><?= formatPrice($bk['total_amount']) ?></strong>
                </div>
              </div>

              <div class="small text-muted mb-3">
                <div><i class="fas fa-map-marker-alt text-primary me-1"></i> <strong>Boarding:</strong> <?= e($bk['boarding_point']) ?></div>
                <div class="mt-1"><i class="fas fa-map-pin text-danger me-1"></i> <strong>Dropping:</strong> <?= e($bk['dropping_point']) ?></div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top flex-wrap gap-2">
              <a href="<?= $baseUrl ?>/user/ticket.php?pnr=<?= urlencode($bk['pnr']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-print me-1"></i> View Ticket
              </a>

              <?php if ($isUpcoming): ?>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $bk['id'] ?>">
                  <i class="fas fa-ban me-1"></i> Cancel Ticket
                </button>

                <!-- Cancellation Modal -->
                <div class="modal fade" id="cancelModal<?= $bk['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content rounded-4 border-0">
                      <form action="<?= $baseUrl ?>/booking/cancel.php" method="POST">
                        <?= getCsrfField() ?>
                        <input type="hidden" name="pnr" value="<?= e($bk['pnr']) ?>">

                        <div class="modal-header">
                          <h5 class="modal-title fw-bold text-danger">Cancel Booking PNR: <?= e($bk['pnr']) ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-start">
                          <p class="small text-muted">Are you sure you want to cancel this booking?</p>
                          <div class="p-3 bg-light rounded-3 small mb-3">
                            <h6 class="fw-bold text-dark mb-1">Cancellation Policy:</h6>
                            <ul class="mb-0 ps-3">
                              <li>> 24 hours before departure: <strong>90% refund</strong></li>
                              <li>12 to 24 hours before departure: <strong>70% refund</strong></li>
                              <li>< 12 hours before departure: <strong>Non-refundable</strong></li>
                            </ul>
                          </div>
                          <div class="mb-3">
                            <label class="form-label small fw-bold">Reason for Cancellation</label>
                            <select name="cancel_reason" class="form-select form-select-sm" required>
                              <option value="Change of travel plans">Change of travel plans</option>
                              <option value="Personal emergency">Personal emergency</option>
                              <option value="Booked by mistake">Booked by mistake</option>
                              <option value="Other reason">Other reason</option>
                            </select>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep Booking</button>
                          <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
