CREATE DATABASE IF NOT EXISTS da_convenience_store
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE da_convenience_store;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255) DEFAULT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY category_id (category_id),
    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS cart (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_cart_product (product_id),
    CONSTRAINT fk_cart_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE
) ENGINE = InnoDB;

INSERT INTO categories (id, name, image, sort_order)
SELECT * FROM (
    SELECT 1 AS id, 'School Supplies' AS name, 'Rectangle 43.png' AS image, 1 AS sort_order
    UNION ALL SELECT 2, 'Beauty Products', 'Rectangle 58.png', 2
    UNION ALL SELECT 3, 'Clothes & Footwear', 'Rectangle 59.png', 3
    UNION ALL SELECT 4, 'Beverages', 'Rectangle 62.png', 4
    UNION ALL SELECT 5, 'Cooking Condiments', 'Rectangle 63.png', 5
    UNION ALL SELECT 6, 'Toiletries', 'Rectangle 64.png', 6
    UNION ALL SELECT 7, 'Cooking Utensils', 'Rectangle 67.png', 7
    UNION ALL SELECT 8, 'Hair Accessories', 'Rectangle 66.png', 8
    UNION ALL SELECT 9, 'Snacks', 'Rectangle 61.png', 9
    UNION ALL SELECT 10, 'Ice Creams', 'Rectangle 65.png', 10
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM categories);

