<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Order.php';
require_once __DIR__ . '/../../src/Product.php';
requireLogin();

$order = new Order($db);
$product = new Product($db);

// Determine current view mode
$isViewingSales = isset($_GET['view']) && $_GET['view'] === 'sales' && isSeller($db);
$sellerId = isSeller($db) ? $product->getSellerId($_SESSION['user_id']) : null;

// Get appropriate orders
if ($isViewingSales) {
    $orders = $order->getSellerOrders($_SESSION['user_id']);
    $sectionTitle = "My Sales";
} else {
    $orders = $order->getUserOrders($_SESSION['user_id']);
    $sectionTitle = "My Orders";
}

$pageTitle = $sectionTitle;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $sectionTitle ?></h2>

        <?php if (isSeller($db)): ?>
            <div class="btn-group" role="group">
                <a href="order_list.php?view=orders"
                    class="btn btn-outline-primary <?= !$isViewingSales ? 'active' : '' ?>">
                    My Orders
                </a>
                <a href="order_list.php?view=sales" class="btn btn-outline-primary <?= $isViewingSales ? 'active' : '' ?>">
                    My Sales
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            <?= $isViewingSales ? 'You have no sales yet.' : 'You haven\'t placed any orders yet.' ?>
            <a href="<?= BASE_URL ?>">Browse products</a> to get started!
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <?php if ($isViewingSales): ?>
                            <th>Customer</th>
                        <?php endif; ?>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <?php if ($isViewingSales): ?>
                                <td>
                                    <a
                                        href="<?= BASE_URL ?>includes/messaging/initiate_chat.php?seller_id=<?= $_SESSION['user_id'] ?>&buyer_id=<?= $order['buyer_id'] ?>">
                                        <?= htmlspecialchars($order['buyer_name']) ?>
                                    </a>
                                </td>
                            <?php endif; ?>
                            <td><?= date('M j, Y', strtotime($order['date_created'])) ?></td>
                            <td><?= $order['item_count'] ?></td>
                            <td>R<?= number_format($order['total'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                    <?= $order['status'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="<?= $isViewingSales ? 'order_manage.php' : 'order_view.php' ?>?id=<?= $order['id'] ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($isViewingSales): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown">
                                                Update
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php foreach (['Pending', 'Processing', 'Shipped', 'Completed'] as $status): ?>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="update_order_status.php?order_id=<?= $order['id'] ?>&status=<?= $status ?>">
                                                            Mark as <?= $status ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php';?>