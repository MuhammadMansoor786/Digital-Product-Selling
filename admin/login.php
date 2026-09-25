<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

$pageTitle = 'Admin Login';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    redirect(SITE_URL . 'admin/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    $db = getDB();
    $admin = $db->fetchOne("SELECT * FROM admins WHERE email = ?", [$email]);
    
    if ($admin && verifyPassword($password, $admin['password'])) {
        // Set session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_name'] = $admin['full_name'];
        
        setFlashMessage('success', 'Welcome, ' . htmlspecialchars($admin['full_name']) . '!');
        redirect(SITE_URL . 'admin/');
    } else {
        setFlashMessage('danger', 'Invalid email or password');
    }
}

require_once '../includes/admin-header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">Admin Login</h2>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?php echo SITE_URL; ?>" class="text-muted">
                        <i class="bi bi-arrow-left"></i> Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>
