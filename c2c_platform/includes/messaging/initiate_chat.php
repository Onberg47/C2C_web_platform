<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/MessageRoom.php';
requireLogin();

try {
    // Get user IDs - seller is current user when initiated from order list
    $sellerUserId = $_GET['seller_id'] ?? null;
    $buyerUserId = $_GET['buyer_id'] ?? null;
    $productId = $_GET['product_id'] ?? null; // Optional for product context

    // Validate participants
    if (!$sellerUserId || !$buyerUserId) {
        throw new Exception("Invalid chat participants");
    }
    if ($sellerUserId === $buyerUserId) {
        throw new Exception("You cannot message yourself");
    }


    // Verify current user is one of the participants
    if ($_SESSION['user_id'] != $sellerUserId && $_SESSION['user_id'] != $buyerUserId) {
        throw new Exception("Unauthorized chat access");
    }

    // Get or create room
    $messageRoom = new MessageRoom($db);
    $roomId = $messageRoom->getOrCreateRoom($sellerUserId, $buyerUserId);

    // Prepare default message if product context exists
    $defaultMessage = '';
    if ($productId) {
        $product = new Product($db);
        $productDetails = $product->getProductDetails($productId);
        if ($productDetails) {
            $defaultMessage = rawurlencode(
                "Good day, I am interested in product: " .
                $productDetails['name'] . " (ID: " . $productId . ")"
            );
        }
    }

    // Redirect to message room
    $redirectUrl = BASE_URL . "includes/messaging/message_room.php?id=$roomId";
    if ($defaultMessage) {
        $redirectUrl .= "&default_message=$defaultMessage";
        $productParam = $productId ? "&product_id=$productId" : "";
        header("Location: " . BASE_URL . "includes/messaging/message_room.php?id=$roomId$productParam");
        exit();
    }

    header("Location: $redirectUrl");
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
    exit();
}