<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

redirectIfNotAdmin();

$pageTitle = 'Payment Settings';

$db = getDB();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['gateway'])) {
    $gateway = sanitizeInput($_GET['gateway']);
    $db->delete('payment_settings', 'payment_gateway = ?', [$gateway]);
    setFlashMessage('success', 'Payment gateway deleted successfully');
    redirect('payments.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentGateway = sanitizeInput($_POST['payment_gateway']);
    $apiKey = sanitizeInput($_POST['api_key']);
    $apiSecret = sanitizeInput($_POST['api_secret']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if payment gateway settings already exist
    $existingSettings = $db->fetchOne("SELECT * FROM payment_settings WHERE payment_gateway = ?", [$paymentGateway]);
    
    if ($existingSettings) {
        // Update existing settings
        $db->update('payment_settings', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'is_active' => $isActive
        ], 'payment_gateway = ?', [$paymentGateway]);
        
        setFlashMessage('success', 'Payment settings updated successfully');
    } else {
        // Insert new settings
        $db->insert('payment_settings', [
            'payment_gateway' => $paymentGateway,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'is_active' => $isActive
        ]);
        
        setFlashMessage('success', 'Payment settings added successfully');
    }
    
    redirect('payments.php');
}

// Get all payment settings
$paymentSettings = $db->fetchAll("SELECT * FROM payment_settings ORDER BY payment_gateway ASC");

require_once '../includes/admin-header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4">Payment Management</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Add Payment Gateway</h5>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="payment_gateway" class="form-label">Payment Gateway</label>
                        <select class="form-select" id="payment_gateway" name="payment_gateway" required>
                            <option value="">Select Gateway</option>
                            <option value="stripe">Stripe</option>
                            <option value="paypal">PayPal</option>
                            <option value="razorpay">Razorpay</option>
                            <option value="paystack">Paystack</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="api_key" class="form-label">API Key / Public Key</label>
                        <input type="text" class="form-control" id="api_key" name="api_key" required>
                        <small class="text-muted">Enter your public API key from the payment gateway</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="api_secret" class="form-label">API Secret / Private Key</label>
                        <input type="password" class="form-control" id="api_secret" name="api_secret" required>
                        <small class="text-muted">Enter your secret API key from the payment gateway</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active">
                        <label class="form-check-label" for="is_active">
                            Enable this payment gateway
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Configured Payment Gateways</h5>
                
                <?php if (empty($paymentSettings)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No payment gateways configured yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Gateway</th>
                                    <th>API Key</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentSettings as $setting): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo ucfirst($setting['payment_gateway']); ?></strong>
                                        </td>
                                        <td>
                                            <code><?php echo substr($setting['api_key'], 0, 20); ?>...</code>
                                        </td>
                                        <td>
                                            <?php if ($setting['is_active'] == 1): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editPayment('<?php echo $setting['payment_gateway']; ?>', '<?php echo $setting['api_key']; ?>', '<?php echo $setting['api_secret']; ?>', <?php echo $setting['is_active']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="payments.php?action=delete&gateway=<?php echo $setting['payment_gateway']; ?>" 
                                               class="btn btn-sm btn-outline-danger btn-delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function editPayment(gateway, apiKey, apiSecret, isActive) {
    document.getElementById('payment_gateway').value = gateway;
    document.getElementById('api_key').value = apiKey;
    document.getElementById('api_secret').value = apiSecret;
    document.getElementById('is_active').checked = isActive == 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<?php require_once '../includes/admin-footer.php'; ?>
