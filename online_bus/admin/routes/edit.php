<?php
/**
 * Admin - Edit Route
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdminLogin();
$appConfig = require __DIR__ . '/../../config/app.php';
$baseUrl = $appConfig['base_url'];
$adminUrl = $appConfig['admin_url'];
$db = getDB();

$routeId = (int)($_GET['id'] ?? 0);
if ($routeId <= 0) {
    header("Location: {$adminUrl}/routes/index.php");
    exit;
}

$stmt = $db->prepare("SELECT r.*, fc.name as from_city_name, tc.name as to_city_name 
                      FROM routes r 
                      JOIN indian_cities fc ON r.from_city_id = fc.id 
                      JOIN indian_cities tc ON r.to_city_id = tc.id 
                      WHERE r.id = ? LIMIT 1");
$stmt->execute([$routeId]);
$route = $stmt->fetch();

if (!$route) {
    die("Route not found.");
}

$boardingList = json_decode($route['boarding_points'] ?? '[]', true) ?: [];
$droppingList = json_decode($route['dropping_points'] ?? '[]', true) ?: [];

$boardingText = implode("\n", array_column($boardingList, 'name'));
$droppingText = implode("\n", array_column($droppingList, 'name'));

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $distance = (int)($_POST['distance_km'] ?? $route['distance_km']);
    $duration = cleanInput($_POST['estimated_duration'] ?? $route['estimated_duration']);
    $durationMin = (int)($_POST['duration_minutes'] ?? $route['duration_minutes']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $rawBoarding = trim($_POST['boarding_points'] ?? '');
    $rawDropping = trim($_POST['dropping_points'] ?? '');

    $boardingArr = [];
    foreach (explode("\n", $rawBoarding) as $line) {
        $clean = trim($line);
        if (!empty($clean)) {
            $boardingArr[] = ['name' => $clean, 'landmark' => 'Pick-up Stop', 'time_offset' => 0];
        }
    }

    $droppingArr = [];
    foreach (explode("\n", $rawDropping) as $line) {
        $clean = trim($line);
        if (!empty($clean)) {
            $droppingArr[] = ['name' => $clean, 'landmark' => 'Drop Stop', 'time_offset' => $durationMin];
        }
    }

    $updStmt = $db->prepare("UPDATE routes SET distance_km = ?, estimated_duration = ?, duration_minutes = ?, boarding_points = ?, dropping_points = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
    if ($updStmt->execute([$distance, $duration, $durationMin, json_encode($boardingArr, JSON_UNESCAPED_UNICODE), json_encode($droppingArr, JSON_UNESCAPED_UNICODE), $isActive, $routeId])) {
        logAdminActivity($currentAdmin['id'], 'edit_route', "Updated route #{$routeId} ({$route['from_city_name']} to {$route['to_city_name']})");
        header("Location: {$adminUrl}/routes/index.php?updated=1");
        exit;
    } else {
        $errorMessage = 'Failed to update route.';
    }
}

$adminPageTitle = "Edit Route – " . e($route['from_city_name']) . " to " . e($route['to_city_name']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= $adminUrl ?>/routes/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0">Edit Route: <?= e($route['from_city_name']) ?> → <?= e($route['to_city_name']) ?></h4>
    </div>
  </header>

  <div class="admin-content-body">
    <div class="admin-table-card p-4">
      <form method="POST">
        <?= getCsrfField() ?>

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Source City</label>
            <input type="text" class="form-control bg-light" value="<?= e($route['from_city_name']) ?>" readonly>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Destination City</label>
            <input type="text" class="form-control bg-light" value="<?= e($route['to_city_name']) ?>" readonly>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Total Distance (km)</label>
            <input type="number" name="distance_km" class="form-control" value="<?= e($route['distance_km']) ?>" required>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Estimated Duration</label>
            <input type="text" name="estimated_duration" class="form-control" value="<?= e($route['estimated_duration']) ?>" required>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Duration (Minutes)</label>
            <input type="number" name="duration_minutes" class="form-control" value="<?= e($route['duration_minutes']) ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Boarding Points (One per line)</label>
            <textarea name="boarding_points" class="form-control" rows="4"><?= e($boardingText) ?></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Dropping Points (One per line)</label>
            <textarea name="dropping_points" class="form-control" rows="4"><?= e($droppingText) ?></textarea>
          </div>

          <div class="col-12">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" id="routeActiveSwitch" value="1" <?= ($route['is_active'] == 1) ? 'checked' : '' ?>>
              <label class="form-check-label fw-bold" for="routeActiveSwitch">Route Active & Searchable</label>
            </div>
          </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
          <button type="submit" class="btn btn-st-primary px-4">Save Changes</button>
          <a href="<?= $adminUrl ?>/routes/index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
