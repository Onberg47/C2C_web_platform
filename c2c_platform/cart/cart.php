<?php

require_once __DIR__ . '/../config.php';
requireLogin();

// Database connections
require_once __DIR__ . '/../src/Cart.php';
require_once __DIR__ . '/../src/Shipping.php';

$cart = new Cart($db);
$shipping = new Shipping($db);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_shipping'])) {
        $_SESSION['selected_shipping'] = (int) $_POST['shipping_option'];
    } elseif (isset($_POST['remove_item'])) {
        $cart->removeItem($_SESSION['user_id'], (int) $_POST['variation_id']);
    }
}

// Handle quantity update
if (isset($_POST['update_quantity'])) {
    $variationId = (int) $_POST['variation_id'];
    $quantity = max(1, (int) $_POST['quantity']);

    // Update in DB
    $stmt = $db->prepare("
        UPDATE Cart 
        SET Quantity = ? 
        WHERE User_ID = ? AND Variation_ID = ?
    ");
    $stmt->execute([$quantity, $_SESSION['user_id'], $variationId]);
}

/// /// ///

// Handle shipping selection - must come BEFORE getting shipping options
if (isset($_GET['shipping'])) {
    $selectedShipping = (int) $_GET['shipping'];
    // Only set session if the option is valid
    $_SESSION['selected_shipping'] = $selectedShipping;
} else {
    $selectedShipping = $_SESSION['selected_shipping'] ?? null;
}

// Get shipping options
$shippingOptions = $shipping->getAllOptions();

// Validate selected shipping exists
if ($selectedShipping && !isset($shippingOptions[$selectedShipping])) {
    $selectedShipping = null;
    unset($_SESSION['selected_shipping']); // Clear invalid selection
}

/// /// ///

// Get cart contents
$cartItems = $cart->getCart($_SESSION['user_id']);
$shippingOptions = $shipping->getAllOptions();
$selectedShipping = $_SESSION['selected_shipping'] ?? null;

// Calculate totals
$subtotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);

$shippingCost = 0;
$platformFee = 0;

if ($selectedShipping) {
    $shippingCost = $shippingOptions[$selectedShipping]['base_cost'];
    $platformFee = $shippingOptions[$selectedShipping]['platform_fee'];
}

$total = $subtotal + $shippingCost + $platformFee;

/// /// ///

// Display payment messages if they exist - put this right after opening <?php tag
if (!empty($_SESSION['payment_message'])) {
    // Ensure it's an array and has the required keys
    $message = $_SESSION['payment_message'];
    if (is_array($message) && isset($message['type'], $message['text'])) {
        $type = htmlspecialchars($message['type']);
        $text = htmlspecialchars($message['text']);
        echo "
        <div class='position-fixed bottom-0 end-0 p-3' style='z-index: 11'>
            <div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$text}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        </div>
        <script>
            // Auto-close after 3 seconds
            setTimeout(() => {
                bootstrap.Alert.getOrCreateInstance(document.querySelector('.alert')).close();
            }, 3000);
        </script>
        ";
    }
    unset($_SESSION['payment_message']);
}

/// /// ///

$pageTitle = "Your Shopping Cart";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Your Cart</h2>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="<?= BASE_URL ?>">Browse products</a>
        </div>
    <?php else: ?>
        <!-- Cart Items -->
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="row mb-3 align-items-center border-bottom pb-3">
                                <div class="col-md-2">
                                    <img src="<?= BASE_URL ?>assets/images/products/<?= htmlspecialchars($item['image']) ?>"
                                        class="img-fluid rounded" alt="<?= htmlspecialchars($item['product_name']) ?>"
                                        style="max-height: 80px;">
                                </div>
                                <div class="col-md-5">
                                    <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                    <p class="text-muted mb-1"><?= htmlspecialchars($item['variation_name']) ?></p>
                                    <small class="text-muted">Seller: <?= htmlspecialchars($item['seller_name']) ?></small>
                                </div>

                                <!-- Update cart -->
                                <div class="col-md-3">
                                    <form method="POST" class="d-flex">
                                        <input type="hidden" name="variation_id" value="<?= $item['variation_id'] ?>">
                                        <input type="number" class="form-control" name="quantity"
                                            value="<?= $item['quantity'] ?>" min="1" style="width: 70px;">
                                        <button type="submit" name="update_quantity" class="btn btn-outline-primary ms-2">
                                            Update
                                        </button>
                                    </form>
                                </div>

                                <div class="col-md-2 text-end">
                                    <p class="fw-bold">R<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="variation_id" value="<?= $item['variation_id'] ?>">
                                        <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>

                        <div class="mb-3">
                            <form method="GET" id="shippingForm">
                                <input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>">
                                <select class="form-select" name="shipping" required>
                                    <option value="">Select shipping</option>
                                    <?php foreach ($shippingOptions as $id => $option): ?>
                                        <option value="<?= $id ?>" <?= ($selectedShipping == $id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($option['name']) ?>
                                            (R<?= number_format($option['base_cost'] + $option['platform_fee'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>

                            <script>
                                // Auto-submit when shipping changes
                                document.querySelector('[name="shipping"]').addEventListener('change', function () {
                                    document.getElementById('shippingForm').submit();
                                });
                                document.addEventListener('DOMContentLoaded', function () {
                                    if (!document.querySelector('[name="shipping"]').value) {
                                        document.querySelector('[name="shipping"]').focus();
                                    }
                                });
                            </script>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>R<?= number_format($subtotal, 2) ?></span>
                        </div>

                        <?php if ($selectedShipping): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span>R<?= number_format($shippingCost, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Platform Fee:</span>
                                <span>R<?= number_format($platformFee, 2) ?></span>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total:</span>
                            <span>R<?= number_format($total, 2) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>cart/checkout.php/?shipping=<?= $selectedShipping ?>"
                            class="btn btn-primary w-100 mt-3 <?= !$selectedShipping ? 'disabled' : '' ?>">
                            Proceed to Checkout
                        </a>

                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>