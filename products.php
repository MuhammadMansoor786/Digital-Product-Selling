<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Products';

$db = getDB();

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';

// Build query
$where = ["p.is_active = 1"];
$params = [];

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($search) {
    $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

// Sort options
$sortOptions = [
    'newest' => 'p.created_at DESC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'popular' => 'p.download_count DESC'
];

$orderBy = $sortOptions[$sortBy] ?? $sortOptions['newest'];

// Get products
$products = $db->fetchAll("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $whereClause 
    ORDER BY $orderBy
", $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="products.php" class="text-decoration-none <?php echo $categoryId == 0 ? 'fw-bold text-primary' : 'text-muted'; ?>">
                                All Categories
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li class="mb-2">
                                <a href="products.php?category=<?php echo $category['id']; ?>" 
                                   class="text-decoration-none <?php echo $categoryId == $category['id'] ? 'fw-bold text-primary' : 'text-muted'; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Search and Sort -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <form method="GET" action="">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <?php if ($categoryId > 0): ?>
                            <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-md-6">
                    <select class="form-select" onchange="location.href=this.value">
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" 
                                <?php echo $sortBy == 'newest' ? 'selected' : ''; ?>>
                            Newest First
                        </option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>" 
                                <?php echo $sortBy == 'price_low' ? 'selected' : ''; ?>>
                            Price: Low to High
                        </option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>" 
                                <?php echo $sortBy == 'price_high' ? 'selected' : ''; ?>>
                            Price: High to Low
                        </option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'popular'])); ?>" 
                                <?php echo $sortBy == 'popular' ? 'selected' : ''; ?>>
                            Most Popular
                        </option>
                    </select>
                </div>
            </div>
            
            <!-- Results count -->
            <p class="text-muted mb-3">
                Showing <?php echo count($products); ?> product(s)
                <?php if ($search): ?>
                    for "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
            </p>
            
            <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No products found matching your criteria.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 product-card">
                                <?php if ($product['thumbnail']): ?>
                                    <img src="<?php echo SITE_URL . $product['thumbnail']; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/300x200?text=No+Image" 
                                         class="card-img-top" alt="No image">
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <span class="badge bg-secondary mb-2">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                                    <p class="card-text text-muted small">
                                        <?php echo substr(htmlspecialchars($product['description']), 0, 80) . '...'; ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                                <span class="original-price me-2">
                                                    <?php echo formatPrice($product['original_price']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="price-tag"><?php echo formatPrice($product['price']); ?></span>
                                        </div>
                                        <a href="product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