INSERT INTO products (category_id, name, price)
SELECT * FROM (
    SELECT 1 AS category_id, 'Ballpen (Black)' AS name, 10.00 AS price
    UNION ALL SELECT 1, 'Pencil No. 2', 12.00
    UNION ALL SELECT 1, 'Notebook (80 Leaves)', 35.00
    UNION ALL SELECT 1, 'Intermediate Pad', 28.00
    UNION ALL SELECT 1, 'Correction Tape', 25.00
    UNION ALL SELECT 1, 'Ruler (12 inch)', 15.00
    UNION ALL SELECT 1, 'Eraser', 8.00
    UNION ALL SELECT 1, 'Pencil Sharpener', 12.00
    UNION ALL SELECT 1, 'Highlighter', 30.00
    UNION ALL SELECT 2, 'Facial Cleanser', 120.00
    UNION ALL SELECT 2, 'Sunscreen SPF 50', 249.00
    UNION ALL SELECT 2, 'Lip Balm', 85.00
    UNION ALL SELECT 2, 'Shampoo Sachet', 8.00
    UNION ALL SELECT 2, 'Body Lotion', 199.00
    UNION ALL SELECT 2, 'Face Powder', 150.00
    UNION ALL SELECT 2, 'Nail Polish', 65.00
    UNION ALL SELECT 2, 'Cotton Buds', 20.00
    UNION ALL SELECT 2, 'Hand Sanitizer', 55.00
    UNION ALL SELECT 3, 'Cotton T-Shirt', 199.00
    UNION ALL SELECT 3, 'Slippers', 149.00
    UNION ALL SELECT 3, 'Rubber Shoes', 599.00
    UNION ALL SELECT 3, 'Socks (Pair)', 59.00
    UNION ALL SELECT 3, 'Cap', 129.00
    UNION ALL SELECT 3, 'Denim Shorts', 299.00
    UNION ALL SELECT 3, 'Sandals', 199.00
    UNION ALL SELECT 3, 'Tank Top', 129.00
    UNION ALL SELECT 3, 'Handkerchief', 25.00
    UNION ALL SELECT 4, 'Bottled Water (500ml)', 20.00
    UNION ALL SELECT 4, 'Soft Drinks (1.5L)', 75.00
    UNION ALL SELECT 4, 'Iced Tea (500ml)', 35.00
    UNION ALL SELECT 4, 'Coffee 3-in-1', 10.00
    UNION ALL SELECT 4, 'Energy Drink (250ml)', 45.00
    UNION ALL SELECT 4, 'Powdered Juice', 12.00
    UNION ALL SELECT 4, 'Milk Tea', 25.00
    UNION ALL SELECT 4, 'Soda Can', 45.00
    UNION ALL SELECT 4, 'Hot Coffee Cup', 35.00
    UNION ALL SELECT 5, 'Soy Sauce (385ml)', 28.00
    UNION ALL SELECT 5, 'Vinegar (385ml)', 26.00
    UNION ALL SELECT 5, 'Cooking Oil (1L)', 85.00
    UNION ALL SELECT 5, 'Salt (500g)', 22.00
    UNION ALL SELECT 5, 'Black Pepper (50g)', 38.00
    UNION ALL SELECT 5, 'Fish Sauce', 30.00
    UNION ALL SELECT 5, 'Brown Sugar (500g)', 65.00
    UNION ALL SELECT 5, 'Garlic Powder', 35.00
    UNION ALL SELECT 5, 'Bay Leaves', 15.00
    UNION ALL SELECT 6, 'Bath Soap', 45.00
    UNION ALL SELECT 6, 'Toothpaste (100g)', 89.00
    UNION ALL SELECT 6, 'Toothbrush', 35.00
    UNION ALL SELECT 6, 'Tissue Roll', 30.00
    UNION ALL SELECT 6, 'Alcohol (500ml)', 95.00
    UNION ALL SELECT 6, 'Dental Floss', 45.00
    UNION ALL SELECT 6, 'Deodorant', 110.00
    UNION ALL SELECT 6, 'Shampoo Bottle', 149.00
    UNION ALL SELECT 6, 'Conditioner', 149.00
    UNION ALL SELECT 7, 'Frying Pan', 349.00
    UNION ALL SELECT 7, 'Cooking Pot', 499.00
    UNION ALL SELECT 7, 'Spatula', 79.00
    UNION ALL SELECT 7, 'Ladle', 69.00
    UNION ALL SELECT 7, 'Kitchen Knife', 129.00
    UNION ALL SELECT 7, 'Rice Cooker', 899.00
    UNION ALL SELECT 7, 'Mixing Bowl', 149.00
    UNION ALL SELECT 7, 'Chopping Board', 199.00
    UNION ALL SELECT 7, 'Measuring Cup', 89.00
    UNION ALL SELECT 8, 'Hair Tie (Pack)', 25.00
    UNION ALL SELECT 8, 'Hair Clip', 35.00
    UNION ALL SELECT 8, 'Comb', 30.00
    UNION ALL SELECT 8, 'Headband', 45.00
    UNION ALL SELECT 8, 'Hair Brush', 89.00
    UNION ALL SELECT 8, 'Hair Clip Set', 55.00
    UNION ALL SELECT 8, 'Scrunchies', 40.00
    UNION ALL SELECT 8, 'Hair Net', 20.00
    UNION ALL SELECT 8, 'Hair Wax', 120.00
    UNION ALL SELECT 9, 'Potato Chips', 35.00
    UNION ALL SELECT 9, 'Chocolate Bar', 45.00
    UNION ALL SELECT 9, 'Cookies', 30.00
    UNION ALL SELECT 9, 'Crackers', 20.00
    UNION ALL SELECT 9, 'Instant Noodles', 15.00
    UNION ALL SELECT 9, 'Candy', 5.00
    UNION ALL SELECT 9, 'Biscuits', 25.00
    UNION ALL SELECT 9, 'Peanuts', 30.00
    UNION ALL SELECT 9, 'Corn Chips', 35.00
    UNION ALL SELECT 10, 'Vanilla Ice Cream (1L)', 199.00
    UNION ALL SELECT 10, 'Chocolate Ice Cream (1L)', 199.00
    UNION ALL SELECT 10, 'Ice Cream Cone', 25.00
    UNION ALL SELECT 10, 'Ice Pop', 15.00
    UNION ALL SELECT 10, 'Sundae Cup', 49.00
    UNION ALL SELECT 10, 'Mango Ice Cream (1L)', 199.00
    UNION ALL SELECT 10, 'Strawberry Ice Cream (1L)', 199.00
    UNION ALL SELECT 10, 'Ice Cream Sandwich', 30.00
    UNION ALL SELECT 10, 'Frozen Yogurt', 59.00
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM products);

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL DEFAULT '',
    address TEXT NOT NULL,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED DEFAULT NULL,
    product_name VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id),
    KEY order_id (order_id),
    CONSTRAINT fk_order_item_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_item_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE SET NULL
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_admin_username (username)
) ENGINE = InnoDB;

INSERT INTO admins (username, password_hash)
SELECT 'admin', '$2y$10$pA2tuBW0sOELjjmcThQMkOjeUYN66Fwugqr.YF4nFWrvq5X/tpOSm'
WHERE NOT EXISTS (
    SELECT 1 FROM admins WHERE username = 'admin'
);

UPDATE products
SET stock = CASE
    WHEN id % 7 = 0 THEN 0
    WHEN id % 5 = 0 THEN 5
    WHEN id % 3 = 0 THEN 12
    ELSE 30
END
WHERE stock = 0;
