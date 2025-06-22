<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/MessageRoom.php';
require_once __DIR__ . '/../../src/Message.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "includes/auth/login.php");
    exit();
}

$messageRoom = new MessageRoom($db);
$message = new Message($db);

try {
    // Get all message rooms for current user
    $userId = $_SESSION['user_id'];

    // Rooms where user is the buyer
    $buyerRooms = $messageRoom->getRoomsAsBuyer($userId);

    // Rooms where user is the seller
    $sellerRooms = $messageRoom->getRoomsAsSeller($userId);

    // Enhance rooms with last message and unread count
    $enhanceRoom = function (&$room) use ($message) {
        $room['last_message'] = $message->getLastMessage($room['message_room_id']);
        $room['unread_count'] = $message->getUnreadCount($room['message_room_id'], $_SESSION['user_id']);
    };

    array_walk($buyerRooms, $enhanceRoom);
    array_walk($sellerRooms, $enhanceRoom);

} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = "My Messages";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Messages</h2>
    </div>

    <!-- Buyer Chats Section -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Your Conversations with Sellers</h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($buyerRooms)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-chat-square-text display-4 text-muted mb-3"></i>
                    <h4>No conversations yet</h4>
                    <p class="text-muted">Start a conversation with a seller from their product page</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($buyerRooms as $room): ?>
                        <a href="message_room.php?id=<?= $room['message_room_id'] ?>"
                            class="list-group-item list-group-item-action py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($room['seller_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= displayMessageContent($room['last_message']['contents'] ?? null, 50) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="d-block text-muted">
                                        <?= $room['last_message'] ?
                                            formatMessageTime($room['last_message']['timestamp']) : '' ?>
                                    </small>
                                    <?php if ($room['unread_count'] > 0): ?>
                                        <span class="badge bg-primary rounded-pill">
                                            <?= $room['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seller Chats Section -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h3 class="mb-0">Customer Inquiries</h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($sellerRooms)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people display-4 text-muted mb-3"></i>
                    <h4>No customer messages yet</h4>
                    <p class="text-muted">Customers will appear here when they message you</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($sellerRooms as $room): ?>
                        <a href="message_room.php?id=<?= $room['message_room_id'] ?>"
                            class="list-group-item list-group-item-action py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($room['user_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= displayMessageContent($room['last_message']['contents'] ?? null, 50) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="d-block text-muted">
                                        <?= $room['last_message'] ?
                                            formatMessageTime($room['last_message']['timestamp']) : '' ?>
                                    </small>
                                    <?php if ($room['unread_count'] > 0): ?>
                                        <span class="badge bg-primary rounded-pill">
                                            <?= $room['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- End of chat sections -->
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>