<div class="card h-100 shadow-sm">
    <!-- Product Image -->
    <div class="position-relative">
        <img src="<?= BASE_URL ?>assets/images/products/<?= htmlspecialchars($product['image'] ?? 'default.png') ?>"
            class="card-img-top" alt="<?= htmlspecialchars($product['alt_text'] ?? $product['name']) ?>"
            style="height: 200px; object-fit: cover;">

        <!-- Rating Badge -->
        <div class="position-absolute top-0 end-0 m-2">
            <span class="badge bg-warning text-dark">
                <i class="bi bi-star-fill"></i> <?= number_format($product['ranking'], 1) ?>
            </span>
        </div>
    </div>

    <!-- Product Body -->
    <div class="card-body d-flex flex-column">
        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
        <div class="mt-auto">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-primary fw-bold">R<?= number_format($product['price'], 2) ?></span>
                <small class="text-muted">Sold by <?= htmlspecialchars($product['seller_name']) ?></small>
            </div>
            <a href="<?= BASE_URL ?>product/view.php?id=<?= $product['id'] ?>"
                class="btn btn-sm btn-outline-primary w-100">
                View Details
            </a>
        </div>
    </div>
</div>