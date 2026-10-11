# Shreenathji Travels – Online Bus Booking System

A full-stack, enterprise-grade, luxury online bus booking web application built with **Core PHP 8+ (PDO)**, **MySQL**, **HTML5**, **CSS3**, **JavaScript (ES6+)**, **Bootstrap 5**, **AJAX**, **Three.js**, and **Razorpay Payment Gateway (TEST MODE)**.

---

## 🌟 Key Features

### 🚌 Passenger Portal
- **Modern 3D Animated Hero**: Three.js procedural 3D luxury coach driving continuously on an asphalt road with rotating alloy wheels, glowing LED headlights, day/night horizon, dynamic clouds, and interactive mouse-pan perspective.
- **Comprehensive Indian City Autocomplete**: Seamless search covering 150+ Indian cities across all states and union territories, with state tags, popular city chips, keyboard navigation, and From/To swap button.
- **Route Availability & Past-Date Protection**: Strictly filters active bus schedules on selected dates. Enforces Indian Standard Time (IST) local date checks on both frontend and backend to block past dates.
- **Dynamic Bus Filtering & Sorting**: Multi-filter without reloading:
  - Coach Type: Sleeper (Berths), Seater (Pushback), Mixed (Hybrid)
  - AC / Non-AC Coaches
  - Departure Time Slots: Morning, Afternoon, Evening, Night
  - Price Range Slider (₹400 – ₹2500)
  - Sorting: Cheapest Fare, Earliest Departure, Latest Departure, Most Seats
- **Interactive Seat Selection Layout**:
  - Realistic bus cabin with steering wheel cockpit and aisle
  - Lower Deck & Upper Deck tabs for sleeper and multi-axle coaches
  - Status color coding: Available, Selected (orange/gold), Booked/Locked (padlocked), Ladies-Reserved (pink badge)
  - Real-time total fare counter and seat list drawer
- **Atomic Concurrency Seat Locking**: Temporary 10-minute seat lock with server-side expiration in MySQL transactions to reduce double booking. Displays an active countdown timer to passengers during checkout.
- **Multi-Passenger Manifest**: Dynamic form generation for every selected seat with name, age, gender, and contact details.
- **Razorpay Payment Integration (TEST MODE)**:
  - Server-side Razorpay order generation with key protection
  - Official Razorpay Test Checkout modal (UPI, Cards, NetBanking, Wallets)
  - Cryptographic HMAC-SHA256 signature verification (`hash_hmac('sha256', order_id . '|' . payment_id, secret)`)
  - 1-Click Instant Test Payment simulation for offline/developer testing
  - Webhook endpoint with signature verification and duplicate-event idempotency protection
- **Printable E-Ticket**:
  - Formatted ticket with Shreenathji Travels branding, PNR reference, bus specifications, boarding/dropping landmarks, and terms
  - Scannable QR code verification
  - Clean `@media print` layout for PDF export or physical printing
- **Passenger Dashboard & Self-Service**:
  - My Bookings history: Upcoming, Completed, and Cancelled trips
  - Automated cancellation refund calculator: >24h (90% refund), 12-24h (70% refund), <12h (non-refundable)
  - Track PNR & ticket status without logging in

---

### 🛡️ Admin Management Console (`/admin/`)
- **Separate Authentication**: Completely isolated admin sessions (`$_SESSION['admin_id']`) with bcrypt verification, role-based controls, and CSRF protection.
- **Dynamic Real-Time Dashboard**:
  - Live counts: Registered users, active coaches, total routes, today's trips, confirmed bookings, pending payments, cancellations
  - Total verified revenue from captured Razorpay payments
  - Chart.js Monthly Revenue Trend and Booking Status Doughnut breakdown
  - Top popular routes ranking
- **Fleet Management**:
  - Add, edit, activate/deactivate, and delete buses
  - Configures coach type, layout (`seater_2x2`, `sleeper_2x1`, `mixed_seater_sleeper`), total capacity, base fare, and amenities
  - **Dual Image Functionality**: Admin can either upload image files (JPG/PNG/WebP with MIME validation and secure storage in `assets/uploads/buses/`) OR provide direct public image URLs (with SSRF protection and live preview)
  - Automatically generates `bus_seats` records so new coaches are immediately ready for bookings
- **Route Management**:
  - Create routes with source and destination cities chosen from the Indian cities database
  - Configure distance (km), estimated duration, boarding points, and dropping points
