<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Home';

$db = getDB();

// Get featured products (latest products)
$featuredProducts = $db->fetchAll("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
");

// Get categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Premium Digital Products</h1>
        <p class="lead mb-4">Discover high-quality ebooks, software, templates, and more</p>
        <a href="products.php" class="btn btn-light btn-lg px-5">Browse Products</a>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Browse by Category</h2>
        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="products.php?category=<?php echo $category['id']; ?>" 
                       class="text-decoration-none">
                        <div class="card h-100 text-center p-3">
                            <div class="card-body">
                                <i class="bi bi-folder fs-1 text-primary mb-2"></i>
                                <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Featured Products</h2>
            <a href="products.php" class="btn btn-outline-primary">View All</a>
        </div>
        
        <?php if (empty($featuredProducts)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No products available yet. Check back soon!
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-md-6 col-lg-3">
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
                                    <?php echo substr(htmlspecialchars($product['description']), 0, 100) . '...'; ?>
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
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose Us?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-shield-check fs-1 text-success mb-3"></i>
                        <h4>Secure Payments</h4>
                        <p class="text-muted">Your payment information is safe and secure with our encrypted checkout.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-lightning-charge fs-1 text-warning mb-3"></i>
                        <h4>Instant Download</h4>
                        <p class="text-muted">Get immediate access to your purchased digital products after payment.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-headset fs-1 text-primary mb-3"></i>
                        <h4>24/7 Support</h4>
                        <p class="text-muted">Our dedicated support team is here to help you anytime you need assistance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
