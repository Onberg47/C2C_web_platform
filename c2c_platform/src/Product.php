<?php
class Product
{
    private $db;
    private $uploadBaseDir;

    public function __construct($db)
    {
        $this->db = $db;
        $this->uploadBaseDir = rtrim(UPLOAD_BASE_DIR, '/') . '/';

        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadBaseDir)) {
            mkdir($this->uploadBaseDir, 0755, true);
        }
    }

    /// /// /// Functions /// /// ///

    /// Searches ///
    public function getAllSellers()
    {
        $query = "
        SELECT s.Seller_ID, u.Username 
        FROM Seller s
        JOIN Users u ON s.User_ID = u.User_ID
        ORDER BY u.Username
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // getAllSellers()

    public function getAllProducts()
    {
        $query = "
            SELECT 
                p.Product_ID as id,
                p.Name as name,
                p.Ranking as ranking,
                p.Date_Created as date_created,
                u.Username as seller_name,
                pv.Price as price,
                pi.File_Name as image,
                pi.Alt_Text as alt_text
            FROM Product p
            JOIN Seller s ON p.Seller_ID = s.Seller_ID
            JOIN Users u ON s.User_ID = u.User_ID
            JOIN ProductVariation pv ON p.Product_ID = pv.Product_ID
            LEFT JOIN ProductImage pi ON pv.Variation_ID = pi.Variation_ID
            GROUP BY p.Product_ID
            ORDER BY p.Date_Created DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // getAllProducts()

    public function searchProducts($searchTerm)
    {
        $query = "
        SELECT 
            p.Product_ID as id,
            p.Name as name,
            p.Ranking as ranking,
            p.Date_Created as date_created,
            u.Username as seller_name,
            pv.Price as price,
            pi.File_Name as image,
            pi.Alt_Text as alt_text
        FROM Product p
        JOIN Seller s ON p.Seller_ID = s.Seller_ID
        JOIN Users u ON s.User_ID = u.User_ID
        JOIN ProductVariation pv ON p.Product_ID = pv.Product_ID
        LEFT JOIN ProductImage pi ON pv.Variation_ID = pi.Variation_ID
        WHERE p.Name LIKE ? OR pv.Description LIKE ?
        GROUP BY p.Product_ID
        ORDER BY p.Date_Created DESC
    ";

        $stmt = $this->db->prepare($query);
        $searchParam = "%$searchTerm%";
        $stmt->bindParam(1, $searchParam);
        $stmt->bindParam(2, $searchParam);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // searchProducts()

    /// Filters ///
    public function getFilteredProducts($filters = [])
    {
        // Base query
        $query = "
        SELECT 
            p.Product_ID as id,
            p.Name as name,
            p.Ranking as ranking,
            p.Date_Created as date_created,
            u.Username as seller_name,
            pv.Price as price,
            pi.File_Name as image,
            pi.Alt_Text as alt_text,
            s.Seller_ID as seller_id
        FROM Product p
        JOIN Seller s ON p.Seller_ID = s.Seller_ID
        JOIN Users u ON s.User_ID = u.User_ID
        JOIN ProductVariation pv ON p.Product_ID = pv.Product_ID
        LEFT JOIN ProductImage pi ON pv.Variation_ID = pi.Variation_ID
    ";

        // WHERE conditions
        $conditions = [];
        $params = [];

        // Search term
        if (!empty($filters['search'])) {
            $conditions[] = "(p.Name LIKE ? OR pv.Description LIKE ?)";
            $searchParam = "%{$filters['search']}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        // Price filter
        if (!empty($filters['max_price'])) {
            $conditions[] = "pv.Price <= ?";
            $params[] = $filters['max_price'];
        }

        // Rating filter
        if (!empty($filters['min_rating'])) {
            $conditions[] = "p.Ranking >= ?";
            $params[] = $filters['min_rating'];
        }

        // Seller filter
        if (!empty($filters['seller_id'])) {
            $conditions[] = "s.Seller_ID = ?";
            $params[] = $filters['seller_id'];
        }

        // Combine conditions
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Group and order
        $query .= " GROUP BY p.Product_ID ORDER BY p.Date_Created DESC";

        // Execute
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // getFilteredProducts()

    /// Details ///

    public function getProductDetails($productId, $variationId = null)
    {
        $query = "
        SELECT 
            p.Product_ID as id,
            p.Name as name,
            p.Ranking as ranking,
            p.Date_Created as date_created,
            u.Username as seller_name,
            s.Seller_ID as seller_id,
            pv.Variation_ID as default_variation_id,
            pv.Description as description,
            (SELECT File_Name FROM ProductImage 
             WHERE Variation_ID = IFNULL(:variation_id, pv.Variation_ID) 
             LIMIT 1) as main_image
        FROM Product p
        JOIN Seller s ON p.Seller_ID = s.Seller_ID
        JOIN Users u ON s.User_ID = u.User_ID
        JOIN ProductVariation pv ON p.Product_ID = pv.Product_ID
        WHERE p.Product_ID = :product_id
        GROUP BY p.Product_ID
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':product_id' => $productId,
            ':variation_id' => $variationId
        ]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $product['images'] = $this->getProductImages($variationId ?? $product['default_variation_id']);
        }

        return $product;
    } // getProductDetails()

    public function getProductImages($variationId)
    {
        $query = "
        SELECT 
            ProductImage_ID as product_image_id,
            File_Name as file_name,
            Alt_Text as alt_text
        FROM ProductImage
        WHERE Variation_ID = ?
        ORDER BY ProductImage_ID
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$variationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // getProductImages()

    public function getProductVariations($productId)
    {
        $query = "
        SELECT 
            Variation_ID as variation_id,
            Variation_Name as name,
            Price as price,
            Stock_Quantity as stock,
            Description as description
        FROM ProductVariation
        WHERE Product_ID = ?
        ORDER BY Price
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // getProductVariations()

    // For cart and checkout details
    public function getVariationDetails($variationId)
    {
        $query = "
        SELECT 
            pv.*,
            p.Name as product_name,
            p.Product_ID as product_id,
            s.Seller_ID as seller_id,
            u.User_ID as user_id,
            u.Username as seller_name
        FROM ProductVariation pv
        JOIN Product p ON pv.Product_ID = p.Product_ID
        JOIN Seller s ON p.Seller_ID = s.Seller_ID
        JOIN Users u ON s.User_ID = u.User_ID
        WHERE pv.Variation_ID = ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$variationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSellerIdForVariation($variationId)
    {
        $query = "
        SELECT s.Seller_ID as seller_id
        FROM ProductVariation pv
        JOIN Product p ON pv.Product_ID = p.Product_ID
        JOIN Seller s ON p.Seller_ID = s.Seller_ID
        WHERE pv.Variation_ID = ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$variationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['seller_id'] : null;
    } // getSellerIdForVariation()

    /// For Seller's Dash ///

    public function getSellerId($userId)
    {
        $stmt = $this->db->prepare("
        SELECT Seller_ID FROM Seller WHERE User_ID = ?
    ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['Seller_ID'] : null;
    }

    public function getProductsBySeller($sellerId)
    {
        $stmt = $this->db->prepare("
        SELECT 
            p.Product_ID as id,
            p.Name as name,
            p.Ranking as ranking,
            p.Date_Created as date_created
        FROM Product p
        WHERE p.Seller_ID = ?
        ORDER BY p.Date_Created DESC
    ");
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } // getProductsBySeller()

    public function getMainProductImage($productId)
    {
        $stmt = $this->db->prepare("
        SELECT pi.File_Name
        FROM ProductImage pi
        JOIN ProductVariation pv ON pi.Variation_ID = pv.Variation_ID
        WHERE pv.Product_ID = ?
        LIMIT 1
    ");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['File_Name'] : null;
    } // getMainProductImage()

    /// /// Create Products /// ///

    public function createProduct($sellerId, $productName)
    {
        $stmt = $this->db->prepare("
        INSERT INTO Product (Seller_ID, Name) 
        VALUES (?, ?)
    ");
        $stmt->execute([$sellerId, $productName]);
        return $this->db->lastInsertId();
    } // createProduct()

    public function createVariation($productId, $name, $price, $stock, $description)
    {
        $stmt = $this->db->prepare("
        INSERT INTO ProductVariation 
        (Product_ID, Variation_Name, Price, Stock_Quantity, Description) 
        VALUES (?, ?, ?, ?, ?)
    ");
        $stmt->execute([$productId, $name, $price, $stock, $description]);
        return $this->db->lastInsertId();
    } // createVariation()

    public function uploadVariationImages($variationId, $fileNames, $tmpFiles)
    {
        $uploadBaseDir = UPLOAD_BASE_DIR;
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        // Create variation directory
        $varDir = 'var_' . $variationId . '/';
        $fullDirPath = $uploadBaseDir . $varDir;

        if (!file_exists($fullDirPath)) {
            if (!mkdir($fullDirPath, 0755, true)) {
                throw new Exception("Failed to create image directory");
            }
        }

        // Process each file
        foreach ($fileNames as $index => $fileName) {
            // Skip if no file
            if (empty($fileName) || empty($tmpFiles[$index]))
                continue;

            $tmpPath = $tmpFiles[$index];

            // Verify file
            if (!is_uploaded_file($tmpPath))
                continue;

            // Check file type
            $fileType = mime_content_type($tmpPath);
            if (!isset($allowedTypes[$fileType]))
                continue;

            $ext = $allowedTypes[$fileType];
            $newName = 'img_' . uniqid() . '.' . $ext;
            $destPath = $fullDirPath . $newName;

            // Move file
            if (move_uploaded_file($tmpPath, $destPath)) {
                // Store in DB with relative path (var_X/filename.ext)
                $dbPath = $varDir . $newName;

                $stmt = $this->db->prepare("
                INSERT INTO ProductImage 
                (Variation_ID, File_Name, File_Type, Alt_Text) 
                VALUES (?, ?, ?, ?)
            ");
                $stmt->execute([
                    $variationId,
                    $dbPath,
                    $ext,
                    $this->generateAltText($variationId, $index)
                ]);
            }
        }
    } // upload()

    private function generateAltText($variationId, $index)
    {
        $productName = $this->getProductNameByVariation($variationId);
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $productName);
        return substr($cleanName, 0, 50) . ' image ' . ($index + 1);
    } // generateAltText()

    private function getProductNameByVariation($variationId)
    {
        $stmt = $this->db->prepare("
        SELECT p.Name 
        FROM Product p
        JOIN ProductVariation pv ON p.Product_ID = pv.Product_ID
        WHERE pv.Variation_ID = ?
    ");
        $stmt->execute([$variationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['Name'] : 'Product Image';
    } // getProductNameByVariation()

    /// /// Delete Product /// ///
    public function deleteProduct($productId)
    {
        $this->db->beginTransaction();

        try {
            // 1. Delete wishlist entries
            $this->db->prepare("
            DELETE FROM Wishlisted 
            WHERE Variation_ID IN (
                SELECT Variation_ID FROM ProductVariation 
                WHERE Product_ID = ?
            )
        ")->execute([$productId]);

            // 2. Delete product reviews
            $this->db->prepare("
            DELETE FROM ProductReview 
            WHERE Product_ID = ?
        ")->execute([$productId]);

            // 3. Delete product images
            $variations = $this->getProductVariations($productId);
            foreach ($variations as $variation) {
                $this->deleteVariationImages($variation['variation_id']);
            }

            // 4. Delete product variations
            $this->db->prepare("
            DELETE FROM ProductVariation 
            WHERE Product_ID = ?
        ")->execute([$productId]);

            // 5. Delete the product itself
            $stmt = $this->db->prepare("
            DELETE FROM Product 
            WHERE Product_ID = ?
        ");
            $stmt->execute([$productId]);

            $this->db->commit();
            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    } // deleteProduct()

    private function deleteVariationImages($variationId)
    {
        // First get image paths
        $images = $this->db->prepare("
        SELECT File_Name FROM ProductImage 
        WHERE Variation_ID = ?
    ");
        $images->execute([$variationId]);
        $imageRecords = $images->fetchAll();

        // Delete from database
        $this->db->prepare("
        DELETE FROM ProductImage 
        WHERE Variation_ID = ?
    ")->execute([$variationId]);

        // Delete physical files
        foreach ($imageRecords as $image) {
            $filePath = UPLOAD_BASE_DIR . $image['File_Name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Remove variation directory if empty
        $varDir = UPLOAD_BASE_DIR . 'var_' . $variationId . '/';
        if (file_exists($varDir)) {
            @rmdir($varDir); // @ suppresses warning if not empty
        }
    } // deleteVariationImages()

    /// /// Modify /// ///

    public function updateVariation($variationId, $name, $price, $stock, $description)
    {
        $stmt = $this->db->prepare("
        UPDATE ProductVariation 
        SET Variation_Name = ?, Price = ?, Stock_Quantity = ?, Description = ?
        WHERE Variation_ID = ?
    ");
        return $stmt->execute([$name, $price, $stock, $description, $variationId]);
    } // updateVariation()

    public function updateProduct($ProductId, $name)
    {
        $stmt = $this->db->prepare("
        UPDATE Product
        SET Name = ?
        WHERE Product_ID = ?
    ");
        return $stmt->execute([$name, $ProductId]);
    } // updateProduct()

    public function deleteImage($imageId)
    {
        // First get the image path
        $stmt = $this->db->prepare("
        SELECT File_Name FROM ProductImage 
        WHERE ProductImage_ID = ?
    ");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            // Delete physical file
            $filePath = $this->uploadBaseDir . $image['File_Name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete database record
            $stmt = $this->db->prepare("
            DELETE FROM ProductImage 
            WHERE ProductImage_ID = ?
        ");
            return $stmt->execute([$imageId]);
        }
        return false;
    }

    public function getImageById($imageId)
    {
        $stmt = $this->db->prepare("
        SELECT * FROM ProductImage 
        WHERE ProductImage_ID = ?
    ");
        $stmt->execute([$imageId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } // getImageById()


} // Product
?>