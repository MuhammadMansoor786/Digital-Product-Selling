<?php
require_once __DIR__ . '/../config/database.php';

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    return $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    
    $db = getDB();
    return $db->fetchOne("SELECT * FROM admins WHERE id = ?", [$_SESSION['admin_id']]);
}

function isUserBlocked($userId) {
    $db = getDB();
    $user = $db->fetchOne("SELECT is_blocked FROM users WHERE id = ?", [$userId]);
    return $user && $user['is_blocked'] == 1;
}

// Redirect Functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        redirect(SITE_URL . 'login.php');
    }
}

function redirectIfNotAdmin() {
    if (!isAdminLoggedIn()) {
        redirect(SITE_URL . 'admin/login.php');
    }
}

// Input Sanitization
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitizeArray($array) {
    return array_map('sanitizeInput', $array);
}

// Password Functions
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// File Upload Functions
function uploadFile($file, $subfolder = '') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file size
    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit'];
    }
    
    // Validate file type
    if (!in_array($fileExt, ALLOWED_FILE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = UPLOAD_DIR . $subfolder;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;
    
    // Move file
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        $relativePath = 'uploads/' . $subfolder . $newFileName;
        return ['success' => true, 'path' => $relativePath, 'filename' => $newFileName];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Price Formatting
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Date Formatting
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Generate Order Number
function generateOrderNumber() {
    return 'ORD-' . strtoupper(uniqid());
}

// Generate Coupon Code
function generateCouponCode() {
    return strtoupper(substr(md5(uniqid()), 0, 8));
}

// Flash Messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Pagination
function getPagination($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_next' => $currentPage < $totalPages,
        'has_prev' => $currentPage > 1
    ];
}

// Slug Generation
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// Validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Analytics
function trackEvent($eventType, $eventData = [], $userId = null) {
    $db = getDB();
    $userId = $userId ?? (isLoggedIn() ? $_SESSION['user_id'] : null);
    
    $db->insert('analytics', [
        'event_type' => $eventType,
        'event_data' => json_encode($eventData),
        'user_id' => $userId
    ]);
}
