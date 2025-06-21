<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Product.php';

// Redirect if not logged in or not a seller
if (!isLoggedIn() || !isSeller($db)) {
    header("Location: " . BASE_URL . "includes/auth/login.php");
    exit();
}

$product = new Product($db);
$error = '';

try {
    // Get seller ID
    $sellerId = $product->getSellerId($_SESSION['user_id']);
    if (!$sellerId)
        throw new Exception("Seller account not found");

    // Get products with their main image and variation counts
    $products = $product->getProductsBySeller($sellerId);

    // Enhance each product with its variations
    foreach ($products as &$productData) {
        $productData['variations'] = $product->getProductVariations($productData['id']);
        $productData['main_image'] = $product->getMainProductImage($productData['id']);
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = "My Product Listings";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Product Listings</h2>
        <a href="add_product.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add New Product
        </a>
    </div>

    <?php if (empty($products)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-box-seam display-4 text-muted mb-3"></i>
                <h3>No Products Listed Yet</h3>
                <p class="text-muted">Get started by adding your first product</p>
                <a href="add_product.php" class="btn btn-primary btn-lg">
                    Add Your First Product
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Variations</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($product['main_image'])): ?>
                                        <img src="<?= BASE_URL . 'assets/images/products/' . htmlspecialchars($product['main_image']) ?>"
                                            class="rounded me-3" width="60" height="60" style="object-fit: cover;">
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                                        <small class="text-muted">ID: <?= htmlspecialchars($product['id']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= count($product['variations']) ?> options
                            </td>
                            <td>
                                <?= array_sum(array_column($product['variations'], 'stock')) ?> total
                            </td>
                            <td>
                                <span class="badge bg-<?= $product['active'] ? 'success' : 'warning' ?>">
                                    <?= $product['active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form method="POST" action="delete_product.php" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this product permanently? This cannot be undone!')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>