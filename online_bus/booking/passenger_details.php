<?php
/**
 * Passenger Details Entry Page
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

cleanExpiredSeatLocks($db);

$scheduleId = (int)($_GET['schedule_id'] ?? 0);
$sessionId = session_id();

// Verify active seat lock from DB
$lockStmt = $db->prepare("SELECT sl.seat_number, UNIX_TIMESTAMP(sl.expires_at) as expires_ts,
                                 bs.seat_type, bs.deck, bs.price_extra
                          FROM seat_locks sl
                          JOIN schedules s ON sl.schedule_id = s.id
                          LEFT JOIN bus_seats bs ON s.bus_id = bs.bus_id AND sl.seat_number = bs.seat_number
                          WHERE sl.schedule_id = ? AND sl.session_id = ? AND sl.expires_at > NOW()");
$lockStmt->execute([$scheduleId, $sessionId]);
$lockedSeats = $lockStmt->fetchAll();

if (empty($lockedSeats)) {
    // No active lock or expired
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8"><title>Seat Lock Expired – Shreenathji Travels</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
      <div class="card p-5 text-center shadow-sm border-0 rounded-4" style="max-width: 500px;">
        <div class="text-warning fs-1 mb-3">⏰</div>
        <h3 class="fw-bold">Seat Lock Expired</h3>
        <p class="text-muted">Your 10-minute temporary seat hold has expired or no seats were selected. Please select your seats again to continue.</p>
        <a href="<?= $baseUrl ?>/booking/select_seats.php?schedule_id=<?= $scheduleId ?>" class="btn btn-st-gold">Choose Seats Again</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$expiresAtTimestamp = $lockedSeats[0]['expires_ts'];

// Fetch Schedule & Bus Info
$schedStmt = $db->prepare("SELECT s.*, 
                                  b.bus_name, b.bus_type, b.bus_number,
                                  r.estimated_duration, r.boarding_points, r.dropping_points,
                                  fc.name as from_city, tc.name as to_city
                           FROM schedules s
                           JOIN buses b ON s.bus_id = b.id
                           JOIN routes r ON s.route_id = r.id
                           JOIN indian_cities fc ON r.from_city_id = fc.id
                           JOIN indian_cities tc ON r.to_city_id = tc.id
                           WHERE s.id = ? LIMIT 1");
$schedStmt->execute([$scheduleId]);
$schedule = $schedStmt->fetch();

// Calculate Fares
$baseTotal = 0;
$seatList = [];
foreach ($lockedSeats as $ls) {
    $sNum = $ls['seat_number'];
    $seatList[] = $sNum;
    $fare = (float)$schedule['base_fare'] + (float)$ls['price_extra'];
    if ($ls['seat_type'] === 'sleeper' && !empty($schedule['sleeper_fare'])) {
        $fare = (float)$schedule['sleeper_fare'] + (float)$ls['price_extra'];
    }
    $baseTotal += $fare;
}

$taxRate = (float)($appConfig['tax_rate'] ?? 0.05);
$taxAmount = round($baseTotal * $taxRate, 2);
$convFee = (float)($appConfig['convenience_fee'] ?? 25.00);
$totalPayable = $baseTotal + $taxAmount + $convFee;

// User pre-fill if logged in
$currentUser = getCurrentUser();
$defaultName = $currentUser['full_name'] ?? '';
$defaultEmail = $currentUser['email'] ?? '';
$defaultMobile = $currentUser['mobile'] ?? '';

$errorMessage = '';

// Handle POST Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $contactName = cleanInput($_POST['contact_name'] ?? '');
    $contactEmail = cleanInput($_POST['contact_email'] ?? '');
    $contactMobile = cleanInput($_POST['contact_mobile'] ?? '');
    $boardingPoint = cleanInput($_POST['boarding_point'] ?? '');
    $droppingPoint = cleanInput($_POST['dropping_point'] ?? '');

    $passengers = $_POST['passengers'] ?? [];

    if (empty($contactName) || empty($contactEmail) || empty($contactMobile)) {
        $errorMessage = 'Please provide contact name, email, and mobile number.';
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please provide a valid email address.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $contactMobile)) {
        $errorMessage = 'Please enter a valid 10-digit Indian mobile number.';
    } elseif (count($passengers) !== count($lockedSeats)) {
        $errorMessage = 'Passenger information is required for every selected seat.';
    } else {
        // Validate each passenger
        $validatedPassengers = [];
        foreach ($seatList as $idx => $sNum) {
            $pData = $passengers[$sNum] ?? null;
            if (!$pData || empty(trim($pData['name'])) || empty($pData['age']) || empty($pData['gender'])) {
                $errorMessage = "Please complete all fields for Seat {$sNum}.";
                break;
            }
            $age = (int)$pData['age'];
            if ($age < 1 || $age > 120) {
                $errorMessage = "Invalid age specified for Seat {$sNum}.";
                break;
            }
            $validatedPassengers[] = [
                'seat_number' => $sNum,
                'name' => cleanInput($pData['name']),
                'age' => $age,
                'gender' => in_array($pData['gender'], ['male', 'female', 'other'], true) ? $pData['gender'] : 'male',
                'fare' => $baseTotal / count($seatList)
            ];
        }

        if (empty($errorMessage)) {
            // Re-verify lock not expired
            $lockRecheck = $db->prepare("SELECT COUNT(*) FROM seat_locks WHERE schedule_id = ? AND session_id = ? AND expires_at > NOW()");
            $lockRecheck->execute([$scheduleId, $sessionId]);
            if ($lockRecheck->fetchColumn() < count($seatList)) {
                $errorMessage = 'Your seat lock has expired. Please select seats again.';
            } else {
                try {
                    $db->beginTransaction();
                    $pnr = generatePNR();
                    $userId = $_SESSION['user_id'] ?? null;

                    $bStmt = $db->prepare("INSERT INTO bookings (pnr, user_id, schedule_id, journey_date, contact_name, contact_email, contact_mobile, boarding_point, dropping_point, total_seats, base_amount, tax_amount, convenience_fee, total_amount, payment_status, booking_status, created_at)
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending_payment', NOW())");
                    $bStmt->execute([
                        $pnr,
                        $userId,
                        $scheduleId,
                        $schedule['departure_date'],
                        $contactName,
                        $contactEmail,
                        $contactMobile,
                        $boardingPoint ?: 'Default Boarding Stop',
                        $droppingPoint ?: 'Default Dropping Stop',
                        count($seatList),
                        $baseTotal,
                        $taxAmount,
                        $convFee,
                        $totalPayable
                    ]);
                    $bookingId = $db->lastInsertId();

                    // Insert Passengers
                    $pStmt = $db->prepare("INSERT INTO booking_passengers (booking_id, seat_number, passenger_name, age, gender, seat_fare)
                                           VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($validatedPassengers as $vp) {
                        $pStmt->execute([$bookingId, $vp['seat_number'], $vp['name'], $vp['age'], $vp['gender'], $vp['fare']]);
                    }

                    $db->commit();

                    // Redirect to payment page
                    header("Location: {$baseUrl}/booking/payment.php?pnr=" . urlencode($pnr));
                    exit;

                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log("Booking creation error: " . $e->getMessage());
                    $errorMessage = 'Failed to create booking. Please try again.';
                }
            }
        }
    }
}

$pageTitle = "Passenger Details – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <!-- Seat Lock Countdown Warning Banner -->
  <div class="seat-lock-countdown-banner">
    <i class="fas fa-stopwatch fs-5"></i>
    <span>Seats <strong><?= implode(', ', $seatList) ?></strong> are temporarily locked for you. Time remaining:</span>
    <span class="badge bg-dark text-warning fs-6" id="lock-countdown-timer">--:--</span>
  </div>

  <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i> <?= e($errorMessage) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <form method="POST" id="passenger-form">
    <?= getCsrfField() ?>
    <input type="hidden" name="boarding_point" id="hidden_boarding_point" value="">
    <input type="hidden" name="dropping_point" id="hidden_dropping_point" value="">

    <div class="row g-4">
      <!-- Left: Passenger and Contact Details -->
      <div class="col-lg-8">
        <!-- Trip Brief Summary Card -->
        <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1"><?= e($schedule['bus_type']) ?></span>
              <h5 class="fw-bold mb-0"><?= e($schedule['from_city']) ?> <i class="fas fa-arrow-right text-warning small mx-1"></i> <?= e($schedule['to_city']) ?></h5>
              <div class="text-muted small mt-1">
                <?= formatDateIndian($schedule['departure_date']) ?> • Departure: <?= formatTime($schedule['departure_time']) ?>
              </div>
            </div>
            <div class="text-end">
              <span class="text-muted small d-block">Selected Seats</span>
              <span class="fw-bold text-primary fs-5"><?= implode(', ', $seatList) ?></span>
            </div>
          </div>
        </div>

        <!-- Dynamic Passenger Information Forms for Each Seat -->
        <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-users text-primary me-2"></i>Passenger Information</h5>
          <p class="text-muted small mb-4">Please enter names exactly as shown on government photo identification.</p>

          <?php foreach ($seatList as $idx => $sNum): ?>
            <div class="p-3 mb-3 bg-light rounded-3 border">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0">
                  <span class="badge bg-primary me-2">Passenger <?= $idx + 1 ?></span> Seat: <span class="text-primary fw-bold"><?= e($sNum) ?></span>
                </h6>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Full Name *</label>
                  <input type="text" class="form-control" name="passengers[<?= e($sNum) ?>][name]" placeholder="e.g. Rahul Sharma" required value="<?= ($idx === 0) ? e($defaultName) : '' ?>">
                </div>

                <div class="col-md-3">
                  <label class="form-label small fw-bold text-muted">Age (Years) *</label>
                  <input type="number" class="form-control" name="passengers[<?= e($sNum) ?>][age]" placeholder="Age" min="1" max="120" required value="28">
                </div>

                <div class="col-md-3">
                  <label class="form-label small fw-bold text-muted">Gender *</label>
                  <select class="form-select" name="passengers[<?= e($sNum) ?>][gender]" required>
                    <option value="male" selected>Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Contact Information Card -->
        <div class="bg-white p-4 rounded-4 border shadow-sm">
          <h5 class="fw-bold mb-3"><i class="fas fa-address-book text-primary me-2"></i>Contact Details</h5>
          <p class="text-muted small mb-3">Your booking confirmation and e-ticket will be sent here.</p>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Contact Person Name *</label>
              <input type="text" class="form-control" name="contact_name" value="<?= e($defaultName) ?>" placeholder="Full Name" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Email Address *</label>
              <input type="email" class="form-control" name="contact_email" value="<?= e($defaultEmail) ?>" placeholder="email@domain.com" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Mobile Number (India) *</label>
              <div class="input-group">
                <span class="input-group-text bg-light">+91</span>
                <input type="tel" class="form-control" name="contact_mobile" value="<?= e($defaultMobile) ?>" placeholder="10-digit mobile number" pattern="[6-9][0-9]{9}" required>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Fare Breakdown & Checkout Button -->
      <div class="col-lg-4">
        <div class="bg-white p-4 rounded-4 border shadow-sm sticky-top" style="top: 90px;">
          <h5 class="fw-bold mb-3"><i class="fas fa-receipt text-primary me-2"></i>Fare Summary</h5>

          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">Base Fare (<?= count($seatList) ?> Seats)</span>
            <span class="fw-semibold"><?= formatPrice($baseTotal) ?></span>
          </div>

          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">GST (5%)</span>
            <span class="fw-semibold"><?= formatPrice($taxAmount) ?></span>
          </div>

          <div class="d-flex justify-content-between mb-3">
            <span class="text-secondary">Convenience Fee</span>
            <span class="fw-semibold"><?= formatPrice($convFee) ?></span>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="fs-5 fw-bold text-dark">Total Payable</span>
            <span class="fs-4 fw-bold text-primary"><?= formatPrice($totalPayable) ?></span>
          </div>

          <button type="submit" class="btn btn-st-gold btn-lg w-100 justify-content-center">
            Proceed to Payment <i class="fas fa-lock ms-2"></i>
          </button>

          <div class="text-center mt-3">
            <span class="badge bg-success-subtle text-success p-2 small">
              <i class="fas fa-shield-alt me-1"></i> Razorpay Test Gateway Secured
            </span>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Sync Boarding & Dropping Points from sessionStorage
  const bp = sessionStorage.getItem('st_boarding_point') || '';
  const dp = sessionStorage.getItem('st_dropping_point') || '';
  document.getElementById('hidden_boarding_point').value = bp;
  document.getElementById('hidden_dropping_point').value = dp;

  // Seat Lock Countdown
  const expiresAt = <?= (int)$expiresAtTimestamp ?>;
  const timerEl = document.getElementById('lock-countdown-timer');

  function updateTimer() {
    const now = Math.floor(Date.now() / 1000);
    const diff = expiresAt - now;

    if (diff <= 0) {
      if (timerEl) timerEl.textContent = 'EXPIRED';
      alert('Your temporary seat lock has expired. Please select seats again.');
      window.location.href = "<?= $baseUrl ?>/booking/select_seats.php?schedule_id=<?= $scheduleId ?>";
      return;
    }

    const m = Math.floor(diff / 60);
    const s = diff % 60;
    if (timerEl) {
      timerEl.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
  }

  updateTimer();
  setInterval(updateTimer, 1000);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
