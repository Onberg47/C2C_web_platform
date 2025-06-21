<?php
require_once __DIR__ . '/../config.php';

// Get product ID
$productId = (int) ($_GET['id'] ?? 0);

// Database connection
require_once __DIR__ . '/../src/Product.php';
$product = new Product($db);

// Fetch product details
// Get selected variation if any
$variationId = $_GET['variation'] ?? null;

// Fetch product details
$productDetails = $product->getProductDetails($productId, $variationId);
$variations = $product->getProductVariations($productId);

// If specific variation requested but not found, redirect to default view
if ($variationId && !in_array($variationId, array_column($variations, 'variation_id'))) {
    header("Location: ?id=$productId");
    exit();
}

// Handle 404 if product not found
if (empty($productDetails)) {
    header("HTTP/1.0 404 Not Found");
    die("Product not found");
}

$pageTitle = $productDetails['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Product Gallery Section -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body text-center">
                <!-- Main Image -->
                <img id="mainProductImage"
                    src="<?= BASE_URL ?>assets/images/products/<?= htmlspecialchars($productDetails['main_image']) ?>"
                    class="img-fluid rounded" alt="<?= htmlspecialchars($productDetails['name']) ?>"
                    style="max-height: 400px;">

                <!-- Thumbnails -->
                <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
                    <?php foreach ($productDetails['images'] as $image): ?>
                        <img src="<?= BASE_URL ?>assets/images/products/<?= htmlspecialchars($image['file_name']) ?>"
                            class="img-thumbnail cursor-pointer" style="width: 80px; height: 80px; object-fit: cover;"
                            onclick="document.getElementById('mainProductImage').src = this.src"
                            alt="<?= htmlspecialchars($image['alt_text']) ?>">
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Section -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h1><?= htmlspecialchars($productDetails['name']) ?></h1>

                <!-- Rating -->
                <div class="d-flex align-items-center mb-3">
                    <div class="text-warning me-2">
                        <?= str_repeat('★', floor($productDetails['ranking'])) ?>
                        <?= str_repeat('☆', 5 - floor($productDetails['ranking'])) ?>
                    </div>
                    <span class="text-muted">(<?= number_format($productDetails['ranking'], 1) ?>)</span>
                </div>

                <!-- Variations -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Options:</label>
                    <select class="form-select" id="variationSelect">
                        <?php foreach ($variations as $var): ?>
                            <option value="<?= $var['variation_id'] ?>" data-price="<?= $var['price'] ?>"
                                data-stock="<?= $var['stock'] ?>" <?= ($variationId && $var['variation_id'] == $variationId) || (!$variationId && $var === reset($variations)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($var['name']) ?> -
                                R<?= number_format($var['price'], 2) ?>
                                (<?= $var['stock'] > 0 ? "In Stock" : "Out of Stock" ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price -->
                <div class="fs-3 text-primary mb-4">
                    R<span id="productPrice">
                        <?= number_format(
                            ($variationId
                                ? $variations[array_search($variationId, array_column($variations, 'variation_id'))]['price']
                                : $variations[0]['price'] ?? 0),
                            2
                        )
                            ?>
                    </span>
                </div>

                <!-- Add to Cart -->
                <div class="d-flex gap-2 mb-4">
                    <div class="input-group" style="width: 120px;">
                        <button class="btn btn-outline-secondary" type="button" id="decrementQty">-</button>
                        <input type="number" class="form-control text-center" value="1" min="1" id="productQty">
                        <button class="btn btn-outline-secondary" type="button" id="incrementQty">+</button>
                    </div>
                    <button class="btn btn-primary flex-grow-1" id="addToCartBtn">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                    </button>
                    <button class="btn btn-outline-danger" id="wishlistBtn">
                        <i class="bi bi-heart"></i>
                    </button>
                </div>

                <!-- Seller Info -->
                <div class="card mb-4">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">
                                <i class="bi bi-person-circle"></i>
                                Sold by: <?= htmlspecialchars($productDetails['seller_name']) ?>
                            </span>
                            <a href="<?= BASE_URL ?>messages/?to=<?= $productDetails['seller_id'] ?>"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-envelope"></i> Contact Seller
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <h5>Description</h5>
                    <p class="text-muted">
                        <?= !empty($variationId
                            ? $variations[array_search($variationId, array_column($variations, 'variation_id'))]['description']
                            : $productDetails['description'])
                            ? nl2br(htmlspecialchars(
                                $variationId
                                ? $variations[array_search($variationId, array_column($variations, 'variation_id'))]['description']
                                : $productDetails['description']
                            ))
                            : 'No description provided' ?>
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Product Page -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const variationSelect = document.getElementById('variationSelect');
        const currentVariation = new URLSearchParams(window.location.search).get('variation');

        // Initialize with correct variation
        if (currentVariation) {
            variationSelect.value = currentVariation;
            updatePrice();
        }

        // Update price when variation changes
        variationSelect.addEventListener('change', function () {
            const variationId = this.value;
            updatePrice();

            // Only reload if the variation actually changed
            if (currentVariation !== variationId) {
                window.location.href = `?id=<?= $productId ?>&variation=${variationId}`;
            }
        });

        function updatePrice() {
            const selectedOption = variationSelect.options[variationSelect.selectedIndex];
            document.getElementById('productPrice').textContent =
                parseFloat(selectedOption.dataset.price).toFixed(2);
        }

        // Quantity controls
        document.getElementById('incrementQty').addEventListener('click', () => {
            const qty = document.getElementById('productQty');
            qty.value = parseInt(qty.value) + 1;
        });

        document.getElementById('decrementQty').addEventListener('click', () => {
            const qty = document.getElementById('productQty');
            if (qty.value > 1) qty.value = parseInt(qty.value) - 1;
        });

        // Add to Cart functionality
        document.getElementById('addToCartBtn').addEventListener('click', async () => {
            const variationId = document.getElementById('variationSelect').value;
            const quantity = document.getElementById('productQty').value;

            try {
                const response = await fetch('<?= BASE_URL ?>api/cart_add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        variation_id: variationId,
                        quantity: quantity
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Item added to cart!');
                    // Optionally update cart count in navbar
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Network error - please try again');
            }
        });

    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>