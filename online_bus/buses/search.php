<?php
/**
 * Bus Search & Listing with Real-time Filters
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

$fromCityName = trim($_GET['from'] ?? 'Ahmedabad');
$toCityName = trim($_GET['to'] ?? 'Mumbai');
$journeyDate = trim($_GET['date'] ?? date('Y-m-d'));

// Today's date check (prevent past dates on backend)
$todayStr = date('Y-m-d');
$isPastDate = false;
if ($journeyDate < $todayStr) {
    $isPastDate = true;
}

$route = null;
$schedules = [];
$minPrice = 0;
$maxPrice = 2500;

if (!$isPastDate && !empty($fromCityName) && !empty($toCityName)) {
    // 1. Find route
    $routeStmt = $db->prepare("SELECT r.*, fc.name as from_city_name, tc.name as to_city_name 
                               FROM routes r 
                               JOIN indian_cities fc ON r.from_city_id = fc.id 
                               JOIN indian_cities tc ON r.to_city_id = tc.id 
                               WHERE (fc.name = ? OR fc.name LIKE ?) 
                                 AND (tc.name = ? OR tc.name LIKE ?) 
                                 AND r.is_active = 1 LIMIT 1");
    $routeStmt->execute([$fromCityName, $fromCityName . '%', $toCityName, $toCityName . '%']);
    $route = $routeStmt->fetch();

    if ($route) {
        // 2. Find schedules on this route on the selected date
        $schedSql = "SELECT s.*, 
                            b.bus_name, b.bus_number, b.operator_name, b.bus_type, b.seating_layout, 
                            b.total_seats, b.primary_image, b.image_type, b.amenities, b.cancellation_policy
                     FROM schedules s
                     JOIN buses b ON s.bus_id = b.id
                     WHERE s.route_id = ? 
                       AND s.departure_date = ? 
                       AND s.status = 'scheduled' 
                       AND b.is_active = 1
                     ORDER BY s.departure_time ASC";
        $schedStmt = $db->prepare($schedSql);
        $schedStmt->execute([$route['id'], $journeyDate]);
        $rawSchedules = $schedStmt->fetchAll();

        foreach ($rawSchedules as $sched) {
            $sid = $sched['id'];

            // Calculate Booked seats
            $bCountStmt = $db->prepare("SELECT COUNT(*) FROM booking_passengers bp 
                                        JOIN bookings b ON bp.booking_id = b.id 
                                        WHERE b.schedule_id = ? AND b.booking_status IN ('confirmed', 'pending_payment')");
            $bCountStmt->execute([$sid]);
            $bookedSeatsCount = (int)$bCountStmt->fetchColumn();

            // Calculate Blocked seats
            $blockCountStmt = $db->prepare("SELECT COUNT(*) FROM blocked_seats WHERE schedule_id = ?");
            $blockCountStmt->execute([$sid]);
            $blockedSeatsCount = (int)$blockCountStmt->fetchColumn();

            // Calculate Active Locked seats
            $lockCountStmt = $db->prepare("SELECT COUNT(*) FROM seat_locks WHERE schedule_id = ? AND expires_at > NOW()");
            $lockCountStmt->execute([$sid]);
            $lockedSeatsCount = (int)$lockCountStmt->fetchColumn();

            $availableSeats = max(0, $sched['total_seats'] - ($bookedSeatsCount + $blockedSeatsCount + $lockedSeatsCount));

            $sched['available_seats_count'] = $availableSeats;
            $sched['amenities_list'] = json_decode($sched['amenities'] ?? '[]', true) ?: [];
            $sched['boarding_points_list'] = json_decode($route['boarding_points'] ?? '[]', true) ?: [];
            $sched['dropping_points_list'] = json_decode($route['dropping_points'] ?? '[]', true) ?: [];

            $schedules[] = $sched;
        }
    }
}

$pageTitle = "Buses from " . e($fromCityName) . " to " . e($toCityName) . " on " . formatDateIndian($journeyDate);
$includeSearchScript = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Search Header Bar -->
<div class="py-4" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff;">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="fs-4 fw-bold"><?= e($fromCityName) ?></span>
          <i class="fas fa-arrow-right text-warning"></i>
          <span class="fs-4 fw-bold"><?= e($toCityName) ?></span>
        </div>
        <div class="text-white-50 small">
          <i class="fas fa-calendar-alt text-warning me-1"></i> <?= formatDateIndian($journeyDate) ?> 
          <?php if ($route): ?>
            • <i class="fas fa-road me-1 text-info"></i> <?= e($route['distance_km']) ?> km (Approx. <?= e($route['estimated_duration']) ?>)
          <?php endif; ?>
        </div>
      </div>

      <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#modifySearchCollapse">
        <i class="fas fa-edit me-1"></i> Modify Search
      </button>
    </div>

    <!-- Collapsible Search Bar for changing search directly -->
    <div class="collapse mt-3" id="modifySearchCollapse">
      <div class="card card-body bg-white text-dark shadow-sm border-0 p-3" style="border-radius: 14px;">
        <form action="<?= $baseUrl ?>/buses/search.php" method="GET" id="bus-search-form">
          <div class="row g-2 align-items-center">
            <div class="col-md-4">
              <div class="search-field-group">
                <input type="text" class="form-control" id="from_city_input" name="from" value="<?= e($fromCityName) ?>" placeholder="From City" required autocomplete="off">
                <input type="hidden" id="from_city_id" name="from_id" value="<?= e($_GET['from_id'] ?? '') ?>">
                <div class="city-suggestions-dropdown" id="from_suggestions"></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="search-field-group">
                <input type="text" class="form-control" id="to_city_input" name="to" value="<?= e($toCityName) ?>" placeholder="To City" required autocomplete="off">
                <input type="hidden" id="to_city_id" name="to_id" value="<?= e($_GET['to_id'] ?? '') ?>">
                <div class="city-suggestions-dropdown" id="to_suggestions"></div>
              </div>
            </div>
            <div class="col-md-2">
              <input type="date" class="form-control" id="journey_date_input" name="date" value="<?= e($journeyDate) ?>" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-st-gold w-100"><i class="fas fa-search me-1"></i> Update</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Main Search Body -->
<div class="container py-5">
  <?php if ($isPastDate): ?>
    <!-- Past Date Error -->
    <div class="text-center py-5">
      <div class="mb-3 text-danger fs-1"><i class="fas fa-calendar-times"></i></div>
      <h3 class="fw-bold">Invalid Journey Date</h3>
      <p class="text-muted">You selected a past date. Please pick today or a future journey date.</p>
      <a href="<?= $baseUrl ?>/buses/search.php?from=<?= urlencode($fromCityName) ?>&to=<?= urlencode($toCityName) ?>&date=<?= date('Y-m-d') ?>" class="btn btn-st-primary mt-2">
        Search for Today (<?= formatDateIndian(date('Y-m-d')) ?>)
      </a>
    </div>

  <?php elseif (empty($schedules)): ?>
    <!-- No Buses Available Empty State -->
    <div class="text-center py-5">
      <div class="mb-3" style="font-size: 70px;">🚌💨</div>
      <h3 class="fw-bold mb-2">No buses available for this route on the selected date.</h3>
      <p class="text-muted max-w-500 mx-auto mb-4">
        We could not find active bus schedules from <strong><?= e($fromCityName) ?></strong> to <strong><?= e($toCityName) ?></strong> on <strong><?= formatDateIndian($journeyDate) ?></strong>.
      </p>

      <div class="d-inline-flex gap-2 flex-wrap justify-content-center">
        <!-- Suggest Next Day -->
        <?php 
          $nextDate = date('Y-m-d', strtotime($journeyDate . ' +1 day'));
        ?>
        <a href="<?= $baseUrl ?>/buses/search.php?from=<?= urlencode($fromCityName) ?>&to=<?= urlencode($toCityName) ?>&date=<?= $nextDate ?>" class="btn btn-st-gold">
          <i class="fas fa-calendar-plus me-1"></i> Try Next Day (<?= formatDateIndian($nextDate) ?>)
        </a>
        <a href="<?= $baseUrl ?>/index.php" class="btn btn-st-outline">
          <i class="fas fa-home me-1"></i> Back to Homepage
        </a>
      </div>

      <div class="mt-5 p-4 bg-white rounded-4 border mx-auto" style="max-width: 600px;">
        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-lightbulb text-warning me-1"></i> Travel Tip</h6>
        <p class="text-secondary small mb-0">Popular daily routes with guaranteed departures include <strong>Ahmedabad to Mumbai</strong>, <strong>Surat to Pune</strong>, and <strong>Ahmedabad to Udaipur</strong>.</p>
      </div>
    </div>

  <?php else: ?>
    <!-- Bus Search Results with Interactive Filters -->
    <div class="row g-4">
      <!-- Left Filters Sidebar -->
      <div class="col-lg-3">
        <div class="bg-white p-4 rounded-4 border shadow-sm sticky-top" style="top: 90px; z-index: 10;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fas fa-sliders-h me-1 text-primary"></i> Filters</h5>
            <button class="btn btn-link text-danger p-0 small text-decoration-none fw-bold" id="btn-clear-filters">Reset All</button>
          </div>

          <!-- Bus Types -->
          <div class="mb-4">
            <label class="form-label fw-bold text-uppercase small text-muted">Bus Type</label>
            <div class="form-check mb-1">
              <input class="form-check-input filter-type" type="checkbox" value="sleeper" id="filterSleeper">
              <label class="form-check-label" for="filterSleeper">Sleeper (Berths)</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input filter-type" type="checkbox" value="seater" id="filterSeater">
              <label class="form-check-label" for="filterSeater">Seater (Pushback)</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input filter-type" type="checkbox" value="mixed" id="filterMixed">
              <label class="form-check-label" for="filterMixed">Seater + Sleeper</label>
            </div>
          </div>

          <!-- AC / Non-AC -->
          <div class="mb-4">
            <label class="form-label fw-bold text-uppercase small text-muted">AC Preference</label>
            <div class="form-check mb-1">
              <input class="form-check-input filter-ac" type="checkbox" value="ac" id="filterAC">
              <label class="form-check-label" for="filterAC">AC Coaches</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input filter-ac" type="checkbox" value="non-ac" id="filterNonAC">
              <label class="form-check-label" for="filterNonAC">Non-AC Coaches</label>
            </div>
          </div>

          <!-- Departure Time Slots -->
          <div class="mb-4">
            <label class="form-label fw-bold text-uppercase small text-muted">Departure Time</label>
            <div class="form-check mb-1">
              <input class="form-check-input filter-time" type="checkbox" value="morning" id="timeMorning">
              <label class="form-check-label" for="timeMorning">Morning (06:00 - 12:00)</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input filter-time" type="checkbox" value="afternoon" id="timeAfternoon">
              <label class="form-check-label" for="timeAfternoon">Afternoon (12:00 - 17:00)</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input filter-time" type="checkbox" value="evening" id="timeEvening">
              <label class="form-check-label" for="timeEvening">Evening (17:00 - 21:00)</label>
            </div>
            <div class="form-check mb-1">
              <input class="form-check-input filter-time" type="checkbox" value="night" id="timeNight">
              <label class="form-check-label" for="timeNight">Night (21:00 - 06:00)</label>
            </div>
          </div>

          <!-- Price Max Slider -->
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <label class="form-label fw-bold text-uppercase small text-muted mb-0">Max Price</label>
              <span class="fw-bold text-primary" id="price-slider-val">₹2500</span>
            </div>
            <input type="range" class="form-range" id="price-range-slider" min="400" max="2500" step="50" value="2500">
          </div>
        </div>
      </div>

      <!-- Right Buses Cards Stream -->
      <div class="col-lg-9">
        <!-- Top Toolbar: Match Count & Sort By -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div class="text-secondary fw-semibold">
            Showing <span class="text-dark fw-bold" id="matching-buses-count"><?= count($schedules) ?></span> available buses
          </div>

          <div class="d-flex align-items-center gap-2">
            <span class="small text-muted fw-bold">Sort By:</span>
            <select class="form-select form-select-sm w-auto" id="sort-buses-select">
              <option value="cheapest">Cheapest Fare</option>
              <option value="earliest" selected>Earliest Departure</option>
              <option value="latest">Latest Departure</option>
              <option value="seats">Most Seats Available</option>
            </select>
          </div>
        </div>

        <!-- Bus Cards Container -->
        <div id="buses-cards-stream">
          <?php foreach ($schedules as $sched): ?>
            <?php 
              // Determine bus tag attributes for client-side filtering
              $isAc = (stripos($sched['bus_type'], 'AC') !== false && stripos($sched['bus_type'], 'Non-AC') === false) ? 'ac' : 'non-ac';
              $layoutType = 'sleeper';
              if ($sched['seating_layout'] === 'seater_2x2') {
                  $layoutType = 'seater';
              } elseif ($sched['seating_layout'] === 'mixed_seater_sleeper') {
                  $layoutType = 'mixed';
              }

              $depHour = (int)date('H', strtotime($sched['departure_time']));
              $timeSlot = 'night';
              if ($depHour >= 6 && $depHour < 12) $timeSlot = 'morning';
              elseif ($depHour >= 12 && $depHour < 17) $timeSlot = 'afternoon';
              elseif ($depHour >= 17 && $depHour < 21) $timeSlot = 'evening';

              $fareValue = (float)$sched['base_fare'];
              $isNextDay = ($sched['arrival_date'] > $sched['departure_date']);
            ?>

            <div class="bus-card" 
                 data-ac="<?= $isAc ?>" 
                 data-type="<?= $layoutType ?>" 
                 data-time="<?= $timeSlot ?>" 
                 data-price="<?= $fareValue ?>" 
                 data-deptime="<?= $sched['departure_time'] ?>" 
                 data-seats="<?= $sched['available_seats_count'] ?>">

              <div class="row align-items-center">
                <!-- Bus Info Column -->
                <div class="col-lg-4 col-md-12">
                  <div class="d-flex align-items-center gap-3">
                    <?php if (!empty($sched['primary_image'])): ?>
                      <img src="<?= e($sched['primary_image']) ?>" alt="<?= e($sched['bus_name']) ?>" class="rounded-3" style="width: 80px; height: 60px; object-fit: cover;">
                    <?php else: ?>
                      <div class="rounded-3 bg-light d-flex align-items-center justify-content-center text-primary" style="width: 80px; height: 60px; font-size: 24px;">
                        <i class="fas fa-bus"></i>
                      </div>
                    <?php endif; ?>
                    <div>
                      <div class="bus-operator-name"><?= e($sched['bus_name']) ?></div>
                      <span class="bus-type-badge"><?= e($sched['bus_type']) ?></span>
                      <div class="text-muted small mt-1"><i class="fas fa-id-card-alt me-1"></i><?= e($sched['bus_number']) ?></div>
                    </div>
                  </div>
                </div>

                <!-- Timing & Duration Column -->
                <div class="col-lg-5 col-md-7 my-3 my-lg-0">
                  <div class="bus-time-display">
                    <div class="time-col text-start">
                      <h4><?= formatTime($sched['departure_time']) ?></h4>
                      <p><?= e($route['from_city_name']) ?></p>
                    </div>

                    <div class="duration-indicator">
                      <div class="duration-text"><?= e($route['estimated_duration']) ?></div>
                      <div class="duration-line"></div>
                      <span class="badge bg-secondary-subtle text-secondary small">Direct</span>
                    </div>

                    <div class="time-col text-end">
                      <h4>
                        <?= formatTime($sched['arrival_time']) ?>
                        <?php if ($isNextDay): ?>
                          <span class="badge bg-warning text-dark small" style="font-size: 0.65rem; vertical-align: top;">+1 Day</span>
                        <?php endif; ?>
                      </h4>
                      <p><?= e($route['to_city_name']) ?></p>
                    </div>
                  </div>
                </div>

                <!-- Price & Select Seat Column -->
                <div class="col-lg-3 col-md-5 text-lg-end text-center">
                  <div class="text-muted small">Starts from</div>
                  <div class="fs-3 fw-bold text-primary mb-1"><?= formatPrice($sched['base_fare']) ?></div>
                  
                  <?php if ($sched['available_seats_count'] > 0): ?>
                    <div class="text-success small fw-bold mb-2">
                      <i class="fas fa-check-circle me-1"></i> <?= $sched['available_seats_count'] ?> Seats Left
                    </div>
                    <a href="<?= $baseUrl ?>/booking/select_seats.php?schedule_id=<?= $sched['id'] ?>" class="btn btn-st-primary w-100">
                      <i class="fas fa-couch me-1"></i> Select Seats
                    </a>
                  <?php else: ?>
                    <div class="text-danger small fw-bold mb-2">
                      <i class="fas fa-times-circle me-1"></i> Sold Out
                    </div>
                    <button class="btn btn-secondary w-100" disabled>Sold Out</button>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Amenities & Cancellation Strip -->
              <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex flex-wrap align-items-center">
                  <?php if (!empty($sched['amenities_list'])): ?>
                    <?php 
                      $amenityIcons = [
                        'wifi' => ['icon' => 'fa-wifi', 'label' => 'Free Wi-Fi'],
                        'water_bottle' => ['icon' => 'fa-tint', 'label' => 'Water Bottle'],
                        'charging_point' => ['icon' => 'fa-plug', 'label' => 'Charging Point'],
                        'blanket' => ['icon' => 'fa-bed', 'label' => 'Clean Blanket'],
                        'reading_light' => ['icon' => 'fa-lightbulb', 'label' => 'Reading Lamp'],
                        'gps_tracking' => ['icon' => 'fa-map-pin', 'label' => 'GPS Live Tracking'],
                        'cctv' => ['icon' => 'fa-video', 'label' => 'CCTV'],
                        'first_aid' => ['icon' => 'fa-medkit', 'label' => 'First Aid']
                      ];
                    ?>
                    <?php foreach ($sched['amenities_list'] as $amenityKey): ?>
                      <?php if (isset($amenityIcons[$amenityKey])): ?>
                        <span class="amenity-chip">
                          <i class="fas <?= $amenityIcons[$amenityKey]['icon'] ?> text-primary"></i> 
                          <?= $amenityIcons[$amenityKey]['label'] ?>
                        </span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>

                <div class="small text-muted">
                  <i class="fas fa-shield-alt text-success me-1"></i> Free cancellation up to 24 hrs
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Interactive Client-side Filtering & Sorting Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const cards = Array.from(document.querySelectorAll('.bus-card'));
  const countEl = document.getElementById('matching-buses-count');
  const slider = document.getElementById('price-range-slider');
  const sliderVal = document.getElementById('price-slider-val');
  const sortSelect = document.getElementById('sort-buses-select');
  const clearBtn = document.getElementById('btn-clear-filters');

  function applyFilters() {
    const selectedTypes = Array.from(document.querySelectorAll('.filter-type:checked')).map(cb => cb.value);
    const selectedAC = Array.from(document.querySelectorAll('.filter-ac:checked')).map(cb => cb.value);
    const selectedTimes = Array.from(document.querySelectorAll('.filter-time:checked')).map(cb => cb.value);
    const maxPrice = parseFloat(slider ? slider.value : 999999);

    let matchCount = 0;

    cards.forEach(card => {
      const cardType = card.getAttribute('data-type');
      const cardAc = card.getAttribute('data-ac');
      const cardTime = card.getAttribute('data-time');
      const cardPrice = parseFloat(card.getAttribute('data-price') || 0);

      let show = true;

      // Type filter
      if (selectedTypes.length > 0 && !selectedTypes.includes(cardType)) {
        show = false;
      }
      // AC filter
      if (selectedAC.length > 0 && !selectedAC.includes(cardAc)) {
        show = false;
      }
      // Time filter
      if (selectedTimes.length > 0 && !selectedTimes.includes(cardTime)) {
        show = false;
      }
      // Price filter
      if (cardPrice > maxPrice) {
        show = false;
      }

      if (show) {
        card.style.display = 'block';
        matchCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (countEl) countEl.textContent = matchCount;
  }

  // Sort Logic
  function applySort() {
    if (!sortSelect) return;
    const mode = sortSelect.value;
    const stream = document.getElementById('buses-cards-stream');
    if (!stream) return;

    const visibleCards = cards.slice();
    visibleCards.sort((a, b) => {
      if (mode === 'cheapest') {
        return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
      } else if (mode === 'earliest') {
        return a.getAttribute('data-deptime').localeCompare(b.getAttribute('data-deptime'));
      } else if (mode === 'latest') {
        return b.getAttribute('data-deptime').localeCompare(a.getAttribute('data-deptime'));
      } else if (mode === 'seats') {
        return parseInt(b.getAttribute('data-seats')) - parseInt(a.getAttribute('data-seats'));
      }
      return 0;
    });

    visibleCards.forEach(c => stream.appendChild(c));
  }

  // Bind change listeners
  document.querySelectorAll('.filter-type, .filter-ac, .filter-time').forEach(cb => {
    cb.addEventListener('change', applyFilters);
  });

  if (slider) {
    slider.addEventListener('input', function () {
      if (sliderVal) sliderVal.textContent = '₹' + this.value;
      applyFilters();
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', applySort);
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      document.querySelectorAll('.filter-type, .filter-ac, .filter-time').forEach(cb => cb.checked = false);
      if (slider) {
        slider.value = slider.max;
        if (sliderVal) sliderVal.textContent = '₹' + slider.max;
      }
      applyFilters();
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
