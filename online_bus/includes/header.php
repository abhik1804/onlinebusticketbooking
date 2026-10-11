<?php
/**
 * Shared Header Template
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);
$appConfig = require __DIR__ . '/../config/app.php';
$pageTitle = $pageTitle ?? ($appConfig['app_name'] . ' – ' . $appConfig['tagline']);
$pageDescription = $pageDescription ?? 'Book luxury bus tickets online with Shreenathji Travels. AC Volvo, BharatBenz Sleeper coaches with live tracking, sanitized buses, and secure Razorpay payments.';
$baseUrl = $appConfig['base_url'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="keywords" content="Shreenathji Travels, bus booking, Volvo bus, Sleeper bus, luxury bus tickets, Gujarat to Mumbai bus">
  <meta name="theme-color" content="#0f172a">

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= $baseUrl ?>/assets/images/logo.svg">

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom Core Design System (with Cache-Busting) -->
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css?v=<?= file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time() ?>">
  <?php if (!empty($includeHomepageV2Styles)): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/homepage-v2.css?v=<?= file_exists(__DIR__ . '/../assets/css/homepage-v2.css') ? filemtime(__DIR__ . '/../assets/css/homepage-v2.css') : time() ?>">
  <?php endif; ?>
  <?php if (!empty($includeSeatStyles)): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/seat-layout.css?v=<?= file_exists(__DIR__ . '/../assets/css/seat-layout.css') ? filemtime(__DIR__ . '/../assets/css/seat-layout.css') : time() ?>">
  <?php endif; ?>

  <!-- Three.js CDN for 3D animated canvas -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

  <script>
    window.ST_BASE_URL = "<?= $baseUrl ?>";
  </script>
</head>
<body>
