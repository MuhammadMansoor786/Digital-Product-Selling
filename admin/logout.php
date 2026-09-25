<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Destroy session
session_unset();
session_destroy();

setFlashMessage('success', 'You have been logged out successfully.');
redirect(SITE_URL . 'admin/login.php');
