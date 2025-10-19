<?php
/**
 * Razorpay Configuration
 * 
 * Replace with your actual Razorpay credentials
 */

// Test Mode Keys (for development)
define('RAZORPAY_KEY_ID', 'rzp_test_RVFF6QBmy4OAtT');
define('RAZORPAY_KEY_SECRET', 'Xl45VsHInFJNw4nzMmzViCNo');

// Live Mode Keys (for production - uncomment when going live)
// define('RAZORPAY_KEY_ID', 'rzp_live_XXXXXXXXXXXXXXX');
// define('RAZORPAY_KEY_SECRET', 'YOUR_LIVE_SECRET_KEY_HERE');

// Environment
define('RAZORPAY_ENV', 'test'); // Change to 'live' for production

// Business Details
define('RAZORPAY_BUSINESS_NAME', 'Dinedesk');
define('RAZORPAY_BUSINESS_LOGO', 'https://your-domain.com/logo.png'); // Optional
define('RAZORPAY_CURRENCY', 'INR');

?>
