<?php
/**
 * User Registration Page
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];

if (isUserLoggedIn()) {
    header("Location: {$baseUrl}/user/dashboard.php");
    exit;
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $fullName = cleanInput($_POST['full_name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $mobile = cleanInput($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $gender = cleanInput($_POST['gender'] ?? 'male');

    if (empty($fullName) || empty($email) || empty($mobile) || empty($password)) {
        $errorMessage = 'All required fields must be filled.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $errorMessage = 'Please enter a valid 10-digit Indian mobile number.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Password and confirm password do not match.';
    } else {
        $db = getDB();

        // Check duplicate email
        $chkEmail = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $chkEmail->execute([$email]);
        if ($chkEmail->fetch()) {
            $errorMessage = 'An account with this email address already exists. Please log in.';
        } else {
            // Check duplicate mobile
            $chkMobile = $db->prepare("SELECT id FROM users WHERE mobile = ? LIMIT 1");
            $chkMobile->execute([$mobile]);
            if ($chkMobile->fetch()) {
                $errorMessage = 'An account with this mobile number already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $insStmt = $db->prepare("INSERT INTO users (full_name, email, mobile, password_hash, gender, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                if ($insStmt->execute([$fullName, $email, $mobile, $passwordHash, $gender])) {
                    $newUserId = $db->lastInsertId();
                    loginUser([
                        'id' => $newUserId,
                        'full_name' => $fullName,
                        'email' => $email,
                        'mobile' => $mobile
                    ]);
                    header("Location: {$baseUrl}/user/dashboard.php?registered=1");
                    exit;
                } else {
                    $errorMessage = 'Registration failed due to a server error. Please try again.';
                }
            }
        }
    }
}

$pageTitle = "Create Account – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-8">
      <div class="bg-white p-4 p-md-5 rounded-4 border shadow-sm">
        <div class="text-center mb-4">
          <h3 class="fw-bold text-dark">Join Shreenathji Travels</h3>
          <p class="text-muted small">Create your account to manage bookings, earn discounts, and track buses live</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
          <div class="alert alert-danger py-2 small" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= e($errorMessage) ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <?= getCsrfField() ?>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Full Name *</label>
            <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required value="<?= e($_POST['full_name'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Email Address *</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Mobile Number *</label>
            <div class="input-group">
              <span class="input-group-text bg-light">+91</span>
              <input type="tel" name="mobile" class="form-control" placeholder="10-digit mobile number" pattern="[6-9][0-9]{9}" required value="<?= e($_POST['mobile'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Gender</label>
            <div class="d-flex gap-4">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male" checked>
                <label class="form-check-label small" for="genderMale">Male</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female">
                <label class="form-check-label small" for="genderFemale">Female</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="genderOther" value="other">
                <label class="form-check-label small" for="genderOther">Other</label>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Password *</label>
            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
          </div>

          <div class="mb-4">
            <label class="form-label small fw-bold text-muted">Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required minlength="6">
          </div>

          <button type="submit" class="btn btn-st-primary w-100 justify-content-center py-2 mb-3">
            Create Account <i class="fas fa-arrow-right ms-2"></i>
          </button>

          <div class="text-center small text-muted">
            Already registered? <a href="<?= $baseUrl ?>/user/login.php" class="fw-bold">Sign In here</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
