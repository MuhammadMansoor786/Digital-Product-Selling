<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Login';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    $db = getDB();
    $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($user && verifyPassword($password, $user['password'])) {
        // Check if user is blocked
        if ($user['is_blocked'] == 1) {
            setFlashMessage('danger', 'Your account has been blocked. Please contact support.');
        } else {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Track login event
            trackEvent('user_login', ['email' => $email], $user['id']);
            
            setFlashMessage('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');
            redirect('dashboard.php');
        }
    } else {
        setFlashMessage('danger', 'Invalid email or password');
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Login</h2>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Don't have an account? <a href="signup.php">Sign up here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
