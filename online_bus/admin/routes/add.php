<?php
/**
 * Admin - Add New Route
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

// Fetch all Indian cities for dropdowns
$cities = $db->query("SELECT id, name, state FROM indian_cities ORDER BY name ASC")->fetchAll();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $fromCityId = (int)($_POST['from_city_id'] ?? 0);
    $toCityId = (int)($_POST['to_city_id'] ?? 0);
    $distance = (int)($_POST['distance_km'] ?? 0);
    $duration = cleanInput($_POST['estimated_duration'] ?? '8 hrs');
    $durationMin = (int)($_POST['duration_minutes'] ?? 480);

    // Boarding & Dropping points (parsed from text area line-by-line)
    $rawBoarding = trim($_POST['boarding_points'] ?? '');
    $rawDropping = trim($_POST['dropping_points'] ?? '');

    $boardingArr = [];
    foreach (explode("\n", $rawBoarding) as $line) {
        $cleanLine = trim($line);
        if (!empty($cleanLine)) {
            $boardingArr[] = ['name' => $cleanLine, 'landmark' => 'Main Pick-up Point', 'time_offset' => 0];
        }
    }
    if (empty($boardingArr)) {
        $boardingArr[] = ['name' => 'Central Bus Terminal', 'landmark' => 'Near Railway Station', 'time_offset' => 0];
    }

    $droppingArr = [];
    foreach (explode("\n", $rawDropping) as $line) {
        $cleanLine = trim($line);
        if (!empty($cleanLine)) {
            $droppingArr[] = ['name' => $cleanLine, 'landmark' => 'Highway Drop Point', 'time_offset' => $durationMin];
        }
    }
    if (empty($droppingArr)) {
        $droppingArr[] = ['name' => 'Main Highway Plaza', 'landmark' => 'Opposite City Center', 'time_offset' => $durationMin];
    }

    if ($fromCityId <= 0 || $toCityId <= 0) {
        $errorMessage = 'Please select both departure and destination cities.';
    } elseif ($fromCityId === $toCityId) {
        $errorMessage = 'Source and destination cities cannot be the same.';
    } else {
        // Duplicate route check
        $chkStmt = $db->prepare("SELECT id FROM routes WHERE from_city_id = ? AND to_city_id = ? LIMIT 1");
        $chkStmt->execute([$fromCityId, $toCityId]);
        if ($chkStmt->fetch()) {
            $errorMessage = 'A route between these two cities already exists.';
        } else {
            $insStmt = $db->prepare("INSERT INTO routes (from_city_id, to_city_id, distance_km, estimated_duration, duration_minutes, boarding_points, dropping_points, is_active, created_at)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            if ($insStmt->execute([$fromCityId, $toCityId, $distance, $duration, $durationMin, json_encode($boardingArr, JSON_UNESCAPED_UNICODE), json_encode($droppingArr, JSON_UNESCAPED_UNICODE)])) {
                logAdminActivity($currentAdmin['id'], 'create_route', "Created route from city ID {$fromCityId} to {$toCityId}");
                header("Location: {$adminUrl}/routes/index.php?created=1");
                exit;
            } else {
                $errorMessage = 'Failed to create route.';
            }
        }
    }
}

$adminPageTitle = "Add Route – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= $adminUrl ?>/routes/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0">Create New Route</h4>
    </div>
  </header>

  <div class="admin-content-body">
    <?php if (!empty($errorMessage)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i> <?= e($errorMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="admin-table-card p-4">
      <form method="POST">
        <?= getCsrfField() ?>

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Source City (Origin) *</label>
            <select name="from_city_id" class="form-select" required>
              <option value="">Select departure city...</option>
              <?php foreach ($cities as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['state']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Destination City *</label>
            <select name="to_city_id" class="form-select" required>
              <option value="">Select destination city...</option>
              <?php foreach ($cities as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['state']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Total Distance (km) *</label>
            <input type="number" name="distance_km" class="form-control" placeholder="e.g. 525" required min="1" value="525">
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Duration (Display) *</label>
            <input type="text" name="estimated_duration" class="form-control" placeholder="e.g. 8 hrs 30 mins" required value="8 hrs 30 mins">
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Duration (Minutes) *</label>
            <input type="number" name="duration_minutes" class="form-control" placeholder="e.g. 510" required min="10" value="510">
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Boarding Points (One per line)</label>
            <textarea name="boarding_points" class="form-control" rows="4" placeholder="Paldi Cross Roads&#10;Geeta Mandir Stand&#10;CTM Toll Plaza">Paldi Cross Roads (Shreenathji Travels Office)
Geeta Mandir ST Stand Gate No 2
CTM Express Toll Plaza</textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Dropping Points (One per line)</label>
            <textarea name="dropping_points" class="form-control" rows="4" placeholder="Borivali East&#10;Andheri East&#10;Sion Circle">Borivali East National Park
Andheri East Gundavali Metro
Sion Circle Cinema</textarea>
          </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
          <button type="submit" class="btn btn-st-primary px-4">Create Route</button>
          <a href="<?= $adminUrl ?>/routes/index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
