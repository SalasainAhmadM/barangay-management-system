<?php
// Email configuration
define('SMTP_HOST', 'smtp.gmail.com'); // Your SMTP host
define('SMTP_PORT', 587); // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', 'samcena.902604@gmail.com'); // Your email
define('SMTP_PASSWORD', 'eilr usiq mkgy etrs'); // Your app password
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('FROM_EMAIL', 'samcena.902604@gmail.com'); // Updated to match SMTP_USERNAME
define('FROM_NAME', 'Barangay Management System');

// Define system name and admin email with defaults
if (!defined('SYSTEM_NAME')) {
    define('SYSTEM_NAME', 'Barangay Management System');
}

if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'samcena.902604@gmail.com'); // Default fallback
}
?>