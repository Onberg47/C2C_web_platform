<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/MessageRoom.php';
require_once __DIR__ . '/../../src/Message.php';
requireLogin();

$messageRoom = new MessageRoom($db);
$message = new Message($db);
$error = '';
$roomInfo = null;
$messages = [];

try {
    // Get room ID from URL
    $roomId = $_GET['id'] ?? null;
    if (!$roomId)
        throw new Exception("No conversation specified");

    // Verify user has access to this room
    $userId = $_SESSION['user_id'];
    $roomInfo = $messageRoom->getRoomInfo($roomId, $userId);
    if (!$roomInfo)
        throw new Exception("Conversation not found");

    // Mark messages as read when opening
    $message->markMessagesAsRead($roomId, $userId);

    // Load messages
    $messages = $message->getRoomMessages($roomId);

} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = "Conversation";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($roomInfo): ?>
        <!-- Chat Header (Sticky) -->
        <div class="card mb-3 sticky-top" style="top: 56px; z-index: 1000;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        Conversation with <?= htmlspecialchars($roomInfo['other_user_name']) ?>
                    </h5>
                    <small><?= $roomInfo['is_seller'] ? 'Seller' : 'Buyer' ?></small>
                </div>
                <a href="my_messages.php" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="card mb-3" style="height: 60vh; overflow-y: auto;">
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-square-text display-4"></i>
                        <p>No messages yet</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($messages as $msg): ?>
                            <div class="<?= $msg['sender_id'] == $_SESSION['user_id'] ? 'align-self-end' : 'align-self-start' ?>">
                                <div class="card <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'bg-light' : '' ?>"
                                    style="max-width: 75%;">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between small text-muted mb-1">
                                            <span><?= htmlspecialchars($msg['sender_name']) ?></span>
                                            <span><?= formatMessageTime($msg['timestamp']) ?></span>
                                        </div>
                                        <p class="mb-0"><?= displayMessageContent($msg['contents']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Message Input -->
        <div class="card">
            <div class="card-body">
                <form id="messageForm" method="POST" action="send_message.php">
                    <input type="hidden" name="room_id" value="<?= $roomId ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="input-group">
                        <textarea name="message" class="form-control" placeholder="Type your message..." rows="1"
                            required></textarea>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


<!-- JavaScript for message_room Page -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Get default message from URL
        const urlParams = new URLSearchParams(window.location.search);
        const defaultMessage = urlParams.get('default_message');

        if (defaultMessage) {
            const textarea = document.querySelector('#messageForm textarea');
            if (textarea) {
                textarea.value = decodeURIComponent(defaultMessage);
                textarea.focus();

                // Remove the parameter from URL without reload
                history.replaceState({}, '', window.location.pathname + '?id=' + <?= $roomId ?>);
            }
        }

        // Auto-expand textarea as user types
        const textarea = document.querySelector('#messageForm textarea');
        if (textarea) {
            textarea.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    });
</script>