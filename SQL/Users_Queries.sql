### ### ### ###
### Recommended indexing:

-- Crucial for User_ID lookups (used in WHERE clause)
CREATE INDEX idx_seller_user ON Seller(User_ID);

-- Speeds up order filtering by seller
CREATE INDEX idx_order_seller ON `Order`(Seller_ID); 

-- Helps count order products quickly
CREATE INDEX idx_op_order ON OrderProducts(Order_ID);

-- For fast user lookups (both buyer and seller)
CREATE INDEX idx_users_id ON Users(User_ID);




###	###	###
##
### - Query general information about all users with basic seller information
SELECT 
    u.User_ID,
    s.Seller_ID,
    u.Username,
    u.Email_Address,
    uc.Password_HASH,
    u.Date_Registered,
    a.Street AS Delivery_Street,
    a.ZIP_Code AS Delivery_ZIP_Code
FROM 
    Users u
LEFT JOIN 
    UserCredentials uc ON u.User_ID = uc.User_ID
LEFT JOIN 
    Seller s ON u.User_ID = s.User_ID
LEFT JOIN 
    Addresses a ON u.DeliveryAddress_ID = a.Address_ID
ORDER BY 
    u.Date_Registered DESC;

###	###	###
##
### - Remove a specific user and all related fields
START TRANSACTION;

-- Store the username for logging before deletion
SET @target_user_id = 2;
SET @username = (SELECT Username FROM Users WHERE User_ID = @target_user_id);

-- 1. First delete from tables WITHOUT foreign key constraints to Users
DELETE FROM Wishlisted WHERE User_ID = @target_user_id;
DELETE FROM ProductReview WHERE User_ID = @target_user_id;
DELETE FROM Message WHERE Sender_ID = @target_user_id;
DELETE FROM MessageRoom WHERE User_ID = @target_user_id;
DELETE FROM Report WHERE Reporter_ID = @target_user_id OR Target_ID = @target_user_id;
DELETE FROM AdminLogs WHERE User_ID = @target_user_id OR Target_ID = @target_user_id;

-- 2. Delete seller-related data if they're a seller
DELETE FROM OrderProducts 
WHERE Order_ID IN (SELECT Order_ID FROM `Order` WHERE User_ID = @target_user_id OR Seller_ID IN 
                  (SELECT Seller_ID FROM Seller WHERE User_ID = @target_user_id));

DELETE FROM OrderStatusHistory 
WHERE Order_ID IN (SELECT Order_ID FROM `Order` WHERE User_ID = @target_user_id OR Seller_ID IN 
                  (SELECT Seller_ID FROM Seller WHERE User_ID = @target_user_id));

DELETE FROM `Order` 
WHERE User_ID = @target_user_id OR Seller_ID IN 
      (SELECT Seller_ID FROM Seller WHERE User_ID = @target_user_id);

DELETE FROM Transaction 
WHERE Payee_ID = @target_user_id OR Recipient_ID = @target_user_id;

DELETE FROM SellerPickups 
WHERE Seller_ID IN (SELECT Seller_ID FROM Seller WHERE User_ID = @target_user_id);

-- 3. Finally delete the user themselves (cascades to credentials, auth tokens, etc.)
DELETE FROM Users WHERE User_ID = @target_user_id;

-- Log the deletion (optional)
INSERT INTO AdminLogs (User_ID, Action_Type, Description, Timestamp)
VALUES (CURRENT_USER(), 'Account Deletion', CONCAT('Deleted user: ', @username), NOW());

COMMIT;

###	###	###
#
## - add products and a seller

START TRANSACTION;

