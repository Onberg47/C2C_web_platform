### ### ### ### ### ### ###
### Sample data ###
### ### ### ### ### ### ###

-- Sample products
INSERT INTO Product (Seller_ID, Name, Ranking, Date_Created) VALUES
(1, 'Vintage Camera', 4.5, NOW()),
(1, 'Wireless Headphones', 4.2, NOW()),
(2, 'Programming Book', 4.8, NOW());

-- Sample variations
INSERT INTO ProductVariation (Product_ID, Variation_Name, Price, Stock_Quantity, Description) VALUES
(1, 'Black', 199.99, 5, 'Excellent condition vintage camera'),
(2, 'Pro Model', 129.99, 10, 'Noise cancelling headphones'),
(3, '2023 Edition', 49.99, 20, 'Latest programming techniques');

-- Sample images
INSERT INTO ProductImage (Variation_ID, File_Name, File_Type, Alt_Text) VALUES
(1, 'camera1.jpg', 'image/jpeg', 'Vintage camera front view'),
(2, 'headphones1.jpg', 'image/jpeg', 'Wireless headphones');

### ### ###
-- Sample Shipping options
INSERT INTO ShippingOptions (ShippingOption_ID, Name, Priority, Base_Cost, Platform_Fee) VALUES
(1, 'Express', 1, 90, 15),
(2, 'Economy', 2, 30, 10);
