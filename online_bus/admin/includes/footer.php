<?php
/**
 * Admin Shared Footer
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);
?>

  <!-- Toast Container -->
  <div id="st-toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;"></div>

  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Main Admin Scripts -->
  <script src="<?= $baseUrl ?>/assets/js/main.js"></script>

  <script>
    // Admin Mobile Sidebar Toggle
    document.getElementById('btnToggleSidebar')?.addEventListener('click', function() {
      document.getElementById('adminSidebar')?.classList.toggle('show');
    });
  </script>
</body>
</html>
