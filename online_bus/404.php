<?php
/**
 * 404 Not Found Page
 * Shreenathji Travels
 */

define('IN_APP', true);
$appConfig = require __DIR__ . '/config/app.php';
$baseUrl = $appConfig['base_url'];
$pageTitle = "Page Not Found – 404 – Shreenathji Travels";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5 text-center" style="min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
  <div style="font-size: 80px;">🛣️</div>
  <h1 class="display-3 fw-bold text-dark mt-2">404</h1>
  <h3 class="fw-bold mb-3">Road Not Found</h3>
  <p class="text-muted" style="max-width: 480px;">The bus route or page you are looking for has taken a detour or does not exist.</p>
  <div class="d-flex gap-2 mt-2">
    <a href="<?= $baseUrl ?>/index.php" class="btn btn-st-primary"><i class="fas fa-home me-1"></i> Back to Homepage</a>
    <a href="<?= $baseUrl ?>/buses/search.php" class="btn btn-st-outline"><i class="fas fa-search me-1"></i> Search Buses</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
