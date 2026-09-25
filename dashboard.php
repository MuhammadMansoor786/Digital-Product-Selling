<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

redirectIfNotLoggedIn();

$pageTitle = 'Dashboard';

$db = getDB();
$user = getCurrentUser();

// Get user's recent orders
$recentOrders = $db->fetchAll("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
", [$_SESSION['user_id']]);

// Get total spent
$totalSpent = $db->fetchOne("
    SELECT SUM(final_amount) as total 
    FROM orders 
    WHERE user_id = ? AND payment_status = 'paid'
", [$_SESSION['user_id']]);

// Get total orders
$totalOrders = $db->fetchOne("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE user_id = ?
", [$_SESSION['user_id']]);

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-person-circle fs-1 text-primary"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-cart-check me-2"></i> My Orders
                    </a>
                    <a href="support.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-lifebuoy me-2"></i> Support Tickets
                    </a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <h2 class="mb-4">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
            
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <h6 class="text-muted">Total Orders</h6>
                            <h3 class="mb-0"><?php echo $totalOrders['count'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h6 class="text-muted">Total Spent</h6>
                            <h3 class="mb-0"><?php echo formatPrice($totalSpent['total'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title">Recent Orders</h5>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <?php if (empty($recentOrders)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You haven't placed any orders yet.
                            <a href="products.php" class="alert-link">Browse products</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo formatDate($order['created_at']); ?></td>
                                            <td><?php echo $order['item_count']; ?></td>
                                            <td><?php echo formatPrice($order['final_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
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
</div>

<?php require_once 'includes/footer.php'; ?>
