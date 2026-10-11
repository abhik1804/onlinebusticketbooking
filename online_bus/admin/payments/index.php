<?php
/**
 * Admin - Razorpay Payment Transactions
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

$statusFilter = cleanInput($_GET['status'] ?? '');
$search = cleanInput($_GET['q'] ?? '');

$sql = "SELECT p.*, b.pnr, b.contact_name, b.contact_mobile, fc.name as from_city, tc.name as to_city
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN routes r ON s.route_id = r.id
        JOIN indian_cities fc ON r.from_city_id = fc.id
        JOIN indian_cities tc ON r.to_city_id = tc.id";

$where = [];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(b.pnr LIKE ? OR p.razorpay_order_id LIKE ? OR p.razorpay_payment_id LIKE ? OR b.contact_name LIKE ?)";
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY p.id DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Total verified captured revenue
$revTotal = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'captured'")->fetchColumn();

$adminPageTitle = "Razorpay Payments – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">Razorpay Payment Transactions</h4>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= $adminUrl ?>/payments/export.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" class="btn btn-outline-success btn-sm">
        <i class="fas fa-file-csv me-1"></i> Export Transactions
      </a>
    </div>
  </header>

  <div class="admin-content-body">
    <!-- Revenue Callout Banner -->
    <div class="bg-white p-3 rounded-4 border shadow-sm mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <span class="text-muted small fw-bold text-uppercase">Verified Gross Revenue</span>
        <h3 class="fw-bold text-success mb-0"><?= formatPrice($revTotal) ?></h3>
      </div>
      <span class="badge bg-success-subtle text-success p-2">
        <i class="fas fa-shield-alt me-1"></i> Razorpay Test Webhook & HMAC Synchronized
      </span>
    </div>

    <div class="admin-table-card">
      <div class="admin-table-header">
        <form action="<?= $adminUrl ?>/payments/index.php" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Order ID, Payment ID, PNR..." value="<?= e($search) ?>" style="width: 250px;">
          
          <select name="status" class="form-select form-select-sm w-auto">
            <option value="">All Statuses</option>
            <option value="captured" <?= ($statusFilter === 'captured') ? 'selected' : '' ?>>Captured (Success)</option>
            <option value="created" <?= ($statusFilter === 'created') ? 'selected' : '' ?>>Created</option>
            <option value="refunded" <?= ($statusFilter === 'refunded') ? 'selected' : '' ?>>Refunded</option>
            <option value="failed" <?= ($statusFilter === 'failed') ? 'selected' : '' ?>>Failed</option>
          </select>

          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
          <a href="<?= $adminUrl ?>/payments/index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>

        <span class="small text-muted fw-bold">Transactions: <?= count($payments) ?></span>
      </div>

      <div class="table-responsive">
        <table class="table admin-table align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>PNR</th>
              <th>Order ID / Payment ID</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($payments)): ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">No transactions found.</td></tr>
            <?php else: ?>
              <?php foreach ($payments as $pay): ?>
                <tr>
                  <td>#<?= $pay['id'] ?></td>
                  <td>
                    <a href="<?= $adminUrl ?>/bookings/view.php?id=<?= $pay['booking_id'] ?>" class="fw-bold text-primary text-decoration-none">
                      <?= e($pay['pnr']) ?>
                    </a>
                  </td>
                  <td>
                    <div class="font-monospace small fw-bold text-dark"><?= e($pay['razorpay_payment_id'] ?? 'Pending') ?></div>
                    <small class="text-muted font-monospace"><?= e($pay['razorpay_order_id']) ?></small>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= e($pay['contact_name']) ?></div>
                    <small class="text-muted">+91 <?= e($pay['contact_mobile']) ?></small>
                  </td>
                  <td class="fw-bold fs-6"><?= formatPrice($pay['amount']) ?></td>
                  <td>
                    <span class="badge bg-light text-dark border text-uppercase"><?= e($pay['payment_method'] ?? 'Online') ?></span>
                  </td>
                  <td>
                    <?php if ($pay['status'] === 'captured'): ?>
                      <span class="badge bg-success-subtle text-success">Captured</span>
                    <?php elseif ($pay['status'] === 'refunded'): ?>
                      <span class="badge bg-danger-subtle text-danger">Refunded</span>
                    <?php elseif ($pay['status'] === 'failed'): ?>
                      <span class="badge bg-danger">Failed</span>
                    <?php else: ?>
                      <span class="badge bg-warning-subtle text-warning">Created</span>
                    <?php endif; ?>
                  </td>
                  <td class="small text-muted"><?= date('d M Y, h:i A', strtotime($pay['created_at'])) ?></td>
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
