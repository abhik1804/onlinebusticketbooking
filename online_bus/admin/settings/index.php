<?php
/**
 * Admin - Website & Gateway Settings
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

$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $settingsToUpdate = [
        'site_name' => cleanInput($_POST['site_name'] ?? 'Shreenathji Travels'),
        'site_tagline' => cleanInput($_POST['site_tagline'] ?? 'Your Journey, Our Responsibility'),
        'site_phone' => cleanInput($_POST['site_phone'] ?? '+91 79 2658 9999'),
        'site_email' => cleanInput($_POST['site_email'] ?? 'support@shreenathjitravels.com'),
        'site_address' => cleanInput($_POST['site_address'] ?? ''),
        'tax_percentage' => cleanInput($_POST['tax_percentage'] ?? '5.00'),
        'convenience_fee' => cleanInput($_POST['convenience_fee'] ?? '25.00'),
        'razorpay_key_id' => cleanInput($_POST['razorpay_key_id'] ?? ''),
        'razorpay_key_secret' => cleanInput($_POST['razorpay_key_secret'] ?? ''),
        'cancellation_policy_24h' => cleanInput($_POST['cancellation_policy_24h'] ?? '90'),
        'cancellation_policy_12h' => cleanInput($_POST['cancellation_policy_12h'] ?? '70'),
        'cancellation_policy_0h' => cleanInput($_POST['cancellation_policy_0h'] ?? '0')
    ];

    $stmt = $db->prepare("INSERT INTO website_settings (key_name, key_value, updated_at) 
                          VALUES (?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE key_value = VALUES(key_value), updated_at = NOW()");

    foreach ($settingsToUpdate as $key => $val) {
        $stmt->execute([$key, $val]);
    }

    logAdminActivity($currentAdmin['id'], 'update_settings', "Updated portal settings");
    $successMsg = 'Website settings and payment gateway credentials saved successfully!';
}

// Fetch all settings
$settings = [];
$rows = $db->query("SELECT key_name, key_value FROM website_settings")->fetchAll();
foreach ($rows as $r) {
    $settings[$r['key_name']] = $r['key_value'];
}

// Fetch recent activity logs
$logsStmt = $db->query("SELECT l.*, a.name as admin_name 
                        FROM admin_activity_logs l 
                        LEFT JOIN admins a ON l.admin_id = a.id 
                        ORDER BY l.id DESC LIMIT 15");
$logs = $logsStmt->fetchAll();

$adminPageTitle = "Website Settings – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">System & Gateway Settings</h4>
    </div>
  </header>

  <div class="admin-content-body">
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> <?= e($successMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="admin-table-card p-4">
          <form method="POST">
            <?= getCsrfField() ?>

            <!-- General Settings -->
            <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-globe text-primary me-2"></i>Brand & Support Details</h5>
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Website Name</label>
                <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name'] ?? 'Shreenathji Travels') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Brand Tagline</label>
                <input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline'] ?? 'Your Journey, Our Responsibility') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Customer Helpline Phone</label>
                <input type="text" name="site_phone" class="form-control" value="<?= e($settings['site_phone'] ?? '+91 79 2658 9999') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Support Email</label>
                <input type="email" name="site_email" class="form-control" value="<?= e($settings['site_email'] ?? 'support@shreenathjitravels.com') ?>" required>
              </div>
              <div class="col-12">
                <label class="form-label small fw-bold">Office Address</label>
                <textarea name="site_address" class="form-control" rows="2"><?= e($settings['site_address'] ?? 'Shreenathji House, Nr. Paldi Cross Roads, Ellisbridge, Ahmedabad, Gujarat 380006') ?></textarea>
              </div>
            </div>

            <!-- Tax & Fare Settings -->
            <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-percentage text-warning me-2"></i>Fares, Taxes & Convenience Fees</h5>
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label small fw-bold">GST Rate (%)</label>
                <input type="number" step="0.1" name="tax_percentage" class="form-control" value="<?= e($settings['tax_percentage'] ?? '5.00') ?>" required>
                <small class="text-muted">Standard 5% GST on bus passenger transport</small>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Flat Convenience Fee (₹)</label>
                <input type="number" step="1" name="convenience_fee" class="form-control" value="<?= e($settings['convenience_fee'] ?? '25.00') ?>" required>
              </div>
            </div>

            <!-- Cancellation Policy Refund % -->
            <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-undo-alt text-danger me-2"></i>Cancellation Refund Rules</h5>
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <label class="form-label small fw-bold">> 24h Before Dep (% Refund)</label>
                <input type="number" name="cancellation_policy_24h" class="form-control" value="<?= e($settings['cancellation_policy_24h'] ?? '90') ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">12 to 24h (% Refund)</label>
                <input type="number" name="cancellation_policy_12h" class="form-control" value="<?= e($settings['cancellation_policy_12h'] ?? '70') ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">< 12h (% Refund)</label>
                <input type="number" name="cancellation_policy_0h" class="form-control" value="<?= e($settings['cancellation_policy_0h'] ?? '0') ?>" required>
              </div>
            </div>

            <!-- Razorpay Gateway Credentials -->
            <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-shield-alt text-success me-2"></i>Razorpay Gateway Configuration (TEST MODE)</h5>
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Razorpay Test Key ID</label>
                <input type="text" name="razorpay_key_id" class="form-control font-monospace" value="<?= e($settings['razorpay_key_id'] ?? 'rzp_test_1DP5mmOlF5G5ag') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Razorpay Test Key Secret</label>
                <input type="password" name="razorpay_key_secret" class="form-control font-monospace" value="<?= e($settings['razorpay_key_secret'] ?? 's9P7qW9t0Zk1xY2a3b4c5d6e') ?>" required>
              </div>
            </div>

            <button type="submit" class="btn btn-st-primary px-4">
              <i class="fas fa-save me-1"></i> Save All Settings
            </button>
          </form>
        </div>
      </div>

      <!-- Right: Audit Logs -->
      <div class="col-lg-4">
        <div class="admin-table-card p-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-history text-secondary me-2"></i>Admin Audit Log</h5>
          <div class="small">
            <?php if (empty($logs)): ?>
              <p class="text-muted">No recent activity recorded.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($logs as $l): ?>
                  <li class="list-group-item px-0 py-2">
                    <div class="fw-semibold text-dark"><?= e($l['action']) ?></div>
                    <div class="text-muted small"><?= e($l['details']) ?></div>
                    <div class="text-secondary small" style="font-size: 0.7rem;">
                      By <?= e($l['admin_name'] ?? 'System') ?> • <?= date('d M, h:i A', strtotime($l['created_at'])) ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
