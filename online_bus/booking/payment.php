<?php
/**
 * Razorpay Payment Checkout Page
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$appConfig = require __DIR__ . '/../config/app.php';
$baseUrl = $appConfig['base_url'];
$db = getDB();

$pnr = cleanInput($_GET['pnr'] ?? '');
if (empty($pnr)) {
    header("Location: {$baseUrl}/index.php");
    exit;
}

// Fetch booking details
$stmt = $db->prepare("SELECT b.*, 
                             s.departure_date, s.departure_time, s.arrival_date, s.arrival_time,
                             bus.bus_name, bus.bus_type, bus.bus_number,
                             fc.name as from_city, tc.name as to_city
                      FROM bookings b
                      JOIN schedules s ON b.schedule_id = s.id
                      JOIN buses bus ON s.bus_id = bus.id
                      JOIN routes r ON s.route_id = r.id
                      JOIN indian_cities fc ON r.from_city_id = fc.id
                      JOIN indian_cities tc ON r.to_city_id = tc.id
                      WHERE b.pnr = ? LIMIT 1");
$stmt->execute([$pnr]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking reference not found. <a href='{$baseUrl}/index.php'>Return to Homepage</a>");
}

if ($booking['booking_status'] === 'confirmed') {
    header("Location: {$baseUrl}/booking/confirmation.php?pnr=" . urlencode($pnr));
    exit;
}

// Fetch Passengers
$pStmt = $db->prepare("SELECT * FROM booking_passengers WHERE booking_id = ? ORDER BY seat_number ASC");
$pStmt->execute([$booking['id']]);
$passengers = $pStmt->fetchAll();

$pageTitle = "Pay Securely – PNR: " . e($pnr) . " – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Razorpay Checkout SDK (Test Mode) -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <!-- Test Mode Notice Banner -->
      <div class="alert alert-info border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-3">
        <i class="fas fa-flask fs-3 text-primary"></i>
        <div>
          <h6 class="fw-bold mb-1">Razorpay Sandbox / Test Mode Active</h6>
          <p class="mb-0 small">
            This checkout operates in <strong>Razorpay TEST MODE</strong>. No actual money will be deducted. You can test with Razorpay sandbox cards, test UPI IDs, or click the Instant Test Simulation button below.
          </p>
        </div>
      </div>

      <!-- Booking Overview Card -->
      <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-3 mb-3">
          <div>
            <span class="badge bg-warning text-dark mb-1">Payment Pending</span>
            <h4 class="fw-bold mb-0">Booking Ref: <span class="text-primary"><?= e($booking['pnr']) ?></span></h4>
          </div>
          <div class="text-end">
            <span class="small text-muted d-block">Amount Due</span>
            <span class="fs-3 fw-bold text-primary"><?= formatPrice($booking['total_amount']) ?></span>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="text-muted small">Route & Bus</div>
            <div class="fw-bold text-dark fs-5"><?= e($booking['from_city']) ?> → <?= e($booking['to_city']) ?></div>
            <div class="text-secondary small"><?= e($booking['bus_name']) ?> (<?= e($booking['bus_type']) ?>)</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small">Journey Date & Time</div>
            <div class="fw-bold text-dark"><?= formatDateIndian($booking['journey_date']) ?></div>
            <div class="text-secondary small">Departs: <?= formatTime($booking['departure_time']) ?></div>
          </div>
        </div>

        <div class="p-3 bg-light rounded-3 mb-3 small">
          <div class="mb-1"><strong>Boarding Point:</strong> <?= e($booking['boarding_point']) ?></div>
          <div><strong>Dropping Point:</strong> <?= e($booking['dropping_point']) ?></div>
        </div>

        <h6 class="fw-bold text-dark mb-2">Passengers (<?= count($passengers) ?>)</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th>Seat</th>
                <th>Passenger Name</th>
                <th>Age / Gender</th>
                <th class="text-end">Seat Fare</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($passengers as $p): ?>
                <tr>
                  <td><span class="badge bg-primary"><?= e($p['seat_number']) ?></span></td>
                  <td class="fw-semibold"><?= e($p['passenger_name']) ?></td>
                  <td class="text-capitalize"><?= e($p['age']) ?> Yrs / <?= e($p['gender']) ?></td>
                  <td class="text-end"><?= formatPrice($p['seat_fare']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-2 border-top text-secondary small">
          <span>Base: <?= formatPrice($booking['base_amount']) ?> + GST: <?= formatPrice($booking['tax_amount']) ?> + Fee: <?= formatPrice($booking['convenience_fee']) ?></span>
          <span class="fw-bold text-dark fs-6">Total: <?= formatPrice($booking['total_amount']) ?></span>
        </div>
      </div>

      <!-- Payment Actions Card -->
      <div class="bg-white p-4 rounded-4 border shadow-sm text-center">
        <h5 class="fw-bold mb-3">Choose Your Payment Option</h5>

        <div class="d-grid gap-3 max-w-500 mx-auto">
          <!-- Button 1: Official Razorpay Test Checkout Modal -->
          <button type="button" class="btn btn-st-primary btn-lg justify-content-center" id="btn-rzp-pay">
            <i class="fas fa-lock me-2"></i> Pay with Razorpay Test Checkout (<?= formatPrice($booking['total_amount']) ?>)
          </button>

          <!-- Button 2: Instant Test Simulator (One-click Sandbox Confirmation) -->
          <button type="button" class="btn btn-outline-success btn-lg justify-content-center" id="btn-simulate-pay">
            <i class="fas fa-check-double me-2"></i> Instant Test Payment Simulation (1-Click)
          </button>
        </div>

        <div class="mt-4 pt-3 border-top text-center text-muted small">
          <i class="fas fa-shield-alt text-success me-1"></i> 256-Bit SSL Encrypted & RBI Guideline Compliant
          <div class="mt-2 d-flex justify-content-center gap-3">
            <span><i class="fab fa-cc-visa text-primary"></i> Visa</span>
            <span><i class="fab fa-cc-mastercard text-danger"></i> MasterCard</span>
            <span><i class="fas fa-mobile-alt text-success"></i> GooglePay / PhonePe / Paytm</span>
            <span><i class="fas fa-university text-info"></i> NetBanking</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const pnr = "<?= e($pnr) ?>";
  const baseUrl = "<?= $baseUrl ?>";
  const btnRzp = document.getElementById('btn-rzp-pay');
  const btnSimulate = document.getElementById('btn-simulate-pay');

  // 1. Razorpay Official Modal Trigger
  btnRzp.addEventListener('click', function () {
    btnRzp.disabled = true;
    btnRzp.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Initializing Razorpay...';

    fetch(`${baseUrl}/payment/create_order.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ pnr: pnr })
    })
    .then(res => res.json())
    .then(orderData => {
      if (!orderData.success) {
        showToast(orderData.message || 'Failed to create payment order.', 'danger');
        btnRzp.disabled = false;
        btnRzp.innerHTML = '<i class="fas fa-lock me-2"></i> Pay with Razorpay Checkout';
        return;
      }

      // Configure Razorpay Options
      const options = {
        key: orderData.key_id,
        amount: orderData.amount,
        currency: orderData.currency,
        name: orderData.merchant_name,
        description: orderData.merchant_description,
        order_id: orderData.order_id,
        prefill: {
          name: orderData.contact_name,
          email: orderData.contact_email,
          contact: orderData.contact_mobile
        },
        theme: {
          color: orderData.theme_color || '#1d4ed8'
        },
        handler: function (response) {
          // Verify payment on backend
          verifyPaymentOnBackend({
            pnr: pnr,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature: response.razorpay_signature
          });
        },
        modal: {
          ondismiss: function () {
            btnRzp.disabled = false;
            btnRzp.innerHTML = '<i class="fas fa-lock me-2"></i> Pay with Razorpay Checkout';
            showToast('Payment window closed. You can retry payment anytime.', 'warning');
          }
        }
      };

      const rzp = new Razorpay(options);
      rzp.on('payment.failed', function (response) {
        showToast(`Payment Failed: ${response.error.description}`, 'danger');
        btnRzp.disabled = false;
        btnRzp.innerHTML = '<i class="fas fa-lock me-2"></i> Retry Payment';
      });

      rzp.open();
    })
    .catch(err => {
      console.error(err);
      btnRzp.disabled = false;
      btnRzp.innerHTML = '<i class="fas fa-lock me-2"></i> Pay with Razorpay Checkout';
      showToast('Network error while launching Razorpay. Please use Test Simulation.', 'danger');
    });
  });

  // 2. Instant Test Simulation Handler
  btnSimulate.addEventListener('click', function () {
    if (!confirm('Proceed with instant simulated test payment verification?')) return;

    btnSimulate.disabled = true;
    btnSimulate.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Simulating Test Payment...';

    // Call create_order first to ensure order is registered
    fetch(`${baseUrl}/payment/create_order.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ pnr: pnr })
    })
    .then(res => res.json())
    .then(orderData => {
      const orderId = orderData.order_id || 'order_sim_' + Date.now();
      const mockPaymentId = 'pay_test_' + Math.random().toString(36).substring(2, 12);

      // Verify simulated payment
      verifyPaymentOnBackend({
        pnr: pnr,
        razorpay_order_id: orderId,
        razorpay_payment_id: mockPaymentId,
        razorpay_signature: 'simulated_test_sig',
        payment_method: 'upi_test'
      });
    })
    .catch(err => {
      console.error(err);
      btnSimulate.disabled = false;
      btnSimulate.innerHTML = '<i class="fas fa-check-double me-2"></i> Instant Test Payment Simulation';
      showToast('Error during simulation. Please try again.', 'danger');
    });
  });

  function verifyPaymentOnBackend(payload) {
    showToast('Verifying payment signature with Shreenathji Travels server...', 'info');

    fetch(`${baseUrl}/payment/verify_payment.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(resData => {
      if (resData.success) {
        showToast('Payment successful! Redirecting to ticket confirmation...', 'success');
        setTimeout(() => {
          window.location.href = resData.redirect;
        }, 800);
      } else {
        showToast(resData.message || 'Payment verification failed.', 'danger');
        btnRzp.disabled = false;
        btnSimulate.disabled = false;
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Network error during payment verification.', 'danger');
      btnRzp.disabled = false;
      btnSimulate.disabled = false;
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
