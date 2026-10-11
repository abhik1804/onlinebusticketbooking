<?php
/**
 * Admin - Route Management
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

// Handle Status Toggle
if (isset($_GET['toggle_id'])) {
    $toggleId = (int)$_GET['toggle_id'];
    $db->prepare("UPDATE routes SET is_active = IF(is_active=1, 0, 1), updated_at = NOW() WHERE id = ?")->execute([$toggleId]);
    header("Location: {$adminUrl}/routes/index.php?toggled=1");
    exit;
}

$routesStmt = $db->query("SELECT r.*, 
                                 fc.name as from_city, fc.state as from_state,
                                 tc.name as to_city, tc.state as to_state,
                                 (SELECT COUNT(*) FROM schedules s WHERE s.route_id = r.id) as schedule_count
                          FROM routes r
                          JOIN indian_cities fc ON r.from_city_id = fc.id
                          JOIN indian_cities tc ON r.to_city_id = tc.id
                          ORDER BY r.id DESC");
$routes = $routesStmt->fetchAll();

$adminPageTitle = "Route Management – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">Route Management</h4>
    </div>
    <a href="<?= $adminUrl ?>/routes/add.php" class="btn btn-st-primary btn-sm">
      <i class="fas fa-plus me-1"></i> Add New Route
    </a>
  </header>

  <div class="admin-content-body">
    <?php if (isset($_GET['created'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> Route created successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> Route updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-trash me-1"></i> Route deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="admin-table-card">
      <div class="admin-table-header">
        <h5 class="fw-bold mb-0">Active Travel Routes (<?= count($routes) ?>)</h5>
      </div>

      <div class="table-responsive">
        <table class="table admin-table align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Source (From)</th>
              <th>Destination (To)</th>
              <th>Distance</th>
              <th>Est. Duration</th>
              <th>Schedules</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($routes)): ?>
              <tr><td colspan="8" class="text-center py-4 text-muted">No routes configured yet.</td></tr>
            <?php else: ?>
              <?php foreach ($routes as $r): ?>
                <tr>
                  <td>#<?= $r['id'] ?></td>
                  <td>
                    <div class="fw-bold text-dark"><?= e($r['from_city']) ?></div>
                    <small class="text-muted"><?= e($r['from_state']) ?></small>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= e($r['to_city']) ?></div>
                    <small class="text-muted"><?= e($r['to_state']) ?></small>
                  </td>
                  <td><?= e($r['distance_km']) ?> km</td>
                  <td><?= e($r['estimated_duration']) ?></td>
                  <td>
                    <span class="badge bg-primary-subtle text-primary"><?= $r['schedule_count'] ?> trips</span>
                  </td>
                  <td>
                    <?php if ($r['is_active']): ?>
                      <a href="<?= $adminUrl ?>/routes/index.php?toggle_id=<?= $r['id'] ?>" class="badge bg-success-subtle text-success text-decoration-none">
                        Active
                      </a>
                    <?php else: ?>
                      <a href="<?= $adminUrl ?>/routes/index.php?toggle_id=<?= $r['id'] ?>" class="badge bg-danger-subtle text-danger text-decoration-none">
                        Inactive
                      </a>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <a href="<?= $adminUrl ?>/routes/edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="<?= $adminUrl ?>/routes/delete.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this route?');">
                      <i class="fas fa-trash"></i>
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
