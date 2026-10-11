<?php
/**
 * Admin - Reports & Analytics
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

$startDate = cleanInput($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

// Summary Metrics for Date Range
$sumStmt = $db->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN b.booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
    SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
    SUM(CASE WHEN b.booking_status = 'confirmed' THEN b.total_amount ELSE 0 END) as total_sales,
    SUM(b.refund_amount) as total_refunds
    FROM bookings b
    WHERE DATE(b.created_at) BETWEEN ? AND ?");
$sumStmt->execute([$startDate, $endDate]);
$summary = $sumStmt->fetch();

// Daily Sales Trend within selected Range
$trendStmt = $db->prepare("SELECT DATE(b.created_at) as sale_date, 
                                  COUNT(b.id) as daily_bookings, 
                                  SUM(CASE WHEN b.booking_status = 'confirmed' THEN b.total_amount ELSE 0 END) as daily_revenue
                           FROM bookings b 
                           WHERE DATE(b.created_at) BETWEEN ? AND ?
                           GROUP BY DATE(b.created_at)
                           ORDER BY sale_date ASC");
$trendStmt->execute([$startDate, $endDate]);
$trendRows = $trendStmt->fetchAll();

$trendDates = [];
$trendRev = [];
$trendCount = [];
foreach ($trendRows as $tr) {
    $trendDates[] = date('d M', strtotime($tr['sale_date']));
    $trendRev[] = (float)$tr['daily_revenue'];
    $trendCount[] = (int)$tr['daily_bookings'];
}

$adminPageTitle = "Reports & Analytics – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">Financial & Operational Reports</h4>
    </div>
  </header>

  <div class="admin-content-body">
    <!-- Filter Date Range Bar -->
    <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
      <form action="<?= $adminUrl ?>/reports/index.php" method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label small fw-bold text-muted">From Date</label>
          <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-bold text-muted">To Date</label>
          <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button type="submit" class="btn btn-st-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Generate Report</button>
          <a href="<?= $adminUrl ?>/reports/index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>

    <!-- Range Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="admin-stat-card">
          <div class="text-muted small fw-bold text-uppercase">Net Confirmed Sales</div>
          <div class="fs-4 fw-bold text-success mt-1"><?= formatPrice($summary['total_sales'] ?? 0) ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-stat-card stat-success">
          <div class="text-muted small fw-bold text-uppercase">Total Bookings</div>
          <div class="fs-4 fw-bold text-dark mt-1"><?= (int)($summary['total_bookings'] ?? 0) ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-stat-card stat-warning">
          <div class="text-muted small fw-bold text-uppercase">Confirmed Trips</div>
          <div class="fs-4 fw-bold text-primary mt-1"><?= (int)($summary['confirmed_count'] ?? 0) ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="admin-stat-card stat-purple">
          <div class="text-muted small fw-bold text-uppercase">Processed Refunds</div>
          <div class="fs-4 fw-bold text-danger mt-1"><?= formatPrice($summary['total_refunds'] ?? 0) ?></div>
        </div>
      </div>
    </div>

    <!-- Trend Chart -->
    <div class="admin-table-card p-4">
      <h5 class="fw-bold mb-3">Daily Revenue & Bookings Trend</h5>
      <div style="height: 340px;">
        <canvas id="rangeTrendChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('rangeTrendChart')?.getContext('2d');
  if (ctx) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($trendDates) ?>,
        datasets: [
          {
            label: 'Daily Revenue (₹)',
            data: <?= json_encode($trendRev) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.3,
            yAxisID: 'y'
          },
          {
            label: 'Total Bookings',
            data: <?= json_encode($trendCount) ?>,
            borderColor: '#f59e0b',
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            tension: 0.3,
            yAxisID: 'y1'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            beginAtZero: true,
            ticks: { callback: function(val) { return '₹' + val; } }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            grid: { drawOnChartArea: false },
            beginAtZero: true
          }
        }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
