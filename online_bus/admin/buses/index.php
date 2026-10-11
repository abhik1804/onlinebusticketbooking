<?php
/**
 * Admin - Bus Fleet Management
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
    $db->prepare("UPDATE buses SET is_active = IF(is_active=1, 0, 1), updated_at = NOW() WHERE id = ?")->execute([$toggleId]);
    header("Location: {$adminUrl}/buses/index.php?toggled=1");
    exit;
}

$search = cleanInput($_GET['q'] ?? '');
$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM bus_seats bs WHERE bs.bus_id = b.id) as configured_seats,
        (SELECT COUNT(*) FROM schedules s WHERE s.bus_id = b.id) as total_schedules
        FROM buses b";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE b.bus_name LIKE ? OR b.bus_number LIKE ? OR b.bus_type LIKE ?";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$sql .= " ORDER BY b.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$buses = $stmt->fetchAll();

$adminPageTitle = "Bus Management – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">Bus Fleet Management</h4>
    </div>
    <a href="<?= $adminUrl ?>/buses/add.php" class="btn btn-st-primary btn-sm">
      <i class="fas fa-plus me-1"></i> Add New Bus
    </a>
  </header>

  <div class="admin-content-body">
    <?php if (isset($_GET['created'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> New bus added and seat layout configured successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> Bus details updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-trash me-1"></i> Bus deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i> <?= e($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="admin-table-card">
      <div class="admin-table-header">
        <form action="<?= $adminUrl ?>/buses/index.php" method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name, reg no..." value="<?= e($search) ?>" style="width: 250px;">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
          <?php if (!empty($search)): ?>
            <a href="<?= $adminUrl ?>/buses/index.php" class="btn btn-outline-secondary btn-sm">Clear</a>
          <?php endif; ?>
        </form>
        <span class="small text-muted fw-bold">Total Buses: <?= count($buses) ?></span>
      </div>

      <div class="table-responsive">
        <table class="table admin-table align-middle">
          <thead>
            <tr>
              <th>Image</th>
              <th>Bus Name & Reg No</th>
              <th>Type / Layout</th>
              <th>Seats</th>
              <th>Schedules</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($buses)): ?>
              <tr><td colspan="7" class="text-center py-4 text-muted">No buses found.</td></tr>
            <?php else: ?>
              <?php foreach ($buses as $bus): ?>
                <tr>
                  <td style="width: 70px;">
                    <?php 
                      $imgSrc = !empty($bus['primary_image']) ? (str_starts_with($bus['primary_image'], 'http') ? $bus['primary_image'] : $baseUrl . '/' . $bus['primary_image']) : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=150&q=80';
                    ?>
                    <img src="<?= e($imgSrc) ?>" alt="Bus" class="rounded-2" style="width: 60px; height: 42px; object-fit: cover;">
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= e($bus['bus_name']) ?></div>
                    <small class="text-primary fw-semibold"><?= e($bus['bus_number']) ?></small>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= e($bus['bus_type']) ?></span>
                    <div class="small text-muted mt-1 text-capitalize"><?= str_replace('_', ' ', $bus['seating_layout']) ?></div>
                  </td>
                  <td>
                    <span class="fw-bold"><?= $bus['configured_seats'] ?></span> / <?= $bus['total_seats'] ?>
                  </td>
                  <td>
                    <span class="badge bg-info-subtle text-info"><?= $bus['total_schedules'] ?> trips</span>
                  </td>
                  <td>
                    <?php if ($bus['is_active']): ?>
                      <a href="<?= $adminUrl ?>/buses/index.php?toggle_id=<?= $bus['id'] ?>" class="badge bg-success-subtle text-success text-decoration-none" title="Click to Deactivate">
                        <i class="fas fa-check-circle me-1"></i> Active
                      </a>
                    <?php else: ?>
                      <a href="<?= $adminUrl ?>/buses/index.php?toggle_id=<?= $bus['id'] ?>" class="badge bg-danger-subtle text-danger text-decoration-none" title="Click to Activate">
                        <i class="fas fa-ban me-1"></i> Inactive
                      </a>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <a href="<?= $adminUrl ?>/buses/edit.php?id=<?= $bus['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Bus">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="<?= $adminUrl ?>/buses/delete.php?id=<?= $bus['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this bus?');" title="Delete Bus">
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
