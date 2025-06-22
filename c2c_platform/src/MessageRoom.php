<?php

class MessageRoom
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /// ///

    // New method to get or create room
    public function getOrCreateRoom($sellerUserId, $buyerUserId)
    {
        // Check if room exists
        $stmt = $this->db->prepare("
        SELECT MessageRoom_ID 
        FROM MessageRoom 
        WHERE Sellers_User_ID = ? AND User_ID = ?
    ");
        $stmt->execute([$sellerUserId, $buyerUserId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($room) {
            return $room['MessageRoom_ID'];
        }

        // Create new room
        $stmt = $this->db->prepare("
        INSERT INTO MessageRoom (Sellers_User_ID, User_ID)
        VALUES (?, ?)
    ");
        $stmt->execute([$sellerUserId, $buyerUserId]);
        return $this->db->lastInsertId();
    }

    /// /// 

    public function getRoomsAsBuyer($userId)
    {
        $stmt = $this->db->prepare("
        SELECT 
            mr.MessageRoom_ID as message_room_id,
            mr.Sellers_User_ID as sellers_user_id,
            u.Username as seller_name
        FROM MessageRoom mr
        JOIN Users u ON mr.Sellers_User_ID = u.User_ID
        WHERE mr.User_ID = ?
        ORDER BY mr.MessageRoom_ID DESC
    ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Updated getRoomsAsSeller
    public function getRoomsAsSeller($sellerUserId)
    {
        $stmt = $this->db->prepare("
        SELECT 
            mr.MessageRoom_ID as message_room_id, 
            mr.User_ID as user_id, 
            u.Username as user_name
        FROM MessageRoom mr
        JOIN Users u ON mr.User_ID = u.User_ID
        WHERE mr.Sellers_User_ID = ?
        ORDER BY mr.MessageRoom_ID DESC
    ");
        $stmt->execute([$sellerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // getRoomsAsSeller()

    /// ///

    public function getRoomInfo($roomId, $userId)
    {
        $stmt = $this->db->prepare("
        SELECT 
            mr.MessageRoom_ID,
            mr.Sellers_User_ID,
            mr.User_ID,
            IF(mr.Sellers_User_ID = ?, u.Username, s.Username) as other_user_name,
            mr.Sellers_User_ID = ? as is_seller
        FROM MessageRoom mr
        LEFT JOIN Users u ON mr.User_ID = u.User_ID
        LEFT JOIN Users s ON mr.Sellers_User_ID = s.User_ID
        WHERE mr.MessageRoom_ID = ? 
        AND (mr.Sellers_User_ID = ? OR mr.User_ID = ?)
    ");
        $stmt->execute([$userId, $userId, $roomId, $userId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } // getRoomInfo()

    /// /// ///

    public function verifyUserAccess($roomId, $userId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM MessageRoom 
        WHERE MessageRoom_ID = ? 
        AND (Sellers_User_ID = ? OR User_ID = ?)
    ");
        $stmt->execute([$roomId, $userId, $userId]);
        return $stmt->fetchColumn() > 0;
    } // verifyUserAccess()

    // unused for now but I should add it for "production"
    public function validateParticipants($sellerUserId, $buyerUserId)
    {
        if ($sellerUserId === $buyerUserId) {
            throw new Exception("Seller and buyer cannot be the same user");
        }

        // Verify both users exist
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Users WHERE User_ID IN (?, ?)");
        $stmt->execute([$sellerUserId, $buyerUserId]);
        if ($stmt->fetchColumn() !== 2) {
            throw new Exception("Invalid chat participants");
        }
    }

} // MessageRoom