- **Schedule Management**:
  - Assign buses to routes on single dates or bulk-generate recurring trips (7, 14, 30 days)
  - Configure departure & arrival times (supports next-day arrivals)
  - Block/Unblock specific seats for maintenance or VIP holds
  - Schedule conflict prevention for the same vehicle
- **Booking Manifests & Export**:
  - Search bookings by PNR, passenger name, mobile, email, or date
  - View full passenger breakdown and payment status
  - Admin cancellation and refund override
  - One-click CSV export with Excel UTF-8 BOM
- **User Management**:
  - Search registered passengers, booking history, and toggle active/suspended status
- **Payment Gateway & Portal Settings**:
  - Edit helpline phone numbers, office address, GST tax rate, convenience fees
  - Configure Razorpay Test Key ID and Key Secret
  - Custom cancellation refund policy percentages
  - FAQ manager (Add, edit, delete FAQs)
  - Audit activity logs

---

## 💻 Tech Stack & Dependencies

| Layer | Technologies |
|---|---|
| **Backend** | Core PHP 8+ (PDO, Strict Types, Prepared Statements) |
| **Database** | MySQL (InnoDB, UTF-8mb4, Normalized Foreign Keys) |
| **Styling** | Vanilla CSS3 (Custom Design System, Glassmorphism) + Bootstrap 5.3.2 |
| **Icons & Fonts**| FontAwesome 6, Google Fonts (Plus Jakarta Sans) |
| **3D & Visuals** | Three.js (WebGL Procedural Canvas Animation) |
| **Analytics** | Chart.js 4.4 |
| **Payment** | Razorpay Checkout SDK (Test Mode) + HMAC-SHA256 Backend Verification |
| **Server** | Apache / XAMPP on Windows |

---

## 🚀 Quick Setup & Installation

### Step 1: Ensure XAMPP Services are Running
Start **Apache** and **MySQL** in your XAMPP Control Panel.

### Step 2: Database Initialization
Open PowerShell or your command prompt and run the turnkey installer:
```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\online_bus\database\install.php"
```
*Alternatively, you can visit `http://localhost/online_bus/database/install.php` in your web browser.*

This will automatically:
1. Create the `shreenathji_travels` database.
2. Import all tables from `database/database.sql`.
3. Seed 151 comprehensive Indian cities from `database/seed_cities.sql`.
4. Create default admins, website settings, sample luxury buses, and seat maps.
5. Generate hundreds of active schedules for current and upcoming dates.
6. Create sample verified bookings for immediate demonstration.

---

## 🔑 Default Login Credentials

### Admin Console (`http://localhost/online_bus/admin/`)
- **Email:** `admin@shreenathji.com`
- **Password:** `Admin@12345`

### Passenger Account (`http://localhost/online_bus/user/login.php`)
- **Email / Mobile:** `user@shreenathji.com` or `9876543210`
- **Password:** `User@12345`

*(You can also register a new passenger account anytime via `http://localhost/online_bus/user/register.php`)*

---

## 💳 Razorpay Test Mode Configuration

Razorpay settings are managed in `config/razorpay.php` and via the Admin Settings page:

```php
return [
    'mode' => 'test',
    'key_id' => 'rzp_test_1DP5mmOlF5G5ag',
    'key_secret' => 's9P7qW9t0Zk1xY2a3b4c5d6e',
    'webhook_secret' => 'st_whsec_test_998877',
    'currency' => 'INR',
    'merchant_name' => 'Shreenathji Travels',
    'theme_color' => '#1d4ed8'
];
```

