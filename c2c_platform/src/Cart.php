<?php

class Cart
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /// /// /// Functions /// /// ///

    public function getCart($userId)
    {
        $query = "
            SELECT 
                c.Quantity as quantity,
                pv.Variation_ID as variation_id,
                pv.Variation_Name as variation_name,
                pv.Price as price,
                p.Product_ID as product_id,
                p.Name as product_name,
                u.Username as seller_name,
                (SELECT File_Name FROM ProductImage pi 
                 WHERE pi.Variation_ID = pv.Variation_ID LIMIT 1) as image
            FROM Cart c
            JOIN ProductVariation pv ON c.Variation_ID = pv.Variation_ID
            JOIN Product p ON pv.Product_ID = p.Product_ID
            JOIN Seller s ON p.Seller_ID = s.Seller_ID
            JOIN Users u ON s.User_ID = u.User_ID
            WHERE c.User_ID = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addToCart($userId, $variationId, $quantity = 1)
    {
        // Check if item already in cart
        $stmt = $this->db->prepare("
            INSERT INTO Cart (User_ID, Variation_ID, Quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE Quantity = Quantity + VALUES(Quantity)
        ");

        return $stmt->execute([$userId, $variationId, $quantity]);
    }

    public function removeItem($userId, $variationId)
    {
        $stmt = $this->db->prepare("
            DELETE FROM Cart 
            WHERE User_ID = ? AND Variation_ID = ?
        ");

        return $stmt->execute([$userId, $variationId]);
    }

    public function getCount($userId)
    {
        $stmt = $this->db->prepare("
        SELECT SUM(Quantity) as total 
        FROM Cart 
        WHERE User_ID = ?
    ");
        $stmt->execute([$userId]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function clearCart($userId)
    {
        $stmt = $this->db->prepare("
        DELETE FROM Cart 
        WHERE User_ID = ?
    ");
        return $stmt->execute([$userId]);
    }

} // Cart