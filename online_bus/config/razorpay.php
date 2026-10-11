<?php
/**
 * Razorpay Payment Gateway Configuration (TEST MODE)
 * Shreenathji Travels
 *
 * NOTE: Keep secret keys strictly on the server-side.
 * Never echo or pass the secret key to frontend JavaScript.
 */

defined('IN_APP') or define('IN_APP', true);

return [
    // Operating Mode: 'test' or 'live'
    'mode' => 'test',

    // Razorpay TEST Credentials (Standard Razorpay Sandbox Keys)
    'key_id' => 'rzp_test_1DP5mmOlF5G5ag',
    'key_secret' => 's9P7qW9t0Zk1xY2a3b4c5d6e',

    // Optional Webhook Secret for signature validation
    'webhook_secret' => 'st_whsec_test_998877',

    // Currency settings
    'currency' => 'INR',

    // Test mode simulated gateway flag (allows testing without active internet / external API block)
    'simulate_in_test_if_offline' => true,

    // Company branding for Razorpay Checkout Modal
    'merchant_name' => 'Shreenathji Travels',
    'merchant_description' => 'Bus Ticket Booking Checkout',
    'theme_color' => '#1d4ed8'
];
