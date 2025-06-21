<?php
$pageTitle = "Browse Products";
require_once __DIR__ . '/includes/header.php';

// Database connection
require_once __DIR__ . '/src/Product.php';
$product = new Product($db);

// Get filter values
$filters = [
    'search' => $_GET['search'] ?? '',
    'max_price' => $_GET['price'] ?? '',
    'min_rating' => $_GET['rating'] ?? '',
    'seller_id' => $_GET['seller'] ?? ''
];

// Get filtered products
$products = $product->getFilteredProducts($filters);

// Get all sellers for filter dropdown
$sellers = $product->getAllSellers();
?>

<div class="container mt-4">
    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <form method="GET" class="d-flex">
                <input class="form-control me-2" type="search" name="search" placeholder="Search products..."
                    aria-label="Search" value="<?= htmlspecialchars($filters['search']) ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="filterForm" method="GET">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search']) ?>">

                        <div class="row g-3">
                            <!-- Price Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Max Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">R</span>
                                    <input type="number" class="form-control" name="price"
                                        value="<?= htmlspecialchars($filters['max_price']) ?>" placeholder="Any" min="0"
                                        step="0.01">
                                </div>
                            </div>

                            <!-- Rating Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Min Rating</label>
                                <select class="form-select" name="rating">
                                    <option value="">Any</option>
                                    <option value="5" <?= $filters['min_rating'] == '5' ? 'selected' : '' ?>>★★★★★</option>
                                    <option value="4" <?= $filters['min_rating'] == '4' ? 'selected' : '' ?>>★★★★+</option>
                                    <option value="3" <?= $filters['min_rating'] == '3' ? 'selected' : '' ?>>★★★+</option>
                                </select>
                            </div>

                            <!-- Seller Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Seller</label>
                                <select class="form-select" name="seller">
                                    <option value="">All Sellers</option>
                                    <?php foreach ($sellers as $seller): ?>
                                        <option value="<?= $seller['Seller_ID'] ?>"
                                            <?= $filters['seller_id'] == $seller['Seller_ID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($seller['Username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-funnel"></i> Apply
                                </button>
                                <a href="?" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="row">
        <?php if (empty($products)): ?>
            <div class="col-12 text-center my-5">
                <div class="alert alert-info">
                    No products found matching your criteria.
                    <?php if (!empty($filters['search'])): ?>
                        Search term: "<?= htmlspecialchars($filters['search']) ?>"
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <?php include __DIR__ . '/includes/product_card.php'; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>