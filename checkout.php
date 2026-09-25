<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

redirectIfNotLoggedIn();

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$couponCode = isset($_GET['coupon']) ? sanitizeInput($_GET['coupon']) : '';

if (!$productId) {
    setFlashMessage('danger', 'Invalid product');
    redirect('products.php');
}

$db = getDB();

// Get product details
$product = $db->fetchOne("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1
", [$productId]);

if (!$product) {
    setFlashMessage('danger', 'Product not found');
    redirect('products.php');
}

// Check if user is blocked
if (isUserBlocked($_SESSION['user_id'])) {
    setFlashMessage('danger', 'Your account has been blocked. Please contact support.');
    redirect('dashboard.php');
}

// Calculate price
$totalAmount = $product['price'];
$discountAmount = 0;
$appliedCoupon = null;

// Apply coupon if provided
if ($couponCode) {
    $coupon = $db->fetchOne("
        SELECT * FROM coupons 
        WHERE code = ? 
        AND is_active = 1 
        AND valid_from <= NOW() 
        AND valid_until >= NOW()
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ", [$couponCode]);
    
    if ($coupon) {
        if ($totalAmount >= $coupon['min_purchase']) {
            if ($coupon['discount_type'] == 'percentage') {
                $discountAmount = ($totalAmount * $coupon['discount_value']) / 100;
            } else {
                $discountAmount = $coupon['discount_value'];
            }
            
            // Apply max discount limit if set
            if ($coupon['max_discount'] && $discountAmount > $coupon['max_discount']) {
                $discountAmount = $coupon['max_discount'];
            }
            
            $appliedCoupon = $coupon;
        }
    }
}

$finalAmount = $totalAmount - $discountAmount;

$pageTitle = 'Checkout';

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="mb-4">Checkout</h2>
                    
                    <!-- Product Summary -->
                    <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">
                        <?php if ($product['thumbnail']): ?>
                            <img src="<?php echo SITE_URL . $product['thumbnail']; ?>" 
                                 class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/80x80?text=No+Image" 
                                 class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;" alt="No image">
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($product['title']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Coupon Form -->
                    <form method="GET" action="" class="mb-4">
                        <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                        <div class="input-group">
                            <input type="text" class="form-control" name="coupon" 
                                   placeholder="Enter coupon code" 
                                   value="<?php echo htmlspecialchars($couponCode); ?>">
                            <button type="submit" class="btn btn-outline-primary">Apply Coupon</button>
                        </div>
                    </form>
                    
                    <?php if ($appliedCoupon): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Coupon applied! 
                            You saved <?php echo formatPrice($discountAmount); ?>
                        </div>
                    <?php elseif ($couponCode): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Invalid or expired coupon code.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Payment Form -->
                    <form method="POST" action="process-order.php">
                        <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                        <input type="hidden" name="coupon_code" value="<?php echo htmlspecialchars($couponCode); ?>">
                        <input type="hidden" name="total_amount" value="<?php echo $totalAmount; ?>">
                        <input type="hidden" name="discount_amount" value="<?php echo $discountAmount; ?>">
                        <input type="hidden" name="final_amount" value="<?php echo $finalAmount; ?>">
                        
                        <h5 class="mb-3">Payment Information</h5>
                        
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" 
                                   placeholder="1234 5678 9012 3456" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiry_date" name="expiry_date" 
                                       placeholder="MM/YY" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" name="cvv" 
                                       placeholder="123" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cardholder_name" class="form-label">Cardholder Name</label>
                            <input type="text" class="form-control" id="cardholder_name" name="cardholder_name" 
                                   placeholder="John Doe" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            This is a demo. No actual payment will be processed.
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-lock"></i> Pay <?php echo formatPrice($finalAmount); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($totalAmount); ?></span>
                    </div>
                    
                    <?php if ($discountAmount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Discount</span>
                            <span>-<?php echo formatPrice($discountAmount); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong class="price-tag"><?php echo formatPrice($finalAmount); ?></strong>
                    </div>
                    
                    <div class="text-muted small">
                        <p class="mb-1"><i class="bi bi-check-circle"></i> Instant download after payment</p>
                        <p class="mb-1"><i class="bi bi-check-circle"></i> Secure payment processing</p>
                        <p class="mb-0"><i class="bi bi-check-circle"></i> 24/7 customer support</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
