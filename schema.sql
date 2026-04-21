-- ============================================================
--  LandersOnline Database Schema
--  Run this in phpMyAdmin or MySQL CLI:
--  mysql -u root -p123 < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS LandersOnline CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE LandersOnline;

-- ── Accounts (login credentials for all roles) ──
CREATE TABLE IF NOT EXISTS Accounts (
    Acct_Id               INT          PRIMARY KEY,
    Acct_Email            VARCHAR(150) NOT NULL UNIQUE,
    Acct_Password         VARCHAR(255) NOT NULL,
    Acct_Role             ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    Acct_Status           ENUM('active','inactive') NOT NULL DEFAULT 'active',
    Acct_MustChangePassword TINYINT(1) NOT NULL DEFAULT 0,
    Acct_CreatedAt        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Customers ──
CREATE TABLE IF NOT EXISTS Customers (
    Cust_Id         INT          PRIMARY KEY,
    Cust_AcctId     INT          NOT NULL,
    Cust_Name       VARCHAR(150),
    Cust_Phone      VARCHAR(30),
    Cust_Address    TEXT,
    Cust_CardType   VARCHAR(50),
    Cust_CardNumber VARCHAR(100),
    Cust_CardName   VARCHAR(150),
    FOREIGN KEY (Cust_AcctId) REFERENCES Accounts(Acct_Id) ON DELETE CASCADE
);

-- ── Categories ──
CREATE TABLE IF NOT EXISTS Categories (
    Cat_Id     INT          PRIMARY KEY AUTO_INCREMENT,
    Cat_Name   VARCHAR(100) NOT NULL,
    Cat_Status ENUM('active','inactive') NOT NULL DEFAULT 'active'
);

-- ── Products ──
CREATE TABLE IF NOT EXISTS Products (
    Prod_Id       INT            PRIMARY KEY,
    Prod_CatId    INT,
    Prod_Name     VARCHAR(200)   NOT NULL,
    Prod_Size     VARCHAR(50),
    Prod_Price    DECIMAL(10,2)  NOT NULL,
    Prod_OldPrice DECIMAL(10,2),
    Prod_Stock    INT            NOT NULL DEFAULT 0,
    Prod_Image    VARCHAR(500),
    Prod_Status   ENUM('active','inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (Prod_CatId) REFERENCES Categories(Cat_Id) ON DELETE SET NULL
);

-- ── Cart ──
CREATE TABLE IF NOT EXISTS Cart (
    Cart_Id     INT PRIMARY KEY AUTO_INCREMENT,
    Cart_AcctId INT NOT NULL,
    Cart_ProdId INT NOT NULL,
    Cart_Qty    INT NOT NULL DEFAULT 1,
    FOREIGN KEY (Cart_AcctId) REFERENCES Accounts(Acct_Id) ON DELETE CASCADE,
    FOREIGN KEY (Cart_ProdId) REFERENCES Products(Prod_Id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart (Cart_AcctId, Cart_ProdId)
);

-- ── Orders ──
CREATE TABLE IF NOT EXISTS Orders (
    Ord_Id        INT            PRIMARY KEY AUTO_INCREMENT,
    Ord_AcctId    INT            NOT NULL,
    Ord_CustId    INT            NOT NULL,
    Ord_Total     DECIMAL(10,2)  NOT NULL,
    Ord_DelivFee  DECIMAL(10,2)  NOT NULL DEFAULT 0,
    Ord_Address   TEXT,
    Ord_Status    ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    Ord_CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Ord_AcctId) REFERENCES Accounts(Acct_Id),
    FOREIGN KEY (Ord_CustId) REFERENCES Customers(Cust_Id)
);

-- ── Order Items ──
CREATE TABLE IF NOT EXISTS OrderItems (
    OrdItem_Id      INT           PRIMARY KEY AUTO_INCREMENT,
    OrdItem_OrdId   INT           NOT NULL,
    OrdItem_ProdId  INT           NOT NULL,
    OrdItem_ProdName VARCHAR(200),
    OrdItem_Price   DECIMAL(10,2) NOT NULL,
    OrdItem_Qty     INT           NOT NULL DEFAULT 1,
    FOREIGN KEY (OrdItem_OrdId)  REFERENCES Orders(Ord_Id)   ON DELETE CASCADE,
    FOREIGN KEY (OrdItem_ProdId) REFERENCES Products(Prod_Id) ON DELETE SET NULL
);

-- ============================================================
--  Seed Data
-- ============================================================

-- Admin account (password: Admin@123)
INSERT IGNORE INTO Accounts (Acct_Id, Acct_Email, Acct_Password, Acct_Role)
VALUES (1001, 'admin@landers.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categories
INSERT IGNORE INTO Categories (Cat_Name) VALUES
('Food Cupboard'),
('Beverages'),
('Health & Beauty'),
('Home & Outdoor'),
('Beer, Wine & Spirits'),
('Household & Laundry'),
('Pet Care'),
('Chocolates, Candies & Sweets'),
('Baby, Kids & Toys'),
('Electronics'),
('Fruits & Vegetables'),
('Dairy & Chilled'),
('Bakery'),
('Frozen');

-- Sample Products
INSERT IGNORE INTO Products (Prod_Id, Prod_CatId, Prod_Name, Prod_Size, Prod_Price, Prod_OldPrice, Prod_Stock) VALUES
(5001, 1, 'Nutella Ice Cream Pint', '470mL', 520.95, NULL, 50),
(5002, 1, 'Siviero Maria Blueberry Cheesecake Gelato', '1L/500g', 229.95, 312.95, 30),
(5003, 1, 'Haagen-Dazs Belgian Chocolate Ice Cream', '460mL', 352.95, 468.95, 25),
(5004, 2, 'San Miguel Pale Pilsen', '330mL x 24', 699.00, NULL, 100),
(5005, 1, 'Kirkland Signature Dark Roast Coffee', '1.13kg', 899.00, 1100.00, 40),
(5006, 6, 'Tide Detergent Powder', '3.8kg', 349.75, NULL, 60),
(5007, 7, 'Pedigree Adult Dog Food', '3kg', 445.00, 520.00, 35),
(5008, 10, 'Energizer AA Batteries', '24pk', 399.00, NULL, 80);
