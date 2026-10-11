<?php
/**
 * Admin Shared Header
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdminLogin();
$currentAdmin = getCurrentAdmin();
$appConfig = require __DIR__ . '/../../config/app.php';
$baseUrl = $appConfig['base_url'];
$adminUrl = $appConfig['admin_url'];

$adminPageTitle = $adminPageTitle ?? 'Admin Portal – Shreenathji Travels';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($adminPageTitle) ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= $baseUrl ?>/assets/images/logo.svg">

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- FontAwesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <!-- Core CSS & Admin CSS -->
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css">
</head>
<body class="admin-body">
