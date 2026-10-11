<?php
/**
 * 500 Internal Server Error Page
 * Shreenathji Travels
 */

define('IN_APP', true);
$appConfig = require __DIR__ . '/config/app.php';
$baseUrl = $appConfig['base_url'];
$pageTitle = "Service Temporarily Unavailable – 500 – Shreenathji Travels";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5 text-center" style="min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
  <div style="font-size: 80px;">⚙️</div>
  <h1 class="display-4 fw-bold text-dark mt-2">500</h1>
  <h3 class="fw-bold mb-3">Pit Stop Required</h3>
  <p class="text-muted" style="max-width: 480px;">We are experiencing a temporary maintenance glitch. Our engineers are already tuning up the engines.</p>
  <div class="d-flex gap-2 mt-2">
    <a href="<?= $baseUrl ?>/index.php" class="btn btn-st-primary"><i class="fas fa-home me-1"></i> Back to Homepage</a>
    <a href="tel:<?= e($appConfig['support_phone']) ?>" class="btn btn-st-outline"><i class="fas fa-phone-alt me-1"></i> Call Support</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
