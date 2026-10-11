<?php
/**
 * Admin - Add New Bus with Dual Image Support (Upload or URL)
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

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $busName = cleanInput($_POST['bus_name'] ?? '');
    $busNumber = strtoupper(cleanInput($_POST['bus_number'] ?? ''));
    $operatorName = cleanInput($_POST['operator_name'] ?? 'Shreenathji Travels');
    $busType = cleanInput($_POST['bus_type'] ?? 'Volvo Multi-Axle AC Sleeper');
    $seatingLayout = cleanInput($_POST['seating_layout'] ?? 'sleeper_2x1');
    $totalSeats = (int)($_POST['total_seats'] ?? 30);
    $cancellationPolicy = cleanInput($_POST['cancellation_policy'] ?? 'Standard cancellation policy applies.');
    $amenities = $_POST['amenities'] ?? [];

    $imageType = cleanInput($_POST['image_source_type'] ?? 'url');
    $primaryImage = '';

    // Handle Image Source
    if ($imageType === 'upload' && !empty($_FILES['bus_image_file']['name'])) {
        $uploadRes = handleBusImageUpload($_FILES['bus_image_file']);
        if (!$uploadRes['success']) {
            $errorMessage = $uploadRes['message'];
        } else {
            $primaryImage = $uploadRes['path'];
        }
    } elseif ($imageType === 'url') {
        $imageUrl = cleanInput($_POST['bus_image_url'] ?? '');
        if (!empty($imageUrl)) {
            $valRes = validateBusImageUrl($imageUrl);
            if (!$valRes['valid']) {
                $errorMessage = $valRes['message'];
            } else {
                $primaryImage = $valRes['url'];
            }
        } else {
            // Default placeholder
            $primaryImage = 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80';
        }
    }

    if (empty($busName) || empty($busNumber)) {
        $errorMessage = 'Bus Name and Vehicle Registration Number are required.';
    }

    // Check duplicate bus number
    $chkStmt = $db->prepare("SELECT id FROM buses WHERE bus_number = ? LIMIT 1");
    $chkStmt->execute([$busNumber]);
    if ($chkStmt->fetch()) {
        $errorMessage = "A bus with registration number '{$busNumber}' already exists.";
    }

    if (empty($errorMessage)) {
        try {
            $db->beginTransaction();

            $amenitiesJson = json_encode(array_values($amenities), JSON_UNESCAPED_UNICODE);

            $insStmt = $db->prepare("INSERT INTO buses (bus_name, bus_number, operator_name, bus_type, seating_layout, total_seats, primary_image, image_type, amenities, cancellation_policy, is_active, created_at)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $insStmt->execute([$busName, $busNumber, $operatorName, $busType, $seatingLayout, $totalSeats, $primaryImage, $imageType, $amenitiesJson, $cancellationPolicy]);
            $busId = $db->lastInsertId();

            // Automatically generate bus_seats layout records
            $seatStmt = $db->prepare("INSERT INTO bus_seats (bus_id, seat_number, deck, seat_type, row_num, col_num, is_ladies_preferred, price_extra) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            if ($seatingLayout === 'sleeper_2x1') {
                $numRows = ceil($totalSeats / 6);
                $seatIdx = 1;
                for ($r = 1; $r <= $numRows; $r++) {
                    // Lower Deck
                    $seatStmt->execute([$busId, 'L' . $seatIdx++, 'lower', 'sleeper', $r, 1, 0, 50.00]);
                    $seatStmt->execute([$busId, 'L' . $seatIdx++, 'lower', 'sleeper', $r, 2, ($r <= 2 ? 1 : 0), 0.00]);
                    $seatStmt->execute([$busId, 'L' . $seatIdx++, 'lower', 'sleeper', $r, 3, ($r <= 2 ? 1 : 0), 0.00]);
                }
                $seatIdx = 1;
                for ($r = 1; $r <= $numRows; $r++) {
                    // Upper Deck
                    $seatStmt->execute([$busId, 'U' . $seatIdx++, 'upper', 'sleeper', $r, 1, 0, 50.00]);
                    $seatStmt->execute([$busId, 'U' . $seatIdx++, 'upper', 'sleeper', $r, 2, ($r <= 2 ? 1 : 0), 0.00]);
                    $seatStmt->execute([$busId, 'U' . $seatIdx++, 'upper', 'sleeper', $r, 3, ($r <= 2 ? 1 : 0), 0.00]);
                }
            } elseif ($seatingLayout === 'seater_2x2') {
                $numRows = ceil($totalSeats / 4);
                for ($r = 1; $r <= $numRows; $r++) {
                    $cols = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
                    foreach ($cols as $char => $colIdx) {
                        $isLadies = ($r === 1 && ($char === 'A' || $char === 'B')) ? 1 : 0;
                        $seatStmt->execute([$busId, $r . $char, 'lower', 'seater', $r, $colIdx, $isLadies, 0.00]);
                    }
                }
            } elseif ($seatingLayout === 'mixed_seater_sleeper') {
                // Lower 20 seater
                for ($r = 1; $r <= 5; $r++) {
                    $cols = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
                    foreach ($cols as $char => $colIdx) {
                        $seatStmt->execute([$busId, 'S' . $r . $char, 'lower', 'seater', $r, $colIdx, ($r === 1 ? 1 : 0), 0.00]);
                    }
                }
                // Upper 10 sleeper
                for ($r = 1; $r <= 5; $r++) {
                    $seatStmt->execute([$busId, 'U' . ($r * 2 - 1), 'upper', 'sleeper', $r, 1, 0, 100.00]);
                    $seatStmt->execute([$busId, 'U' . ($r * 2), 'upper', 'sleeper', $r, 2, 0, 100.00]);
                }
            }

            $db->commit();
            logAdminActivity($currentAdmin['id'], 'create_bus', "Created new bus {$busNumber} ({$busName})");
            header("Location: {$adminUrl}/buses/index.php?created=1");
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Failed to create bus: " . $e->getMessage());
            $errorMessage = 'Failed to save bus record. Please try again.';
        }
    }
}

$adminPageTitle = "Add New Bus – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= $adminUrl ?>/buses/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0">Add New Bus Coach</h4>
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
      <form method="POST" enctype="multipart/form-data">
        <?= getCsrfField() ?>

        <div class="row g-4">
          <!-- Col 1: Coach Information -->
          <div class="col-lg-7">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Coach Specifications</h5>

            <div class="mb-3">
              <label class="form-label small fw-bold">Bus Name / Model *</label>
              <input type="text" name="bus_name" class="form-control" placeholder="e.g. Shreenathji Royal Cruiser Multi-Axle" required value="<?= e($_POST['bus_name'] ?? '') ?>">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Vehicle Registration Number *</label>
                <input type="text" name="bus_number" class="form-control" placeholder="e.g. GJ-01-ST-9999" required value="<?= e($_POST['bus_number'] ?? '') ?>" style="text-transform: uppercase;">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Fleet Operator Name</label>
                <input type="text" name="operator_name" class="form-control" value="<?= e($_POST['operator_name'] ?? 'Shreenathji Travels') ?>" required>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Bus Category & Type *</label>
                <select name="bus_type" class="form-select" required>
                  <option value="Volvo Multi-Axle AC Sleeper">Volvo Multi-Axle AC Sleeper</option>
                  <option value="AC Sleeper (2+1)">AC Sleeper (2+1)</option>
                  <option value="BharatBenz AC Seater (2+2)">BharatBenz AC Seater (2+2)</option>
                  <option value="AC Seater + Sleeper">AC Seater + Sleeper (Hybrid)</option>
                  <option value="Non-AC Sleeper (2+1)">Non-AC Sleeper (2+1)</option>
                  <option value="Non-AC Seater (2+2)">Non-AC Seater (2+2)</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Seating Layout Mode *</label>
                <select name="seating_layout" id="seating_layout_select" class="form-select" required>
                  <option value="sleeper_2x1">Sleeper (2+1 Upper/Lower Berths)</option>
                  <option value="seater_2x2">Seater (2+2 Pushback Seats)</option>
                  <option value="mixed_seater_sleeper">Mixed (Lower Seater + Upper Sleeper)</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Total Capacity (Seats) *</label>
                <input type="number" name="total_seats" id="total_seats_input" class="form-control" value="30" min="20" max="60" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Cancellation Policy Note</label>
                <input type="text" name="cancellation_policy" class="form-control" value="Standard policy: 90% refund >24h, 70% 12-24h">
              </div>
            </div>

            <!-- Amenities Checkboxes -->
            <div class="mb-3">
              <label class="form-label small fw-bold d-block">Configured Amenities</label>
              <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="amenities[]" value="wifi" id="amenity_wifi" checked>
                  <label class="form-check-label small" for="amenity_wifi">Wi-Fi</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="amenities[]" value="water_bottle" id="amenity_water" checked>
                  <label class="form-check-label small" for="amenity_water">Water Bottle</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="amenities[]" value="charging_point" id="amenity_charging" checked>
                  <label class="form-check-label small" for="amenity_charging">Charging Point</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="amenities[]" value="blanket" id="amenity_blanket" checked>
                  <label class="form-check-label small" for="amenity_blanket">Clean Blanket</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="amenities[]" value="gps_tracking" id="amenity_gps" checked>
                  <label class="form-check-label small" for="amenity_gps">Live GPS</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="amenities[]" value="reading_light" id="amenity_light" checked>
                  <label class="form-check-label small" for="amenity_light">Reading Light</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="amenities[]" value="cctv" id="amenity_cctv">
                  <label class="form-check-label small" for="amenity_cctv">CCTV</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Col 2: Bus Photograph (Upload OR URL with Instant Preview) -->
          <div class="col-lg-5">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Bus Photograph</h5>

            <div class="mb-3">
              <label class="form-label small fw-bold">Image Method</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="image_source_type" id="img_type_url" value="url" checked>
                  <label class="form-check-label small" for="img_type_url">Direct Image URL</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="image_source_type" id="img_type_upload" value="upload">
                  <label class="form-check-label small" for="img_type_upload">Upload from Computer</label>
                </div>
              </div>
            </div>

            <!-- Option A: Direct URL Input -->
            <div id="url_input_wrap" class="mb-3">
              <label class="form-label small fw-bold">Public Image URL (HTTPS)</label>
              <input type="url" name="bus_image_url" id="bus_image_url_input" class="form-control" placeholder="https://..." value="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80">
              <small class="text-muted">Paste image URL copied from web</small>
            </div>

            <!-- Option B: File Upload Input -->
            <div id="file_input_wrap" class="mb-3" style="display: none;">
              <label class="form-label small fw-bold">Select Image File (JPG, PNG, WebP)</label>
              <input type="file" name="bus_image_file" id="bus_image_file_input" class="form-control" accept="image/jpeg,image/png,image/webp">
              <small class="text-muted">Max file size: 5MB</small>
            </div>

            <!-- Live Image Preview Box -->
            <div class="mb-3">
              <label class="form-label small fw-bold d-block">Photograph Preview</label>
              <div class="bus-img-preview-box" id="bus-preview-box">
                <img id="bus-preview-img" src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80" alt="Preview">
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
          <button type="submit" class="btn btn-st-primary px-4">
            <i class="fas fa-save me-1"></i> Save Bus & Generate Seat Map
          </button>
          <a href="<?= $adminUrl ?>/buses/index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const rdoUrl = document.getElementById('img_type_url');
  const rdoUpload = document.getElementById('img_type_upload');
  const urlWrap = document.getElementById('url_input_wrap');
  const fileWrap = document.getElementById('file_input_wrap');
  const urlInput = document.getElementById('bus_image_url_input');
  const fileInput = document.getElementById('bus_image_file_input');
  const previewImg = document.getElementById('bus-preview-img');

  rdoUrl.addEventListener('change', function () {
    urlWrap.style.display = 'block';
    fileWrap.style.display = 'none';
    if (urlInput.value) {
      previewImg.src = urlInput.value;
    }
  });

  rdoUpload.addEventListener('change', function () {
    urlWrap.style.display = 'none';
    fileWrap.style.display = 'block';
  });

  urlInput.addEventListener('input', function () {
    if (this.value.trim().startsWith('http')) {
      previewImg.src = this.value.trim();
    }
  });

  fileInput.addEventListener('change', function () {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewImg.src = e.target.result;
      };
      reader.readAsDataURL(this.files[0]);
    }
  });

  // Dynamic capacity suggestion
  const layoutSelect = document.getElementById('seating_layout_select');
  const seatsInput = document.getElementById('total_seats_input');
  layoutSelect.addEventListener('change', function () {
    if (this.value === 'sleeper_2x1') {
      seatsInput.value = 30;
    } else if (this.value === 'seater_2x2') {
      seatsInput.value = 40;
    } else if (this.value === 'mixed_seater_sleeper') {
      seatsInput.value = 30;
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
