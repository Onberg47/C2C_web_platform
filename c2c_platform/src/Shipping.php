<?php
class Shipping
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllOptions()
    {
        $query = "
            SELECT 
                ShippingOption_ID as id,
                Name as name,
                Priority as priority,
                Base_Cost as base_cost,
                Platform_Fee as platform_fee
            FROM ShippingOptions
            ORDER BY Priority
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $options = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $option) {
            $options[$option['id']] = $option;
        }

        return $options;
    }

    // Get details of a particualr shipping for Order creation
    public function getOption($shippingId)
    {
        $query = "
        SELECT 
            ShippingOption_ID as id,
            Name as name,
            Priority as priority,
            Base_Cost as base_cost,
            Platform_Fee as platform_fee
        FROM ShippingOptions
        WHERE ShippingOption_ID = ?
        LIMIT 1
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$shippingId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception("Shipping option not found");
        }

        return $result;
    }

} // Shipping