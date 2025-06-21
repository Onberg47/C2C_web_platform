<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$data = json_decode(file_get_contents('php://input'), true);
$variationId = (int) ($data['variation_id'] ?? 0);
$quantity = (int) ($data['quantity'] ?? 1);

if ($variationId <= 0 || $quantity <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid input']));
}

require_once __DIR__ . '/../src/Cart.php';
$cart = new Cart($db);

try {
    $success = $cart->addToCart($_SESSION['user_id'], $variationId, $quantity);
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}