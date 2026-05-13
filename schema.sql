CREATE DATABASE LANDERSONLINE;
 
USE LandersOnline;
 
-- ── Tables ───────────────────────────────────────────────────────
 
CREATE TABLE IF NOT EXISTS Accounts (
    Acct_Id             INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Acct_Email          VARCHAR(150) NOT NULL UNIQUE,
    Acct_Password       VARCHAR(255) NOT NULL,
    Acct_Role           ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    Acct_Status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    Acct_MustChangePw   TINYINT(1)   NOT NULL DEFAULT 0,
    Acct_CreatedAt      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
 
CREATE TABLE IF NOT EXISTS Customers (
    Cust_Id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Cust_AcctId     INT          NOT NULL,
    Cust_Name       VARCHAR(150),
    Cust_Phone      VARCHAR(30),
    FOREIGN KEY (Cust_AcctId) REFERENCES Accounts(Acct_Id) ON DELETE CASCADE
);
 
CREATE TABLE IF NOT EXISTS Categories (
    Cat_Id     INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Cat_Name   VARCHAR(100) NOT NULL,
    Cat_Status ENUM('active','inactive') NOT NULL DEFAULT 'active'
);
 
CREATE TABLE IF NOT EXISTS Products (
    Prod_Id       INT            NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Prod_CatId    INT,
    Prod_Name     VARCHAR(200)   NOT NULL,
    Prod_Size     VARCHAR(50),
    Prod_Price    DECIMAL(10,2)  NOT NULL,
    Prod_OldPrice DECIMAL(10,2)  DEFAULT NULL,
    Prod_Stock    INT            NOT NULL DEFAULT 0,
    Prod_Image    VARCHAR(500)   DEFAULT NULL,
    Prod_Status   ENUM('active','inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (Prod_CatId) REFERENCES Categories(Cat_Id) ON DELETE SET NULL
);
 
CREATE TABLE IF NOT EXISTS Cart (
    Cart_Id     INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Cart_AcctId INT NOT NULL,
    Cart_ProdId INT NOT NULL,
    Cart_Qty    INT NOT NULL DEFAULT 1,
    UNIQUE KEY unique_cart_item (Cart_AcctId, Cart_ProdId),
    FOREIGN KEY (Cart_AcctId) REFERENCES Accounts(Acct_Id) ON DELETE CASCADE,
    FOREIGN KEY (Cart_ProdId) REFERENCES Products(Prod_Id) ON DELETE CASCADE
);
 
CREATE TABLE IF NOT EXISTS Orders (
    Ord_Id        INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Ord_AcctId    INT           NOT NULL,
    Ord_CustId    INT           NOT NULL,
    Ord_Total     DECIMAL(10,2) NOT NULL,
    Ord_DelivFee  DECIMAL(10,2) NOT NULL DEFAULT 0,
    Ord_Address   TEXT,
    Ord_Status    ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    Ord_CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Ord_AcctId) REFERENCES Accounts(Acct_Id),
    FOREIGN KEY (Ord_CustId) REFERENCES Customers(Cust_Id)
);
 
CREATE TABLE IF NOT EXISTS OrderItems (
    OrdItem_Id       INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    OrdItem_OrdId    INT           NOT NULL,
    OrdItem_ProdId   INT,
    OrdItem_ProdName VARCHAR(200),
    OrdItem_Price    DECIMAL(10,2) NOT NULL,
    OrdItem_Qty      INT           NOT NULL DEFAULT 1,
    FOREIGN KEY (OrdItem_OrdId)  REFERENCES Orders(Ord_Id)   ON DELETE CASCADE,
    FOREIGN KEY (OrdItem_ProdId) REFERENCES Products(Prod_Id) ON DELETE SET NULL
);
 
-- ── Categories ────────────────────────────────────────────────────
INSERT IGNORE INTO Categories (Cat_Name) VALUES
('Food Cupboard'), ('Beverages'), ('Health & Beauty'),
('Home & Outdoor'), ('Beer, Wine & Spirits'), ('Household & Laundry'),
('Pet Care'), ('Chocolates, Candies & Sweets'), ('Baby, Kids & Toys'),
('Electronics'), ('Fruits & Vegetables'), ('Dairy & Chilled'),
('Bakery'), ('Frozen');
 



--  ADMIN ACCOUNT — plain text password, must chan  ge on first login 
INSERT IGNORE INTO Accounts (Acct_Email, Acct_Password, Acct_Role, Acct_Status, Acct_MustChangePw)
VALUES ('admin@landers.ph', 'Admin@123', 'admin', 'active', 1);

INSERT IGNORE INTO Accounts (Acct_Email, Acct_Password, Acct_Role, Acct_Status, Acct_MustChangePw)
VALUES ('admin2@landers.ph', 'Admin@1234', 'admin', 'active', 1);