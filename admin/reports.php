<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

redirectIfNotAdmin();

$pageTitle = 'Reports & Analytics';

$db = getDB();

// Get date range filter
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Sales Statistics
$salesStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(final_amount) as total_revenue,
        AVG(final_amount) as avg_order_value,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
    FROM orders 
    WHERE created_at BETWEEN ? AND ?
", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

// User Statistics
$userStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_users
    FROM users
", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

// Product Statistics
$productStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_products,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_products,
        SUM(download_count) as total_downloads
    FROM products
");

// Sales by Category
$salesByCategory = $db->fetchAll("
    SELECT c.name, SUM(oi.price * oi.quantity) as revenue, COUNT(oi.id) as sales_count
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY revenue DESC
    LIMIT 10
", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

// Top Selling Products
$topProducts = $db->fetchAll("
    SELECT p.title, COUNT(oi.id) as sales_count, SUM(oi.price * oi.quantity) as revenue
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY sales_count DESC
    LIMIT 10
", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

// Daily Sales (Last 7 days)
$dailySales = $db->fetchAll("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(final_amount) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

// Support Ticket Statistics
$ticketStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_tickets,
        COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets
    FROM support_tickets
    WHERE created_at BETWEEN ? AND ?
", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

require_once '../includes/admin-header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4">Reports & Analytics</h2>
    </div>
</div>

<!-- Date Filter -->
<div class="row mb-4">
    <div class="col-md-6">
        <form method="GET" action="">
            <div class="input-group">
                <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Overview Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Total Revenue</h6>
                <h3 class="mb-0"><?php echo formatPrice($salesStats['total_revenue'] ?? 0); ?></h3>
                <small class="text-muted"><?php echo $salesStats['total_orders'] ?? 0; ?> orders</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Paid Orders</h6>
                <h3 class="mb-0"><?php echo number_format($salesStats['paid_orders'] ?? 0); ?></h3>
                <small class="text-muted"><?php echo formatPrice($salesStats['total_revenue'] ?? 0); ?> revenue</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Avg Order Value</h6>
                <h3 class="mb-0"><?php echo formatPrice($salesStats['avg_order_value'] ?? 0); ?></h3>
                <small class="text-muted">Per order</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <h6 class="text-muted">New Users</h6>
                <h3 class="mb-0"><?php echo number_format($userStats['new_users'] ?? 0); ?></h3>
                <small class="text-muted">This period</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Total Users</h6>
                <h3 class="mb-0"><?php echo number_format($userStats['total_users'] ?? 0); ?></h3>
                <small class="text-muted">Registered</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Active Products</h6>
                <h3 class="mb-0"><?php echo number_format($productStats['active_products'] ?? 0); ?></h3>
                <small class="text-muted">Of <?php echo number_format($productStats['total_products'] ?? 0); ?> total</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Total Downloads</h6>
                <h3 class="mb-0"><?php echo number_format($productStats['total_downloads'] ?? 0); ?></h3>
                <small class="text-muted">All time</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Support Tickets</h6>
                <h3 class="mb-0"><?php echo number_format($ticketStats['total_tickets'] ?? 0); ?></h3>
                <small class="text-muted"><?php echo number_format($ticketStats['open_tickets'] ?? 0); ?> open</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Sales by Category -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-4">Sales by Category</h5>
                
                <?php if (empty($salesByCategory)): ?>
                    <p class="text-muted">No sales data for this period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Sales</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesByCategory as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name'] ?? 'Uncategorized'); ?></td>
                                        <td><?php echo number_format($category['sales_count']); ?></td>
                                        <td><?php echo formatPrice($category['revenue']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Selling Products -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-4">Top Selling Products</h5>
                
                <?php if (empty($topProducts)): ?>
                    <p class="text-muted">No sales data for this period.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sales</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['title']); ?></td>
                                        <td><?php echo number_format($product['sales_count']); ?></td>
                                        <td><?php echo formatPrice($product['revenue']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Daily Sales -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Daily Sales (Last 7 Days)</h5>
                
                <?php if (empty($dailySales)): ?>
                    <p class="text-muted">No sales data available.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxRevenue = max(array_column($dailySales, 'revenue'));
                                foreach ($dailySales as $day): 
                                    $percentage = $maxRevenue > 0 ? ($day['revenue'] / $maxRevenue) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo formatDate($day['date'], 'M d, Y'); ?></td>
                                        <td><?php echo number_format($day['orders']); ?></td>
                                        <td><?php echo formatPrice($day['revenue']); ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $percentage; ?>%;" 
                                                     aria-valuenow="<?php echo $percentage; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo number_format($percentage, 1); ?>%
                                                </div>
                                            </div>
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

<?php require_once '../includes/admin-footer.php'; ?>
