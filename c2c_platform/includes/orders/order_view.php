<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Order.php';
require_once __DIR__ . '/../../src/Product.php';
requireLogin();

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header("Location: order_list.php");
    exit();
}

$order = new Order($db);
$orderDetails = $order->getOrderDetails($orderId, $_SESSION['user_id']);

if (!$orderDetails) {
    $_SESSION['error_message'] = "Order not found";
    header("Location: order_list.php");
    exit();
}

$pageTitle = "Order #" . $orderDetails['id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Single Order View -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Order #<?= $orderDetails['id'] ?></h2>
    <a href="order_list.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Orders
    </a>
</div>

<div class="row">
    <!-- Order Summary -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Order Details</h5>

                <!-- Order Status -->
                <div class="alert alert-<?= getStatusColor($orderDetails['status']) ?> mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Status: <?= htmlspecialchars($orderDetails['status']) ?></strong>
                        <small><?= date('M j, Y g:i A', strtotime($orderDetails['date_created'])) ?></small>
                    </div>
                </div>

                <!-- Products List -->
                <h6 class="mb-3">Products</h6>
                <?php foreach ($orderDetails['products'] as $product): ?>
                    <div class="row mb-3 border-bottom pb-3 align-items-center">
                        <div class="col-md-2">
                            <img src="<?= BASE_URL ?>assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                class="img-fluid rounded" alt="<?= htmlspecialchars($product['product_name']) ?>"
                                style="max-height: 80px;">
                        </div>
                        <div class="col-md-6">
                            <h6><?= htmlspecialchars($product['product_name']) ?></h6>
                            <p class="text-muted mb-1"><?= htmlspecialchars($product['variation_name']) ?></p>
                            <small class="text-muted">Seller: <?= htmlspecialchars($product['seller_name']) ?></small>
                        </div>
                        <div class="col-md-2 text-center">
                            <span class="text-muted">x<?= $product['quantity'] ?></span>
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="fw-bold">R<?= number_format($product['price'] * $product['quantity'], 2) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Contact Seller Column -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Seller Information</h5>
            </div>
            <div class="card-body">
                <?php
                // Get seller's user ID (not seller_id)
                $sellerUserId = (new Product($db))->getSellerUserId($orderDetails['seller_id']);
                ?>
                <div class="d-grid gap-2 mt-3">
                    <a href="<?= BASE_URL ?>includes/messaging/initiate_chat.php?product_id=<?= $orderDetails['products'][0]['product_id'] ?? '' ?>&seller_id=<?= $sellerUserId ?>&buyer_id=<?= $_SESSION['user_id'] ?>"
                        class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-envelope"></i> Contact Seller
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>