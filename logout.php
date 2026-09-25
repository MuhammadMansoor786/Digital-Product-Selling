<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Track logout event if user was logged in
if (isLoggedIn()) {
    trackEvent('user_logout', [], $_SESSION['user_id']);
}

// Destroy session
session_unset();
session_destroy();

setFlashMessage('success', 'You have been logged out successfully.');
redirect('login.php');
