<?php
/**
 * Admin - Edit Bus Coach
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

$busId = (int)($_GET['id'] ?? 0);
if ($busId <= 0) {
    header("Location: {$adminUrl}/buses/index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM buses WHERE id = ? LIMIT 1");
$stmt->execute([$busId]);
$bus = $stmt->fetch();

if (!$bus) {
    die("Bus not found.");
}

$currentAmenities = json_decode($bus['amenities'] ?? '[]', true) ?: [];
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $busName = cleanInput($_POST['bus_name'] ?? '');
    $busNumber = strtoupper(cleanInput($_POST['bus_number'] ?? ''));
    $operatorName = cleanInput($_POST['operator_name'] ?? 'Shreenathji Travels');
    $busType = cleanInput($_POST['bus_type'] ?? $bus['bus_type']);
    $cancellationPolicy = cleanInput($_POST['cancellation_policy'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $amenities = $_POST['amenities'] ?? [];

    $imageType = cleanInput($_POST['image_source_type'] ?? $bus['image_type']);
    $primaryImage = $bus['primary_image'];

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
        }
    }

    if (empty($busName) || empty($busNumber)) {
        $errorMessage = 'Bus Name and Vehicle Registration Number are required.';
    }

    // Check duplicate bus number
    $chkStmt = $db->prepare("SELECT id FROM buses WHERE bus_number = ? AND id != ? LIMIT 1");
    $chkStmt->execute([$busNumber, $busId]);
    if ($chkStmt->fetch()) {
        $errorMessage = "A bus with registration number '{$busNumber}' already exists.";
    }

    if (empty($errorMessage)) {
        $amenitiesJson = json_encode(array_values($amenities), JSON_UNESCAPED_UNICODE);

        $updStmt = $db->prepare("UPDATE buses SET 
                                 bus_name = ?, bus_number = ?, operator_name = ?, bus_type = ?, 
                                 primary_image = ?, image_type = ?, amenities = ?, cancellation_policy = ?, 
                                 is_active = ?, updated_at = NOW() 
                                 WHERE id = ?");
        if ($updStmt->execute([$busName, $busNumber, $operatorName, $busType, $primaryImage, $imageType, $amenitiesJson, $cancellationPolicy, $isActive, $busId])) {
            logAdminActivity($currentAdmin['id'], 'edit_bus', "Updated bus {$busNumber} ({$busName})");
            header("Location: {$adminUrl}/buses/index.php?updated=1");
            exit;
        } else {
            $errorMessage = 'Failed to update bus details.';
        }
    }
}

$adminPageTitle = "Edit Bus – " . e($bus['bus_name']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= $adminUrl ?>/buses/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0">Edit Bus: <?= e($bus['bus_number']) ?></h4>
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
          <div class="col-lg-7">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Coach Specifications</h5>

            <div class="mb-3">
              <label class="form-label small fw-bold">Bus Name / Model *</label>
              <input type="text" name="bus_name" class="form-control" required value="<?= e($bus['bus_name']) ?>">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Vehicle Registration Number *</label>
                <input type="text" name="bus_number" class="form-control" required value="<?= e($bus['bus_number']) ?>" style="text-transform: uppercase;">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Fleet Operator Name</label>
                <input type="text" name="operator_name" class="form-control" value="<?= e($bus['operator_name']) ?>" required>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Bus Category & Type *</label>
                <select name="bus_type" class="form-select" required>
                  <option value="Volvo Multi-Axle AC Sleeper" <?= ($bus['bus_type'] === 'Volvo Multi-Axle AC Sleeper') ? 'selected' : '' ?>>Volvo Multi-Axle AC Sleeper</option>
                  <option value="AC Sleeper (2+1)" <?= ($bus['bus_type'] === 'AC Sleeper (2+1)') ? 'selected' : '' ?>>AC Sleeper (2+1)</option>
                  <option value="BharatBenz AC Seater (2+2)" <?= ($bus['bus_type'] === 'BharatBenz AC Seater (2+2)') ? 'selected' : '' ?>>BharatBenz AC Seater (2+2)</option>
                  <option value="AC Seater + Sleeper" <?= ($bus['bus_type'] === 'AC Seater + Sleeper') ? 'selected' : '' ?>>AC Seater + Sleeper (Hybrid)</option>
                  <option value="Non-AC Sleeper (2+1)" <?= ($bus['bus_type'] === 'Non-AC Sleeper (2+1)') ? 'selected' : '' ?>>Non-AC Sleeper (2+1)</option>
                  <option value="Non-AC Seater (2+2)" <?= ($bus['bus_type'] === 'Non-AC Seater (2+2)') ? 'selected' : '' ?>>Non-AC Seater (2+2)</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Status</label>
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" <?= ($bus['is_active'] == 1) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="isActiveSwitch">Active for bookings</label>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Cancellation Policy Note</label>
              <input type="text" name="cancellation_policy" class="form-control" value="<?= e($bus['cancellation_policy']) ?>">
            </div>

            <!-- Amenities -->
            <div class="mb-3">
              <label class="form-label small fw-bold d-block">Configured Amenities</label>
              <div class="d-flex flex-wrap gap-3">
                <?php
                  $allAmenities = [
                    'wifi' => 'Wi-Fi',
                    'water_bottle' => 'Water Bottle',
                    'charging_point' => 'Charging Point',
                    'blanket' => 'Clean Blanket',
                    'gps_tracking' => 'Live GPS',
                    'reading_light' => 'Reading Light',
                    'cctv' => 'CCTV',
                    'first_aid' => 'First Aid'
                  ];
                  foreach ($allAmenities as $key => $label):
                ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $key ?>" id="am_<?= $key ?>" <?= in_array($key, $currentAmenities, true) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="am_<?= $key ?>"><?= $label ?></label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Col 2: Image Replacement -->
          <div class="col-lg-5">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Bus Photograph</h5>

            <div class="mb-3">
              <label class="form-label small fw-bold">Image Method</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="image_source_type" id="edit_type_url" value="url" <?= ($bus['image_type'] === 'url') ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="edit_type_url">Image URL</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="image_source_type" id="edit_type_upload" value="upload" <?= ($bus['image_type'] === 'upload') ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="edit_type_upload">Upload New File</label>
                </div>
              </div>
            </div>

            <div id="url_wrap" class="mb-3" style="<?= ($bus['image_type'] === 'upload') ? 'display: none;' : '' ?>">
              <label class="form-label small fw-bold">Image URL</label>
              <input type="url" name="bus_image_url" id="bus_url_input" class="form-control" value="<?= e($bus['primary_image']) ?>">
            </div>

            <div id="upload_wrap" class="mb-3" style="<?= ($bus['image_type'] === 'url') ? 'display: none;' : '' ?>">
              <label class="form-label small fw-bold">Upload New Image</label>
              <input type="file" name="bus_image_file" id="bus_file_input" class="form-control" accept="image/*">
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold d-block">Photograph Preview</label>
              <div class="bus-img-preview-box">
                <?php
                  $currImg = !empty($bus['primary_image']) ? (str_starts_with($bus['primary_image'], 'http') ? $bus['primary_image'] : $baseUrl . '/' . $bus['primary_image']) : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80';
                ?>
                <img id="bus-edit-preview" src="<?= e($currImg) ?>" alt="Preview">
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
          <button type="submit" class="btn btn-st-primary px-4">Save Changes</button>
          <a href="<?= $adminUrl ?>/buses/index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const rdoUrl = document.getElementById('edit_type_url');
  const rdoUpload = document.getElementById('edit_type_upload');
  const urlWrap = document.getElementById('url_wrap');
  const uploadWrap = document.getElementById('upload_wrap');
  const urlInput = document.getElementById('bus_url_input');
  const fileInput = document.getElementById('bus_file_input');
  const previewImg = document.getElementById('bus-edit-preview');

  rdoUrl.addEventListener('change', function () {
    urlWrap.style.display = 'block';
    uploadWrap.style.display = 'none';
  });

  rdoUpload.addEventListener('change', function () {
    urlWrap.style.display = 'none';
    uploadWrap.style.display = 'block';
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
