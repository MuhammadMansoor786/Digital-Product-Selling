<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

redirectIfNotAdmin();

$pageTitle = 'Order Management';

$db = getDB();

// Handle status update
if (isset($_POST['action']) && $_POST['action'] == 'update_status' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $status = sanitizeInput($_POST['status']);
    $paymentStatus = sanitizeInput($_POST['payment_status']);
    
    $db->update('orders', [
        'status' => $status,
        'payment_status' => $paymentStatus
    ], 'id = ?', [$orderId]);
    
    setFlashMessage('success', 'Order status updated successfully');
    redirect('orders.php');
}

// Get search term
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter) {
    $where .= " AND o.status = ?";
    $params[] = $statusFilter;
}

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$orders = $db->fetchAll("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE $where 
    ORDER BY o.created_at DESC 
    LIMIT $perPage OFFSET $offset
", $params);

// Get total count for pagination
$totalOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE $where", $params);
$pagination = getPagination($totalOrders['count'], $perPage, $page);

require_once '../includes/admin-header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4">Order Management</h2>
    </div>
</div>

<!-- Search and Filter -->
<div class="row mb-4">
    <div class="col-md-8">
        <form method="GET" action="">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search by order number, customer name, or email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select class="form-select" name="status" style="max-width: 150px;">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="refunded" <?php echo $statusFilter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No orders found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Order Status</th>
                            <th>Payment Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($order['email'] ?? ''); ?></small>
                                </td>
                                <td><?php echo formatPrice($order['final_amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($order['status']) {
                                            'pending' => 'warning',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'refunded' => 'secondary',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($order['payment_status']) {
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'failed' => 'danger',
                                            'refunded' => 'secondary',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($order['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo !$pagination['has_prev'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">
                                Previous
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Order Detail Modals -->
<?php foreach ($orders as $order): ?>
    <?php
    // Get order items
    $orderItems = $db->fetchAll("
        SELECT oi.*, p.title, p.thumbnail 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ", [$order['id']]);
    ?>
    
    <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?>
                            <br><strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? ''); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Date:</strong> <?php echo formatDate($order['created_at']); ?>
                            <br><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Subtotal:</strong> <?php echo formatPrice($order['total_amount']); ?>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <br><strong>Discount:</strong> -<?php echo formatPrice($order['discount_amount']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Total:</strong> <?php echo formatPrice($order['final_amount']); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="" class="d-flex gap-2">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        
                        <select class="form-select" name="status" style="width: 150px;">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="refunded" <?php echo $order['status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                        
                        <select class="form-select" name="payment_status" style="width: 150px;">
                            <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $order['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once '../includes/admin-footer.php'; ?>
