<?php
/**
 * User Dashboard
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireUserLogin();
$user = getCurrentUser();
$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

$userId = $user['id'];

// Total stats
$stats = [
    'total' => 0,
    'upcoming' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$todayStr = date('Y-m-d');

$stmt = $db->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN b.booking_status = 'confirmed' AND s.departure_date >= ? THEN 1 ELSE 0 END) as upcoming_bookings,
    SUM(CASE WHEN b.booking_status = 'confirmed' AND s.departure_date < ? THEN 1 ELSE 0 END) as completed_bookings,
    SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    WHERE b.user_id = ?");
$stmt->execute([$todayStr, $todayStr, $userId]);
$row = $stmt->fetch();

if ($row) {
    $stats['total'] = (int)$row['total_bookings'];
    $stats['upcoming'] = (int)$row['upcoming_bookings'];
    $stats['completed'] = (int)$row['completed_bookings'];
    $stats['cancelled'] = (int)$row['cancelled_bookings'];
}

// Recent Bookings
$recStmt = $db->prepare("SELECT b.*, 
                                s.departure_date, s.departure_time, s.arrival_time,
                                bus.bus_name, bus.bus_type,
                                fc.name as from_city, tc.name as to_city
                         FROM bookings b
                         JOIN schedules s ON b.schedule_id = s.id
                         JOIN buses bus ON s.bus_id = bus.id
                         JOIN routes r ON s.route_id = r.id
                         JOIN indian_cities fc ON r.from_city_id = fc.id
                         JOIN indian_cities tc ON r.to_city_id = tc.id
                         WHERE b.user_id = ?
                         ORDER BY b.id DESC
                         LIMIT 5");
$recStmt->execute([$userId]);
$recentBookings = $recStmt->fetchAll();

$pageTitle = "My Dashboard – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <!-- Welcome Top Bar -->
  <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h3 class="fw-bold mb-1">Namaste, <?= e($user['full_name']) ?>! 🙏</h3>
        <p class="text-muted small mb-0">Welcome to your Shreenathji Travels passenger portal. Manage your trips and tickets anytime.</p>
      </div>
      <div>
        <a href="<?= $baseUrl ?>/buses/search.php" class="btn btn-st-primary">
          <i class="fas fa-plus-circle me-1"></i> Book New Journey
        </a>
      </div>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="bg-white p-3 rounded-4 border shadow-sm text-center">
        <div class="text-primary fs-3 fw-bold mb-1"><?= $stats['total'] ?></div>
        <div class="text-muted small fw-semibold">Total Bookings</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bg-white p-3 rounded-4 border shadow-sm text-center">
        <div class="text-success fs-3 fw-bold mb-1"><?= $stats['upcoming'] ?></div>
        <div class="text-muted small fw-semibold">Upcoming Trips</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bg-white p-3 rounded-4 border shadow-sm text-center">
        <div class="text-secondary fs-3 fw-bold mb-1"><?= $stats['completed'] ?></div>
        <div class="text-muted small fw-semibold">Completed Trips</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bg-white p-3 rounded-4 border shadow-sm text-center">
        <div class="text-danger fs-3 fw-bold mb-1"><?= $stats['cancelled'] ?></div>
        <div class="text-muted small fw-semibold">Cancelled Trips</div>
      </div>
    </div>
  </div>

  <!-- Main Content: Recent Trips & Quick Links -->
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="bg-white p-4 rounded-4 border shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0"><i class="fas fa-ticket-alt text-primary me-2"></i>Recent Bookings</h5>
          <a href="<?= $baseUrl ?>/user/my_bookings.php" class="small fw-bold text-primary">View All</a>
        </div>

        <?php if (empty($recentBookings)): ?>
          <div class="text-center py-4 text-muted">
            <i class="fas fa-bus-alt fs-1 text-light-emphasis mb-2 d-block"></i>
            No bookings found yet. Plan your next adventure today!
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>PNR</th>
                  <th>Route</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                  <th>Amount</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentBookings as $bk): ?>
                  <tr>
                    <td>
                      <span class="fw-bold text-primary"><?= e($bk['pnr']) ?></span>
                    </td>
                    <td>
                      <div class="fw-semibold"><?= e($bk['from_city']) ?> → <?= e($bk['to_city']) ?></div>
                      <small class="text-muted"><?= e($bk['bus_name']) ?></small>
                    </td>
                    <td>
                      <div><?= date('d M Y', strtotime($bk['journey_date'])) ?></div>
                      <small class="text-muted"><?= formatTime($bk['departure_time']) ?></small>
                    </td>
                    <td>
                      <?php if ($bk['booking_status'] === 'confirmed'): ?>
                        <span class="badge bg-success-subtle text-success">Confirmed</span>
                      <?php elseif ($bk['booking_status'] === 'cancelled'): ?>
                        <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                      <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td class="fw-bold"><?= formatPrice($bk['total_amount']) ?></td>
                    <td class="text-end">
                      <a href="<?= $baseUrl ?>/user/ticket.php?pnr=<?= urlencode($bk['pnr']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View Ticket">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Sidebar Quick Links -->
    <div class="col-lg-4">
      <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
        <h5 class="fw-bold mb-3"><i class="fas fa-user-circle text-primary me-2"></i>My Profile</h5>
        <div class="mb-2"><strong>Name:</strong> <?= e($user['full_name']) ?></div>
        <div class="mb-2"><strong>Email:</strong> <?= e($user['email']) ?></div>
        <div class="mb-3"><strong>Mobile:</strong> +91 <?= e($user['mobile']) ?></div>
        <a href="<?= $baseUrl ?>/user/profile.php" class="btn btn-outline-secondary btn-sm w-100">
          <i class="fas fa-cog me-1"></i> Edit Profile & Password
        </a>
      </div>

      <div class="p-4 rounded-4 text-white shadow-sm" style="background: linear-gradient(135deg, #1d4ed8, #0f172a);">
        <h6 class="fw-bold mb-2"><i class="fas fa-shield-alt text-warning me-2"></i>Travel Assistance</h6>
        <p class="small text-white-50 mb-3">Need to change your boarding point or have special assistance requirements? Contact our 24/7 passenger desk.</p>
        <a href="tel:<?= e($appConfig['support_phone']) ?>" class="btn btn-light btn-sm text-primary fw-bold">
          <i class="fas fa-phone-alt me-1"></i> <?= e($appConfig['support_phone']) ?>
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
