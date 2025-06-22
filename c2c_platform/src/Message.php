<?php

class Message
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /// ///

    // In src/Message.php
    public function sendMessage($roomId, $senderId, $content)
    {
        // Validate input
        if (empty(trim($content))) {
            throw new Exception("Message cannot be empty");
        }

        $stmt = $this->db->prepare("
        INSERT INTO Message 
        (MessageRoom_ID, Sender_ID, Contents) 
        VALUES (?, ?, ?)
    ");

        $cleanContent = sanitizeInput($content);
        $success = $stmt->execute([$roomId, $senderId, $cleanContent]);

        if (!$success) {
            throw new Exception("Failed to send message");
        }

        return $this->db->lastInsertId();
    } // sendMessage()

    /// /// ///

    public function getRoomMessages($roomId)
    {
        $stmt = $this->db->prepare("
        SELECT 
            m.Message_ID as message_id,
            m.MessageRoom_ID as messageRoom_id,
            m.Sender_ID as sender_id,
            m.Contents as contents,
            m.is_read,
            m.Timestamp as timestamp,
            u.Username as sender_name
        FROM Message m
        JOIN Users u ON m.Sender_ID = u.User_ID
        WHERE m.MessageRoom_ID = ?
        ORDER BY m.Timestamp ASC
    ");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead($messageId, $userId)
    {
        // For future read receipts
    }

    /// /// ///

    public function getLastMessage($roomId)
    {
        $stmt = $this->db->prepare("
        SELECT contents, timestamp 
        FROM Message 
        WHERE MessageRoom_ID = ? 
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
        $stmt->execute([$roomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } // getLastMessage()

    public function getUnreadCount($roomId, $userId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) as count
        FROM Message
        WHERE MessageRoom_ID = ? 
        AND Sender_ID != ?
        AND is_read = 0
    ");
        $stmt->execute([$roomId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } // getUnreadCount()

    /// /// ///

    public function markMessagesAsRead($roomId, $userId)
    {
        $stmt = $this->db->prepare("
        UPDATE Message 
        SET is_read = TRUE 
        WHERE MessageRoom_ID = ? 
        AND Sender_ID != ?
        AND is_read = FALSE
    ");
        return $stmt->execute([$roomId, $userId]);
    } // markMessagesAsRead()

} // Message