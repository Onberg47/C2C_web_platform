<?php
require_once __DIR__ . '/../../config.php';
requireLogin();

// Database connections
require_once __DIR__ . '/../../src/Order.php';
require_once __DIR__ . '/../../src/Product.php';

$order = new Order($db);
$product = new Product($db);

// Get user's orders
$orders = $order->getUserOrders($_SESSION['user_id']);

// Handle single order view if ID is provided
$orderDetail = null;
if (isset($_GET['order_id'])) {
    $orderId = (int) $_GET['order_id'];
    $orderDetail = $order->getOrderDetails($orderId, $_SESSION['user_id']);
}

$pageTitle = $orderDetail ? "Order #" . $orderDetail['id'] : "Your Orders";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <?php if ($orderDetail): ?>
        <!-- Single Order View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Order #<?= $orderDetail['id'] ?></h2>
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
                        <div class="alert alert-<?= getStatusColor($orderDetail['status']) ?> mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Status: <?= htmlspecialchars($orderDetail['status']) ?></strong>
                                <small><?= date('M j, Y g:i A', strtotime($orderDetail['date_created'])) ?></small>
                            </div>
                        </div>

                        <!-- Products List -->
                        <h6 class="mb-3">Products</h6>
                        <?php foreach ($orderDetail['products'] as $product): ?>
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
                                    <span
                                        class="fw-bold">R<?= number_format($product['price'] * $product['quantity'], 2) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>

                        <div class="mb-3">
                            <h6>Shipping Method</h6>
                            <p><?= htmlspecialchars($orderDetail['shipping_name']) ?></p>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>R<?= number_format($orderDetail['subtotal'], 2) ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>R<?= number_format($orderDetail['shipping_cost'], 2) ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Platform Fee:</span>
                            <span>R<?= number_format($orderDetail['platform_fee'], 2) ?></span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total:</span>
                            <span>R<?= number_format($orderDetail['total'], 2) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Transaction Details -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment</h5>
                        <p class="text-success">
                            <i class="bi bi-check-circle-fill"></i>
                            Paid on <?= date('M j, Y', strtotime($orderDetail['date_created'])) ?>
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="#" class="btn btn-outline-primary">
                                <i class="bi bi-printer"></i> Print Receipt
                            </a>
                            <?php if ($orderDetail['status'] === 'Processing'): ?>
                                <a href="#" class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Cancel Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Orders List View -->
        <h2 class="mb-4">Your Orders</h2>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                You haven't placed any orders yet.
                <a href="<?= BASE_URL ?>">Browse products</a> to get started!
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($orders as $order): ?>
                    <a href="order_list.php?order_id=<?= $order['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Order #<?= $order['id'] ?></h5>
                                <small class="text-muted">
                                    <?= date('M j, Y g:i A', strtotime($order['date_created'])) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                                <div class="fw-bold mt-1">R<?= number_format($order['total'], 2) ?></div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// Helper function for status colors
function getStatusColor($status)
{
    switch ($status) {
        case 'Completed':
            return 'success';
        case 'Processing':
            return 'primary';
        case 'Cancelled':
            return 'secondary';
        case 'Shipped':
            return 'info';
        default:
            return 'warning';
    }
}

require_once __DIR__ . '/../../includes/footer.php';
?>