### How Live Payments Can Be Enabled Later:
1. Sign up on [Razorpay Dashboard](https://dashboard.razorpay.com/).
2. Generate Live API Keys (`rzp_live_...`).
3. Update `key_id` and `key_secret` in `config/razorpay.php` or in `/admin/settings/index.php`.
4. Switch `'mode' => 'live'`.
*(The app defaults strictly to TEST MODE to prevent accidental live charges during development.)*

---

## 📁 Directory Structure

```
online_bus/
├── .htaccess
├── index.php                      # Homepage with 3D bus canvas, search, featured routes
├── 404.php                        # Custom 404 road detour error page
├── 500.php                        # Custom 500 server error page
├── config/
│   ├── app.php                    # Application settings (Tax rate, fee, timezone)
│   ├── database.php               # PDO MySQL configuration
│   └── razorpay.php               # Razorpay test keys and settings
├── includes/
│   ├── db.php                     # PDO singleton database connection
│   ├── auth.php                   # Authentication helpers for user & admin
│   ├── functions.php              # Utility helpers, PNR generator, currency, image handlers
│   ├── csrf.php                   # CSRF token generator & verification
│   ├── header.php                 # Global HTML5 header
│   ├── navbar.php                 # Sticky glassmorphism navigation
│   └── footer.php                 # Global footer
├── assets/
│   ├── css/
│   │   ├── style.css              # Core design system tokens & responsive classes
│   │   ├── seat-layout.css        # Seater & Sleeper cabin layout styles
│   │   └── admin.css              # Admin sidebar, tables, cards, stat badges
│   ├── js/
│   │   ├── main.js                # Sticky navbar, toast notifications, UI helpers
│   │   ├── three-bus.js           # Three.js 3D animated bus canvas
│   │   ├── search.js              # City autocomplete & search form validation
│   │   └── seat-selection.js      # Interactive seat picker & lock triggers
│   ├── images/
│   │   └── logo.svg               # SVG logo emblem
│   └── uploads/
│       ├── buses/                 # Stored bus upload photographs
│       └── profiles/
├── api/
│   ├── cities.php                 # AJAX Indian city autocomplete endpoint
│   ├── lock_seats.php             # Atomic seat holding endpoint (10-min expiry)
│   └── release_seats.php          # Seat release endpoint
├── buses/
│   └── search.php                 # Multi-filter search results page
├── booking/
│   ├── select_seats.php           # Graphical seat selection page
│   ├── passenger_details.php      # Passenger details form & lock countdown
│   ├── payment.php                # Razorpay checkout & test simulation page
│   ├── confirmation.php           # Booking confirmed success screen
│   └── cancel.php                 # Cancellation refund handler
├── payment/
│   ├── create_order.php           # Razorpay order creator (server-side)
│   ├── verify_payment.php         # HMAC-SHA256 signature verification endpoint
│   └── webhook.php                # Razorpay webhook listener
├── user/
│   ├── register.php               # User registration
│   ├── login.php                  # User login (email or mobile)
│   ├── logout.php                 # User logout
│   ├── dashboard.php              # Passenger dashboard
│   ├── profile.php                # Profile & password management
│   ├── my_bookings.php            # Trips history & cancellation modal
│   ├── ticket.php                 # Printable e-ticket with QR code
│   └── pnr_status.php             # Public PNR lookup
├── admin/
│   ├── index.php                  # Admin router
│   ├── login.php                  # Admin login
│   ├── logout.php                 # Admin logout
│   ├── dashboard.php              # Analytics dashboard with Chart.js
│   ├── includes/
│   │   ├── header.php             # Admin layout header
│   │   ├── sidebar.php            # Admin sidebar
│   │   └── footer.php             # Admin layout footer
│   ├── buses/                     # Bus fleet CRUD & dual image management
│   ├── routes/                    # Route CRUD with Indian cities
│   ├── schedules/                 # Trips generator & seat blocker
│   ├── bookings/                  # Bookings manifest & CSV export
│   ├── users/                     # Passenger management & suspension
│   ├── payments/                  # Razorpay transactions list & CSV export
│   ├── reports/                   # Date range analytics
│   └── settings/                  # Brand, tax, policy & FAQs management
└── database/
    ├── database.sql               # Database schema
    ├── seed_cities.sql            # Comprehensive Indian cities seed
    ├── sample_data.sql            # Demo coaches, routes, settings, FAQs
    └── install.php                # CLI & Web turnkey database installer
```

---

## 🔒 Security Best Practices Implemented
- **PDO Prepared Statements**: 100% parameter binding across all queries.
- **XSS Prevention**: HTML output escaping via `e()` helper.
- **CSRF Tokens**: Form submissions require verified session tokens.
- **Password Security**: Bcrypt via PHP's `password_hash()` and `password_verify()`.
- **SSRF Protection**: Image URL validator blocks local loopback and internal network IP ranges.
- **Safe File Uploads**: Image uploads are strictly validated by MIME type (`finfo`), size, and given randomized filenames. Executable file extensions are prohibited.
- **Race Condition Prevention**: Database transactions (`BEGIN TRANSACTION ... FOR UPDATE`) during seat locks and booking confirmations.
