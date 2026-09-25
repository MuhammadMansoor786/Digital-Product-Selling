<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    setFlashMessage('danger', 'Invalid product ID');
    redirect('products.php');
}

$db = getDB();

// Get product details
$product = $db->fetchOne("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1
", [$productId]);

if (!$product) {
    setFlashMessage('danger', 'Product not found');
    redirect('products.php');
}

// Get product images
$images = $db->fetchAll("SELECT * FROM product_images WHERE product_id = ?", [$productId]);

// Get product metadata
$metadata = $db->fetchAll("SELECT * FROM product_metadata WHERE product_id = ?", [$productId]);
$metadataArray = [];
foreach ($metadata as $meta) {
    $metadataArray[$meta['meta_key']] = $meta['meta_value'];
}

// Track product view
trackEvent('product_view', ['product_id' => $productId, 'product_title' => $product['title']]);

$pageTitle = $product['title'];

require_once 'includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['title']); ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Product Images -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <?php if ($product['thumbnail']): ?>
                        <img src="<?php echo SITE_URL . $product['thumbnail']; ?>" 
                             class="img-fluid w-100" style="border-radius: 12px;" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>">
                    <?php elseif (!empty($images)): ?>
                        <img src="<?php echo SITE_URL . $images[0]['image_path']; ?>" 
                             class="img-fluid w-100" style="border-radius: 12px;" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/600x400?text=No+Image" 
                             class="img-fluid w-100" style="border-radius: 12px;" alt="No image">
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (count($images) > 1): ?>
                <div class="row mt-3 g-2">
                    <?php foreach ($images as $image): ?>
                        <div class="col-3">
                            <img src="<?php echo SITE_URL . $image['image_path']; ?>" 
                                 class="img-fluid rounded" style="cursor: pointer;" 
                                 alt="Product image">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Details -->
        <div class="col-lg-6">
            <span class="badge bg-secondary mb-2">
                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
            </span>
            <h1 class="mb-3"><?php echo htmlspecialchars($product['title']); ?></h1>
            
            <div class="mb-3">
                <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                    <span class="original-price fs-4 me-2">
                        <?php echo formatPrice($product['original_price']); ?>
                    </span>
                <?php endif; ?>
                <span class="price-tag fs-2"><?php echo formatPrice($product['price']); ?></span>
            </div>
            
            <div class="mb-4">
                <h5>Description</h5>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            
            <?php if (!empty($metadataArray)): ?>
                <div class="mb-4">
                    <h5>Product Details</h5>
                    <ul class="list-unstyled">
                        <?php foreach ($metadataArray as $key => $value): ?>
                            <li class="mb-2">
                                <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?>:</strong>
                                <?php echo htmlspecialchars($value); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="mb-4">
                <h5>File Information</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-file-earmark"></i> Type: <?php echo htmlspecialchars($product['file_type'] ?? 'N/A'); ?></li>
                    <li><i class="bi bi-hdd"></i> Size: <?php echo htmlspecialchars($product['file_size'] ?? 'N/A'); ?></li>
                    <li><i class="bi bi-download"></i> Downloads: <?php echo number_format($product['download_count']); ?></li>
                </ul>
            </div>
            
            <div class="d-grid gap-2">
                <?php if (isLoggedIn()): ?>
                    <a href="checkout.php?product_id=<?php echo $product['id']; ?>" 
                       class="btn btn-primary btn-lg">
                        <i class="bi bi-cart"></i> Buy Now
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-cart"></i> Login to Purchase
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
