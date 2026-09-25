<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

redirectIfNotLoggedIn();

$pageTitle = 'My Orders';

$db = getDB();

// Get user's orders with items
$orders = $db->fetchAll("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
", [$_SESSION['user_id']]);

require_once 'includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Orders</li>
        </ol>
    </nav>
    
    <h1 class="mb-4">My Orders</h1>
    
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> You haven't placed any orders yet.
            <a href="products.php" class="alert-link">Browse products</a> to make your first purchase.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($orders as $order): ?>
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                <span class="text-muted ms-2"><?php echo formatDate($order['created_at']); ?></span>
                            </div>
                            <div>
                                <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'danger'; ?> ms-1">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get order items
                            $orderItems = $db->fetchAll("
                                SELECT oi.*, p.title, p.thumbnail 
                                FROM order_items oi 
                                LEFT JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = ?
                            ", [$order['id']]);
                            ?>
                            
                            <?php foreach ($orderItems as $item): ?>
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                    <?php if ($item['thumbnail']): ?>
                                        <img src="<?php echo SITE_URL . $item['thumbnail']; ?>" 
                                             class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/60x60?text=No+Image" 
                                             class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;" alt="No image">
                                    <?php endif; ?>
                                    
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo formatPrice($item['price']); ?> × <?php echo $item['quantity']; ?>
                                        </small>
                                    </div>
                                    
                                    <?php if ($order['status'] == 'completed' && $order['payment_status'] == 'paid'): ?>
                                        <?php if ($item['download_expiry'] && strtotime($item['download_expiry']) > time()): ?>
                                            <a href="<?php echo SITE_URL . $item['download_link']; ?>" 
                                               class="btn btn-sm btn-primary" download>
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">Download expired</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Not available</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <span class="text-muted">Subtotal: <?php echo formatPrice($order['total_amount']); ?></span>
                                        <span class="text-success ms-2">Discount: -<?php echo formatPrice($order['discount_amount']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <strong>Total: <?php echo formatPrice($order['final_amount']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
