<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Order.php';
require_once __DIR__ . '/../../src/Product.php';
requireLogin();

if (!isSeller($db) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: " . BASE_URL . "includes/auth/login.php");
    exit();
}

$order = new Order($db);
$product = new Product($db);

try {
    $orderId = $_GET['order_id'] ?? null;
    $newStatus = $_GET['status'] ?? null;
    $sellerId = $product->getSellerId($_SESSION['user_id']);

    if (!$orderId || !$newStatus) {
        throw new Exception("Invalid request");
    }

    $order->updateOrderStatus($orderId, $_SESSION['user_id'], $newStatus);
    $_SESSION['success_message'] = "Order status updated successfully!";

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'order_list.php'));
exit();