-- 1. Create the new seller (using user's existing address)
SET @user_id = 2;
SET @address_id = (SELECT DeliveryAddress_ID FROM Users WHERE User_ID = @user_id);

INSERT INTO Seller (User_ID, Address_ID, Ranking, Date_Registered)
VALUES (@user_id, @address_id, 4.0, NOW());

SET @seller_id = LAST_INSERT_ID();

-- 2. Add first product (Gaming Headphones) with variations
INSERT INTO Product (Seller_ID, Name, Ranking, Date_Created)
VALUES (@seller_id, 'Gaming Headphones', 3.0, NOW());

SET @product1_id = LAST_INSERT_ID();

-- Add variations for first product
INSERT INTO ProductVariation (Product_ID, Variation_Name, Price, Stock_Quantity, Description)
VALUES 
    (@product1_id, 'Standard', 800.00, 6, 'Quality gaming headphones with 50mm drivers');

SET @variation1_id = LAST_INSERT_ID();

INSERT INTO ProductVariation (Product_ID, Variation_Name, Price, Stock_Quantity, Description)
VALUES 
    (@product1_id, 'RGB', 920.00, 3, 'Quality gaming headphones with 50mm drivers with RGB lighting');

SET @variation2_id = LAST_INSERT_ID();

-- Add images for first product variations
INSERT INTO ProductImage (Variation_ID, File_Name, File_Type, Alt_Text)
VALUES
    (@variation1_id, 'logi_1_1.png', 'png', 'Photo of standard headphones'),
    (@variation2_id, 'logi_2_1.png', 'png', 'Photo of RGB headphones');

-- 3. Add second product (Wireless Buds) with variation
INSERT INTO Product (Seller_ID, Name, Ranking, Date_Created)
VALUES (@seller_id, 'In ear Wireless buds', 4.6, NOW());

SET @product2_id = LAST_INSERT_ID();

-- Add variation for second product
INSERT INTO ProductVariation (Product_ID, Variation_Name, Price, Stock_Quantity, Description)
VALUES 
    (@product2_id, 'Standard', 680.00, 1, 'In ear wireless buds with noise canceling.');

SET @variation3_id = LAST_INSERT_ID();

-- Add images for second product variation
INSERT INTO ProductImage (Variation_ID, File_Name, File_Type, Alt_Text)
VALUES
    (@variation3_id, 'ie_nc_1_1.png', 'png', 'Photo of earbuds and case'),
    (@variation3_id, 'ie_nc_1_2.png', 'png', 'Macro photo of earbuds');

-- 4. Add sample review for the headphones (assuming review is from user 3)
SET @reviewer_id = 1; -- Change this to the actual reviewing user's ID

INSERT INTO ProductReview (Product_ID, User_ID, Product_Rating, Seller_Rating, Buyer_Comment, Date_Created)
VALUES (@product1_id, @reviewer_id, 3, 4, 'Good headphones, nothing too special. Seller was really nice.', NOW());

-- 5. Verify everything was created
SELECT 
    s.Seller_ID,
    u.Username,
    p.Product_ID,
    p.Name AS Product_Name,
    pv.Variation_ID,
    pv.Variation_Name,
    pv.Price,
    pv.Stock_Quantity,
    pi.File_Name AS Image_File,
    r.Product_Rating,
    r.Buyer_Comment
FROM 
    Seller s
JOIN 
    Users u ON s.User_ID = u.User_ID
JOIN 
    Product p ON s.Seller_ID = p.Seller_ID
JOIN 
    ProductVariation pv ON p.Product_ID = pv.Product_ID
LEFT JOIN
    ProductImage pi ON pv.Variation_ID = pi.Variation_ID
LEFT JOIN
    ProductReview r ON p.Product_ID = r.Product_ID
WHERE 
    s.Seller_ID = @seller_id
ORDER BY
    p.Product_ID, pv.Variation_ID;

COMMIT;


### ### ### ### ###
### - Search products tst
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
WHERE p.Name LIKE '%Test%' 
   OR pv.Description LIKE '%Test%'
GROUP BY p.Product_ID
ORDER BY p.Date_Created DESC;

### ### ### ###
### - List orders and transactions

SELECT 
    o.Order_ID,
    o.User_ID,
    u.Username AS Customer_Username,
    o.Seller_ID,
    s.User_ID AS Seller_User_ID,
    sel_user.Username AS Seller_Username,
    o.Total_Cost,
    o.Shipping_Cost,
    o.Status AS Order_Status,
    t.Transaction_ID,
    t.Payment_Status,
    so.Name AS Shipping_Method,
    GROUP_CONCAT(CONCAT(pv.Variation_Name, ' (x', op.Quantity, ')') 
        SEPARATOR ', ') AS Products,
    COUNT(op.OrderProducts_ID) AS Total_Items,
    SUM(op.Quantity) AS Total_Quantity
FROM 
    `Order` o
JOIN 
    Users u ON o.User_ID = u.User_ID
JOIN 
    Seller s ON o.Seller_ID = s.Seller_ID
JOIN 
    Users sel_user ON s.User_ID = sel_user.User_ID
LEFT JOIN 
    Transaction t ON o.Order_ID = t.Order_ID
JOIN 
    ShippingOptions so ON o.ShippingOption_ID = so.ShippingOption_ID
JOIN 
    OrderProducts op ON o.Order_ID = op.Order_ID
JOIN 
    ProductVariation pv ON op.Variation_ID = pv.Variation_ID
GROUP BY 
    o.Order_ID
ORDER BY 
    o.Order_ID DESC;
    
    
### ### ### ###
### - Delete quick deletion of a Product for testing

START TRANSACTION;
SET @product_id = 4;

DELETE FROM ProductImage WHERE Variation_ID IN (SELECT Variation_ID FROM ProductVariation WHERE Product_ID = @product_id);
DELETE FROM ProductVariation WHERE Product_ID = @product_id;
DELETE FROM Product WHERE Product_ID = @product_id;

COMMIT;

### ### ###
### - 
START TRANSACTION;

-- Set the product ID you want to delete
SET @product_id = 27;

-- 1. First remove from tables without direct foreign key constraints
-- Delete wishlist entries for this product's variations
DELETE FROM Wishlisted 
WHERE Variation_ID IN (
    SELECT Variation_ID 
    FROM ProductVariation 
    WHERE Product_ID = @product_id
);

-- Delete order products for this product's variations
-- Note: Only do this if you're sure about deleting historical order data!
-- DELETE FROM OrderProducts 
-- WHERE Variation_ID IN (
--     SELECT Variation_ID 
--     FROM ProductVariation 
--     WHERE Product_ID = @product_id
-- );

-- 2. Delete product reviews
DELETE FROM ProductReview 
WHERE Product_ID = @product_id;

-- 3. Delete product images (via variations)
DELETE FROM ProductImage 
WHERE Variation_ID IN (
    SELECT Variation_ID 
    FROM ProductVariation 
    WHERE Product_ID = @product_id
);

-- 4. Delete product variations
DELETE FROM ProductVariation 
WHERE Product_ID = @product_id;

-- 5. Finally delete the product itself
DELETE FROM Product 
WHERE Product_ID = @product_id;

-- Verify deletion
SELECT IF(COUNT(*) = 0, 'Product successfully deleted', 'Product still exists') AS deletion_status
FROM Product 
WHERE Product_ID = @product_id;

COMMIT;


### ### ### ###
### - Sample messaging Data 1

START TRANSACTION;

-- 1. Create the message room (if it doesn't exist)
INSERT IGNORE INTO MessageRoom (MessageRoom_ID, Sellers_User_ID, User_ID)
VALUES (1, 2, 1);

-- 2. Add first message (from user 1, 1 hour ago)
INSERT INTO Message (MessageRoom_ID, Sender_ID, Contents, is_read, Timestamp)
VALUES (
    1, 
    1, 
    "Hello, I'm interested in buying product x. Can I customize it?", 
    TRUE,
    DATE_SUB(NOW(), INTERVAL 1 HOUR)
);

-- 3. Add second message (from seller 2, just now)
INSERT INTO Message (MessageRoom_ID, Sender_ID, Contents, is_read, Timestamp)
VALUES (
    1, 
    2, 
    "Good day, sure you can customize it!", 
    FALSE,
    NOW()
);

-- 4. Verify the conversation
SELECT 
    m.Message_ID,
    m.MessageRoom_ID,
    m.Sender_ID,
    u.Username AS Sender_Name,
    m.Contents,
    m.is_read,
    m.Timestamp,
    TIMESTAMPDIFF(MINUTE, m.Timestamp, NOW()) AS Minutes_Ago
FROM 
    Message m
JOIN 
    Users u ON m.Sender_ID = u.User_ID
WHERE 
    m.MessageRoom_ID = 1
ORDER BY 
    m.Timestamp;

COMMIT;


### ### ### ###
### - Sample messaging Data 2

START TRANSACTION;

-- 1. Create the message room (if it doesn't exist)
INSERT IGNORE INTO MessageRoom (MessageRoom_ID, Sellers_User_ID, User_ID)
VALUES (2, 2, 3);

-- 2. Add first message (from user 1, 1 hour ago)
INSERT INTO Message (MessageRoom_ID, Sender_ID, Contents, is_read, Timestamp)
VALUES (
    2, 
    3, 
    "Hi! I'm interested in buying product Y, varient A. Can I Personalize it?", 
    TRUE,
    DATE_SUB(NOW(), INTERVAL 1 HOUR)
);

-- 4. Verify the conversation
SELECT 
    m.Message_ID,
    m.MessageRoom_ID,
    m.Sender_ID,
    u.Username AS Sender_Name,
    m.Contents,
    m.is_read,
    m.Timestamp,
    TIMESTAMPDIFF(MINUTE, m.Timestamp, NOW()) AS Minutes_Ago
FROM Message m
JOIN Users u ON m.Sender_ID = u.User_ID
WHERE m.MessageRoom_ID = 2
ORDER BY m.Timestamp;

COMMIT;


### ### ### ###
###
SELECT 
    o.Order_ID AS id,
    o.Total_Cost AS total,
    o.Status AS status,
    o.Date_Created AS date_created,
    COUNT(op.OrderProducts_ID) AS item_count,
    SUM(op.Quantity) AS total_quantity,
    u.Username AS buyer_name,
    u.User_ID AS buyer_id
FROM `Order` o
JOIN Users u ON o.User_ID = u.User_ID
JOIN OrderProducts op ON o.Order_ID = op.Order_ID
WHERE o.Seller_ID = 1  -- PHP parameter
GROUP BY o.Order_ID
ORDER BY o.Date_Created DESC;

###

SELECT 
    o.Order_ID AS id,
    o.Total_Cost AS total,
    o.Status AS status,
    o.Date_Created AS date_created,
    COUNT(op.OrderProducts_ID) AS item_count,
    SUM(op.Quantity) AS total_quantity,
    buyer.Username AS buyer_name,
    buyer.User_ID AS buyer_id,
    so.Name AS shipping_method
FROM 
    `Order` o
JOIN Seller s ON o.Seller_ID = s.Seller_ID
JOIN Users seller ON s.User_ID = seller.User_ID
JOIN Users buyer ON o.User_ID = buyer.User_ID
JOIN OrderProducts op ON o.Order_ID = op.Order_ID
JOIN ShippingOptions so ON o.ShippingOption_ID = so.ShippingOption_ID
WHERE seller.User_ID = 2
GROUP BY o.Order_ID
ORDER BY o.Date_Created DESC;