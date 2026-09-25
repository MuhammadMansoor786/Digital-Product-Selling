<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Invalid request');
    redirect('products.php');
}

$productId = (int)$_POST['product_id'];
$totalAmount = (float)$_POST['total_amount'];
$discountAmount = (float)$_POST['discount_amount'];
$finalAmount = (float)$_POST['final_amount'];
$couponCode = sanitizeInput($_POST['coupon_code']);

$db = getDB();

// Get product details
$product = $db->fetchOne("SELECT * FROM products WHERE id = ? AND is_active = 1", [$productId]);

if (!$product) {
    setFlashMessage('danger', 'Product not found');
    redirect('products.php');
}

// Check if user is blocked
if (isUserBlocked($_SESSION['user_id'])) {
    setFlashMessage('danger', 'Your account has been blocked. Please contact support.');
    redirect('dashboard.php');
}

// Validate coupon if used
if ($couponCode) {
    $coupon = $db->fetchOne("
        SELECT * FROM coupons 
        WHERE code = ? 
        AND is_active = 1 
        AND valid_from <= NOW() 
        AND valid_until >= NOW()
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ", [$couponCode]);
    
    if (!$coupon) {
        setFlashMessage('danger', 'Invalid or expired coupon');
        redirect('checkout.php?product_id=' . $productId);
    }
}

// Start transaction
try {
    $db->getConnection()->beginTransaction();
    
    // Generate order number
    $orderNumber = generateOrderNumber();
    
    // Create order
    $orderId = $db->insert('orders', [
        'user_id' => $_SESSION['user_id'],
        'order_number' => $orderNumber,
        'total_amount' => $totalAmount,
        'discount_amount' => $discountAmount,
        'final_amount' => $finalAmount,
        'status' => 'completed',
        'payment_status' => 'paid',
        'payment_method' => 'credit_card'
    ]);
    
    if (!$orderId) {
        throw new Exception('Failed to create order');
    }
    
    // Create order item
    $downloadLink = $product['file_path'];
    $downloadExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $orderItemId = $db->insert('order_items', [
        'order_id' => $orderId,
        'product_id' => $productId,
        'quantity' => 1,
        'price' => $product['price'],
        'download_link' => $downloadLink,
        'download_expiry' => $downloadExpiry
    ]);
    
    if (!$orderItemId) {
        throw new Exception('Failed to create order item');
    }
    
    // Update coupon usage if used
    if ($couponCode && isset($coupon)) {
        $db->update('coupons', 
            ['used_count' => $coupon['used_count'] + 1],
            'id = ?',
            [$coupon['id']]
        );
    }
    
    // Increment product download count
    $db->update('products',
        ['download_count' => $product['download_count'] + 1],
        'id = ?',
        [$productId]
    );
    
    // Commit transaction
    $db->getConnection()->commit();
    
    // Track purchase event
    trackEvent('purchase', [
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'product_id' => $productId,
        'amount' => $finalAmount
    ], $_SESSION['user_id']);
    
    setFlashMessage('success', 'Order completed successfully! Your files are ready for download.');
    redirect('orders.php');
    
} catch (Exception $e) {
    // Rollback transaction
    $db->getConnection()->rollBack();
    
    error_log('Order processing error: ' . $e->getMessage());
    setFlashMessage('danger', 'Failed to process order. Please try again.');
    redirect('checkout.php?product_id=' . $productId);
}
