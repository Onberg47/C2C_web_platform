<?php
error_log("\n\n=== NEW PRODUCT SUBMISSION ===");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } elseif ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "CSRF token validation failed";
    } else {
        try {
            // Start transaction using the global $db connection
            $db->beginTransaction();

            // 1. Get seller ID
            $sellerId = $product->getSellerId($_SESSION['user_id']);
            if (!$sellerId)
                throw new Exception("Seller account not found");

            // 2. Create main product
            $productId = $product->createProduct(
                $sellerId,
                sanitizeInput($_POST['product_name'])
            );

            // 3. Process variations
            if (empty($_POST['variation_name'])) {
                throw new Exception("At least one variation is required");
            }

            foreach ($_POST['variation_name'] as $index => $name) {
                if (empty(trim($name)))
                    continue;

                $variationId = $product->createVariation(...); // Your existing code

                // Handle file uploads for this variation
                if (!empty($_FILES['variation_images']['name'][$index])) {
                    // Normalize to always handle as array
                    $fileNames = (array) $_FILES['variation_images']['name'][$index];
                    $tmpFiles = (array) $_FILES['variation_images']['tmp_name'][$index];
                    $errors = (array) $_FILES['variation_images']['error'][$index];

                    $validFiles = [];
                    $validTmpFiles = [];

                    foreach ($fileNames as $fileIndex => $fileName) {
                        if ($errors[$fileIndex] === UPLOAD_ERR_OK && !empty($fileName)) {
                            $validFiles[] = $fileName;
                            $validTmpFiles[] = $tmpFiles[$fileIndex];
                            error_log("Processing file {$fileName} for variation {$variationId}");
                        }
                    }

                    if (!empty($validFiles)) {
                        $product->uploadVariationImages($variationId, $validFiles, $validTmpFiles);
                    }
                }
            }

            error_log("=== PROCESSING COMPLETE ==="); // for variations

            $db->commit();
            $_SESSION['success_message'] = "Product created successfully!";
            header("Location: product_listings.php");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

$pageTitle = "Add New Product";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Add New Product</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form id="productForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- Product Basics -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Product Information</h4>
                            <div class="row mt-3">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Product Name*</label>
                                    <input type="text" class="form-control" name="product_name" required>
                                </div>
                            </div>
                        </div>

                        <!-- Variations Section -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Product Variations</h4>
                            <p class="text-muted">Add different versions of your product (colors, sizes, etc.)</p>

                            <div id="variationsContainer">
                                <!-- Variation Template -->
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
                                                <input type="file" class="form-control"
                                                    name="variation_images[<?= $index ?>][]" multiple accept="image/*">
                                                <small class="text-muted">Upload multiple images for this
                                                    variation</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-primary" id="addVariation">
                                <i class="bi bi-plus-circle"></i> Add Another Variation
                            </button>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Create Product
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

                // Clear all inputs in the cloned variation
                template.querySelectorAll('input:not([type="file"]), textarea').forEach(input => {
                    input.value = '';
                });

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

        // Form validation
        const form = document.getElementById('productForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                const variationNames = document.querySelectorAll('input[name="variation_name[]"]');
                let valid = true;

                variationNames.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required variation fields');
                }
            });
        }
    });

    // Update your addVariation function to:
    function addVariation() {
        const template = container.querySelector('.variation-card').cloneNode(true);

        // Clear all inputs except files
        template.querySelectorAll('input:not([type="file"]), textarea').forEach(input => {
            input.value = '';
        });

        // Clear file input value while keeping the name structure
        const fileInput = template.querySelector('input[type="file"]');
        fileInput.value = '';

        // Update the name attribute with new index
        const newIndex = container.querySelectorAll('.variation-card').length;
        fileInput.name = `variation_images[${newIndex}][]`;

        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-outline-danger btn-sm mt-2';
        removeBtn.innerHTML = '<i class="bi bi-trash"></i> Remove Variation';
        removeBtn.addEventListener('click', function () {
            if (container.querySelectorAll('.variation-card').length > 1) {
                template.remove();
                // Reindex remaining variations
                container.querySelectorAll('.variation-card').forEach((card, index) => {
                    card.querySelector('input[type="file"]').name = `variation_images[${index}][]`;
                });
            } else {
                alert('You need at least one variation');
            }
        });

        template.querySelector('.card-body').appendChild(removeBtn);
        container.appendChild(template);
    }

</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>