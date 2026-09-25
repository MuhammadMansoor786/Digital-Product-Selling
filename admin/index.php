<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

redirectIfNotAdmin();

$pageTitle = 'Admin Dashboard';

$db = getDB();

// Get statistics
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users");
$totalProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$totalOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders");
$totalRevenue = $db->fetchOne("SELECT SUM(final_amount) as total FROM orders WHERE payment_status = 'paid'");

// Get recent orders
$recentOrders = $db->fetchAll("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Get recent support tickets
$recentTickets = $db->fetchAll("
    SELECT st.*, u.full_name 
    FROM support_tickets st 
    LEFT JOIN users u ON st.user_id = u.id 
    WHERE st.status != 'closed'
    ORDER BY st.created_at DESC 
    LIMIT 5
");

// Get top products
$topProducts = $db->fetchAll("
    SELECT p.*, COUNT(oi.id) as sales_count, SUM(oi.price) as revenue
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY sales_count DESC
    LIMIT 5
");

require_once '../includes/admin-header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4">Dashboard</h2>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Total Users</h6>
                <h3 class="mb-0"><?php echo number_format($totalUsers['count'] ?? 0); ?></h3>
                <small class="text-muted">Registered users</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Total Products</h6>
                <h3 class="mb-0"><?php echo number_format($totalProducts['count'] ?? 0); ?></h3>
                <small class="text-muted">Active products</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Total Orders</h6>
                <h3 class="mb-0"><?php echo number_format($totalOrders['count'] ?? 0); ?></h3>
                <small class="text-muted">All orders</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <h6 class="text-muted">Total Revenue</h6>
                <h3 class="mb-0"><?php echo formatPrice($totalRevenue['total'] ?? 0); ?></h3>
                <small class="text-muted">From paid orders</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title">Recent Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                
                <?php if (empty($recentOrders)): ?>
                    <p class="text-muted">No orders yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></td>
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
    
    <!-- Recent Support Tickets -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title">Recent Support Tickets</h5>
                    <a href="support.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                
                <?php if (empty($recentTickets)): ?>
                    <p class="text-muted">No support tickets yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>User</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($ticket['subject'], 0, 20)); ?>...</td>
                                        <td><?php echo htmlspecialchars($ticket['full_name'] ?? 'Unknown'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($ticket['priority']) {
                                                    'low' => 'secondary',
                                                    'medium' => 'info',
                                                    'high' => 'warning',
                                                    'urgent' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($ticket['status']) {
                                                    'open' => 'primary',
                                                    'in_progress' => 'warning',
                                                    'resolved' => 'success',
                                                    'closed' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
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
    
    <!-- Top Products -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title">Top Selling Products</h5>
                    <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                
                <?php if (empty($topProducts)): ?>
                    <p class="text-muted">No products sold yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sales Count</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['title']); ?></td>
                                        <td><?php echo number_format($product['sales_count']); ?></td>
                                        <td><?php echo formatPrice($product['revenue'] ?? 0); ?></td>
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

<?php require_once '../includes/admin-footer.php'; ?>
