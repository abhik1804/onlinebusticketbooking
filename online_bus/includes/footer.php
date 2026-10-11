<?php
/**
 * Shared Footer Template
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);
$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
?>

<footer class="st-footer">
  <div class="container">
    <div class="row g-4">
      <!-- Col 1: About & Helpline -->
      <div class="col-lg-4 col-md-6">
        <img src="<?= $baseUrl ?>/assets/images/logo.svg" alt="Shreenathji Travels" class="footer-logo">
        <p class="text-secondary small mt-2">
          Shreenathji Travels is India's premier luxury bus operator, connecting major cultural and business hubs with unmatched safety, punctuality, and sleeper comfort.
        </p>
        <div class="mt-3">
          <div class="d-flex align-items-center gap-2 mb-2 text-white-50 small">
            <i class="fas fa-map-marker-alt text-warning"></i>
            <span><?= e($appConfig['office_address']) ?></span>
          </div>
          <div class="d-flex align-items-center gap-2 mb-2 text-white-50 small">
            <i class="fas fa-phone-alt text-warning"></i>
            <span><?= e($appConfig['support_phone']) ?></span>
          </div>
          <div class="d-flex align-items-center gap-2 text-white-50 small">
            <i class="fas fa-envelope text-warning"></i>
            <span><?= e($appConfig['support_email']) ?></span>
          </div>
        </div>
      </div>

      <!-- Col 2: Quick Links -->
      <div class="col-lg-2 col-md-6">
        <h5 class="footer-title">Quick Links</h5>
        <ul class="footer-links">
          <li><a href="<?= $baseUrl ?>/index.php"><i class="fas fa-chevron-right me-1 small"></i> Home</a></li>
          <li><a href="<?= $baseUrl ?>/buses/search.php"><i class="fas fa-chevron-right me-1 small"></i> Search Buses</a></li>
          <li><a href="<?= $baseUrl ?>/user/pnr_status.php"><i class="fas fa-chevron-right me-1 small"></i> Track PNR</a></li>
          <li><a href="<?= $baseUrl ?>/user/my_bookings.php"><i class="fas fa-chevron-right me-1 small"></i> My Bookings</a></li>
          <li><a href="<?= $baseUrl ?>/admin/login.php"><i class="fas fa-chevron-right me-1 small"></i> Operator Login</a></li>
        </ul>
      </div>

      <!-- Col 3: Popular Routes -->
      <div class="col-lg-3 col-md-6">
        <h5 class="footer-title">Top Routes</h5>
        <ul class="footer-links">
          <li><a href="<?= $baseUrl ?>/buses/search.php?from=Ahmedabad&to=Mumbai"><i class="fas fa-bus text-warning me-1 small"></i> Ahmedabad to Mumbai</a></li>
          <li><a href="<?= $baseUrl ?>/buses/search.php?from=Surat&to=Pune"><i class="fas fa-bus text-warning me-1 small"></i> Surat to Pune</a></li>
          <li><a href="<?= $baseUrl ?>/buses/search.php?from=Ahmedabad&to=Udaipur"><i class="fas fa-bus text-warning me-1 small"></i> Ahmedabad to Udaipur</a></li>
          <li><a href="<?= $baseUrl ?>/buses/search.php?from=Jaipur&to=New+Delhi"><i class="fas fa-bus text-warning me-1 small"></i> Jaipur to New Delhi</a></li>
          <li><a href="<?= $baseUrl ?>/buses/search.php?from=Bengaluru&to=Panaji"><i class="fas fa-bus text-warning me-1 small"></i> Bengaluru to Goa</a></li>
        </ul>
      </div>

      <!-- Col 4: Payment & Security -->
      <div class="col-lg-3 col-md-6">
        <h5 class="footer-title">Secure Payments</h5>
        <p class="text-secondary small">
          All transactions are secured with 256-bit SSL encryption powered by Razorpay Test Gateway.
        </p>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <span class="badge bg-dark border border-secondary p-2"><i class="fab fa-cc-visa me-1 text-primary"></i> VISA</span>
          <span class="badge bg-dark border border-secondary p-2"><i class="fab fa-cc-mastercard me-1 text-danger"></i> MasterCard</span>
          <span class="badge bg-dark border border-secondary p-2"><i class="fas fa-mobile-alt me-1 text-success"></i> UPI / QR</span>
          <span class="badge bg-dark border border-secondary p-2"><i class="fas fa-university me-1 text-info"></i> Net Banking</span>
        </div>
        <div class="mt-4">
          <h6 class="text-white-50 small mb-2 text-uppercase fw-bold">Follow Us</h6>
          <div class="d-flex gap-2">
            <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="fab fa-instagram"></i></a>
            <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="fab fa-twitter"></i></a>
            <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="fab fa-whatsapp"></i></a>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        &copy; <?= date('Y') ?> Shreenathji Travels. All Rights Reserved. Crafted for premium passenger comfort.
      </div>
      <div class="d-flex gap-3">
        <a href="#" class="text-muted small">Privacy Policy</a>
        <a href="#" class="text-muted small">Terms of Service</a>
        <a href="#" class="text-muted small">Cancellation Policy</a>
      </div>
    </div>
  </div>
</footer>

<!-- Toast Container Placeholder -->
<div id="st-toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;"></div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Main JS -->
<script src="<?= $baseUrl ?>/assets/js/main.js"></script>

<?php if (!empty($includeSearchScript)): ?>
  <script src="<?= $baseUrl ?>/assets/js/search.js"></script>
<?php endif; ?>

<?php if (!empty($includeSeatScript)): ?>
  <script src="<?= $baseUrl ?>/assets/js/seat-selection.js"></script>
<?php endif; ?>

<?php if (!empty($includeThreeBus)): ?>
  <script src="<?= $baseUrl ?>/assets/js/three-bus.js"></script>
<?php endif; ?>

</body>
</html>
