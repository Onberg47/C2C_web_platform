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
$success = '';
$isEditMode = false;
$existingProduct = null;
$existingVariations = [];

// Initialize edit mode if product ID provided
if (isset($_GET['id'])) {
    $isEditMode = true;
    try {
        $existingProduct = $product->getProductDetails($_GET['id']);
        if (!$existingProduct || $existingProduct['seller_id'] != $product->getSellerId($_SESSION['user_id'])) {
            throw new Exception("Product not found or access denied");
        }
        $existingVariations = $product->getProductVariations($_GET['id']);
    } catch (Exception $e) {
        $error = $e->getMessage();
        $isEditMode = false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission";
    } else {
        try {
            $db->beginTransaction();

            // 1. Get seller ID
            $sellerId = $product->getSellerId($_SESSION['user_id']);
            if (!$sellerId)
                throw new Exception("Seller account not found");

            // 2. Create or update product
            if ($isEditMode && !empty($_POST['product_id'])) {
                $productId = $_POST['product_id'];
                $product->updateProduct($productId, sanitizeInput($_POST['product_name']));
            } else {
                $productId = $product->createProduct($sellerId, sanitizeInput($_POST['product_name']));
            }

            // 3. Process variations
            if (empty($_POST['variation_name'])) {
                throw new Exception("At least one variation is required");
            }

            foreach ($_POST['variation_name'] as $index => $name) {
                if (empty(trim($name)))
                    continue;

                // Handle existing variations
                if ($isEditMode && !empty($_POST['existing_variation_id'][$index])) {
                    $variationId = $_POST['existing_variation_id'][$index];
                    $product->updateVariation(
                        $variationId,
                        sanitizeInput($name),
                        (float) $_POST['variation_price'][$index],
                        (int) $_POST['variation_stock'][$index],
                        sanitizeInput($_POST['variation_description'][$index])
                    );
                }
                // Handle new variations
                else {
                    $variationId = $product->createVariation(
                        $productId,
                        sanitizeInput($name),
                        (float) $_POST['variation_price'][$index],
                        (int) $_POST['variation_stock'][$index],
                        sanitizeInput($_POST['variation_description'][$index])
                    );
                }

                // Handle image deletions
                if (!empty($_POST['delete_images'][$index])) {
                    foreach ($_POST['delete_images'][$index] as $imageId) {
                        $product->deleteImage($imageId);
                    }
                }

                // Process new image uploads - USING OUR FIXED VERSION
                if (!empty($_FILES['variation_images']['name'][$index])) {
                    $fileNames = [];
                    $tmpFiles = [];

                    // Handle both single and multiple file uploads
                    if (is_array($_FILES['variation_images']['name'][$index])) {
                        foreach ($_FILES['variation_images']['name'][$index] as $i => $name) {
                            if ($_FILES['variation_images']['error'][$index][$i] === UPLOAD_ERR_OK) {
                                $fileNames[] = $name;
                                $tmpFiles[] = $_FILES['variation_images']['tmp_name'][$index][$i];
                            }
                        }
                    } else {
                        if ($_FILES['variation_images']['error'][$index] === UPLOAD_ERR_OK) {
                            $fileNames[] = $_FILES['variation_images']['name'][$index];
                            $tmpFiles[] = $_FILES['variation_images']['tmp_name'][$index];
                        }
                    }

                    if (!empty($fileNames)) {
                        $product->uploadVariationImages($variationId, $fileNames, $tmpFiles);
                    }
                }
            }

            $db->commit();
            $_SESSION['success_message'] = $isEditMode ? "Product updated successfully!" : "Product created successfully!";
            header("Location: product_listings.php");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

$pageTitle = $isEditMode ? "Edit Product" : "Add New Product";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><?= $pageTitle ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <!-- SUCCESS MESSAGE -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= htmlspecialchars($_SESSION['success_message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <form id="productForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <?php if ($isEditMode): ?>
                            <input type="hidden" name="product_id" value="<?= $existingProduct['id'] ?>">
                        <?php endif; ?>

                        <!-- Product Basics -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Product Information</h4>
                            <div class="row mt-3">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Product Name*</label>
                                    <input type="text" class="form-control" name="product_name" required
                                        value="<?= $isEditMode ? htmlspecialchars($existingProduct['name']) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Variations Section -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Product Variations</h4>
                            <p class="text-muted">Manage different versions of your product</p>

                            <div id="variationsContainer">
                                <?php if ($isEditMode && !empty($existingVariations)): ?>
                                    <?php foreach ($existingVariations as $varIndex => $variation): ?>
                                        <div class="variation-card card mb-3">
                                            <div class="card-body">
                                                <input type="hidden" name="existing_variation_id[]"
                                                    value="<?= $variation['variation_id'] ?>">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Variation Name*</label>
                                                        <input type="text" class="form-control" name="variation_name[]" required
                                                            value="<?= htmlspecialchars($variation['name']) ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Price*</label>
                                                        <input type="number" class="form-control" name="variation_price[]"
                                                            step="0.01" min="0" required
                                                            value="<?= htmlspecialchars($variation['price']) ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Stock*</label>
                                                        <input type="number" class="form-control" name="variation_stock[]"
                                                            min="0" required
                                                            value="<?= htmlspecialchars($variation['stock']) ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" name="variation_description[]" rows="1"><?=
                                                            htmlspecialchars($variation['description']) ?></textarea>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label class="form-label">Images</label>
                                                        <?php
                                                        $variationImages = $product->getProductImages($variation['variation_id']);
                                                        foreach ($variationImages as $image): ?>
                                                            <div class="d-inline-block me-2 mb-2">
                                                                <img src="<?= BASE_URL ?>assets/images/products/<?= htmlspecialchars($image['file_name']) ?>"
                                                                    class="img-thumbnail" width="80">
                                                                <div class="form-check mt-1">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="delete_images[<?= $varIndex ?>][]"
                                                                        value="<?= htmlspecialchars($image['product_image_id']) ?>">
                                                                    <label class="form-check-label small">Delete</label>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <input type="file" class="form-control mt-2"
                                                            name="variation_images[<?= $varIndex ?>][]" multiple>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Default empty variation for new products -->
                                    <div class="variation-card card mb-3">
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Variation Name*</label>
                                                    <input type="text" class="form-control" name="variation_name[]"
                                                        required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Price*</label>
                                                    <input type="number" class="form-control" name="variation_price[]"
                                                        step="0.01" min="0" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Stock*</label>
                                                    <input type="number" class="form-control" name="variation_stock[]"
                                                        min="0" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="variation_description[]"
                                                        rows="1"></textarea>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label">Images</label>
                                                    <input type="file" class="form-control" name="variation_images[0][]"
                                                        multiple accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="button" class="btn btn-outline-primary" id="addVariation">
                                <i class="bi bi-plus-circle"></i> Add Another Variation
                            </button>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <?= $isEditMode ? 'Update Product' : 'Create Product' ?>
                            </button>
                            <a href="product_listings.php" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Add variation
        const addBtn = document.getElementById('addVariation');
        const container = document.getElementById('variationsContainer');

        if (addBtn && container) {
            addBtn.addEventListener('click', function () {
                const template = container.querySelector('.variation-card').cloneNode(true);
                const newIndex = container.querySelectorAll('.variation-card').length;

                // Clear inputs
                template.querySelectorAll('input:not([type="file"]), textarea').forEach(input => {
                    input.value = '';
                });

                // Update file input name with new index
                const fileInput = template.querySelector('input[type="file"]');
                fileInput.name = `variation_images[${newIndex}][]`;
                fileInput.value = '';

                // Add remove button
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-outline-danger btn-sm mt-2';
                removeBtn.innerHTML = '<i class="bi bi-trash"></i> Remove Variation';
                removeBtn.addEventListener('click', function () {
                    if (container.querySelectorAll('.variation-card').length > 1) {
                        template.remove();
                    } else {
                        alert('You need at least one variation');
                    }
                });

                template.querySelector('.card-body').appendChild(removeBtn);
                container.appendChild(template);
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>