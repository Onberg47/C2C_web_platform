drop database platform_db;

CREATE DATABASE platform_db;
USE platform_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ========================
-- CORE USER TABLES
-- ========================

CREATE TABLE Addresses (
    Address_ID INT AUTO_INCREMENT PRIMARY KEY,
    Street VARCHAR(100) NOT NULL,
    City VARCHAR(50) NOT NULL,
    Province VARCHAR(50) NOT NULL,
    ZIP_Code VARCHAR(10) NOT NULL
);

CREATE TABLE Users (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Avatar VARCHAR(255) DEFAULT 'default-avatar.jpg',
    Email_Address VARCHAR(100) NOT NULL UNIQUE,
    DeliveryAddress_ID INT,
    Date_Registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (DeliveryAddress_ID) REFERENCES Addresses(Address_ID)
);

CREATE TABLE UserCredentials (
    UserCredential_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL UNIQUE,
    Password_HASH CHAR(64) NOT NULL,
    Salt CHAR(32) NOT NULL,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE
);

CREATE TABLE AuthTokens (
    Token_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT UNIQUE,
    Token_Expiry_Date DATETIME NOT NULL,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE
);

-- ========================
-- ADMINISTRATION TABLES
-- ========================

CREATE TABLE Roles (
    Role_ID INT AUTO_INCREMENT PRIMARY KEY,
    Role_Name VARCHAR(30) NOT NULL UNIQUE,
    Elevation_Level INT NOT NULL
);

CREATE TABLE UserAdmins (
    UserRole_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT UNIQUE,
    Role_ID INT NOT NULL,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE,
    FOREIGN KEY (Role_ID) REFERENCES Roles(Role_ID)
);

CREATE TABLE AdminLogs (
    Log_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Target_ID INT,
    Action_Type VARCHAR(50) NOT NULL,
    Report_Type VARCHAR(50),
    Status VARCHAR(20) NOT NULL,
    Timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Description TEXT,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Target_ID) REFERENCES Users(User_ID)
);

CREATE TABLE Report (
    Report_ID INT AUTO_INCREMENT PRIMARY KEY,
    Reporter_ID INT NOT NULL,
    Target_ID INT,
    Status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    Timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Description TEXT NOT NULL,
    FOREIGN KEY (Reporter_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Target_ID) REFERENCES Users(User_ID)
);

-- ========================
-- SELLER & PRODUCT TABLES
-- ========================

CREATE TABLE Seller (
    Seller_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL UNIQUE,
    Address_ID INT NOT NULL UNIQUE,
    Ranking DECIMAL(3,2) DEFAULT 0.00,
    Date_Registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE,
    FOREIGN KEY (Address_ID) REFERENCES Addresses(Address_ID)
);

CREATE TABLE PickupPoints (
    PickupPoint_ID INT AUTO_INCREMENT PRIMARY KEY,
    Address_ID INT NOT NULL,
    OperatingHours VARCHAR(100),
    FOREIGN KEY (Address_ID) REFERENCES Addresses(Address_ID)
);

CREATE TABLE SellerPickups (
    SellerPickups_ID INT AUTO_INCREMENT PRIMARY KEY,
    Seller_ID INT NOT NULL,
    PickupPoint_ID INT NOT NULL,
    FOREIGN KEY (Seller_ID) REFERENCES Seller(Seller_ID) ON DELETE CASCADE,
    FOREIGN KEY (PickupPoint_ID) REFERENCES PickupPoints(PickupPoint_ID),
    UNIQUE KEY (Seller_ID, PickupPoint_ID)
);

-- ========================
-- MESSAGING SYSTEM
-- ========================

CREATE TABLE MessageRoom (
    MessageRoom_ID INT AUTO_INCREMENT PRIMARY KEY,
    Sellers_User_ID INT NOT NULL,
    User_ID INT NOT NULL,
    FOREIGN KEY (Sellers_User_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID),
    UNIQUE KEY (Sellers_User_ID, User_ID) -- One room per user pair
);

CREATE TABLE Message (
    Message_ID INT AUTO_INCREMENT PRIMARY KEY,
    MessageRoom_ID INT NOT NULL,
    Sender_ID INT NOT NULL,
    Contents TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    Timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (MessageRoom_ID) REFERENCES MessageRoom(MessageRoom_ID) ON DELETE CASCADE,
    FOREIGN KEY (Sender_ID) REFERENCES Users(User_ID)
);

-- ========================
-- ORDER & TRANSACTION SYSTEM
-- ========================

CREATE TABLE ShippingOptions (
    ShippingOption_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(50) NOT NULL,
    Priority INT NOT NULL,
    Base_Cost DECIMAL(10,2) NOT NULL,
    Platform_Fee DECIMAL(10,2) NOT NULL
);

CREATE TABLE `Order` (
    Order_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT,
    Seller_ID INT,
    Total_Cost DECIMAL(10,2) NOT NULL,
    Shipping_Cost DECIMAL(10,2) NOT NULL,
    ShippingOption_ID INT NOT NULL,
    Status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    Date_Created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Seller_ID) REFERENCES Seller(Seller_ID),
    FOREIGN KEY (ShippingOption_ID) REFERENCES ShippingOptions(ShippingOption_ID)
);

