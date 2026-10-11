<?php
/**
 * Admin - Schedules & Trips Management
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

$filterDate = cleanInput($_GET['date'] ?? date('Y-m-d'));

$sql = "SELECT s.*, 
               b.bus_name, b.bus_number, b.bus_type, b.total_seats,
               fc.name as from_city, tc.name as to_city,
               (SELECT COUNT(*) FROM booking_passengers bp JOIN bookings bk ON bp.booking_id = bk.id WHERE bk.schedule_id = s.id AND bk.booking_status IN ('confirmed', 'pending_payment')) as booked_seats_count,
               (SELECT COUNT(*) FROM blocked_seats blk WHERE blk.schedule_id = s.id) as blocked_seats_count
        FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN routes r ON s.route_id = r.id
        JOIN indian_cities fc ON r.from_city_id = fc.id
        JOIN indian_cities tc ON r.to_city_id = tc.id";

$params = [];
if (!empty($filterDate)) {
    $sql .= " WHERE s.departure_date = ?";
    $params = [$filterDate];
}
$sql .= " ORDER BY s.departure_date ASC, s.departure_time ASC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

$adminPageTitle = "Schedules & Trips – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">Bus Journey Schedules</h4>
    </div>
    <a href="<?= $adminUrl ?>/schedules/add.php" class="btn btn-st-primary btn-sm">
      <i class="fas fa-plus me-1"></i> Add / Generate Schedules
    </a>
  </header>

  <div class="admin-content-body">
    <?php if (isset($_GET['created'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> Schedules created successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="admin-table-card">
      <div class="admin-table-header">
        <form action="<?= $adminUrl ?>/schedules/index.php" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
          <label class="small fw-bold text-muted mb-0">Select Date:</label>
          <input type="date" name="date" class="form-control form-control-sm w-auto" value="<?= e($filterDate) ?>" onchange="this.form.submit()">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
          <a href="<?= $adminUrl ?>/schedules/index.php?date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">Today</a>
        </form>
        <span class="small text-muted fw-bold">Found: <?= count($schedules) ?> schedules</span>
      </div>

      <div class="table-responsive">
        <table class="table admin-table align-middle">
          <thead>
            <tr>
              <th>Date & Time</th>
              <th>Route</th>
              <th>Assigned Bus</th>
              <th>Fares</th>
              <th>Seat Occupancy</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($schedules)): ?>
              <tr><td colspan="7" class="text-center py-4 text-muted">No schedules found for this date.</td></tr>
            <?php else: ?>
              <?php foreach ($schedules as $s): ?>
                <?php
                  $avail = max(0, $s['total_seats'] - ($s['booked_seats_count'] + $s['blocked_seats_count']));
                ?>
                <tr>
                  <td>
                    <div class="fw-bold text-dark"><?= date('d M Y', strtotime($s['departure_date'])) ?></div>
                    <small class="text-primary fw-semibold"><?= formatTime($s['departure_time']) ?> → <?= formatTime($s['arrival_time']) ?></small>
                  </td>
                  <td>
                    <div class="fw-bold"><?= e($s['from_city']) ?> → <?= e($s['to_city']) ?></div>
                  </td>
                  <td>
                    <div><?= e($s['bus_name']) ?></div>
                    <small class="text-muted"><?= e($s['bus_number']) ?> (<?= e($s['bus_type']) ?>)</small>
                  </td>
                  <td>
                    <div class="fw-bold"><?= formatPrice($s['base_fare']) ?></div>
                    <?php if (!empty($s['sleeper_fare'])): ?>
                      <small class="text-muted">Sleeper: <?= formatPrice($s['sleeper_fare']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                        <div class="progress-bar bg-success" style="width: <?= ($s['total_seats'] > 0) ? round(($s['booked_seats_count'] / $s['total_seats']) * 100) : 0 ?>%;"></div>
                      </div>
                      <span class="small fw-semibold"><?= $s['booked_seats_count'] ?> / <?= $s['total_seats'] ?></span>
                    </div>
                    <?php if ($s['blocked_seats_count'] > 0): ?>
                      <small class="text-danger">(<?= $s['blocked_seats_count'] ?> blocked)</small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-success-subtle text-success"><?= ucfirst(e($s['status'])) ?></span>
                  </td>
                  <td class="text-end">
                    <a href="<?= $adminUrl ?>/schedules/block_seats.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning" title="Manage Blocked / VIP Seats">
                      <i class="fas fa-lock"></i> Block Seats
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
