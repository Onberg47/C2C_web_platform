<?php
require_once __DIR__ . '/../config.php';
requireLogin();

// Database connections
require_once __DIR__ . '/../src/Cart.php';
require_once __DIR__ . '/../src/Shipping.php';
require_once __DIR__ . '/../src/Product.php';
require_once __DIR__ . '/../src/Order.php';

$cart = new Cart($db);
$shipping = new Shipping($db);
$product = new Product($db);

// Validate shipping
$selectedShipping = $_GET['shipping'] ?? null;
$shippingOptions = $shipping->getAllOptions();

if (!$selectedShipping || !isset($shippingOptions[$selectedShipping])) {
    header("Location: " . BASE_URL . "cart/cart.php");
    exit;
}

// Get cart items and validate stock
$cartItems = $cart->getCart($_SESSION['user_id']);
$outOfStockItems = [];

foreach ($cartItems as $item) {
    $variation = $product->getVariationDetails($item['variation_id']);
    if ($variation['Stock_Quantity'] < $item['quantity']) {
        $outOfStockItems[] = $item['product_name'];
    }
}

if (!empty($outOfStockItems)) {
    $_SESSION['payment_message'] = [
        'type' => 'warning',
        'text' => 'Some items are out of stock: ' . implode(", ", $outOfStockItems),
        'timestamp' => time()
    ];
    header("Location: " . BASE_URL . "cart/cart.php");
    exit;
}

// Calculate totals
$subtotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);
$shippingCost = $shippingOptions[$selectedShipping]['base_cost'];
$platformFee = $shippingOptions[$selectedShipping]['platform_fee'];
$total = $subtotal + $shippingCost + $platformFee;

// Display checkout page
$pageTitle = "Checkout";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Checkout</h2>

    <!-- Order Summary -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Order Summary</h5>

            <div class="mb-3">
                <h6>Shipping Method</h6>
                <p><?= htmlspecialchars($shippingOptions[$selectedShipping]['name']) ?></p>
                <p>Cost: R<?= number_format($shippingCost + $platformFee, 2) ?></p>
            </div>

            <hr>

            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span>R<?= number_format($subtotal, 2) ?></span>
            </div>

            <div class="d-flex justify-content-between mb-2">
                <span>Shipping:</span>
                <span>R<?= number_format($shippingCost, 2) ?></span>
            </div>

            <div class="d-flex justify-content-between mb-2">
                <span>Platform Fee:</span>
                <span>R<?= number_format($platformFee, 2) ?></span>
            </div>

            <hr>

            <div class="d-flex justify-content-between fw-bold fs-5">
                <span>Total:</span>
                <span>R<?= number_format($total, 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Payment Buttons -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Complete Order</h5>

            <form method="POST" action="<?= BASE_URL ?>cart/process_payment.php" id="paymentForm">
                <input type="hidden" name="shipping_id" value="<?= $selectedShipping ?>">
                <input type="hidden" name="total" value="<?= $total ?>">
                <!-- This is the crucial fix - explicit success value -->
                <input type="hidden" name="payment_result" value="success">
                
                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-success btn-lg py-3">
                        <i class="bi bi-check-circle-fill"></i> Complete Order (R<?= number_format($total, 2) ?>)
                    </button>

                    <button type="button" id="simulateFailure" class="btn btn-outline-danger">
                        <i class="bi bi-x-circle-fill"></i> Simulate Failed Payment
                    </button>
                </div>
            </form>

            <script>
                // Handle simulate failure button
                document.getElementById('simulateFailure').addEventListener('click', function() {
                    if (confirm('Simulate a failed payment?')) {
                        const form = document.getElementById('paymentForm');
                        // Remove any existing payment_result inputs
                        document.querySelectorAll('input[name="payment_result"]').forEach(el => el.remove());
                        const failInput = document.createElement('input');
                        failInput.type = 'hidden';
                        failInput.name = 'payment_result';
                        failInput.value = 'failed';
                        form.appendChild(failInput);
                        form.submit();
                    }
                });

                // Add loading state to payment button
                document.getElementById('paymentForm').addEventListener('submit', function() {
                    const btn = this.querySelector('.btn-success');
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                    btn.disabled = true;
                });
            </script>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>