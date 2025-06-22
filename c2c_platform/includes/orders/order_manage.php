<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Order.php';
require_once __DIR__ . '/../../src/Product.php';
requireLogin();

if (!isSeller($db)) {
    header("Location: order_list.php");
    exit();
}

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header("Location: order_list.php?view=sales");
    exit();
}

$order = new Order($db);
$product = new Product($db);
$sellerId = $product->getSellerId($_SESSION['user_id']);
$orderDetails = $order->getSellerOrderDetails($orderId, $sellerId);

if (!$orderDetails) {
    $_SESSION['error_message'] = "Order not found";
    header("Location: order_list.php?view=sales");
    exit();
}

$pageTitle = "Manage Order #" . $orderDetails['id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Order #<?= $orderDetails['id'] ?></h2>
        <a href="order_list.php?view=sales" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Sales
        </a>
    </div>

    <div class="row">
        <!-- Order Details Column -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Details</h5>
                </div>
                <div class="card-body">
                    <!-- Status Alert -->
                    <div class="alert alert-<?= getStatusColor($orderDetails['status']) ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Current Status: <?= $orderDetails['status'] ?></strong>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    Update Status
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach (['Pending', 'Processing', 'Shipped', 'Completed'] as $status): ?>
                                        <li>
                                            <a class="dropdown-item"
                                                href="update_order_status.php?order_id=<?= $orderDetails['id'] ?>&status=<?= $status ?>">
                                                Mark as <?= $status ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Products List -->
                    <h6 class="mb-3">Products</h6>
                    <?php foreach ($orderDetails['products'] as $product): ?>
                        <div class="row mb-3 border-bottom pb-3 align-items-center">
                            <div class="col-md-2">
                                <img src="<?= BASE_URL ?>assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                    class="img-fluid rounded" style="max-height: 80px;">
                            </div>
                            <div class="col-md-6">
                                <h6><?= htmlspecialchars($product['product_name']) ?></h6>
                                <p class="text-muted mb-1"><?= htmlspecialchars($product['variation_name']) ?></p>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="text-muted">x<?= $product['quantity'] ?></span>
                            </div>
                            <div class="col-md-2 text-end">
                                <span
                                    class="fw-bold">R<?= number_format($product['price'] * $product['quantity'], 2) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Status History -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Status History</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($orderDetails['status_history'] as $history): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?= getStatusColor($history['Status']) ?>">
                                    <?= $history['Status'] ?>
                                </span>
                                <small class="text-muted">
                                    <?= date('M j, Y g:i A', strtotime($history['Timestamp'])) ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Customer & Actions Column -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <h6><?= htmlspecialchars($orderDetails['buyer_name']) ?></h6>
                    <p class="mb-1"><?= htmlspecialchars($orderDetails['buyer_email']) ?></p>

                    <div class="d-grid gap-2 mt-3">

                        <a href="<?= BASE_URL ?>includes/messaging/initiate_chat.php?seller_id=<?= $_SESSION['user_id'] ?>&buyer_id=<?= $orderDetails['buyer_id'] ?>"
                            class="btn btn-primary">
                            <i class="bi bi-envelope"></i> Contact Customer
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>R<?= number_format($orderDetails['subtotal'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>R<?= number_format($orderDetails['shipping_cost'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Platform Fee:</span>
                        <span>R<?= number_format($orderDetails['platform_fee'], 2) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total:</span>
                        <span>R<?= number_format($orderDetails['total'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>