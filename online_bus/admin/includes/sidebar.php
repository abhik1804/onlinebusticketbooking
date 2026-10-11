<?php
/**
 * Admin Sidebar Navigation
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar-brand">
    <img src="<?= $baseUrl ?>/assets/images/logo.svg" alt="Shreenathji Travels">
    <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.65rem;">ADMIN</span>
  </div>

  <ul class="admin-nav">
    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/dashboard.php" class="admin-nav-link <?= ($currentPage === 'dashboard.php') ? 'active' : '' ?>">
        <i class="fas fa-chart-pie"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/buses/index.php" class="admin-nav-link <?= ($currentDir === 'buses') ? 'active' : '' ?>">
        <i class="fas fa-bus"></i>
        <span>Bus Management</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/routes/index.php" class="admin-nav-link <?= ($currentDir === 'routes') ? 'active' : '' ?>">
        <i class="fas fa-route"></i>
        <span>Route Management</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/schedules/index.php" class="admin-nav-link <?= ($currentDir === 'schedules') ? 'active' : '' ?>">
        <i class="fas fa-calendar-alt"></i>
        <span>Schedules & Trips</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/bookings/index.php" class="admin-nav-link <?= ($currentDir === 'bookings') ? 'active' : '' ?>">
        <i class="fas fa-ticket-alt"></i>
        <span>All Bookings</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/users/index.php" class="admin-nav-link <?= ($currentDir === 'users') ? 'active' : '' ?>">
        <i class="fas fa-users"></i>
        <span>Registered Users</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/payments/index.php" class="admin-nav-link <?= ($currentDir === 'payments') ? 'active' : '' ?>">
        <i class="fas fa-credit-card"></i>
        <span>Razorpay Payments</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/reports/index.php" class="admin-nav-link <?= ($currentDir === 'reports') ? 'active' : '' ?>">
        <i class="fas fa-file-invoice-dollar"></i>
        <span>Reports & Analytics</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/settings/index.php" class="admin-nav-link <?= ($currentDir === 'settings' && $currentPage === 'index.php') ? 'active' : '' ?>">
        <i class="fas fa-sliders-h"></i>
        <span>Website Settings</span>
      </a>
    </li>

    <li class="admin-nav-item">
      <a href="<?= $adminUrl ?>/settings/faqs.php" class="admin-nav-link <?= ($currentDir === 'settings' && $currentPage === 'faqs.php') ? 'active' : '' ?>">
        <i class="fas fa-question-circle"></i>
        <span>Manage FAQs</span>
      </a>
    </li>
  </ul>

  <div class="admin-sidebar-footer">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: bold; font-size: 0.8rem;">
          <?= strtoupper(substr($currentAdmin['name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="small">
          <div class="fw-bold text-white"><?= e($currentAdmin['name'] ?? 'Admin') ?></div>
          <div class="text-secondary" style="font-size: 0.72rem;"><?= ucfirst(e($currentAdmin['role'] ?? 'super_admin')) ?></div>
        </div>
      </div>
      <a href="<?= $adminUrl ?>/logout.php" class="text-danger small" title="Logout">
        <i class="fas fa-sign-out-alt fs-5"></i>
      </a>
    </div>
  </div>
</aside>
