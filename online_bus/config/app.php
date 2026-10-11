<?php
/**
 * Application Settings
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);

date_default_timezone_set('Asia/Kolkata');

return [
    'app_name' => 'Shreenathji Travels',
    'tagline' => 'Your Journey, Our Responsibility',
    'base_url' => 'http://localhost/online_bus',
    'admin_url' => 'http://localhost/online_bus/admin',
    'timezone' => 'Asia/Kolkata',
    'seat_lock_seconds' => 600, // 10 minutes lock
    'tax_rate' => 0.05, // 5% GST
    'convenience_fee' => 25.00, // Flat ₹25 convenience fee
    'currency' => 'INR',
    'currency_symbol' => '₹',
    'session_name' => 'ST_TRAVELS_SESS',
    'support_phone' => '+91 79 2658 9999',
    'support_email' => 'support@shreenathjitravels.com',
    'office_address' => 'Shreenathji House, Nr. Paldi Cross Roads, Ellisbridge, Ahmedabad, Gujarat 380006'
];
