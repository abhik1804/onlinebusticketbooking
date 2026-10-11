<?php
/**
 * Admin Dashboard - Analytics & Overview
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();
$currentAdmin = getCurrentAdmin();
$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$adminUrl = $appConfig['admin_url'];
$db = getDB();

$todayDate = date('Y-m-d');

// 1. Fetch Dynamic Statistics from Database
// Users count
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Buses count
$busesStmt = $db->query("SELECT COUNT(*) as total_buses, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_buses FROM buses");
$busesData = $busesStmt->fetch();
$totalBuses = (int)$busesData['total_buses'];
$activeBuses = (int)$busesData['active_buses'];

// Routes count
$totalRoutes = (int)$db->query("SELECT COUNT(*) FROM routes WHERE is_active = 1")->fetchColumn();

// Bookings breakdown
$bookingsStmt = $db->query("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN DATE(created_at) = '{$todayDate}' THEN 1 ELSE 0 END) as today_bookings,
    SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
    SUM(CASE WHEN booking_status = 'pending_payment' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
    FROM bookings");
$bookingsData = $bookingsStmt->fetch();
$totalBookings = (int)$bookingsData['total_bookings'];
$todayBookings = (int)$bookingsData['today_bookings'];
$confirmedBookings = (int)$bookingsData['confirmed_bookings'];
$pendingBookings = (int)$bookingsData['pending_bookings'];
$cancelledBookings = (int)$bookingsData['cancelled_bookings'];

// Verified Revenue from Captured Payments
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'captured'")->fetchColumn();

// 2. Recent Bookings
$recStmt = $db->query("SELECT b.*, 
                              s.departure_date, s.departure_time,
                              bus.bus_name,
                              fc.name as from_city, tc.name as to_city
                       FROM bookings b
                       JOIN schedules s ON b.schedule_id = s.id
                       JOIN buses bus ON s.bus_id = bus.id
                       JOIN routes r ON s.route_id = r.id
                       JOIN indian_cities fc ON r.from_city_id = fc.id
                       JOIN indian_cities tc ON r.to_city_id = tc.id
                       ORDER BY b.id DESC LIMIT 8");
$recentBookings = $recStmt->fetchAll();

// 3. Popular Routes Breakdown
$popRoutesStmt = $db->query("SELECT fc.name as from_city, tc.name as to_city, COUNT(b.id) as booking_count 
                             FROM routes r 
                             JOIN indian_cities fc ON r.from_city_id = fc.id 
                             JOIN indian_cities tc ON r.to_city_id = tc.id 
                             LEFT JOIN schedules s ON r.id = s.route_id 
                             LEFT JOIN bookings b ON s.id = b.schedule_id AND b.booking_status = 'confirmed'
                             GROUP BY r.id 
                             ORDER BY booking_count DESC 
                             LIMIT 5");
$popularRoutes = $popRoutesStmt->fetchAll();

// 4. Monthly Revenue History (Last 6 Months)
$monthsLabels = [];
$monthsRevenue = [];
for ($m = 5; $m >= 0; $m--) {
    $time = strtotime("-{$m} month");
    $yearMonth = date('Y-m', $time);
    $monthLabel = date('M Y', $time);
    $monthsLabels[] = $monthLabel;

    $mStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'captured' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $mStmt->execute([$yearMonth]);
    $monthsRevenue[] = (float)$mStmt->fetchColumn();
}

$adminPageTitle = "Admin Dashboard – Shreenathji Travels";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <!-- Top Header -->
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">System Analytics & Dashboard</h4>
    </div>

    <div class="d-flex align-items-center gap-3">
      <a href="<?= $baseUrl ?>/index.php" target="_blank" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-external-link-alt me-1"></i> View Live Site
      </a>
      <div class="text-secondary small d-none d-md-block">
        <i class="fas fa-clock me-1 text-warning"></i> <?= date('d M Y, h:i A') ?>
      </div>
    </div>
  </header>

  <!-- Content Body -->
  <div class="admin-content-body">
    <!-- Stat Cards Row 1 -->
    <div class="row g-3 mb-4">
      <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small fw-bold text-uppercase">Verified Revenue</div>
              <div class="fs-4 fw-bold text-dark mt-1"><?= formatPrice($totalRevenue) ?></div>
              <div class="text-success small"><i class="fas fa-check-circle me-1"></i> Razorpay Captured</div>
            </div>
            <div class="stat-icon-wrapper">
              <i class="fas fa-rupee-sign text-primary"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card stat-success">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small fw-bold text-uppercase">Today's Bookings</div>
              <div class="fs-4 fw-bold text-dark mt-1"><?= $todayBookings ?></div>
              <div class="text-muted small">Total: <?= $totalBookings ?> Bookings</div>
            </div>
            <div class="stat-icon-wrapper" style="background: #dcfce7; color: #15803d;">
              <i class="fas fa-ticket-alt"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card stat-warning">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small fw-bold text-uppercase">Active Fleet</div>
              <div class="fs-4 fw-bold text-dark mt-1"><?= $activeBuses ?> / <?= $totalBuses ?></div>
              <div class="text-muted small">Across <?= $totalRoutes ?> Routes</div>
            </div>
            <div class="stat-icon-wrapper" style="background: #fef3c7; color: #b45309;">
              <i class="fas fa-bus"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card stat-purple">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small fw-bold text-uppercase">Registered Passengers</div>
              <div class="fs-4 fw-bold text-dark mt-1"><?= $totalUsers ?></div>
              <div class="text-muted small">Active Accounts</div>
            </div>
            <div class="stat-icon-wrapper" style="background: #f3e8ff; color: #7e22ce;">
              <i class="fas fa-users"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
      <!-- Monthly Revenue Bar Chart -->
      <div class="col-lg-8">
        <div class="admin-table-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Revenue Overview (INR)</h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Monthly Trend</span>
          </div>
          <div style="height: 300px;">
            <canvas id="revenueTrendChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Booking Status Doughnut Chart -->
      <div class="col-lg-4">
        <div class="admin-table-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Booking Status Ratio</h5>
            <span class="badge bg-secondary-subtle text-secondary">All Time</span>
          </div>
          <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
            <canvas id="bookingStatusChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Bookings Table & Quick Action Bar -->
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="admin-table-card">
          <div class="admin-table-header">
            <h5 class="fw-bold mb-0"><i class="fas fa-list text-primary me-2"></i>Recent Bookings</h5>
            <a href="<?= $adminUrl ?>/bookings/index.php" class="btn btn-outline-primary btn-sm">View All Bookings</a>
          </div>

          <div class="table-responsive">
            <table class="table admin-table align-middle">
              <thead>
                <tr>
                  <th>PNR</th>
                  <th>Passenger</th>
                  <th>Route</th>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentBookings)): ?>
                  <tr><td colspan="7" class="text-center py-4 text-muted">No bookings recorded yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($recentBookings as $b): ?>
                    <tr>
                      <td class="fw-bold text-primary"><?= e($b['pnr']) ?></td>
                      <td>
                        <div class="fw-semibold"><?= e($b['contact_name']) ?></div>
                        <small class="text-muted">+91 <?= e($b['contact_mobile']) ?></small>
                      </td>
                      <td>
                        <div><?= e($b['from_city']) ?> → <?= e($b['to_city']) ?></div>
                        <small class="text-muted"><?= e($b['bus_name']) ?></small>
                      </td>
                      <td><?= date('d M Y', strtotime($b['journey_date'])) ?></td>
                      <td class="fw-bold"><?= formatPrice($b['total_amount']) ?></td>
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
                        <a href="<?= $baseUrl ?>/user/ticket.php?pnr=<?= urlencode($b['pnr']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Ticket">
                          <i class="fas fa-eye"></i>
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

      <!-- Quick Actions & Top Routes -->
      <div class="col-lg-4">
        <!-- Quick Actions Card -->
        <div class="admin-table-card p-4 mb-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h5>
          <div class="d-grid gap-2">
            <a href="<?= $adminUrl ?>/buses/add.php" class="btn btn-outline-primary text-start">
              <i class="fas fa-plus-circle me-2"></i> Add New Bus Coach
            </a>
            <a href="<?= $adminUrl ?>/routes/add.php" class="btn btn-outline-secondary text-start">
              <i class="fas fa-route me-2"></i> Create Bus Route
            </a>
            <a href="<?= $adminUrl ?>/schedules/add.php" class="btn btn-outline-success text-start">
              <i class="fas fa-calendar-plus me-2"></i> Schedule Journey Trip
            </a>
            <a href="<?= $adminUrl ?>/payments/index.php" class="btn btn-outline-dark text-start">
              <i class="fas fa-credit-card me-2"></i> View Razorpay Transactions
            </a>
          </div>
        </div>

        <!-- Top Routes Ranking -->
        <div class="admin-table-card p-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-trophy text-warning me-2"></i>Top Bus Routes</h5>
          <ul class="list-group list-group-flush">
            <?php foreach ($popularRoutes as $idx => $pr): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div>
                  <span class="badge bg-light text-dark me-2">#<?= $idx + 1 ?></span>
                  <strong><?= e($pr['from_city']) ?> → <?= e($pr['to_city']) ?></strong>
                </div>
                <span class="badge bg-primary rounded-pill"><?= $pr['booking_count'] ?> trips</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js Scripts Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // 1. Monthly Revenue Chart
  const ctxRev = document.getElementById('revenueTrendChart')?.getContext('2d');
  if (ctxRev) {
    new Chart(ctxRev, {
      type: 'bar',
      data: {
        labels: <?= json_encode($monthsLabels) ?>,
        datasets: [{
          label: 'Revenue (₹)',
          data: <?= json_encode($monthsRevenue) ?>,
          backgroundColor: 'rgba(37, 99, 235, 0.85)',
          borderColor: '#1d4ed8',
          borderWidth: 1.5,
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) { return '₹' + value; }
            }
          }
        }
      }
    });
  }

  // 2. Booking Status Doughnut Chart
  const ctxStatus = document.getElementById('bookingStatusChart')?.getContext('2d');
  if (ctxStatus) {
    new Chart(ctxStatus, {
      type: 'doughnut',
      data: {
        labels: ['Confirmed', 'Pending', 'Cancelled'],
        datasets: [{
          data: [<?= $confirmedBookings ?>, <?= $pendingBookings ?>, <?= $cancelledBookings ?>],
          backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        },
        cutout: '65%'
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
