<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Sign Up';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $fullName = sanitizeInput($_POST['full_name']);
    $phone = sanitizeInput($_POST['phone']);
    
    // Validation
    $errors = [];
    
    if (!validateEmail($email)) {
        $errors[] = 'Invalid email address';
    }
    
    if (!validatePassword($password)) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($errors)) {
        $db = getDB();
        
        // Check if email already exists
        $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            setFlashMessage('danger', 'Email already registered');
        } else {
            // Insert new user
            $hashedPassword = hashPassword($password);
            $userId = $db->insert('users', [
                'email' => $email,
                'password' => $hashedPassword,
                'full_name' => $fullName,
                'phone' => $phone
            ]);
            
            if ($userId) {
                setFlashMessage('success', 'Account created successfully! Please login.');
                redirect('login.php');
            } else {
                setFlashMessage('danger', 'Failed to create account. Please try again.');
            }
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Create Account</h2>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number (Optional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Sign Up</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
