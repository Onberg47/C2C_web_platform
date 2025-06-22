<?php

class Order
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /// ///

    public function createOrder($userId, $sellerId, $items, $shippingId)
    {
        $this->db->beginTransaction();

        try {
            // Calculate product total
            $productTotal = array_reduce($items, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);

            // Get shipping costs
            $shippingOption = $this->getShippingOption($shippingId);
            $shippingCost = $shippingOption['base_cost'];
            $platformFee = $shippingOption['platform_fee'];
            $totalCost = $productTotal + $shippingCost + $platformFee;

            // Verify seller exists
            $sellerUserId = $this->getSellerUserId($sellerId);
            if (!$sellerUserId) {
                throw new Exception("Invalid seller ID: $sellerId");
            }

            //Order processing failed: SQLSTATE[21S01]: Insert value list does not match column list: 1136 Column count doesn't match value count at row 1
            // Create the order
            $stmt = $this->db->prepare("
            INSERT INTO `Order` (
                User_ID, 
                Seller_ID, 
                Total_Cost, 
                Shipping_Cost,
                ShippingOption_ID, 
                Status
            ) VALUES (?, ?, ?, ?, ?, 'Processing')
        ");
            $stmt->execute([
                $userId,
                $sellerId,
                $totalCost,
                $shippingCost,
                $shippingId
            ]);
            $orderId = $this->db->lastInsertId();

            // Add order products
            foreach ($items as $item) {
                $this->addOrderProduct($orderId, $item);
                $this->updateProductStock($item['variation_id'], $item['quantity']);
            }

            // Add status history
            $this->addStatusHistory($orderId, 'Processing');

            // Create transaction
            $this->createTransaction(
                $orderId,
                $userId,
                $sellerUserId,
                $totalCost
            );

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function createTransaction($orderId, $userId, $recipientId, $amount)
    {
        $gatewayId = 'TRX_' . uniqid();

        $stmt = $this->db->prepare("
        INSERT INTO Transaction (
            Payee_ID,
            Recipient_ID,
            Order_ID,
            Gateway_Transaction_ID,
            Transaction_Type,
            Payment_Status,
            Amount,
            Date_Created
        ) VALUES (?, ?, ?, ?, 'Purchase', 'Completed', ?, NOW())
    ");
        $stmt->execute([
            $userId,
            $recipientId,
            $orderId,
            $gatewayId,
            $amount
        ]);
    }

    private function addOrderProduct($orderId, $item)
    {
        $stmt = $this->db->prepare("
            INSERT INTO OrderProducts (
                Order_ID, 
                Variation_ID, 
                Quantity
            ) VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $orderId,
            $item['variation_id'],
            $item['quantity']
        ]);
    }

    private function getSellerUserId($sellerId)
    {
        $stmt = $this->db->prepare("
        SELECT User_ID FROM Seller WHERE Seller_ID = ?
    ");
        $stmt->execute([$sellerId]);
        return $stmt->fetchColumn();
    }

    private function updateProductStock($variationId, $quantity)
    {
        $stmt = $this->db->prepare("
            UPDATE ProductVariation 
            SET Stock_Quantity = Stock_Quantity - ? 
            WHERE Variation_ID = ?
        ");
        $stmt->execute([$quantity, $variationId]);
    }

    private function logOrderCreation($userId, $sellerId, $orderId)
    {
        $stmt = $this->db->prepare("
            INSERT INTO AdminLogs (
                User_ID,
                Target_ID,
                Action_Type,
                Report_Type,
                Status,
                Description,
                Timestamp
            ) VALUES (?, ?, 'system', 'New order', 'logged', ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $this->getSellerUserId($sellerId),
            "A new order (#$orderId) was placed and auto-recorded"
        ]);
    }

    private function addStatusHistory($orderId, $status)
    {
        $stmt = $this->db->prepare("
            INSERT INTO OrderStatusHistory (
                Order_ID, 
                Status
            ) VALUES (?, ?)
        ");
        $stmt->execute([$orderId, $status]);
    }

    private function getShippingOption($shippingId)
    {
        $stmt = $this->db->prepare("
            SELECT base_cost, platform_fee 
            FROM ShippingOptions 
            WHERE ShippingOption_ID = ?
        ");
        $stmt->execute([$shippingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /// /// Order_list /// ///

    public function getUserOrders($userId)
    {
        $query = "
        SELECT 
            o.Order_ID as id,
            o.Total_Cost as total,
            o.Status as status,
            o.Date_Created as date_created,
            COUNT(op.OrderProducts_ID) as item_count
        FROM `Order` o
        LEFT JOIN OrderProducts op ON o.Order_ID = op.Order_ID
        WHERE o.User_ID = ?
        GROUP BY o.Order_ID
        ORDER BY o.Date_Created DESC
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderDetails($orderId, $userId)
    {
        // Get order header
        $query = "
        SELECT 
            o.Order_ID as id,
            o.Seller_ID as seller_id,
            o.Total_Cost as total,
            o.Shipping_Cost as shipping_cost,
            o.Status as status,
            o.Date_Created as date_created,
            so.Name as shipping_name,
            so.Platform_Fee as platform_fee,
            (o.Total_Cost - o.Shipping_Cost - so.Platform_Fee) as subtotal
        FROM `Order` o
        JOIN ShippingOptions so ON o.ShippingOption_ID = so.ShippingOption_ID
        WHERE o.Order_ID = ? AND o.User_ID = ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Get order products
        $query = "
        SELECT 
            op.Quantity as quantity,
            pv.Variation_ID as variation_id,
            pv.Variation_Name as variation_name,
            pv.Price as price,
            p.Product_ID as product_id,
            p.Name as product_name,
            u.Username as seller_name,
            (SELECT File_Name FROM ProductImage pi 
             WHERE pi.Variation_ID = pv.Variation_ID LIMIT 1) as image
        FROM OrderProducts op
        JOIN ProductVariation pv ON op.Variation_ID = pv.Variation_ID
        JOIN Product p ON pv.Product_ID = p.Product_ID
        JOIN Seller s ON p.Seller_ID = s.Seller_ID
        JOIN Users u ON s.User_ID = u.User_ID
        WHERE op.Order_ID = ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$orderId]);
        $order['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    /// Seller List

    public function getSellerOrders($sellerUserId)
    {
        $stmt = $this->db->prepare("
        SELECT 
            o.Order_ID AS id,
            o.Total_Cost AS total,
            o.Status AS status,
            o.Date_Created AS date_created,
            COUNT(op.OrderProducts_ID) AS item_count,
            SUM(op.Quantity) AS total_quantity,
            buyer.Username AS buyer_name,
            buyer.User_ID AS buyer_id
        FROM `Order` o
        JOIN Seller s ON o.Seller_ID = s.Seller_ID
        JOIN Users seller ON s.User_ID = seller.User_ID
        JOIN Users buyer ON o.User_ID = buyer.User_ID
        JOIN OrderProducts op ON o.Order_ID = op.Order_ID
        WHERE seller.User_ID = ?
        GROUP BY o.Order_ID
        ORDER BY o.Date_Created DESC
    ");
        $stmt->execute([$sellerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // getSellerOrders()

    public function getSellerOrderDetails($orderId, $sellerId)
    {
        // Verify seller owns this order first
        $stmt = $this->db->prepare("
        SELECT 
            o.Order_ID as id,
            o.Total_Cost as total,
            o.Shipping_Cost as shipping_cost,
            o.Status as status,
            o.Date_Created as date_created,
            so.Name as shipping_name,
            so.Platform_Fee as platform_fee,
            (o.Total_Cost - o.Shipping_Cost - so.Platform_Fee) as subtotal,
            u.User_ID as buyer_id,
            u.Username as buyer_name,
            u.Email_Address as buyer_email
        FROM `Order` o
        JOIN ShippingOptions so ON o.ShippingOption_ID = so.ShippingOption_ID
        JOIN Users u ON o.User_ID = u.User_ID
        JOIN Seller s ON o.Seller_ID = s.Seller_ID
        WHERE o.Order_ID = ? 
        AND s.Seller_ID = ?
    ");

        $stmt->execute([$orderId, $sellerId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Get order products
        $query = "
        SELECT 
            op.Quantity as quantity,
            pv.Variation_ID as variation_id,
            pv.Variation_Name as variation_name,
            pv.Price as price,
            p.Product_ID as product_id,
            p.Name as product_name,
            (SELECT File_Name FROM ProductImage pi 
             WHERE pi.Variation_ID = pv.Variation_ID LIMIT 1) as image
        FROM OrderProducts op
        JOIN ProductVariation pv ON op.Variation_ID = pv.Variation_ID
        JOIN Product p ON pv.Product_ID = p.Product_ID
        WHERE op.Order_ID = ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$orderId]);
        $order['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get status history
        $stmt = $this->db->prepare("
        SELECT Status, Timestamp 
        FROM OrderStatusHistory 
        WHERE Order_ID = ?
        ORDER BY Timestamp DESC
    ");
        $stmt->execute([$orderId]);
        $order['status_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    } // getSellerOrderDetails()

    public function updateOrderStatus($orderId, $sellerUserId, $newStatus)
    {
        $this->db->beginTransaction();
        try {
            // Verify seller owns this order
            $stmt = $this->db->prepare("
            UPDATE `Order` o
            JOIN Seller s ON o.Seller_ID = s.Seller_ID
            SET o.Status = ?
            WHERE o.Order_ID = ? 
            AND s.User_ID = ?
        ");
            $stmt->execute([$newStatus, $orderId, $sellerUserId]);

            // Add status history
            $this->addStatusHistory($orderId, $newStatus);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

} // Order