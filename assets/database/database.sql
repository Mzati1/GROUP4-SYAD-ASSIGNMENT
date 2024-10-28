-- Table for users (customers, admin)
CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    profile_picture LONGBLOB,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer' NOT NULL,
    residence VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE
);
-- Table for menus ( can have multiple menus)
CREATE TABLE Menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Table to store ads
CREATE TABLE Ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image LONGBLOB,
    -- Store the image as a large binary object
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Table for individual menu items (e.g., burger, fries, etc.)
CREATE TABLE Menu_Items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES Menus(id) ON DELETE CASCADE
);
-- Cart table to store the active cart of each user
CREATE TABLE Cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);
-- Cart_Items table to store individual items in a user's cart
CREATE TABLE Cart_Items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES Cart(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES Menu_Items(id) ON DELETE CASCADE
);
-- Orders table to store orders once checkout is completed
CREATE TABLE Orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    status ENUM('pending', 'completed', 'canceled') DEFAULT 'pending' NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);
-- Order_Items table to store items in each order (after checkout)
CREATE TABLE Order_Items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES Orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES Menu_Items(id) ON DELETE CASCADE
);
-- Payments table to store payment details after checkout
CREATE TABLE Payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    user_id INT,
    reference_id VARCHAR(255) NOT NULL,
    payment_status ENUM('pending', 'failed', 'successful') DEFAULT 'pending' NOT NULL,
    payment_method VARCHAR(50),
    amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (order_id) REFERENCES Orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);
-- Table for storing discount codes
CREATE TABLE Discount_Codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_value DECIMAL(5, 2) NOT NULL,
    discount_type ENUM('fixed', 'percentage') NOT NULL,
    usage_count INT DEFAULT 0,
    max_uses INT,
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Table to track discount code usage
CREATE TABLE Discount_Usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discount_code_id INT,
    user_id INT,
    order_id INT,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discount_code_id) REFERENCES Discount_Codes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES Orders(id) ON DELETE CASCADE
);
-- Table to keep records of payments to avoid fraud
CREATE TABLE Payments_Audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT,
    user_id INT,
    reference_id VARCHAR(255) NOT NULL,
    payment_status ENUM('pending', 'failed', 'successful') NOT NULL,
    amount DECIMAL(10, 2),
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES Payments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);
-- Payment audit triggers
DELIMITER // CREATE TRIGGER after_payment_insert
AFTER
INSERT ON Payments FOR EACH ROW BEGIN
INSERT INTO Payments_Audit (
        payment_id,
        user_id,
        reference_id,
        payment_status,
        amount,
        action
    )
VALUES (
        NEW.id,
        NEW.user_id,
        NEW.reference_id,
        NEW.payment_status,
        NEW.amount,
        'INSERT'
    );
END;
// CREATE TRIGGER after_payment_update
AFTER
UPDATE ON Payments FOR EACH ROW BEGIN
INSERT INTO Payments_Audit (
        payment_id,
        user_id,
        reference_id,
        payment_status,
        amount,
        action
    )
VALUES (
        NEW.id,
        NEW.user_id,
        NEW.reference_id,
        NEW.payment_status,
        NEW.amount,
        'UPDATE'
    );
END;
// CREATE TRIGGER after_payment_delete
AFTER DELETE ON Payments FOR EACH ROW BEGIN
INSERT INTO Payments_Audit (
        payment_id,
        user_id,
        reference_id,
        payment_status,
        amount,
        action
    )
VALUES (
        OLD.id,
        OLD.user_id,
        OLD.reference_id,
        OLD.payment_status,
        OLD.amount,
        'DELETE'
    );
END;
// -- Trigger to track discount code usage when a payment is made
CREATE TRIGGER after_discount_code_used
AFTER
INSERT ON Discount_Usage FOR EACH ROW BEGIN
UPDATE Discount_Codes
SET usage_count = usage_count + 1
WHERE id = NEW.discount_code_id;
END;
// DELIMITER;