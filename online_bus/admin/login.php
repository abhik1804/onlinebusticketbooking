<?php
/**
 * Admin Login Page
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$adminUrl = $appConfig['admin_url'];

if (isAdminLoggedIn()) {
    header("Location: {$adminUrl}/dashboard.php");
    exit;
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errorMessage = 'Please enter your admin email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            loginAdmin($admin);
            logAdminActivity($admin['id'], 'admin_login', 'Admin logged into management console.');
            header("Location: {$adminUrl}/dashboard.php");
            exit;
        } else {
            $errorMessage = 'Invalid admin credentials.';
            logAdminActivity(null, 'failed_admin_login', "Failed login attempt for email: {$email}");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal Login – Shreenathji Travels</title>
  <link rel="icon" type="image/svg+xml" href="<?= $baseUrl ?>/assets/images/logo.svg">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height: 100vh; background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%) !important;">

  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-4 col-md-6 col-sm-10">
        <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4" style="background: rgba(255, 255, 255, 0.98);">
          <div class="text-center mb-4">
            <img src="<?= $baseUrl ?>/assets/images/logo.svg" alt="Shreenathji Travels" style="height: 48px;" class="mb-3">
            <h4 class="fw-bold text-dark">Admin Console</h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Authorized Staff Only</span>
          </div>

          <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger py-2 small" role="alert">
              <i class="fas fa-exclamation-circle me-1"></i> <?= e($errorMessage) ?>
            </div>
          <?php endif; ?>

          <!-- Pre-filled Demo Credentials Box -->
          <div class="p-3 bg-light rounded-3 mb-4 small border">
            <div class="fw-bold text-dark mb-1"><i class="fas fa-key text-warning me-1"></i> Default Admin Credentials:</div>
            <div><strong>Email:</strong> <code>admin@shreenathji.com</code></div>
            <div><strong>Password:</strong> <code>Admin@12345</code></div>
          </div>

          <form method="POST">
            <?= getCsrfField() ?>

            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">Admin Email</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control" placeholder="admin@domain.com" required value="admin@shreenathji.com">
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-bold text-muted">Admin Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required value="Admin@12345">
              </div>
            </div>

            <button type="submit" class="btn btn-st-primary w-100 justify-content-center py-2 mb-3">
              Secure Sign In <i class="fas fa-arrow-right ms-2"></i>
            </button>

            <div class="text-center">
              <a href="<?= $baseUrl ?>/index.php" class="small text-muted text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Return to Main Website
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
