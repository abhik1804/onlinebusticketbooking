<?php
/**
 * Shreenathji Travels - Luxury Online Bus Booking System
 * Homepage (V2 Ultra-Premium Edition)
 */

define('IN_APP', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$appConfig = require __DIR__ . '/config/app.php';
$baseUrl = $appConfig['base_url'];

$db = getDB();

// 1. Fetch popular routes with minimum live fare
$popularRoutesStmt = $db->query("SELECT r.id, fc.name as from_city, tc.name as to_city, r.distance_km, r.estimated_duration,
                                MIN(s.base_fare) as min_fare
                                FROM routes r
                                JOIN indian_cities fc ON r.from_city_id = fc.id
                                JOIN indian_cities tc ON r.to_city_id = tc.id
                                LEFT JOIN schedules s ON r.id = s.route_id AND s.status = 'scheduled' AND s.departure_date >= CURRENT_DATE()
                                WHERE r.is_active = 1
                                GROUP BY r.id
                                LIMIT 6");
$popularRoutes = $popularRoutesStmt->fetchAll();

// 2. Fetch luxury fleet coaches for showcase
$fleetStmt = $db->query("SELECT id, bus_name, bus_number, bus_type, seating_layout, total_seats, primary_image, amenities 
                         FROM buses 
                         WHERE is_active = 1 
                         ORDER BY id ASC 
                         LIMIT 4");
$fleetBuses = $fleetStmt->fetchAll();

// 3. Fetch FAQs
$faqsStmt = $db->query("SELECT question, answer, category FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 8");
$faqs = $faqsStmt->fetchAll();

// Script & Style flags
$includeHomepageV2Styles = true;
$includeThreeBus = true;
$includeSearchScript = true;
$pageTitle = "Shreenathji Travels – Luxury Online Bus Booking System";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Critical Instant First-Paint Styles (Guaranteed against any browser cache) -->
<style>
.live-marquee-container {
  background: #090e17 !important;
  background: linear-gradient(90deg, #090e17 0%, #0f172a 50%, #090e17 100%) !important;
  border-bottom: 1px solid rgba(245, 158, 11, 0.25) !important;
  color: #e2e8f0 !important;
  padding: 10px 0 !important;
  font-size: 0.85rem !important;
  font-weight: 600 !important;
  overflow: hidden !important;
  position: relative !important;
  z-index: 1010 !important;
  width: 100% !important;
  display: block !important;
}
.live-marquee-track {
  display: flex !important;
  white-space: nowrap !important;
  animation: marqueeScroll 35s linear infinite !important;
  gap: 40px !important;
  width: max-content !important;
}
.hero-wrapper-v2 {
  position: relative !important;
  background: #0f172a !important;
  background: radial-gradient(circle at 15% 25%, #172554 0%, #0f172a 45%, #050811 100%) !important;
  color: #ffffff !important;
  padding: 70px 0 120px !important;
  overflow: hidden !important;
  min-height: 520px !important;
}
.hero-heading-v2 {
  font-size: 3.4rem !important;
  font-weight: 800 !important;
  line-height: 1.15 !important;
  color: #ffffff !important;
  letter-spacing: -0.03em !important;
  margin-bottom: 20px !important;
}
.hero-subtext-v2 {
  font-size: 1.15rem !important;
  color: #cbd5e1 !important;
  max-width: 580px !important;
  line-height: 1.7 !important;
  margin-bottom: 32px !important;
}
.hero-badge-pill {
  display: inline-flex !important;
  align-items: center !important;
  gap: 10px !important;
  background: rgba(255, 255, 255, 0.08) !important;
  padding: 8px 20px !important;
  border-radius: 9999px !important;
  border: 1px solid rgba(251, 191, 36, 0.35) !important;
  color: #fbbf24 !important;
  font-weight: 700 !important;
  font-size: 0.88rem !important;
  margin-bottom: 22px !important;
}
.hero-wrapper-v2 .btn-outline-light {
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.45) !important;
  background: rgba(255, 255, 255, 0.08) !important;
  backdrop-filter: blur(8px) !important;
  font-weight: 700 !important;
}
.text-gradient-gold {
  background: linear-gradient(135deg, #fef08a 0%, #f59e0b 50%, #d97706 100%) !important;
  -webkit-background-clip: text !important;
  -webkit-text-fill-color: transparent !important;
}
</style>

<!-- 1. Live Notification & Flash Sale Marquee -->
<div class="live-marquee-container" aria-label="Special Announcements">
  <div class="live-marquee-track">
    <div class="d-flex align-items-center gap-2">
      <span class="marquee-badge"><i class="fas fa-bolt"></i> FLASH SALE</span>
      <span>Use code <strong class="text-warning">SHREE20</strong> for 20% Instant Discount on Volvo Sleeper Bookings!</span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="marquee-badge" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-shield-alt"></i> SAFETY FIRST</span>
      <span>100% Sanitized Linen, GPS Live Tracking & Air-Suspension Smooth Rides</span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="marquee-badge" style="background: linear-gradient(135deg, #ec4899, #be185d);"><i class="fas fa-female"></i> LADIES SAFE ZONE</span>
      <span>Reserved Single Sleeper Berths for Solo Female Travelers with Verified Co-passengers</span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="marquee-badge"><i class="fas fa-clock"></i> 98.4% PUNCTUAL</span>
      <span>Guaranteed On-Time Departures with 24x7 Highway Assistance Support</span>
    </div>
    <!-- Duplicate loop segment for seamless continuous scroll -->
    <div class="d-flex align-items-center gap-2">
      <span class="marquee-badge"><i class="fas fa-bolt"></i> FLASH SALE</span>
      <span>Use code <strong class="text-warning">SHREE20</strong> for 20% Instant Discount on Volvo Sleeper Bookings!</span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="marquee-badge" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-shield-alt"></i> SAFETY FIRST</span>
      <span>100% Sanitized Linen, GPS Live Tracking & Air-Suspension Smooth Rides</span>
    </div>
  </div>
</div>

<!-- 2. Hero Section V2 with 3D Virtual Fleet Canvas -->
<section class="hero-wrapper-v2 position-relative">
  <div class="hero-glow-orb-1"></div>
  <div class="hero-glow-orb-2"></div>

  <div class="container position-relative" style="z-index: 5;">
    <div class="row align-items-center g-5">
      <!-- Left Hero Pitch -->
      <div class="col-lg-6">
        <div class="hero-badge-pill">
          <i class="fas fa-crown text-warning"></i>
          <span>India's Premier Luxury Coach Fleet</span>
        </div>

        <h1 class="hero-heading-v2">
          Travel In <br>
          <span class="text-gradient-gold">Royal Comfort</span>, <br>
          Reach On Time.
        </h1>

        <p class="hero-subtext-v2">
          Step into next-generation Volvo Multi-Axle & BharatBenz sleeper coaches. Enjoy sanitized memory-foam berths, individual climate controls, free high-speed Wi-Fi, and real-time live satellite GPS tracking.
        </p>

        <div class="d-flex align-items-center flex-wrap gap-3">
          <a href="#search-console" class="btn btn-st-gold btn-lg px-4 shadow">
            <i class="fas fa-ticket-alt me-2"></i> Book Tickets Now
          </a>
          <button type="button" class="btn btn-outline-light btn-lg px-4" onclick="switchConsoleTab('track')">
            <i class="fas fa-search-location me-2"></i> Track Bus / PNR
          </button>
        </div>

        <!-- Trust Statistics Row -->
        <div class="hero-trust-row">
          <div class="hero-trust-item">
            <i class="fas fa-users text-warning"></i>
            <div>
              <div class="hero-trust-title">500,000+</div>
              <div class="hero-trust-desc">Happy Travelers</div>
            </div>
          </div>
          <div class="hero-trust-item">
            <i class="fas fa-bus text-warning"></i>
            <div>
              <div class="hero-trust-title">150+</div>
              <div class="hero-trust-desc">Luxury Coaches</div>
            </div>
          </div>
          <div class="hero-trust-item">
            <i class="fas fa-star text-warning"></i>
            <div>
              <div class="hero-trust-title">4.8 / 5.0</div>
              <div class="hero-trust-desc">Verified Ratings</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right 3D Virtual Fleet Interactive Experience -->
      <div class="col-lg-6">
        <div class="canvas-3d-container position-relative" id="three-bus-canvas-wrap">
          <!-- Top Telemetry Badges -->
          <div class="canvas-hud-badge canvas-hud-left">
            <span class="pulse-dot"></span> 3D DIGITAL TWIN SIMULATOR
          </div>
          <div class="canvas-hud-badge canvas-hud-right">
            <i class="fas fa-shield-alt text-warning"></i> VOLVO 9600 B11R SLEEPER
          </div>

          <!-- Bottom Telemetry HUD & Controls -->
          <div class="canvas-telemetry-bottom">
            <div class="d-flex align-items-center gap-2">
              <div class="telemetry-chip">
                <i class="fas fa-tachometer-alt text-warning"></i> 82 KM/H EXPRESSWAY
              </div>
              <div class="telemetry-chip d-none d-sm-flex">
                <i class="fas fa-wind text-info"></i> AIR-SUSPENSION ACTIVE
              </div>
            </div>
            <div class="canvas-action-controls">
              <button type="button" class="btn-canvas-action" id="btn-toggle-view" title="Toggle Camera Tilt">
                <i class="fas fa-camera me-1"></i> Interactive View
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. Floating Next-Gen Booking Console -->
<div class="container search-console-wrapper" id="search-console">
  <div class="search-console-card">
    <!-- Tab Switcher -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div class="search-tab-bar" role="tablist">
        <button type="button" class="search-tab-btn active" id="tab-btn-book" onclick="switchConsoleTab('book')">
          <i class="fas fa-bus"></i> Book Bus Tickets
        </button>
        <button type="button" class="search-tab-btn" id="tab-btn-track" onclick="switchConsoleTab('track')">
          <i class="fas fa-satellite-dish"></i> Track Bus / PNR
        </button>
        <button type="button" class="search-tab-btn" id="tab-btn-offers" onclick="switchConsoleTab('offers')">
          <i class="fas fa-tag"></i> Special Deals & Vouchers
        </button>
      </div>

      <div class="text-muted small d-none d-md-flex align-items-center gap-2">
        <i class="fas fa-lock text-success"></i>
        <span>100% Safe Payments • Zero Cancellation Fee Option</span>
      </div>
    </div>

    <!-- TAB PANE 1: Bus Search & Booking -->
    <div id="pane-booking" class="console-tab-pane">
      <form action="<?= $baseUrl ?>/buses/search.php" method="GET" id="bus-search-form">
        <div class="row g-3 align-items-center">
          <!-- Leaving From -->
          <div class="col-lg-4 col-md-5">
            <div class="search-field-box">
              <label class="search-field-label-v2" for="from_city_input">
                <i class="fas fa-map-marker-alt text-primary"></i> Leaving From (Departure)
              </label>
              <input type="text" class="search-field-input-v2" id="from_city_input" name="from" placeholder="Type city or terminal..." autocomplete="off" value="Ahmedabad" required>
              <input type="hidden" id="from_city_id" name="from_id" value="1">
              <div class="search-field-subtext">Paldi, Geeta Mandir, CTM, Iscon Cross</div>
              <div class="city-suggestions-dropdown" id="from_suggestions"></div>
            </div>
          </div>

          <!-- Interactive Swap Button -->
          <div class="col-lg-1 col-md-2 text-center d-flex align-items-center justify-content-center">
            <button type="button" class="btn-swap-cities" id="btn-swap-cities" title="Swap Cities" aria-label="Swap departure and destination cities">
              <i class="fas fa-exchange-alt"></i>
            </button>
          </div>

          <!-- Going To -->
          <div class="col-lg-4 col-md-5">
            <div class="search-field-box">
              <label class="search-field-label-v2" for="to_city_input">
                <i class="fas fa-map-pin text-danger"></i> Going To (Destination)
              </label>
              <input type="text" class="search-field-input-v2" id="to_city_input" name="to" placeholder="Type city or terminal..." autocomplete="off" value="Mumbai" required>
              <input type="hidden" id="to_city_id" name="to_id" value="25">
              <div class="search-field-subtext">Borivali, Andheri, Sion, Dadar, Thane</div>
              <div class="city-suggestions-dropdown" id="to_suggestions"></div>
            </div>
          </div>

          <!-- Journey Date with Quick Date Buttons -->
          <div class="col-lg-3 col-md-12">
            <div class="search-field-box">
              <label class="search-field-label-v2" for="journey_date_input">
                <i class="fas fa-calendar-alt text-warning"></i> Date of Journey
              </label>
              <input type="date" class="search-field-input-v2" id="journey_date_input" name="date" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
              
              <!-- Quick Date Selection Chips -->
              <div class="quick-date-chips">
                <button type="button" class="quick-date-btn active" onclick="setQuickDate(0, this)">Today</button>
                <button type="button" class="quick-date-btn" onclick="setQuickDate(1, this)">Tomorrow</button>
                <button type="button" class="quick-date-btn" onclick="setQuickDate(2, this)">+2 Days</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit & Quick Routes Strip -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4 pt-3 border-top">
          <div class="quick-routes-bar">
            <span class="text-dark fw-bold me-2 small"><i class="fas fa-fire text-danger"></i> Popular:</span>
            <span class="quick-route-chip" data-from="Ahmedabad" data-to="Mumbai" data-from-id="1" data-to-id="25">Ahmedabad ⇄ Mumbai</span>
            <span class="quick-route-chip" data-from="Ahmedabad" data-to="Udaipur" data-from-id="1" data-to-id="46">Ahmedabad ⇄ Udaipur</span>
            <span class="quick-route-chip" data-from="Surat" data-to="Pune" data-from-id="2" data-to-id="26">Surat ⇄ Pune</span>
            <span class="quick-route-chip" data-from="Jaipur" data-to="New Delhi" data-from-id="45" data-to-id="69">Jaipur ⇄ Delhi</span>
            <span class="quick-route-chip" data-from="Ahmedabad" data-to="Rajkot" data-from-id="1" data-to-id="4">Ahmedabad ⇄ Rajkot</span>
          </div>

          <button type="submit" class="btn btn-st-gold btn-lg px-4" id="btn-search-buses">
            <i class="fas fa-search me-2"></i> Search Luxury Coaches
          </button>
        </div>
      </form>
    </div>

    <!-- TAB PANE 2: Instant PNR & Bus Tracking Widget -->
    <div id="pane-tracking" class="console-tab-pane" style="display: none;">
      <div class="row align-items-center g-4 py-2">
        <div class="col-lg-7">
          <h4 class="fw-bold mb-2 text-dark"><i class="fas fa-satellite text-primary me-2"></i> Instant Live Bus & PNR Tracking</h4>
          <p class="text-secondary small mb-3">
            Enter your unique 10-character booking PNR number to view live GPS coach movement, conductor phone number, exact boarding gate location, and download your printable e-ticket.
          </p>

          <form action="<?= $baseUrl ?>/user/pnr_status.php" method="GET" class="d-flex gap-2">
            <div class="input-group input-group-lg">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-ticket-alt"></i></span>
              <input type="text" name="pnr" class="form-control border-start-0 text-uppercase fw-bold" placeholder="Enter PNR (e.g. STB1234567)" required maxlength="20" style="letter-spacing: 0.05em;">
              <button type="submit" class="btn btn-st-primary px-4 fw-bold">
                <i class="fas fa-location-arrow me-1"></i> Check Status
              </button>
            </div>
          </form>
        </div>

        <div class="col-lg-5">
          <div class="p-3 rounded-4 bg-light border">
            <div class="d-flex align-items-center gap-3 mb-2">
              <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="fas fa-check"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small">Live Satellite Telemetry</div>
                <div class="text-muted" style="font-size: 0.75rem;">GPS refreshed every 30 seconds</div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle bg-primary text-white p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="fas fa-file-pdf"></i>
              </div>
              <div>
                <div class="fw-bold text-dark small">Instant WhatsApp Ticket PDF</div>
                <div class="text-muted" style="font-size: 0.75rem;">Download or print your boarding pass anytime</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB PANE 3: Special Deals & Vouchers -->
    <div id="pane-offers" class="console-tab-pane" style="display: none;">
      <div class="row g-3 py-2">
        <div class="col-md-6 col-lg-3">
          <div class="voucher-card p-3">
            <div>
              <span class="badge bg-danger mb-2">HOT DEAL</span>
              <h6 class="fw-bold mb-1">Flat 20% OFF</h6>
              <div class="text-muted small mb-2">On all Volvo Sleeper tickets</div>
              <div class="voucher-code-badge py-1 px-2 mb-2">SHREE20</div>
            </div>
            <button type="button" class="btn-copy-code" onclick="copyCouponCode('SHREE20', this)">Copy</button>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="voucher-card p-3">
            <div>
              <span class="badge bg-primary mb-2">FIRST TRIP</span>
              <h6 class="fw-bold mb-1">₹100 Flat Cash</h6>
              <div class="text-muted small mb-2">For new registered travelers</div>
              <div class="voucher-code-badge py-1 px-2 mb-2">FIRST100</div>
            </div>
            <button type="button" class="btn-copy-code" onclick="copyCouponCode('FIRST100', this)">Copy</button>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="voucher-card p-3">
            <div>
              <span class="badge bg-success mb-2">GROUP TRIP</span>
              <h6 class="fw-bold mb-1">15% Savings</h6>
              <div class="text-muted small mb-2">Book 3 or more berths together</div>
              <div class="voucher-code-badge py-1 px-2 mb-2">FAMILY15</div>
            </div>
            <button type="button" class="btn-copy-code" onclick="copyCouponCode('FAMILY15', this)">Copy</button>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="voucher-card p-3">
            <div>
              <span class="badge bg-warning text-dark mb-2">ROUND TRIP</span>
              <h6 class="fw-bold mb-1">₹150 Return OFF</h6>
              <div class="text-muted small mb-2">When booking return journey</div>
              <div class="voucher-code-badge py-1 px-2 mb-2">RETURN150</div>
            </div>
            <button type="button" class="btn-copy-code" onclick="copyCouponCode('RETURN150', this)">Copy</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 4. Quick Promotional Vouchers Banner -->
<section class="py-4">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="voucher-card">
          <div>
            <div class="text-warning fw-bold small"><i class="fas fa-gift me-1"></i> WELCOME OFFER</div>
            <h5 class="fw-bold mb-1">Flat 20% OFF</h5>
            <p class="text-muted small mb-2">Valid on Volvo Multi-Axle routes</p>
            <div class="voucher-code-badge">SHREE20</div>
          </div>
          <button type="button" class="btn-copy-code ms-3" onclick="copyCouponCode('SHREE20', this)">
            <i class="far fa-copy me-1"></i> Copy
          </button>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="voucher-card">
          <div>
            <div class="text-primary fw-bold small"><i class="fas fa-bolt me-1"></i> EXPRESS SAVINGS</div>
            <h5 class="fw-bold mb-1">₹100 Flat Discount</h5>
            <p class="text-muted small mb-2">Instant checkout concession</p>
            <div class="voucher-code-badge">FIRST100</div>
          </div>
          <button type="button" class="btn-copy-code ms-3" onclick="copyCouponCode('FIRST100', this)">
            <i class="far fa-copy me-1"></i> Copy
          </button>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="voucher-card">
          <div>
            <div class="text-success fw-bold small"><i class="fas fa-users me-1"></i> FAMILY & FRIENDS</div>
            <h5 class="fw-bold mb-1">Save 15% Group</h5>
            <p class="text-muted small mb-2">On booking 3+ passenger seats</p>
            <div class="voucher-code-badge">FAMILY15</div>
          </div>
          <button type="button" class="btn-copy-code ms-3" onclick="copyCouponCode('FAMILY15', this)">
            <i class="far fa-copy me-1"></i> Copy
          </button>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="voucher-card">
          <div>
            <div class="text-danger fw-bold small"><i class="fas fa-sync-alt me-1"></i> RETURN BONUS</div>
            <h5 class="fw-bold mb-1">₹150 Return Pass</h5>
            <p class="text-muted small mb-2">Auto applied on round trips</p>
            <div class="voucher-code-badge">RETURN150</div>
          </div>
          <button type="button" class="btn-copy-code ms-3" onclick="copyCouponCode('RETURN150', this)">
            <i class="far fa-copy me-1"></i> Copy
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 5. Popular Express Routes Section -->
<section class="section-padding bg-light" id="popular-routes">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
      <div>
        <div class="text-primary fw-bold text-uppercase small" style="letter-spacing: 0.08em;">Direct Daily Expressways</div>
        <h2 class="section-title">Popular Routes & Fares</h2>
        <p class="section-subtitle">Comfortable overnight sleeper coaches connecting western & central India</p>
      </div>
      <a href="<?= $baseUrl ?>/buses/search.php" class="btn btn-st-outline mb-3">
        View All 150+ Routes <i class="fas fa-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-4">
      <?php if (!empty($popularRoutes)): ?>
        <?php foreach ($popularRoutes as $idx => $r): ?>
          <div class="col-lg-4 col-md-6">
            <div class="route-card h-100 d-flex flex-column">
              <div class="route-card-img-wrap">
                <?php 
                  $imgs = [
                    'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=700&q=80',
                    'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?auto=format&fit=crop&w=700&q=80',
                    'https://images.unsplash.com/photo-1557223562-6c77ef16210f?auto=format&fit=crop&w=700&q=80',
                    'https://images.unsplash.com/photo-1598971861713-54ad16a7e72e?auto=format&fit=crop&w=700&q=80',
                    'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=700&q=80',
                    'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?auto=format&fit=crop&w=700&q=80'
                  ];
                  $imgUrl = $imgs[$idx % count($imgs)];
                ?>
                <img src="<?= $imgUrl ?>" alt="<?= e($r['from_city']) ?> to <?= e($r['to_city']) ?> Luxury Bus" class="route-card-img" loading="lazy">
                <div class="route-price-tag">
                  Starting <span><?= formatPrice($r['min_fare'] ?? 750) ?></span>
                </div>
              </div>

              <div class="route-card-body d-flex flex-column justify-content-between flex-grow-1">
                <div>
                  <div class="route-cities">
                    <span><?= e($r['from_city']) ?></span>
                    <i class="fas fa-arrow-right text-warning small"></i>
                    <span><?= e($r['to_city']) ?></span>
                  </div>
                  <div class="route-meta">
                    <span><i class="fas fa-road me-1 text-primary"></i> <?= e($r['distance_km']) ?> km</span>
                    <span><i class="fas fa-clock me-1 text-success"></i> <?= e($r['estimated_duration']) ?></span>
                    <span><i class="fas fa-shield-alt me-1 text-info"></i> Express</span>
                  </div>
                </div>

                <a href="<?= $baseUrl ?>/buses/search.php?from=<?= urlencode($r['from_city']) ?>&to=<?= urlencode($r['to_city']) ?>&date=<?= date('Y-m-d') ?>" class="btn btn-st-primary w-100 justify-content-center mt-3">
                  Book Bus Tickets <i class="fas fa-chevron-right ms-1"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- 6. Luxury Fleet Showcase Section -->
<section class="section-padding bg-white" id="fleet-showcase">
  <div class="container">
    <div class="text-center max-w-700 mx-auto mb-5">
      <div class="text-primary fw-bold text-uppercase small" style="letter-spacing: 0.08em;">World-Class Engineering</div>
      <h2 class="section-title">The Shreenathji Primo Fleet</h2>
      <p class="section-subtitle">Experience high-deck panoramic luxury coaches designed for whisper-quiet overnight journeys</p>
    </div>

    <div class="row g-4">
      <?php if (!empty($fleetBuses)): ?>
        <?php foreach ($fleetBuses as $fBus): ?>
          <?php 
            $amenitiesArr = json_decode($fBus['amenities'] ?? '[]', true) ?: [];
            $busImg = $fBus['primary_image'] ?: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80';
          ?>
          <div class="col-lg-6">
            <div class="fleet-card-v2">
              <div class="fleet-img-wrap">
                <img src="<?= e($busImg) ?>" alt="<?= e($fBus['bus_name']) ?>" class="fleet-img" loading="lazy">
                <span class="fleet-badge-tag">
                  <i class="fas fa-shield-alt me-1"></i> <?= e($fBus['bus_type']) ?>
                </span>
              </div>
              <div class="fleet-body">
                <div>
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h4 class="fw-bold text-dark mb-0"><?= e($fBus['bus_name']) ?></h4>
                    <span class="badge bg-primary text-white py-2 px-3 fw-bold"><?= e($fBus['bus_number']) ?></span>
                  </div>
                  <p class="text-secondary small mb-3">
                    Premium air-suspension chassis with ergonomic orthopaedic berths, private noise-dampening privacy curtains, and independent dual-zone temperature management.
                  </p>

                  <!-- Specs Pills -->
                  <div class="fleet-spec-list">
                    <span class="fleet-spec-pill"><i class="fas fa-chair text-primary"></i> <?= e($fBus['total_seats']) ?> Berths</span>
                    <span class="fleet-spec-pill"><i class="fas fa-arrows-alt-v text-success"></i> <?= ucwords(str_replace('_', ' ', $fBus['seating_layout'])) ?></span>
                    <span class="fleet-spec-pill"><i class="fas fa-wifi text-warning"></i> High-Speed Wi-Fi</span>
                    <span class="fleet-spec-pill"><i class="fas fa-plug text-info"></i> Type-C Fast Charge</span>
                    <span class="fleet-spec-pill"><i class="fas fa-tint text-primary"></i> Mineral Water</span>
                    <span class="fleet-spec-pill"><i class="fas fa-shield-virus text-danger"></i> Sanitized Linens</span>
                  </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                  <div>
                    <span class="text-muted small">Starting from</span>
                    <div class="fw-bold text-dark fs-5">₹799 <small class="text-muted fs-6">/ berth</small></div>
                  </div>
                  <a href="<?= $baseUrl ?>/buses/search.php?from=Ahmedabad&to=Mumbai" class="btn btn-st-gold px-4">
                    Book This Coach <i class="fas fa-arrow-right ms-1"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- 7. Live Virtual Expressway Radar Simulation Widget -->
<section class="section-padding bg-dark text-white position-relative" style="background: radial-gradient(circle at 10% 20%, #0a0f1d 0%, #0f172a 100%);">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <span class="badge bg-warning text-dark fw-bold mb-3 px-3 py-2"><i class="fas fa-satellite-dish me-1"></i> SATELLITE GPS TELEMETRY</span>
        <h2 class="fw-bold mb-3 text-white" style="font-size: 2.4rem;">
          Track Every Kilometer in <span class="text-gradient-gold">Real-Time</span>
        </h2>
        <p class="text-white-50 mb-4" style="line-height: 1.7;">
          Our entire fleet is connected to India's NAVIC & GPS satellite constellation. Family members can monitor live coach movement, current expressway velocity, rest stops, and exact ETA right from their mobile browser.
        </p>

        <div class="d-flex flex-column gap-3 mb-4">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-25 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
              <i class="fas fa-shield-check text-warning"></i>
            </div>
            <div>
              <div class="fw-bold text-white">Breathalyzer Certified Drivers</div>
              <div class="text-white-50 small">Every bus captain undergoes biometric safety checks before departure</div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-25 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
              <i class="fas fa-bell text-warning"></i>
            </div>
            <div>
              <div class="fw-bold text-white">Boarding SMS & WhatsApp Alerts</div>
              <div class="text-white-50 small">Automatic reminder sent 30 minutes before bus arrives at your stop</div>
            </div>
          </div>
        </div>

        <a href="<?= $baseUrl ?>/user/pnr_status.php" class="btn btn-st-gold btn-lg px-4">
          <i class="fas fa-search-location me-2"></i> Track Your Ticket Online
        </a>
      </div>

      <!-- Radar Telemetry Widget Card -->
      <div class="col-lg-7">
        <div class="radar-simulation-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <span class="pulse-dot"></span>
              <span class="fw-bold text-white small">ACTIVE EXPRESSWAY TRIP: ST-EXP-109</span>
            </div>
            <span class="badge bg-success py-1 px-3">ON SCHEDULE</span>
          </div>

          <div class="d-flex justify-content-between align-items-center text-white mb-2">
            <div>
              <div class="text-white-50 small">DEPARTURE</div>
              <div class="fw-bold fs-5">Ahmedabad (Paldi)</div>
              <div class="text-warning small"><i class="fas fa-clock me-1"></i> 21:00 PM (Departed)</div>
            </div>
            <div class="text-center px-3">
              <i class="fas fa-arrow-right text-white-50 fs-4"></i>
              <div class="text-white-50 small mt-1">525 KM Total</div>
            </div>
            <div class="text-end">
              <div class="text-white-50 small">DESTINATION</div>
              <div class="fw-bold fs-5">Mumbai (Borivali)</div>
              <div class="text-success small"><i class="fas fa-flag-checkered me-1"></i> 06:15 AM (ETA)</div>
            </div>
          </div>

          <!-- Pulsing Progress Track -->
          <div class="radar-progress-track">
            <div class="radar-progress-fill" style="width: 68%;">
              <div class="radar-bus-icon">
                <i class="fas fa-bus"></i>
              </div>
            </div>
          </div>

          <!-- Live Metrics Grid -->
          <div class="row g-3 pt-2">
            <div class="col-4">
              <div class="p-2 rounded bg-white bg-opacity-10 text-center">
                <div class="text-white-50" style="font-size: 0.72rem;">CURRENT SPEED</div>
                <div class="fw-bold text-warning fs-6">84 KM/H</div>
              </div>
            </div>
            <div class="col-4">
              <div class="p-2 rounded bg-white bg-opacity-10 text-center">
                <div class="text-white-50" style="font-size: 0.72rem;">NEXT HALT</div>
                <div class="fw-bold text-white fs-6">Surat Bypass (15m)</div>
              </div>
            </div>
            <div class="col-4">
              <div class="p-2 rounded bg-white bg-opacity-10 text-center">
                <div class="text-white-50" style="font-size: 0.72rem;">CABIN TEMP</div>
                <div class="fw-bold text-info fs-6">21.5°C AC</div>
              </div>
            </div>
          </div>

          <div class="mt-3 pt-3 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center text-white-50 small">
            <span><i class="fas fa-satellite me-1 text-primary"></i> Lock: 14 Satellites Active</span>
            <span>Bus Captain: Vikram Singh (Verified)</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 8. Why Choose Shreenathji Primo -->
<section class="section-padding bg-white" id="why-us">
  <div class="container">
    <div class="text-center max-w-700 mx-auto mb-5">
      <div class="text-primary fw-bold text-uppercase small" style="letter-spacing: 0.08em;">Uncompromised Excellence</div>
      <h2 class="section-title">Why 500,000+ Passengers Choose Us</h2>
      <p class="section-subtitle">We don't simply sell bus tickets — we deliver secure, punctual, and restorative travel experiences.</p>
    </div>

    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon-box">
            <i class="fas fa-satellite-dish"></i>
          </div>
          <h4 class="fw-bold mb-2">Live GPS & Shareable Links</h4>
          <p class="text-muted small">Share your real-time bus location with your family via WhatsApp. Track boarding points, tolls, and arrival times live.</p>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon-box">
            <i class="fas fa-bed"></i>
          </div>
          <h4 class="fw-bold mb-2">Sanitized Sleeper Berths</h4>
          <p class="text-muted small">Every passenger receives freshly laundered blankets, sanitized pillow covers, and privacy curtains in sealed packets.</p>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon-box">
            <i class="fas fa-female"></i>
          </div>
          <h4 class="fw-bold mb-2">Ladies-Reserved Safe Zone</h4>
          <p class="text-muted small">Special reserved single berths for solo female travelers with guaranteed verified women co-passengers only.</p>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon-box">
            <i class="fas fa-clock"></i>
          </div>
          <h4 class="fw-bold mb-2">98.4% On-Time Record</h4>
          <p class="text-muted small">Strict adherence to departure timetables, verified driver breathalyzer tests, and zero breakdown guarantee.</p>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon-box">
            <i class="fas fa-credit-card"></i>
          </div>
          <h4 class="fw-bold mb-2">100% Secure Razorpay Checkout</h4>
          <p class="text-muted small">Pay instantly using UPI, Google Pay, PhonePe, Credit/Debit cards, or Net Banking with zero hidden surcharge.</p>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon-box">
            <i class="fas fa-headset"></i>
          </div>
          <h4 class="fw-bold mb-2">24x7 Passenger Concierge</h4>
          <p class="text-muted small">Dedicated support helpline and WhatsApp assistance for boarding assistance, route queries, or luggage support.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 9. Customer Reviews & Ratings -->
<section class="section-padding" style="background-color: #f8fafc;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="text-primary fw-bold text-uppercase small" style="letter-spacing: 0.08em;">Verified Passenger Testimonials</div>
      <h2 class="section-title">What Our Travelers Say</h2>
      <p class="section-subtitle">Real feedback from verified passengers across Gujarat, Maharashtra, and Rajasthan</p>
    </div>

    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="review-card h-100 d-flex flex-column justify-content-between">
          <div>
            <div class="review-stars mb-2">
              <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
            <p class="text-secondary small fst-italic mb-3">
              "Booked Ahmedabad to Mumbai Volvo Sleeper. The bus was sparkling clean, blankets smelled fresh, and reached Borivali sharp at 6:10 AM. The seat selection layout made picking lower berth effortless!"
            </p>
          </div>
          <div class="reviewer-profile">
            <div class="reviewer-avatar">AP</div>
            <div>
              <div class="fw-bold text-dark">Aniket Patel</div>
              <div class="text-muted small">Frequent Traveler, Ahmedabad</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="review-card h-100 d-flex flex-column justify-content-between">
          <div>
            <div class="review-stars mb-2">
              <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
            <p class="text-secondary small fst-italic mb-3">
              "As a solo female traveler from Surat to Pune, the ladies-reserved seat feature and live location tracking gave my family total peace of mind. Razorpay checkout was lightning fast!"
            </p>
          </div>
          <div class="reviewer-profile">
            <div class="reviewer-avatar" style="background: linear-gradient(135deg, #ec4899, #f43f5e);">PS</div>
            <div>
              <div class="fw-bold text-dark">Priyanka Sharma</div>
              <div class="text-muted small">Software Engineer, Pune</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="review-card h-100 d-flex flex-column justify-content-between">
          <div>
            <div class="review-stars mb-2">
              <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
            <p class="text-secondary small fst-italic mb-3">
              "Superb experience on Udaipur route! The bus captain was extremely polite, charging sockets worked continuously, and the printable ticket PDF had all clear landmarks for boarding."
            </p>
          </div>
          <div class="reviewer-profile">
            <div class="reviewer-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">VR</div>
            <div>
              <div class="fw-bold text-dark">Vikram Rathore</div>
              <div class="text-muted small">Business Owner, Udaipur</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 10. FAQs Section with Live Search Filter -->
<section class="section-padding bg-white" id="faqs">
  <div class="container" style="max-width: 900px;">
    <div class="text-center mb-4">
      <div class="text-primary fw-bold text-uppercase small" style="letter-spacing: 0.08em;">Instant Help & Policies</div>
      <h2 class="section-title">Frequently Asked Questions</h2>
      <p class="section-subtitle">Everything you need to know about booking, cancellation, luggage, and ladies safety</p>
    </div>

    <!-- Live FAQ Search Filter Input -->
    <div class="mb-4">
      <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden">
        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control bg-light border-0" id="faq-search-input" placeholder="Type to filter FAQs (e.g. cancellation, baggage, ladies seats, refund)...">
        <button class="btn btn-light border-0 text-muted" type="button" id="btn-clear-faq-search" style="display: none;">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="accordion st-accordion" id="accordionFaqs">
      <?php if (!empty($faqs)): ?>
        <?php foreach ($faqs as $i => $faq): ?>
          <div class="accordion-item faq-item">
            <h3 class="accordion-header" id="heading<?= $i ?>">
              <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $i ?>">
                <?= e($faq['question']) ?>
              </button>
            </h3>
            <div id="collapse<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $i ?>" data-bs-parent="#accordionFaqs">
              <div class="accordion-body text-secondary faq-answer">
                <?= nl2br(e($faq['answer'])) ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div id="no-faq-matches" class="text-center py-4 text-muted" style="display: none;">
      <i class="fas fa-search fa-2x mb-2 text-secondary"></i>
      <p>No matching questions found. Please call our 24x7 helpline for quick assistance.</p>
    </div>
  </div>
</section>

<!-- 11. Quick PNR Tracking Banner -->
<section class="py-5" style="background: linear-gradient(135deg, #1d4ed8, #0f172a); color: #fff;">
  <div class="container text-center">
    <h3 class="fw-bold mb-3">Already Have a Booking?</h3>
    <p class="text-white-50 mb-4" style="max-width: 580px; margin: 0 auto;">Check live bus GPS tracking, download your printable PDF bus ticket, or manage your trip with your unique PNR number.</p>
    <a href="<?= $baseUrl ?>/user/pnr_status.php" class="btn btn-st-gold btn-lg shadow">
      <i class="fas fa-ticket-alt me-2"></i> Track Your Ticket by PNR
    </a>
  </div>
</section>

<!-- 12. Floating WhatsApp Concierge Widget -->
<a href="https://wa.me/919876543210?text=Hello%20Shreenathji%20Travels,%20I%20need%20assistance%20with%20my%20bus%20booking" target="_blank" rel="noopener noreferrer" class="floating-whatsapp-widget" title="Chat with support on WhatsApp">
  <i class="fab fa-whatsapp fs-5"></i>
  <span>Need Help? Chat</span>
</a>

<!-- Embedded UI Interaction Scripts for V2 Homepage -->
<script>
// 1. Console Tab Switcher
function switchConsoleTab(tabKey) {
  // Update Buttons
  document.querySelectorAll('.search-tab-btn').forEach(btn => btn.classList.remove('active'));
  document.getElementById('pane-booking').style.display = 'none';
  document.getElementById('pane-tracking').style.display = 'none';
  document.getElementById('pane-offers').style.display = 'none';

  if (tabKey === 'book') {
    document.getElementById('tab-btn-book')?.classList.add('active');
    document.getElementById('pane-booking').style.display = 'block';
  } else if (tabKey === 'track') {
    document.getElementById('tab-btn-track')?.classList.add('active');
    document.getElementById('pane-tracking').style.display = 'block';
    const pnrInput = document.querySelector('#pane-tracking input[name="pnr"]');
    if (pnrInput) pnrInput.focus();
  } else if (tabKey === 'offers') {
    document.getElementById('tab-btn-offers')?.classList.add('active');
    document.getElementById('pane-offers').style.display = 'block';
  }

  // Smooth scroll to console if clicked from hero
  const consoleEl = document.getElementById('search-console');
  if (consoleEl && window.scrollY < 200) {
    consoleEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// 2. Quick Date Button Selector
function setQuickDate(offsetDays, btnEl) {
  document.querySelectorAll('.quick-date-btn').forEach(b => b.classList.remove('active'));
  if (btnEl) btnEl.classList.add('active');

  const targetDate = new Date();
  targetDate.setDate(targetDate.getDate() + offsetDays);
  
  // Format YYYY-MM-DD
  const year = targetDate.getFullYear();
  const month = String(targetDate.getMonth() + 1).padStart(2, '0');
  const day = String(targetDate.getDate()).padStart(2, '0');
  const dateStr = `${year}-${month}-${day}`;

  const dateInput = document.getElementById('journey_date_input');
  if (dateInput) {
    dateInput.value = dateStr;
  }
}

// 3. 1-Click Coupon Code Copy
function copyCouponCode(code, btnElement) {
  navigator.clipboard.writeText(code).then(() => {
    const originalHtml = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-check text-success"></i> Copied!';
    btnElement.classList.add('bg-success', 'text-white');

    if (typeof showToast === 'function') {
      showToast('Coupon code ' + code + ' copied to clipboard!', 'success');
    }

    setTimeout(() => {
      btnElement.innerHTML = originalHtml;
      btnElement.classList.remove('bg-success', 'text-white');
    }, 2000);
  }).catch(() => {
    // Fallback prompt
    prompt('Copy this coupon code:', code);
  });
}

// 4. Interactive FAQ Live Search Filter
document.addEventListener('DOMContentLoaded', function () {
  const faqSearchInput = document.getElementById('faq-search-input');
  const clearFaqBtn = document.getElementById('btn-clear-faq-search');
  const faqItems = document.querySelectorAll('.faq-item');
  const noMatchesMsg = document.getElementById('no-faq-matches');

  if (faqSearchInput) {
    faqSearchInput.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      let matchCount = 0;

      if (clearFaqBtn) {
        clearFaqBtn.style.display = q.length > 0 ? 'inline-block' : 'none';
      }

      faqItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (!q || text.includes(q)) {
          item.style.display = '';
          matchCount++;
        } else {
          item.style.display = 'none';
        }
      });

      if (noMatchesMsg) {
        noMatchesMsg.style.display = (matchCount === 0 && q.length > 0) ? 'block' : 'none';
      }
    });

    if (clearFaqBtn) {
      clearFaqBtn.addEventListener('click', function () {
        faqSearchInput.value = '';
        faqSearchInput.dispatchEvent(new Event('input'));
        faqSearchInput.focus();
      });
    }
  }

  // 5. 3D Canvas Button Tilt Trigger
  const btnToggleView = document.getElementById('btn-toggle-view');
  if (btnToggleView) {
    let tilted = false;
    btnToggleView.addEventListener('click', function () {
      tilted = !tilted;
      this.classList.toggle('active');
      this.innerHTML = tilted 
        ? '<i class="fas fa-check text-warning me-1"></i> 3D Cinematic Mode' 
        : '<i class="fas fa-camera me-1"></i> Interactive View';
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
