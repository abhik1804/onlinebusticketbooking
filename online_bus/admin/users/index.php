<?php
/**
 * Admin - User Management
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdminLogin();
$appConfig = require __DIR__ . '/../../config/app.php';
$adminUrl = $appConfig['admin_url'];
$db = getDB();

// Handle Status Toggle
if (isset($_GET['toggle_id'])) {
    $toggleId = (int)$_GET['toggle_id'];
    $db->prepare("UPDATE users SET is_active = IF(is_active=1, 0, 1), updated_at = NOW() WHERE id = ?")->execute([$toggleId]);
    header("Location: {$adminUrl}/users/index.php?toggled=1");
    exit;
}

$search = cleanInput($_GET['q'] ?? '');
$sql = "SELECT u.id, u.full_name, u.email, u.mobile, u.gender, u.is_active, u.created_at,
               (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) as total_bookings,
               (SELECT COALESCE(SUM(b.total_amount), 0) FROM bookings b WHERE b.user_id = u.id AND b.booking_status = 'confirmed') as total_spent
        FROM users u";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ?";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}
$sql .= " ORDER BY u.id DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$adminPageTitle = "Registered Users – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">Registered Passengers</h4>
    </div>
  </header>

  <div class="admin-content-body">
    <div class="admin-table-card">
      <div class="admin-table-header">
        <form action="<?= $adminUrl ?>/users/index.php" method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Search passenger name, email, mobile..." value="<?= e($search) ?>" style="width: 280px;">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
          <?php if (!empty($search)): ?>
            <a href="<?= $adminUrl ?>/users/index.php" class="btn btn-outline-secondary btn-sm">Clear</a>
          <?php endif; ?>
        </form>
        <span class="small text-muted fw-bold">Total Accounts: <?= count($users) ?></span>
      </div>

      <div class="table-responsive">
        <table class="table admin-table align-middle">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Mobile</th>
              <th>Bookings</th>
              <th>Total Spent</th>
              <th>Registered</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">No registered users found.</td></tr>
            <?php else: ?>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: bold; font-size: 0.8rem;">
                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                      </div>
                      <div class="fw-semibold text-dark"><?= e($u['full_name']) ?></div>
                    </div>
                  </td>
                  <td><?= e($u['email']) ?></td>
                  <td>+91 <?= e($u['mobile']) ?></td>
                  <td><span class="badge bg-primary-subtle text-primary"><?= $u['total_bookings'] ?> trips</span></td>
                  <td class="fw-bold"><?= formatPrice($u['total_spent']) ?></td>
                  <td class="small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <?php if ($u['is_active']): ?>
                      <span class="badge bg-success-subtle text-success">Active</span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger">Suspended</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <a href="<?= $adminUrl ?>/users/index.php?toggle_id=<?= $u['id'] ?>" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" onclick="return confirm('Toggle status for this user?');">
                      <?= $u['is_active'] ? '<i class="fas fa-user-slash me-1"></i> Suspend' : '<i class="fas fa-user-check me-1"></i> Activate' ?>
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
