<?php
/**
 * Shared Navbar Template
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);
require_once __DIR__ . '/auth.php';
$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$isLoggedIn = isUserLoggedIn();
$userName = $_SESSION['user_name'] ?? 'Passenger';
?>

<!-- Top Notification Bar -->
<div class="top-notice-bar">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
      <span><i class="fas fa-phone-alt me-1 text-warning"></i> 24x7 Helpline: <a href="tel:<?= e($appConfig['support_phone']) ?>" class="fw-bold"><?= e($appConfig['support_phone']) ?></a></span>
      <span class="d-none d-md-inline text-muted">•</span>
      <span class="d-none d-md-inline"><i class="fas fa-envelope me-1 text-warning"></i> <?= e($appConfig['support_email']) ?></span>
    </div>
    <div class="d-flex align-items-center gap-3">
      <a href="<?= $baseUrl ?>/user/pnr_status.php"><i class="fas fa-ticket-alt me-1 text-warning"></i> Track PNR / Print Ticket</a>
      <span class="text-muted">•</span>
      <a href="<?= $baseUrl ?>/admin/login.php" target="_blank" class="badge bg-secondary text-white py-1 px-2" style="font-size: 0.72rem;"><i class="fas fa-lock me-1"></i> Admin</a>
    </div>
  </div>
</div>

<!-- Main Sticky Navbar -->
<nav class="navbar navbar-expand-lg st-navbar">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= $baseUrl ?>/index.php">
      <img src="<?= $baseUrl ?>/assets/images/logo.svg" alt="Shreenathji Travels Logo">
    </a>

    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#stMainNavbar" aria-controls="stMainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="stMainNavbar">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="<?= $baseUrl ?>/index.php">
            <i class="fas fa-home me-1"></i> Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'search.php') ? 'active' : '' ?>" href="<?= $baseUrl ?>/buses/search.php">
            <i class="fas fa-bus me-1"></i> Search Buses
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= $baseUrl ?>/index.php#popular-routes">
            <i class="fas fa-route me-1"></i> Popular Routes
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= $baseUrl ?>/index.php#why-us">
            <i class="fas fa-shield-alt me-1"></i> Why Choose Us
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= $baseUrl ?>/index.php#faqs">
            <i class="fas fa-question-circle me-1"></i> FAQs
          </a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <?php if ($isLoggedIn): ?>
          <div class="dropdown">
            <button class="btn btn-st-outline dropdown-toggle d-flex align-items-center gap-2" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle fs-5"></i>
              <span><?= e($userName) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userMenuBtn" style="border-radius: 12px; min-width: 200px;">
              <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>/user/dashboard.php"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard</a></li>
              <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>/user/my_bookings.php"><i class="fas fa-ticket-alt me-2 text-success"></i> My Bookings</a></li>
              <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>/user/profile.php"><i class="fas fa-user-cog me-2 text-secondary"></i> My Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item py-2 text-danger" href="<?= $baseUrl ?>/user/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= $baseUrl ?>/user/login.php" class="btn btn-st-outline">
            <i class="fas fa-sign-in-alt me-1"></i> Login
          </a>
          <a href="<?= $baseUrl ?>/user/register.php" class="btn btn-st-primary">
            <i class="fas fa-user-plus me-1"></i> Sign Up
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
