<?php
/**
 * User Login Page
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

$redirect = cleanInput($_GET['redirect'] ?? '');
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $identifier = cleanInput($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $errorMessage = 'Please enter your email/mobile and password.';
    } else {
        $db = getDB();
        // Lookup by email OR mobile
        $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR mobile = ?) AND is_active = 1 LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            if (!empty($redirect) && str_starts_with($redirect, '/')) {
                header("Location: {$redirect}");
            } else {
                header("Location: {$baseUrl}/user/dashboard.php");
            }
            exit;
        } else {
            $errorMessage = 'Invalid email/mobile or password.';
        }
    }
}

$pageTitle = "Login – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-8">
      <div class="bg-white p-4 p-md-5 rounded-4 border shadow-sm">
        <div class="text-center mb-4">
          <h3 class="fw-bold text-dark">Welcome Back</h3>
          <p class="text-muted small">Sign in to your Shreenathji Travels passenger account</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
          <div class="alert alert-danger py-2 small" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= e($errorMessage) ?>
          </div>
        <?php endif; ?>

        <!-- Quick Demo Credentials Hint -->
        <div class="p-3 bg-light rounded-3 mb-4 small border">
          <div class="fw-bold text-dark mb-1"><i class="fas fa-info-circle text-primary me-1"></i> Demo User Credentials:</div>
          <div><strong>Email/Mobile:</strong> <code>user@shreenathji.com</code> or <code>9876543210</code></div>
          <div><strong>Password:</strong> <code>User@12345</code></div>
        </div>

        <form method="POST">
          <?= getCsrfField() ?>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Email or Mobile Number</label>
            <input type="text" name="identifier" class="form-control" placeholder="user@domain.com or 10-digit mobile" required value="<?= e($_POST['identifier'] ?? 'user@shreenathji.com') ?>">
          </div>

          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-bold text-muted mb-0">Password</label>
            </div>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required value="User@12345">
          </div>

          <button type="submit" class="btn btn-st-primary w-100 justify-content-center py-2 mb-3">
            Sign In <i class="fas fa-arrow-right ms-2"></i>
          </button>

          <div class="text-center small text-muted">
            Don't have an account? <a href="<?= $baseUrl ?>/user/register.php" class="fw-bold">Sign Up now</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
