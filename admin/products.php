<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

redirectIfNotAdmin();

$pageTitle = 'Product Management';

$db = getDB();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    
    // Delete product (cascade will handle related records)
    $db->delete('products', 'id = ?', [$productId]);
    
    setFlashMessage('success', 'Product deleted successfully');
    redirect('products.php');
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $price = (float)$_POST['price'];
    $originalPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $categoryId = (int)$_POST['category_id'];
    
    // File upload handling
    $thumbnail = null;
    $filePath = null;
    $fileSize = null;
    $fileType = null;
    
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $uploadResult = uploadFile($_FILES['thumbnail'], 'thumbnails/');
        if ($uploadResult['success']) {
            $thumbnail = $uploadResult['path'];
        }
    }
    
    if (isset($_FILES['product_file']) && $_FILES['product_file']['error'] == 0) {
        $uploadResult = uploadFile($_FILES['product_file'], 'products/');
        if ($uploadResult['success']) {
            $filePath = $uploadResult['path'];
            $fileSize = formatFileSize($_FILES['product_file']['size']);
            $fileType = strtolower(pathinfo($_FILES['product_file']['name'], PATHINFO_EXTENSION));
        }
    }
    
    if ($productId > 0) {
        // Update existing product
        $updateData = [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'category_id' => $categoryId
        ];
        
        if ($originalPrice) $updateData['original_price'] = $originalPrice;
        if ($thumbnail) $updateData['thumbnail'] = $thumbnail;
        if ($filePath) $updateData['file_path'] = $filePath;
        if ($fileSize) $updateData['file_size'] = $fileSize;
        if ($fileType) $updateData['file_type'] = $fileType;
        
        $db->update('products', $updateData, 'id = ?', [$productId]);
        setFlashMessage('success', 'Product updated successfully');
    } else {
        // Add new product
        if (!$filePath) {
            setFlashMessage('danger', 'Product file is required');
            redirect('products.php');
        }
        
        $productData = [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'category_id' => $categoryId,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_type' => $fileType
        ];
        
        if ($originalPrice) $productData['original_price'] = $originalPrice;
        if ($thumbnail) $productData['thumbnail'] = $thumbnail;
        
        $productId = $db->insert('products', $productData);
        
        if ($productId) {
            setFlashMessage('success', 'Product added successfully');
        } else {
            setFlashMessage('danger', 'Failed to add product');
        }
    }
    
    redirect('products.php');
}

// Get search term
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId > 0) {
    $where .= " AND category_id = ?";
    $params[] = $categoryId;
}

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$products = $db->fetchAll("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $where 
    ORDER BY p.created_at DESC 
    LIMIT $perPage OFFSET $offset
", $params);

// Get total count for pagination
$totalProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products p WHERE $where", $params);
$pagination = getPagination($totalProducts['count'], $perPage, $page);

// Get categories for filter and form
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");

require_once '../includes/admin-header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Product Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                <i class="bi bi-plus"></i> Add Product
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
                       placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select class="form-select" name="category" style="max-width: 150px;">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No products found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Downloads</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['thumbnail']): ?>
                                        <img src="<?php echo SITE_URL . $product['thumbnail']; ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" 
                                             alt="Thumbnail">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['title']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td>
                                    <?php echo formatPrice($product['price']); ?>
                                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                        <br><small class="text-muted"><s><?php echo formatPrice($product['original_price']); ?></s></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($product['download_count']); ?></td>
                                <td>
                                    <?php if ($product['is_active'] == 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($product['description'], ENT_QUOTES); ?>', <?php echo $product['price']; ?>, <?php echo $product['original_price'] ?? 'null'; ?>, <?php echo $product['category_id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" 
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                                Previous
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="product_id" value="0">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="original_price" class="form-label">Original Price (Optional)</label>
                            <input type="number" class="form-control" id="original_price" name="original_price" step="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="thumbnail" class="form-label">Thumbnail Image</label>
                        <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                        <small class="text-muted">Optional</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="product_file" class="form-label">Product File</label>
                        <input type="file" class="form-control" id="product_file" name="product_file" required>
                        <small class="text-muted">Required - Max 50MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduct(id, title, description, price, originalPrice, categoryId) {
    document.getElementById('product_id').value = id;
    document.getElementById('title').value = title;
    document.getElementById('description').value = description;
    document.getElementById('price').value = price;
    document.getElementById('original_price').value = originalPrice || '';
    document.getElementById('category_id').value = categoryId;
    document.getElementById('productModalTitle').textContent = 'Edit Product';
    document.getElementById('product_file').required = false;
    
    new bootstrap.Modal(document.getElementById('productModal')).show();
}

document.getElementById('productModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('product_id').value = '0';
    document.getElementById('title').value = '';
    document.getElementById('description').value = '';
    document.getElementById('price').value = '';
    document.getElementById('original_price').value = '';
    document.getElementById('category_id').selectedIndex = 0;
    document.getElementById('productModalTitle').textContent = 'Add Product';
    document.getElementById('product_file').required = true;
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
