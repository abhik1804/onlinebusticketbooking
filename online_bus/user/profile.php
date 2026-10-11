<?php
/**
 * User Profile & Password Management
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requireUserLogin();
$user = getCurrentUser();
$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

$profileMsg = '';
$profileErr = '';
$passMsg = '';
$passErr = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_profile'])) {
    requireCsrfToken();
    $name = cleanInput($_POST['full_name'] ?? '');
    $mobile = cleanInput($_POST['mobile'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? 'male');
    $address = cleanInput($_POST['address'] ?? '');

    if (empty($name) || empty($mobile)) {
        $profileErr = 'Name and mobile number are required.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $profileErr = 'Please enter a valid 10-digit Indian mobile number.';
    } else {
        // Check duplicate mobile for another user
        $chk = $db->prepare("SELECT id FROM users WHERE mobile = ? AND id != ? LIMIT 1");
        $chk->execute([$mobile, $user['id']]);
        if ($chk->fetch()) {
            $profileErr = 'This mobile number is already in use by another account.';
        } else {
            $upd = $db->prepare("UPDATE users SET full_name = ?, mobile = ?, gender = ?, address = ?, updated_at = NOW() WHERE id = ?");
            if ($upd->execute([$name, $mobile, $gender, $address, $user['id']])) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_mobile'] = $mobile;
                $user = getCurrentUser();
                $profileMsg = 'Profile updated successfully!';
            } else {
                $profileErr = 'Failed to update profile.';
            }
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_change_password'])) {
    requireCsrfToken();
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_new_password'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $passErr = 'All password fields are required.';
    } elseif (strlen($newPass) < 6) {
        $passErr = 'New password must be at least 6 characters.';
    } elseif ($newPass !== $confirmPass) {
        $passErr = 'New password and confirm password do not match.';
    } else {
        // Verify current password from DB
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $currentHash = $stmt->fetchColumn();

        if (!password_verify($currentPass, $currentHash)) {
            $passErr = 'Incorrect current password.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $upd = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            if ($upd->execute([$newHash, $user['id']])) {
                $passMsg = 'Password changed successfully!';
            } else {
                $passErr = 'Failed to update password.';
            }
        }
    }
}

$pageTitle = "My Profile – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <div class="row g-4">
    <!-- Left: Profile Details -->
    <div class="col-lg-6">
      <div class="bg-white p-4 rounded-4 border shadow-sm h-100">
        <h5 class="fw-bold mb-3"><i class="fas fa-user-edit text-primary me-2"></i>Personal Information</h5>

        <?php if (!empty($profileMsg)): ?>
          <div class="alert alert-success py-2 small"><?= e($profileMsg) ?></div>
        <?php endif; ?>
        <?php if (!empty($profileErr)): ?>
          <div class="alert alert-danger py-2 small"><?= e($profileErr) ?></div>
        <?php endif; ?>

        <form method="POST">
          <?= getCsrfField() ?>
          <input type="hidden" name="action_update_profile" value="1">

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Full Name *</label>
            <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Email Address (Read-only)</label>
            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Mobile Number *</label>
            <div class="input-group">
              <span class="input-group-text bg-light">+91</span>
              <input type="tel" name="mobile" class="form-control" value="<?= e($user['mobile']) ?>" pattern="[6-9][0-9]{9}" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Gender</label>
            <select name="gender" class="form-select">
              <option value="male" <?= ($user['gender'] === 'male') ? 'selected' : '' ?>>Male</option>
              <option value="female" <?= ($user['gender'] === 'female') ? 'selected' : '' ?>>Female</option>
              <option value="other" <?= ($user['gender'] === 'other') ? 'selected' : '' ?>>Other</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-bold text-muted">Address / City</label>
            <textarea name="address" class="form-control" rows="2"><?= e($user['address'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn-st-primary">Save Changes</button>
        </form>
      </div>
    </div>

    <!-- Right: Change Password -->
    <div class="col-lg-6">
      <div class="bg-white p-4 rounded-4 border shadow-sm h-100">
        <h5 class="fw-bold mb-3"><i class="fas fa-lock text-primary me-2"></i>Change Password</h5>

        <?php if (!empty($passMsg)): ?>
          <div class="alert alert-success py-2 small"><?= e($passMsg) ?></div>
        <?php endif; ?>
        <?php if (!empty($passErr)): ?>
          <div class="alert alert-danger py-2 small"><?= e($passErr) ?></div>
        <?php endif; ?>

        <form method="POST">
          <?= getCsrfField() ?>
          <input type="hidden" name="action_change_password" value="1">

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Current Password *</label>
            <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">New Password *</label>
            <input type="password" name="new_password" class="form-control" placeholder="At least 6 characters" minlength="6" required>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-bold text-muted">Confirm New Password *</label>
            <input type="password" name="confirm_new_password" class="form-control" placeholder="Repeat new password" minlength="6" required>
          </div>

          <button type="submit" class="btn btn-st-outline">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
