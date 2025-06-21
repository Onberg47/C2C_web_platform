<?php
class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Register new user
    public function register($username, $email, $password, $addressData)
    {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }

        // Check if user exists
        if ($this->findUserByEmail($email)) {
            throw new Exception("Email already registered");
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // 1. Insert address
            $stmt = $this->db->prepare("INSERT INTO Addresses (Street, City, Province, ZIP_Code) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $addressData['street'],
                $addressData['city'],
                $addressData['province'],
                $addressData['zip']
            ]);
            $addressId = $this->db->lastInsertId();

            // 2. Insert user (FIXED COLUMN NAME)
            $stmt = $this->db->prepare("INSERT INTO Users (Username, Email_Address, DeliveryAddress_ID) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $addressId]);
            $userId = $this->db->lastInsertId();

            // 3. Insert credentials
            /* For use with SALT
            $salt = bin2hex(random_bytes(32));
            $hashedPassword = hash('sha256', $password . $salt);
            */
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $salt = ''; // Not needed anymore but keep column for compatibility

            $stmt = $this->db->prepare("INSERT INTO UserCredentials (User_ID, Password_HASH, Salt) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $hashedPassword, $salt]);

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Find user by email
    public function findUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT User_ID FROM Users WHERE Email_Address = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Login user
    public function login($email, $password)
    {
        // Get user with credentials
        $stmt = $this->db->prepare("
            SELECT u.User_ID, u.Username, uc.Password_HASH, uc.Salt 
            FROM Users u
            JOIN UserCredentials uc ON u.User_ID = uc.User_ID
            WHERE u.Email_Address = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("Invalid credentials"); //
        }

        // Verify password
        /* For use with SALT
        $hashedInput = hash('sha256', $password . $user['Salt']);
        if ($hashedInput !== $user['Password_HASH']) {
        */
        if (!password_verify($password, $user['Password_HASH'])) {
            throw new Exception("Invalid credentials");   //
        }

        return [
            'id' => $user['User_ID'],
            'username' => $user['Username']
        ];
    }

    // Check if user is seller
    public function isSeller($userId)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM Seller WHERE User_ID = ?");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetch();
    }

    // Upgrade to seller
    public function becomeSeller($userId, $sellerAddress, $pickupPoints = [])
    {
        $this->db->beginTransaction();

        try {
            // 1. Create seller address
            $addressId = $this->createAddress($sellerAddress);

            // 2. Create seller record
            $stmt = $this->db->prepare("INSERT INTO Seller (User_ID, Address_ID) VALUES (?, ?)");
            $stmt->execute([$userId, $addressId]);
            $sellerId = $this->db->lastInsertId();

            // 3. Process pickup points
            foreach ($pickupPoints as $point) {
                if (!empty($point['street'])) {
                    $pickupAddressId = $this->createAddress([
                        'street' => $point['street'],
                        'city' => $point['city'],
                        'province' => $point['province'],
                        'zip' => $point['zip']
                    ]);

                    // Create pickup point
                    $stmt = $this->db->prepare("INSERT INTO PickupPoints (Address_ID, OperatingHours) VALUES (?, ?)");
                    $stmt->execute([$pickupAddressId, $point['hours']]);
                    $pickupId = $this->db->lastInsertId();

                    // Link to seller
                    $stmt = $this->db->prepare("INSERT INTO SellerPickups (Seller_ID, PickupPoint_ID) VALUES (?, ?)");
                    $stmt->execute([$sellerId, $pickupId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function createAddress($addressData)
    {
        $stmt = $this->db->prepare("
        INSERT INTO Addresses (Street, City, Province, ZIP_Code) 
        VALUES (?, ?, ?, ?)
    ");
        $stmt->execute([
            $addressData['street'],
            $addressData['city'],
            $addressData['province'],
            $addressData['zip']
        ]);
        return $this->db->lastInsertId();
    }

    public function getUserAddress($userId)
    {
        $stmt = $this->db->prepare("
        SELECT a.Street as street, a.City as city, 
               a.Province as province, a.ZIP_Code as zip
        FROM Users u
        JOIN Addresses a ON u.DeliveryAddress_ID = a.Address_ID
        WHERE u.User_ID = ?
    ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }

} // User
?>