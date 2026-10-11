<?php
/**
 * Professional Printable E-Ticket
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
    die("Invalid PNR reference.");
}

// Fetch booking
$stmt = $db->prepare("SELECT b.*, 
                             s.departure_date, s.departure_time, s.arrival_date, s.arrival_time,
                             bus.bus_name, bus.bus_type, bus.bus_number,
                             fc.name as from_city, tc.name as to_city,
                             p.razorpay_payment_id, p.payment_method
                      FROM bookings b
                      JOIN schedules s ON b.schedule_id = s.id
                      JOIN buses bus ON s.bus_id = bus.id
                      JOIN routes r ON s.route_id = r.id
                      JOIN indian_cities fc ON r.from_city_id = fc.id
                      JOIN indian_cities tc ON r.to_city_id = tc.id
                      LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'captured'
                      WHERE b.pnr = ? LIMIT 1");
$stmt->execute([$pnr]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Ticket not found for PNR: " . htmlspecialchars($pnr));
}

// Fetch passengers
$pStmt = $db->prepare("SELECT * FROM booking_passengers WHERE booking_id = ? ORDER BY seat_number ASC");
$pStmt->execute([$booking['id']]);
$passengers = $pStmt->fetchAll();

// QR Code URL (Encoded validation URL)
$verifyUrl = "{$baseUrl}/user/pnr_status.php?pnr=" . urlencode($booking['pnr']);
$qrCodeApi = "https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=" . urlencode($verifyUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Ticket – <?= e($booking['pnr']) ?> – Shreenathji Travels</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #f1f5f9;
      color: #0f172a;
      padding: 30px 15px;
    }
    .ticket-container {
      max-width: 820px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 16px;
      border: 2px dashed #cbd5e1;
      padding: 35px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      position: relative;
    }
    .ticket-header-logo {
      height: 48px;
    }
    .ticket-watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-30deg);
      font-size: 80px;
      font-weight: 800;
      color: rgba(15, 23, 42, 0.03);
      pointer-events: none;
      user-select: none;
    }
    .ticket-meta-block {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 16px;
    }
    .badge-status-confirmed {
      background: #dcfce7;
      color: #166534;
      font-weight: 800;
      padding: 6px 14px;
      border-radius: 20px;
      display: inline-block;
    }
    @media print {
      body {
        background-color: #ffffff;
        padding: 0;
      }
      .no-print {
        display: none !important;
      }
      .ticket-container {
        border: 1px solid #000;
        box-shadow: none;
        border-radius: 0;
        padding: 20px;
        margin: 0;
        width: 100%;
        max-width: 100%;
      }
    }
  </style>
</head>
<body>

  <!-- Print Toolbar (Hidden during print) -->
  <div class="ticket-container mb-3 no-print p-3 d-flex justify-content-between align-items-center bg-white border-0 shadow-sm">
    <a href="<?= $baseUrl ?>/index.php" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i> Back to Homepage
    </a>
    <div class="d-flex gap-2">
      <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fas fa-print me-1"></i> Print Ticket / Save PDF
      </button>
    </div>
  </div>

  <!-- Main Ticket Sheet -->
  <div class="ticket-container">
    <div class="ticket-watermark">SHREENATHJI</div>

    <!-- Header Block -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4 flex-wrap gap-3">
      <div>
        <img src="<?= $baseUrl ?>/assets/images/logo.svg" alt="Shreenathji Travels" class="ticket-header-logo mb-2">
        <div class="text-muted small">
          <?= e($appConfig['office_address']) ?><br>
          Helpline: <?= e($appConfig['support_phone']) ?> • <?= e($appConfig['support_email']) ?>
        </div>
      </div>
      <div class="text-end">
        <span class="badge-status-confirmed mb-2">
          <i class="fas fa-check-circle me-1"></i> <?= strtoupper(e($booking['booking_status'])) ?>
        </span>
        <div class="fw-bold fs-4 text-primary">PNR: <?= e($booking['pnr']) ?></div>
        <div class="text-muted small">Booked: <?= date('d M Y, h:i A', strtotime($booking['created_at'])) ?></div>
      </div>
    </div>

    <!-- Journey Overview Block -->
    <div class="ticket-meta-block mb-4">
      <div class="row g-3 align-items-center">
        <div class="col-sm-5">
          <div class="text-muted small text-uppercase fw-bold">From (Origin)</div>
          <div class="fs-4 fw-bold text-dark"><?= e($booking['from_city']) ?></div>
          <div class="text-primary fw-semibold"><?= formatTime($booking['departure_time']) ?></div>
          <div class="text-muted small"><?= formatDateIndian($booking['journey_date']) ?></div>
        </div>

        <div class="col-sm-2 text-center text-muted">
          <i class="fas fa-bus fs-3 text-warning"></i>
          <div class="small fw-bold mt-1">DIRECT</div>
        </div>

        <div class="col-sm-5 text-sm-end">
          <div class="text-muted small text-uppercase fw-bold">To (Destination)</div>
          <div class="fs-4 fw-bold text-dark"><?= e($booking['to_city']) ?></div>
          <div class="text-primary fw-semibold"><?= formatTime($booking['arrival_time']) ?></div>
          <div class="text-muted small"><?= formatDateIndian($booking['arrival_date']) ?></div>
        </div>
      </div>
    </div>

    <!-- Bus & Route Details -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="p-3 bg-light rounded-3 h-100">
          <h6 class="fw-bold text-dark mb-2"><i class="fas fa-bus-alt text-primary me-2"></i>Coach Details</h6>
          <div class="small">
            <div><strong>Bus Name:</strong> <?= e($booking['bus_name']) ?></div>
            <div><strong>Coach Type:</strong> <?= e($booking['bus_type']) ?></div>
            <div><strong>Vehicle Number:</strong> <?= e($booking['bus_number']) ?></div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="p-3 bg-light rounded-3 h-100">
          <h6 class="fw-bold text-dark mb-2"><i class="fas fa-map-marker-alt text-danger me-2"></i>Boarding Details</h6>
          <div class="small">
            <div><strong>Boarding Point:</strong> <?= e($booking['boarding_point']) ?></div>
            <div><strong>Dropping Point:</strong> <?= e($booking['dropping_point']) ?></div>
            <div><strong>Reporting Time:</strong> 15 mins before departure</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Passengers Table & QR Code Block -->
    <div class="row g-3 mb-4 align-items-center">
      <div class="col-md-9">
        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-users text-primary me-2"></i>Passenger Details</h6>
        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr>
              <th>Seat</th>
              <th>Passenger Name</th>
              <th>Age</th>
              <th>Gender</th>
              <th class="text-end">Fare</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($passengers as $p): ?>
              <tr>
                <td><span class="badge bg-primary"><?= e($p['seat_number']) ?></span></td>
                <td class="fw-bold"><?= e($p['passenger_name']) ?></td>
                <td><?= e($p['age']) ?> Yrs</td>
                <td class="text-capitalize"><?= e($p['gender']) ?></td>
                <td class="text-end"><?= formatPrice($p['seat_fare']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="col-md-3 text-center">
        <img src="<?= $qrCodeApi ?>" alt="Ticket Verification QR" class="img-fluid border p-1 rounded-3 bg-white" style="width: 120px; height: 120px;">
        <div class="text-muted small mt-1" style="font-size: 0.72rem;">Scan to Verify</div>
      </div>
    </div>

    <!-- Fare & Transaction Block -->
    <div class="ticket-meta-block mb-4">
      <div class="row g-2 align-items-center small">
        <div class="col-md-6">
          <div><strong>Payment Status:</strong> <span class="text-success fw-bold text-uppercase"><?= e($booking['payment_status']) ?></span></div>
          <div><strong>Payment Ref / Gateway:</strong> <?= e($booking['razorpay_payment_id'] ?? 'Razorpay Online') ?></div>
          <div><strong>Primary Contact:</strong> <?= e($booking['contact_name']) ?> (<?= e($booking['contact_mobile']) ?>)</div>
        </div>
        <div class="col-md-6 text-md-end">
          <div>Base Fare: <?= formatPrice($booking['base_amount']) ?></div>
          <div>GST (5%): <?= formatPrice($booking['tax_amount']) ?></div>
          <div>Convenience Fee: <?= formatPrice($booking['convenience_fee']) ?></div>
          <div class="fs-5 fw-bold text-primary mt-1">Total Paid: <?= formatPrice($booking['total_amount']) ?></div>
        </div>
      </div>
    </div>

    <!-- Terms & Instructions -->
    <div class="border-top pt-3 text-secondary" style="font-size: 0.76rem; line-height: 1.5;">
      <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">Important Terms & Conditions:</h6>
      <ol class="mb-0 ps-3">
        <li>Please carry a valid government-issued photo ID (Aadhaar, Driving License, Voter ID, Passport) during the journey.</li>
        <li>Passengers must report at the boarding point at least 15 minutes prior to the scheduled departure time.</li>
        <li>Cancellation Policy: >24h before departure: 90% refund; 12–24h: 70% refund; <12h: non-refundable.</li>
        <li>For 24/7 assistance or bus location tracking, call helpline: <strong><?= e($appConfig['support_phone']) ?></strong>.</li>
      </ol>
    </div>
  </div>

</body>
</html>
