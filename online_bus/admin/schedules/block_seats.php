<?php
/**
 * Admin - Manage Blocked / VIP Seats for a Schedule
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdminLogin();
$appConfig = require __DIR__ . '/../../config/app.php';
$adminUrl = $appConfig['admin_url'];
$db = getDB();

$scheduleId = (int)($_GET['id'] ?? 0);
if ($scheduleId <= 0) {
    header("Location: {$adminUrl}/schedules/index.php");
    exit;
}

$stmt = $db->prepare("SELECT s.*, b.bus_name, b.bus_number, fc.name as from_city, tc.name as to_city, b.id as bus_id
                      FROM schedules s
                      JOIN buses b ON s.bus_id = b.id
                      JOIN routes r ON s.route_id = r.id
                      JOIN indian_cities fc ON r.from_city_id = fc.id
                      JOIN indian_cities tc ON r.to_city_id = tc.id
                      WHERE s.id = ? LIMIT 1");
$stmt->execute([$scheduleId]);
$schedule = $stmt->fetch();

if (!$schedule) {
    die("Schedule not found.");
}

// Handle Block Seat Form Submission
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_block'])) {
    requireCsrfToken();
    $seatToBlock = cleanInput($_POST['seat_number'] ?? '');
    $reason = cleanInput($_POST['reason'] ?? 'Maintenance / VIP');

    if (!empty($seatToBlock)) {
        try {
            $ins = $db->prepare("INSERT INTO blocked_seats (schedule_id, seat_number, reason, created_at) VALUES (?, ?, ?, NOW())");
            $ins->execute([$scheduleId, $seatToBlock, $reason]);
            $msg = "Seat {$seatToBlock} blocked successfully.";
        } catch (Exception $e) {
            $msg = "Seat {$seatToBlock} is already blocked.";
        }
    }
}

// Handle Unblock Seat
if (isset($_GET['unblock_id'])) {
    $unblockId = (int)$_GET['unblock_id'];
    $db->prepare("DELETE FROM blocked_seats WHERE id = ? AND schedule_id = ?")->execute([$unblockId, $scheduleId]);
    header("Location: {$adminUrl}/schedules/block_seats.php?id={$scheduleId}&unblocked=1");
    exit;
}

// Fetch configured seats for this bus
$allSeats = $db->prepare("SELECT seat_number FROM bus_seats WHERE bus_id = ? ORDER BY seat_number ASC");
$allSeats->execute([$schedule['bus_id']]);
$busSeatsList = $allSeats->fetchAll(PDO::FETCH_COLUMN);

// Fetch current blocked seats
$blockedStmt = $db->prepare("SELECT * FROM blocked_seats WHERE schedule_id = ? ORDER BY seat_number ASC");
$blockedStmt->execute([$scheduleId]);
$blockedSeats = $blockedStmt->fetchAll();

$adminPageTitle = "Manage Blocked Seats – Schedule #{$scheduleId}";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= $adminUrl ?>/schedules/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0">Seat Hold & Maintenance Management</h4>
    </div>
  </header>

  <div class="admin-content-body">
    <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
      <h5 class="fw-bold mb-1"><?= e($schedule['from_city']) ?> → <?= e($schedule['to_city']) ?></h5>
      <p class="text-muted small mb-0">
        <?= formatDateIndian($schedule['departure_date']) ?> • Departure: <?= formatTime($schedule['departure_time']) ?> • Coach: <?= e($schedule['bus_name']) ?> (<?= e($schedule['bus_number']) ?>)
      </p>
    </div>

    <?php if (!empty($msg)): ?>
      <div class="alert alert-info py-2 small"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Left: Block New Seat -->
      <div class="col-lg-5">
        <div class="admin-table-card p-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-lock text-warning me-2"></i>Block a Seat</h5>
          <form method="POST">
            <?= getCsrfField() ?>
            <input type="hidden" name="action_block" value="1">

            <div class="mb-3">
              <label class="form-label small fw-bold">Select Seat *</label>
              <select name="seat_number" class="form-select" required>
                <option value="">Choose seat...</option>
                <?php foreach ($busSeatsList as $sNum): ?>
                  <option value="<?= e($sNum) ?>">Seat <?= e($sNum) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Hold Reason</label>
              <input type="text" name="reason" class="form-control" value="Maintenance / VIP Hold" required>
            </div>

            <button type="submit" class="btn btn-warning w-100">
              <i class="fas fa-lock me-1"></i> Block Seat
            </button>
          </form>
        </div>
      </div>

      <!-- Right: List Currently Blocked Seats -->
      <div class="col-lg-7">
        <div class="admin-table-card">
          <div class="admin-table-header">
            <h5 class="fw-bold mb-0">Currently Blocked Seats (<?= count($blockedSeats) ?>)</h5>
          </div>

          <div class="table-responsive">
            <table class="table admin-table align-middle">
              <thead>
                <tr>
                  <th>Seat</th>
                  <th>Reason</th>
                  <th>Blocked On</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($blockedSeats)): ?>
                  <tr><td colspan="4" class="text-center py-4 text-muted">No seats currently blocked. All available seats are open to passengers.</td></tr>
                <?php else: ?>
                  <?php foreach ($blockedSeats as $bs): ?>
                    <tr>
                      <td><span class="badge bg-danger fs-6"><?= e($bs['seat_number']) ?></span></td>
                      <td><?= e($bs['reason']) ?></td>
                      <td class="small text-muted"><?= date('d M Y, h:i A', strtotime($bs['created_at'])) ?></td>
                      <td class="text-end">
                        <a href="<?= $adminUrl ?>/schedules/block_seats.php?id=<?= $scheduleId ?>&unblock_id=<?= $bs['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Release block on this seat?');">
                          <i class="fas fa-unlock me-1"></i> Release
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
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
