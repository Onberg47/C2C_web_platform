<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Product.php';

// Redirect if not logged in or not a seller
if (!isLoggedIn() || !isSeller($db)) {
    header("Location: " . BASE_URL . "includes/auth/login.php");
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid form submission";
    header("Location: " . BASE_URL . "includes/seller/product_listings.php");
    exit();
}

if (!empty($_POST['product_id'])) {
    try {
        $product = new Product($db);
        $success = $product->deleteProduct((int) $_POST['product_id']);

        if ($success) {
            $_SESSION['success_message'] = "Product deleted successfully";
        } else {
            $_SESSION['error_message'] = "Product not found or already deleted";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error deleting product: " . $e->getMessage();
    }
}

header("Location: " . BASE_URL . "includes/seller/product_listings.php");
exit();