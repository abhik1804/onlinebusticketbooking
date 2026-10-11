<?php
/**
 * Interactive Seat Selection Page
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

cleanExpiredSeatLocks($db);

$scheduleId = (int)($_GET['schedule_id'] ?? 0);
if ($scheduleId <= 0) {
    header("Location: {$baseUrl}/index.php");
    exit;
}

// Fetch schedule, route and bus details
$stmt = $db->prepare("SELECT s.*, 
                             b.id as bus_id, b.bus_name, b.bus_number, b.operator_name, b.bus_type, b.seating_layout, b.total_seats,
                             r.distance_km, r.estimated_duration, r.boarding_points, r.dropping_points,
                             fc.name as from_city, tc.name as to_city
                      FROM schedules s
                      JOIN buses b ON s.bus_id = b.id
                      JOIN routes r ON s.route_id = r.id
                      JOIN indian_cities fc ON r.from_city_id = fc.id
                      JOIN indian_cities tc ON r.to_city_id = tc.id
                      WHERE s.id = ? AND s.status = 'scheduled' LIMIT 1");
$stmt->execute([$scheduleId]);
$schedule = $stmt->fetch();

if (!$schedule) {
    die("Selected bus schedule is not available. <a href='{$baseUrl}/index.php'>Return to Homepage</a>");
}

// Parse Boarding & Dropping Points
$boardingPoints = json_decode($schedule['boarding_points'] ?? '[]', true) ?: [];
$droppingPoints = json_decode($schedule['dropping_points'] ?? '[]', true) ?: [];

// Fetch Bus Seats Layout
$seatsStmt = $db->prepare("SELECT * FROM bus_seats WHERE bus_id = ? ORDER BY deck ASC, row_num ASC, col_num ASC");
$seatsStmt->execute([$schedule['bus_id']]);
$allSeats = $seatsStmt->fetchAll();

// Group seats by deck and row
$decks = [
    'lower' => [],
    'upper' => []
];

foreach ($allSeats as $st) {
    $d = $st['deck'];
    $r = $st['row_num'];
    if (!isset($decks[$d][$r])) {
        $decks[$d][$r] = [];
    }
    $decks[$d][$r][] = $st;
}

// Fetch currently booked seat numbers
$bStmt = $db->prepare("SELECT bp.seat_number 
                       FROM booking_passengers bp 
                       JOIN bookings b ON bp.booking_id = b.id 
                       WHERE b.schedule_id = ? AND b.booking_status IN ('confirmed', 'pending_payment')");
$bStmt->execute([$scheduleId]);
$bookedSeatNumbers = $bStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch admin blocked seats
$blkStmt = $db->prepare("SELECT seat_number FROM blocked_seats WHERE schedule_id = ?");
$blkStmt->execute([$scheduleId]);
$blockedSeatNumbers = $blkStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch locked seats by other sessions
$sessionId = session_id();
$lockStmt = $db->prepare("SELECT seat_number FROM seat_locks WHERE schedule_id = ? AND session_id != ? AND expires_at > NOW()");
$lockStmt->execute([$scheduleId, $sessionId]);
$lockedByOtherSeatNumbers = $lockStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Select Seats – " . e($schedule['bus_name']) . " (" . e($schedule['from_city']) . " to " . e($schedule['to_city']) . ")";
$includeSeatStyles = true;
$includeSeatScript = true;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Breadcrumb / Header Strip -->
<div class="py-3 bg-white border-bottom">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <a href="<?= $baseUrl ?>/buses/search.php?from=<?= urlencode($schedule['from_city']) ?>&to=<?= urlencode($schedule['to_city']) ?>&date=<?= $schedule['departure_date'] ?>" class="text-secondary small fw-bold">
        <i class="fas fa-arrow-left me-1"></i> Back to Buses
      </a>
      <h4 class="fw-bold mb-0 mt-1">
        <?= e($schedule['from_city']) ?> <i class="fas fa-arrow-right text-warning small mx-1"></i> <?= e($schedule['to_city']) ?>
      </h4>
      <div class="text-muted small">
        <?= formatDateIndian($schedule['departure_date']) ?> • Dep: <?= formatTime($schedule['departure_time']) ?> • <?= e($schedule['bus_name']) ?>
      </div>
    </div>

    <div>
      <span class="badge bg-primary-subtle text-primary border border-primary-subtle p-2">
        <i class="fas fa-bus me-1"></i> <?= e($schedule['bus_type']) ?>
      </span>
    </div>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4" id="bus-seat-map-wrapper">
    <!-- Left Column: Bus Seat Layout -->
    <div class="col-lg-8">
      <div class="bus-seat-layout-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="fw-bold mb-0">Select Your Seats</h5>
            <small class="text-muted">Click on available seats to select (Max 6 seats)</small>
          </div>

          <!-- Deck Switcher Pills (If upper deck exists) -->
          <?php if (!empty($decks['upper'])): ?>
            <div class="deck-nav-pills">
              <button type="button" class="deck-pill-btn active" data-deck="lower">
                <i class="fas fa-layer-group me-1"></i> Lower Deck
              </button>
              <button type="button" class="deck-pill-btn" data-deck="upper">
                <i class="fas fa-layer-group me-1"></i> Upper Deck
              </button>
            </div>
          <?php endif; ?>
        </div>

        <!-- Bus Cabin Chassis Outline -->
        <div class="bus-cabin-chassis">
          <!-- Front Cockpit Area with Driver Wheel -->
          <div class="bus-front-cockpit">
            <span class="steering-wheel-badge">
              <i class="fas fa-dharmachakra"></i> FRONT / DRIVER CABIN
            </span>
          </div>

          <!-- LOWER DECK GRID -->
          <div class="seats-deck-container active" data-deck="lower">
            <div class="seats-grid-table">
              <?php foreach ($decks['lower'] as $rowNum => $rowSeats): ?>
                <div class="seat-row">
                  <?php 
                    // Layout organization based on bus type
                    // For sleeper 2x1: Col 1 is single left, Col 2 & 3 is double right
                    $col1 = null;
                    $colRight = [];
                    foreach ($rowSeats as $s) {
                        if ($s['col_num'] == 1) {
                            $col1 = $s;
                        } else {
                            $colRight[] = $s;
                        }
                    }
                  ?>

                  <!-- Left Col / Berth -->
                  <div class="seat-col-pair">
                    <?php if ($col1): ?>
                      <?php 
                        $sNum = $col1['seat_number'];
                        $isBooked = in_array($sNum, $bookedSeatNumbers);
                        $isBlocked = in_array($sNum, $blockedSeatNumbers);
                        $isLocked = in_array($sNum, $lockedByOtherSeatNumbers);
                        $isUnavailable = ($isBooked || $isBlocked || $isLocked);
                        $statusClass = $isUnavailable ? ($isBlocked ? 'status-blocked' : 'status-booked') : 'status-available';
                        
                        $seatFare = (float)$schedule['base_fare'] + (float)$col1['price_extra'];
                        if ($col1['seat_type'] === 'sleeper' && !empty($schedule['sleeper_fare'])) {
                            $seatFare = (float)$schedule['sleeper_fare'] + (float)$col1['price_extra'];
                        }
                      ?>
                      <div class="bus-seat-unit type-<?= $col1['seat_type'] ?> <?= $statusClass ?> <?= $col1['is_ladies_preferred'] ? 'is-ladies' : '' ?>"
                           data-seat="<?= e($sNum) ?>"
                           data-fare="<?= $seatFare ?>"
                           data-type="<?= $col1['seat_type'] ?>"
                           data-deck="lower"
                           data-ladies="<?= $col1['is_ladies_preferred'] ?>"
                           title="Seat <?= e($sNum) ?> - <?= formatPrice($seatFare) ?>">
                        <span><?= e($sNum) ?></span>
                        <?php if ($col1['is_ladies_preferred']): ?>
                          <span class="ladies-pin" title="Ladies Preferred"></span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Aisle Gangway -->
                  <div class="seat-aisle-gap">Aisle</div>

                  <!-- Right Side Seats / Berths -->
                  <div class="seat-col-pair">
                    <?php foreach ($colRight as $sRight): ?>
                      <?php 
                        $sNum = $sRight['seat_number'];
                        $isBooked = in_array($sNum, $bookedSeatNumbers);
                        $isBlocked = in_array($sNum, $blockedSeatNumbers);
                        $isLocked = in_array($sNum, $lockedByOtherSeatNumbers);
                        $isUnavailable = ($isBooked || $isBlocked || $isLocked);
                        $statusClass = $isUnavailable ? ($isBlocked ? 'status-blocked' : 'status-booked') : 'status-available';
                        
                        $seatFare = (float)$schedule['base_fare'] + (float)$sRight['price_extra'];
                        if ($sRight['seat_type'] === 'sleeper' && !empty($schedule['sleeper_fare'])) {
                            $seatFare = (float)$schedule['sleeper_fare'] + (float)$sRight['price_extra'];
                        }
                      ?>
                      <div class="bus-seat-unit type-<?= $sRight['seat_type'] ?> <?= $statusClass ?> <?= $sRight['is_ladies_preferred'] ? 'is-ladies' : '' ?>"
                           data-seat="<?= e($sNum) ?>"
                           data-fare="<?= $seatFare ?>"
                           data-type="<?= $sRight['seat_type'] ?>"
                           data-deck="lower"
                           data-ladies="<?= $sRight['is_ladies_preferred'] ?>"
                           title="Seat <?= e($sNum) ?> - <?= formatPrice($seatFare) ?>">
                        <span><?= e($sNum) ?></span>
                        <?php if ($sRight['is_ladies_preferred']): ?>
                          <span class="ladies-pin" title="Ladies Preferred"></span>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- UPPER DECK GRID (If exists) -->
          <?php if (!empty($decks['upper'])): ?>
            <div class="seats-deck-container" data-deck="upper">
              <div class="seats-grid-table">
                <?php foreach ($decks['upper'] as $rowNum => $rowSeats): ?>
                  <div class="seat-row">
                    <?php 
                      $col1 = null;
                      $colRight = [];
                      foreach ($rowSeats as $s) {
                          if ($s['col_num'] == 1) {
                              $col1 = $s;
                          } else {
                              $colRight[] = $s;
                          }
                      }
                    ?>
                    <div class="seat-col-pair">
                      <?php if ($col1): ?>
                        <?php 
                          $sNum = $col1['seat_number'];
                          $isBooked = in_array($sNum, $bookedSeatNumbers);
                          $isBlocked = in_array($sNum, $blockedSeatNumbers);
                          $isLocked = in_array($sNum, $lockedByOtherSeatNumbers);
                          $isUnavailable = ($isBooked || $isBlocked || $isLocked);
                          $statusClass = $isUnavailable ? ($isBlocked ? 'status-blocked' : 'status-booked') : 'status-available';
                          
                          $seatFare = (float)$schedule['base_fare'] + (float)$col1['price_extra'];
                          if (!empty($schedule['sleeper_fare'])) {
                              $seatFare = (float)$schedule['sleeper_fare'] + (float)$col1['price_extra'];
                          }
                        ?>
                        <div class="bus-seat-unit type-<?= $col1['seat_type'] ?> <?= $statusClass ?> <?= $col1['is_ladies_preferred'] ? 'is-ladies' : '' ?>"
                             data-seat="<?= e($sNum) ?>"
                             data-fare="<?= $seatFare ?>"
                             data-type="<?= $col1['seat_type'] ?>"
                             data-deck="upper"
                             data-ladies="<?= $col1['is_ladies_preferred'] ?>"
                             title="Seat <?= e($sNum) ?> - <?= formatPrice($seatFare) ?>">
                          <span><?= e($sNum) ?></span>
                          <?php if ($col1['is_ladies_preferred']): ?>
                            <span class="ladies-pin"></span>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </div>

                    <div class="seat-aisle-gap">Aisle</div>

                    <div class="seat-col-pair">
                      <?php foreach ($colRight as $sRight): ?>
                        <?php 
                          $sNum = $sRight['seat_number'];
                          $isBooked = in_array($sNum, $bookedSeatNumbers);
                          $isBlocked = in_array($sNum, $blockedSeatNumbers);
                          $isLocked = in_array($sNum, $lockedByOtherSeatNumbers);
                          $isUnavailable = ($isBooked || $isBlocked || $isLocked);
                          $statusClass = $isUnavailable ? ($isBlocked ? 'status-blocked' : 'status-booked') : 'status-available';
                          
                          $seatFare = (float)$schedule['base_fare'] + (float)$sRight['price_extra'];
                          if (!empty($schedule['sleeper_fare'])) {
                              $seatFare = (float)$schedule['sleeper_fare'] + (float)$sRight['price_extra'];
                          }
                        ?>
                        <div class="bus-seat-unit type-<?= $sRight['seat_type'] ?> <?= $statusClass ?> <?= $sRight['is_ladies_preferred'] ? 'is-ladies' : '' ?>"
                             data-seat="<?= e($sNum) ?>"
                             data-fare="<?= $seatFare ?>"
                             data-type="<?= $sRight['seat_type'] ?>"
                             data-deck="upper"
                             data-ladies="<?= $sRight['is_ladies_preferred'] ?>"
                             title="Seat <?= e($sNum) ?> - <?= formatPrice($seatFare) ?>">
                          <span><?= e($sNum) ?></span>
                          <?php if ($sRight['is_ladies_preferred']): ?>
                            <span class="ladies-pin"></span>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Seat Color Legend -->
        <div class="seat-legend-box">
          <div class="legend-item">
            <span class="legend-swatch swatch-available"></span> Available
          </div>
          <div class="legend-item">
            <span class="legend-swatch swatch-selected"></span> Selected
          </div>
          <div class="legend-item">
            <span class="legend-swatch swatch-booked"></span> Booked / Locked
          </div>
          <div class="legend-item">
            <span class="legend-swatch swatch-ladies"></span> Ladies Reserved
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Booking Points & Proceed Summary -->
    <div class="col-lg-4">
      <div class="bg-white p-4 rounded-4 border shadow-sm sticky-top" style="top: 90px;">
        <h5 class="fw-bold mb-3"><i class="fas fa-map-marked-alt text-primary me-2"></i>Boarding & Dropping</h5>

        <!-- Boarding Point Selection -->
        <div class="mb-3">
          <label class="form-label small fw-bold text-muted text-uppercase">Select Boarding Point</label>
          <select class="form-select" id="selected-boarding-point" required>
            <?php foreach ($boardingPoints as $bp): ?>
              <option value="<?= e($bp['name']) ?> (<?= e($bp['landmark'] ?? '') ?>)">
                <?= e($bp['name']) ?> – <?= e($bp['landmark'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Dropping Point Selection -->
        <div class="mb-4">
          <label class="form-label small fw-bold text-muted text-uppercase">Select Dropping Point</label>
          <select class="form-select" id="selected-dropping-point" required>
            <?php foreach ($droppingPoints as $dp): ?>
              <option value="<?= e($dp['name']) ?> (<?= e($dp['landmark'] ?? '') ?>)">
                <?= e($dp['name']) ?> – <?= e($dp['landmark'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Selected Seats Drawer / Summary -->
        <div id="seat-summary-drawer" class="seat-summary-drawer flex-column align-items-stretch" style="display: none;">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-white-50">Seats Selected (<span id="selected-seats-count">0</span>)</span>
            <span class="seats-list" id="selected-seats-list">-</span>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="small text-white-50">Base Fare Total</span>
            <span class="total-fare" id="selected-seats-total">₹0.00</span>
          </div>

          <button type="button" class="btn btn-st-gold w-100 justify-content-center" id="btn-proceed-passengers" data-schedule-id="<?= $schedule['id'] ?>">
            Proceed to Passenger Details <i class="fas fa-arrow-right ms-2"></i>
          </button>
        </div>

        <div class="mt-4 p-3 bg-light rounded-3 text-secondary small">
          <div class="d-flex gap-2">
            <i class="fas fa-info-circle text-primary mt-1"></i>
            <div>
              <strong>Seat Locking Notice:</strong> Once you click Proceed, your selected seats will be temporarily held for 10 minutes to prevent double booking while you complete passenger details.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Save chosen boarding & dropping points in sessionStorage on proceed
  document.getElementById('btn-proceed-passengers')?.addEventListener('click', function() {
    const bp = document.getElementById('selected-boarding-point')?.value;
    const dp = document.getElementById('selected-dropping-point')?.value;
    if (bp) sessionStorage.setItem('st_boarding_point', bp);
    if (dp) sessionStorage.setItem('st_dropping_point', dp);
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