CREATE TABLE OrderStatusHistory (
    StatusHistory_ID INT AUTO_INCREMENT PRIMARY KEY,
    Order_ID INT NOT NULL,
    Status VARCHAR(20) NOT NULL,
    Timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Order_ID) REFERENCES `Order`(Order_ID) ON DELETE CASCADE
);

CREATE TABLE Transaction (
    Transaction_ID INT AUTO_INCREMENT PRIMARY KEY,
    Payee_ID INT NOT NULL,
    Recipient_ID INT NOT NULL,
    Order_ID INT NOT NULL,
    Gateway_Transaction_ID VARCHAR(100),
    Transaction_Type VARCHAR(30) NOT NULL,
    Payment_Status VARCHAR(20) NOT NULL,
    Date_Created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (Payee_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Recipient_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Order_ID) REFERENCES `Order`(Order_ID)
);

CREATE TABLE OrderProducts (
    OrderProducts_ID INT AUTO_INCREMENT PRIMARY KEY,
    Order_ID INT NOT NULL,
    Variation_ID INT NOT NULL,
    Quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (Order_ID) REFERENCES `Order`(Order_ID) ON DELETE CASCADE,
    FOREIGN KEY (Variation_ID) REFERENCES ProductVariation(Variation_ID),
    UNIQUE KEY (Order_ID, Variation_ID)
);

CREATE TABLE Product (
    Product_ID INT AUTO_INCREMENT PRIMARY KEY,
    Seller_ID INT NOT NULL,
    Name VARCHAR(100) NOT NULL,
    Ranking DECIMAL(3,2) DEFAULT 0.00,
    Date_Created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Seller_ID) REFERENCES Seller(Seller_ID) ON DELETE CASCADE
);

-- ========================
-- PRODUCT CATALOG
-- ========================

CREATE TABLE ProductVariation (
    Variation_ID INT AUTO_INCREMENT PRIMARY KEY,
    Product_ID INT NOT NULL,
    Variation_Name VARCHAR(50) NOT NULL,
    Price DECIMAL(10,2) NOT NULL,
    Stock_Quantity INT NOT NULL DEFAULT 0,
    Description TEXT,
    FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID) ON DELETE CASCADE
);

CREATE TABLE ProductImage (
    ProductImage_ID INT AUTO_INCREMENT PRIMARY KEY,
    Variation_ID INT NOT NULL,
    File_Name VARCHAR(100) NOT NULL,
    File_Type VARCHAR(20) NOT NULL,
    Alt_Text VARCHAR(100),
    FOREIGN KEY (Variation_ID) REFERENCES ProductVariation(Variation_ID) ON DELETE CASCADE
);

CREATE TABLE ProductReview (
    ProductReview_ID INT AUTO_INCREMENT PRIMARY KEY,
    Product_ID INT NOT NULL,
    User_ID INT NOT NULL,
    Product_Rating TINYINT NOT NULL CHECK (Product_Rating BETWEEN 1 AND 5),
    Seller_Rating TINYINT NOT NULL CHECK (Seller_Rating BETWEEN 1 AND 5),
    Buyer_Comment TEXT,
    Date_Created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID) ON DELETE CASCADE,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID),
    UNIQUE KEY (Product_ID, User_ID)
);

CREATE TABLE Cart (
    User_ID INT NOT NULL,
    Variation_ID INT NOT NULL,
    Quantity INT NOT NULL DEFAULT 1,
    Date_Added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (User_ID, Variation_ID),
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE,
    FOREIGN KEY (Variation_ID) REFERENCES ProductVariation(Variation_ID) ON DELETE CASCADE
);

CREATE TABLE Wishlisted (
    WishList_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Variation_ID INT NOT NULL,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE,
    FOREIGN KEY (Variation_ID) REFERENCES ProductVariation(Variation_ID) ON DELETE CASCADE,
    UNIQUE KEY (User_ID, Variation_ID)
);

SET FOREIGN_KEY_CHECKS = 1;


### ### ### ###
### Recommended indexing:
### ###

-- Crucial for User_ID lookups (used in WHERE clause)
CREATE INDEX idx_seller_user ON Seller(User_ID);

-- Speeds up order filtering by seller
CREATE INDEX idx_order_seller ON `Order`(Seller_ID); 

-- Helps count order products quickly
CREATE INDEX idx_op_order ON OrderProducts(Order_ID);

-- For fast user lookups (both buyer and seller)
CREATE INDEX idx_users_id ON Users(User_ID);

-- Core product search indexes
-- CREATE INDEX idx_product_seller ON Product(Seller_ID);
-- CREATE INDEX idx_product_ranking ON Product(Ranking);
-- CREATE INDEX idx_product_date ON Product(Date_Created);

-- Variation performance boosters
CREATE INDEX idx_variation_product ON ProductVariation(Product_ID);
CREATE INDEX idx_variation_price ON ProductVariation(Price);

-- Image loading optimization
CREATE INDEX idx_image_variation ON ProductImage(Variation_ID);