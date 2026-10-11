<?php
/**
 * Admin - Add / Generate Bus Schedules
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

// Fetch buses & routes
$buses = $db->query("SELECT id, bus_name, bus_number, bus_type FROM buses WHERE is_active = 1 ORDER BY bus_name ASC")->fetchAll();
$routes = $db->query("SELECT r.id, r.duration_minutes, fc.name as from_city, tc.name as to_city 
                      FROM routes r 
                      JOIN indian_cities fc ON r.from_city_id = fc.id 
                      JOIN indian_cities tc ON r.to_city_id = tc.id 
                      WHERE r.is_active = 1 
                      ORDER BY fc.name ASC")->fetchAll();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $busId = (int)($_POST['bus_id'] ?? 0);
    $routeId = (int)($_POST['route_id'] ?? 0);
    $startDate = cleanInput($_POST['start_date'] ?? date('Y-m-d'));
    $repeatDays = (int)($_POST['repeat_days'] ?? 1);
    $depTime = cleanInput($_POST['departure_time'] ?? '21:00:00');
    $baseFare = (float)($_POST['base_fare'] ?? 750);
    $sleeperFare = !empty($_POST['sleeper_fare']) ? (float)$_POST['sleeper_fare'] : null;

    if ($busId <= 0 || $routeId <= 0) {
        $errorMessage = 'Please select a valid bus coach and route.';
    } else {
        // Fetch route duration
        $rStmt = $db->prepare("SELECT duration_minutes FROM routes WHERE id = ?");
        $rStmt->execute([$routeId]);
        $durationMin = (int)$rStmt->fetchColumn();

        $schedStmt = $db->prepare("INSERT INTO schedules (bus_id, route_id, departure_date, departure_time, arrival_date, arrival_time, base_fare, sleeper_fare, status, created_at)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())");

        $createdCount = 0;
        $startDt = new DateTime($startDate);

        for ($i = 0; $i < $repeatDays; $i++) {
            $curDate = clone $startDt;
            $curDate->modify("+{$i} days");
            $curDateStr = $curDate->format('Y-m-d');

            // Calculate arrival datetime
            $depDt = new DateTime("{$curDateStr} {$depTime}");
            $arrDt = clone $depDt;
            $arrDt->modify("+{$durationMin} minutes");

            $arrDateStr = $arrDt->format('Y-m-d');
            $arrTimeStr = $arrDt->format('H:i:s');

            // Conflict check for same bus on same date & overlapping time
            $chkConflict = $db->prepare("SELECT id FROM schedules WHERE bus_id = ? AND departure_date = ? AND departure_time = ? LIMIT 1");
            $chkConflict->execute([$busId, $curDateStr, $depTime]);
            if (!$chkConflict->fetch()) {
                $schedStmt->execute([$busId, $routeId, $curDateStr, $depTime, $arrDateStr, $arrTimeStr, $baseFare, $sleeperFare]);
                $createdCount++;
            }
        }

        logAdminActivity($currentAdmin['id'], 'create_schedule', "Generated {$createdCount} trips for bus ID {$busId} on route ID {$routeId}");
        header("Location: {$adminUrl}/schedules/index.php?created=1");
        exit;
    }
}

$adminPageTitle = "Add / Generate Schedules – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= $adminUrl ?>/schedules/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0">Schedule Journey Trips</h4>
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
            <label class="form-label small fw-bold">Select Bus Coach *</label>
            <select name="bus_id" class="form-select" required>
              <option value="">Select bus...</option>
              <?php foreach ($buses as $b): ?>
                <option value="<?= $b['id'] ?>"><?= e($b['bus_name']) ?> (<?= e($b['bus_number']) ?> - <?= e($b['bus_type']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Select Active Route *</label>
            <select name="route_id" class="form-select" required>
              <option value="">Select route...</option>
              <?php foreach ($routes as $r): ?>
                <option value="<?= $r['id'] ?>"><?= e($r['from_city']) ?> → <?= e($r['to_city']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Starting Journey Date *</label>
            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Repeat Schedule For (Days) *</label>
            <select name="repeat_days" class="form-select">
              <option value="1">Single Day Only</option>
              <option value="7">Next 7 Consecutive Days</option>
              <option value="14">Next 14 Days</option>
              <option value="30">Next 30 Days (Full Month)</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Departure Time (24h) *</label>
            <input type="time" name="departure_time" class="form-control" value="21:30" required>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Base Ticket Fare (₹) *</label>
            <input type="number" step="10" name="base_fare" class="form-control" value="850" required>
            <small class="text-muted">Applied to seater seats or lower berths</small>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Sleeper / Upper Berth Fare (₹) (Optional)</label>
            <input type="number" step="10" name="sleeper_fare" class="form-control" value="1150">
            <small class="text-muted">Override fare for sleeper berths</small>
          </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
          <button type="submit" class="btn btn-st-primary px-4">Generate Schedules</button>
          <a href="<?= $adminUrl ?>/schedules/index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
