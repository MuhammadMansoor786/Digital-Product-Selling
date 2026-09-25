<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'digital_product_selling');

// Site Configuration
define('SITE_NAME', 'Digital Product Shop');
define('SITE_URL', 'http://localhost/Digital%20Product%20Selling/');
define('ADMIN_EMAIL', 'admin@digitalshop.com');

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', ['pdf', 'zip', 'rar', 'doc', 'docx', 'mp3', 'mp4', 'jpg', 'jpeg', 'png', 'gif']);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
