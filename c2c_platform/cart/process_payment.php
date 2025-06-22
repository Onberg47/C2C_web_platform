<?php
require_once __DIR__ . '/../config.php';
requireLogin();

// Debug logging
error_log("Payment processor accessed at " . date('Y-m-d H:i:s'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method");
    header("Location: " . BASE_URL);
    exit;
}

// Validate payment simulation
$paymentResult = $_POST['payment_result'] ?? '';
$shippingId = (int) ($_POST['shipping_id'] ?? 0);
$total = (float) ($_POST['total'] ?? 0);

error_log("Payment parameters - result: $paymentResult, shipping: $shippingId, total: $total");

// Validate payment result
if (!in_array($paymentResult, ['success', 'failed'])) {
    error_log("Invalid payment result value");
    $_SESSION['payment_message'] = [
        'type' => 'danger',
        'text' => 'Invalid payment status',
        'timestamp' => time()
    ];
    header("Location: " . BASE_URL . "cart/cart.php");
    exit;
}

// Validate numeric values
if ($shippingId <= 0 || $total <= 0) {
    error_log("Invalid numeric values");
    $_SESSION['payment_message'] = [
        'type' => 'danger',
        'text' => 'Invalid order values',
        'timestamp' => time()
    ];
    header("Location: " . BASE_URL . "cart/cart.php");
    exit;
}

// Handle failed payment
if ($paymentResult === 'failed') {
    error_log("Processing failed payment");
    $_SESSION['payment_message'] = [
        'type' => 'danger',
        'text' => 'Payment was declined. Please try again.',
        'timestamp' => time()
    ];
    header("Location: " . BASE_URL . "cart/cart.php");
    exit;
}

// Handle successful payment
require_once __DIR__ . '/../src/Order.php';
require_once __DIR__ . '/../src/Cart.php';
require_once __DIR__ . '/../src/Product.php';
require_once __DIR__ . '/../src/Shipping.php';

$order = new Order($db);
$cart = new Cart($db);
$product = new Product($db);
$shipping = new Shipping($db);

try {
    // Get cart items
    $cartItems = $cart->getCart($_SESSION['user_id']);
    
    // Group by seller (assuming single seller for simplicity)
    $sellerId = null;
    foreach ($cartItems as $item) {
        $variationDetails = $product->getVariationDetails($item['variation_id']);
        $sellerId = $variationDetails['seller_id'];
        break; // Get first seller (simplified)
    }
    
    if (!$sellerId) {
        throw new Exception("No valid seller found");
    }

    // Create order
    $orderId = $order->createOrder(
        $_SESSION['user_id'],
        $sellerId,
        $cartItems,
        $shippingId
    );
    
    // Clear cart and redirect
    $cart->clearCart($_SESSION['user_id']);
    
    $_SESSION['payment_message'] = [
        'type' => 'success',
        'text' => 'Order successfully placed!',
        'timestamp' => time()
    ];
    header("Location: " . BASE_URL . "includes/oders/order_list.php?order_id=" . $orderId);
    exit;
    
} catch (Exception $e) {
    error_log("Order creation failed: " . $e->getMessage());
    $_SESSION['payment_message'] = [
        'type' => 'danger',
        'text' => 'Order processing failed: ' . $e->getMessage(),
        'timestamp' => time()
    ];
    header("Location: " . BASE_URL . "cart/cart.php");
    exit;
}