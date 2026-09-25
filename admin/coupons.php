<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

redirectIfNotAdmin();

$pageTitle = 'Coupon Management';

$db = getDB();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $couponId = (int)$_GET['id'];
    $db->delete('coupons', 'id = ?', [$couponId]);
    setFlashMessage('success', 'Coupon deleted successfully');
    redirect('coupons.php');
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $couponId = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;
    $code = strtoupper(sanitizeInput($_POST['code']));
    $discountType = sanitizeInput($_POST['discount_type']);
    $discountValue = (float)$_POST['discount_value'];
    $minPurchase = (float)$_POST['min_purchase'];
    $maxDiscount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $validFrom = $_POST['valid_from'];
    $validUntil = $_POST['valid_until'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($couponId > 0) {
        // Update existing coupon
        $updateData = [
            'code' => $code,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'min_purchase' => $minPurchase,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'is_active' => $isActive
        ];
        
        if ($maxDiscount) $updateData['max_discount'] = $maxDiscount;
        if ($usageLimit) $updateData['usage_limit'] = $usageLimit;
        
        $db->update('coupons', $updateData, 'id = ?', [$couponId]);
        setFlashMessage('success', 'Coupon updated successfully');
    } else {
        // Add new coupon
        $couponData = [
            'code' => $code,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'min_purchase' => $minPurchase,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'is_active' => $isActive
        ];
        
        if ($maxDiscount) $couponData['max_discount'] = $maxDiscount;
        if ($usageLimit) $couponData['usage_limit'] = $usageLimit;
        
        $couponId = $db->insert('coupons', $couponData);
        
        if ($couponId) {
            setFlashMessage('success', 'Coupon added successfully');
        } else {
            setFlashMessage('danger', 'Failed to add coupon');
        }
    }
    
    redirect('coupons.php');
}

// Get search term
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND code LIKE ?";
    $params[] = "%$search%";
}

if ($statusFilter !== '') {
    $where .= " AND is_active = ?";
    $params[] = $statusFilter;
}

// Get coupons with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$coupons = $db->fetchAll("
    SELECT * FROM coupons 
    WHERE $where 
    ORDER BY created_at DESC 
    LIMIT $perPage OFFSET $offset
", $params);

// Get total count for pagination
$totalCoupons = $db->fetchOne("SELECT COUNT(*) as count FROM coupons WHERE $where", $params);
$pagination = getPagination($totalCoupons['count'], $perPage, $page);

require_once '../includes/admin-header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Coupon Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#couponModal">
                <i class="bi bi-plus"></i> Add Coupon
            </button>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="row mb-4">
    <div class="col-md-6">
        <form method="GET" action="">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search coupons by code..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select class="form-select" name="status" style="max-width: 150px;">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Coupons Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($coupons)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No coupons found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Min Purchase</th>
                            <th>Usage</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($coupon['code']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                        <?php echo $coupon['discount_value']; ?>%
                                    <?php else: ?>
                                        <?php echo formatPrice($coupon['discount_value']); ?>
                                    <?php endif; ?>
                                    <?php if ($coupon['max_discount']): ?>
                                        <br><small class="text-muted">Max: <?php echo formatPrice($coupon['max_discount']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatPrice($coupon['min_purchase']); ?></td>
                                <td>
                                    <?php echo $coupon['used_count']; ?>
                                    <?php if ($coupon['usage_limit']): ?>
                                        / <?php echo $coupon['usage_limit']; ?>
                                    <?php else: ?>
                                        / ∞
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo formatDate($coupon['valid_from'], 'M d'); ?></small>
                                    <br><small class="text-muted">to <?php echo formatDate($coupon['valid_until'], 'M d'); ?></small>
                                </td>
                                <td>
                                    <?php if ($coupon['is_active'] == 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editCoupon(<?php echo $coupon['id']; ?>, '<?php echo $coupon['code']; ?>', '<?php echo $coupon['discount_type']; ?>', <?php echo $coupon['discount_value']; ?>, <?php echo $coupon['min_purchase']; ?>, <?php echo $coupon['max_discount'] ?? 'null'; ?>, <?php echo $coupon['usage_limit'] ?? 'null'; ?>, '<?php echo $coupon['valid_from']; ?>', '<?php echo $coupon['valid_until']; ?>', <?php echo $coupon['is_active']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="coupons.php?action=delete&id=<?php echo $coupon['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger btn-delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
                                Previous
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . $statusFilter : ''; ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Coupon Modal -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="couponModalTitle">Add Coupon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="coupon_id" id="coupon_id" value="0">
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Coupon Code</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                        <small class="text-muted">Leave empty to auto-generate</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="discount_type" class="form-label">Discount Type</label>
                            <select class="form-select" id="discount_type" name="discount_type" required>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="discount_value" class="form-label">Discount Value</label>
                            <input type="number" class="form-control" id="discount_value" name="discount_value" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_purchase" class="form-label">Min Purchase</label>
                            <input type="number" class="form-control" id="min_purchase" name="min_purchase" step="0.01" value="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="max_discount" class="form-label">Max Discount (Optional)</label>
                            <input type="number" class="form-control" id="max_discount" name="max_discount" step="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="usage_limit" class="form-label">Usage Limit (Optional)</label>
                        <input type="number" class="form-control" id="usage_limit" name="usage_limit">
                        <small class="text-muted">Leave empty for unlimited usage</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="valid_from" class="form-label">Valid From</label>
                            <input type="datetime-local" class="form-control" id="valid_from" name="valid_from" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="valid_until" class="form-label">Valid Until</label>
                            <input type="datetime-local" class="form-control" id="valid_until" name="valid_until" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCoupon(id, code, discountType, discountValue, minPurchase, maxDiscount, usageLimit, validFrom, validUntil, isActive) {
    document.getElementById('coupon_id').value = id;
    document.getElementById('code').value = code;
    document.getElementById('discount_type').value = discountType;
    document.getElementById('discount_value').value = discountValue;
    document.getElementById('min_purchase').value = minPurchase;
    document.getElementById('max_discount').value = maxDiscount || '';
    document.getElementById('usage_limit').value = usageLimit || '';
    document.getElementById('valid_from').value = validFrom;
    document.getElementById('valid_until').value = validUntil;
    document.getElementById('is_active').checked = isActive == 1;
    document.getElementById('couponModalTitle').textContent = 'Edit Coupon';
    
    new bootstrap.Modal(document.getElementById('couponModal')).show();
}

document.getElementById('couponModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('coupon_id').value = '0';
    document.getElementById('code').value = '';
    document.getElementById('discount_type').selectedIndex = 0;
    document.getElementById('discount_value').value = '';
    document.getElementById('min_purchase').value = '0';
    document.getElementById('max_discount').value = '';
    document.getElementById('usage_limit').value = '';
    document.getElementById('valid_from').value = '';
    document.getElementById('valid_until').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('couponModalTitle').textContent = 'Add Coupon';
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
