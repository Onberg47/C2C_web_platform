<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/MessageRoom.php';
require_once __DIR__ . '/../../src/Message.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit("Invalid request");
}

try {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Invalid form submission");
    }

    $message = new Message($db);
    $roomId = $_POST['room_id'] ?? null;
    $content = $_POST['message'] ?? '';

    // Validate room access
    $messageRoom = new MessageRoom($db);
    if (!$messageRoom->verifyUserAccess($roomId, $_SESSION['user_id'])) {
        throw new Exception("Invalid conversation");
    }

    // Send message
    $message->sendMessage($roomId, $_SESSION['user_id'], $content);

    // Return to message room
    header("Location: message_room.php?id=" . $roomId);
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'my_messages.php'));
    exit();
}