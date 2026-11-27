-- Fresh installation for the EXISTING iot database.
-- Does not delete anything and does not include product administration pages.
-- UPC = one product type; EPC = one individual RFID-tagged unit.
CREATE DATABASE IF NOT EXISTS iot;
USE iot;

CREATE TABLE IF NOT EXISTS customers (
    customer_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    address VARCHAR(100) NOT NULL,
    telephone_number VARCHAR(100) NOT NULL,
    email_address VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    membership_code CHAR(6) NOT NULL UNIQUE,
    total_points INT NOT NULL DEFAULT 0,
    is_admin TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkout_products (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    upc VARCHAR(13) NOT NULL UNIQUE,
    producer_company VARCHAR(160) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT chk_checkout_price CHECK (price >= 0),
    CONSTRAINT chk_checkout_upc CHECK (upc REGEXP '^[0-9]{1,13}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One reception records how many units of a UPC were received on a date.
CREATE TABLE IF NOT EXISTS checkout_stock_receptions (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity_received INT NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_note VARCHAR(150) NULL,
    CONSTRAINT fk_checkout_reception_product FOREIGN KEY (product_id) REFERENCES checkout_products(id),
    CONSTRAINT chk_checkout_received CHECK (quantity_received > 0),
    INDEX idx_checkout_reception_product (product_id, received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Two bananas with the SAME UPC can have DIFFERENT EPCs.
CREATE TABLE IF NOT EXISTS checkout_rfid_tags (
    epc VARCHAR(24) NOT NULL PRIMARY KEY,
    product_id INT NOT NULL,
    CONSTRAINT fk_checkout_rfid_product FOREIGN KEY (product_id) REFERENCES checkout_products(id),
    CONSTRAINT chk_checkout_epc CHECK (epc REGEXP '^[0-9A-Fa-f]{1,24}$'),
    INDEX idx_checkout_rfid_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- A barcode scan has epc NULL; each RFID-tag scan has a unique EPC.
CREATE TABLE IF NOT EXISTS checkout_cart_items (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    station VARCHAR(50) NOT NULL DEFAULT 'main',
    product_id INT NOT NULL,
    epc VARCHAR(24) NULL,
    quantity INT NOT NULL DEFAULT 1,
    UNIQUE KEY unique_station_epc (station, epc),
    CONSTRAINT fk_checkout_cart_product FOREIGN KEY (product_id) REFERENCES checkout_products(id),
    CONSTRAINT chk_checkout_cart_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkout_removed_tags (
    station VARCHAR(50) NOT NULL,
    epc VARCHAR(24) NOT NULL,
    PRIMARY KEY (station, epc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders are required for payment simulation, points and purchase history.
-- No receipt page or email system is installed.
CREATE TABLE IF NOT EXISTS checkout_orders (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    station VARCHAR(50) NOT NULL,
    customer_id INT NULL,
    membership_code_snapshot CHAR(6) NULL,
    points_earned INT NOT NULL DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'simulated_paid',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_checkout_order_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkout_order_items (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(160) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_checkout_order_item_order FOREIGN KEY (order_id) REFERENCES checkout_orders(id),
    CONSTRAINT fk_checkout_order_item_product FOREIGN KEY (product_id) REFERENCES checkout_products(id),
    INDEX idx_checkout_sales_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Track sold physical tags so an EPC cannot be paid for a second time.
CREATE TABLE IF NOT EXISTS checkout_sold_epcs (
    epc VARCHAR(24) NOT NULL PRIMARY KEY,
    order_id INT NOT NULL,
    CONSTRAINT fk_checkout_sold_epc_order FOREIGN KEY (order_id) REFERENCES checkout_orders(id),
    CONSTRAINT fk_checkout_sold_epc_tag FOREIGN KEY (epc) REFERENCES checkout_rfid_tags(epc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE OR REPLACE VIEW checkout_product_details AS
SELECT p.id AS product_id, p.name, p.category, p.price, p.upc,
       t.epc, p.producer_company
FROM checkout_products p
LEFT JOIN checkout_rfid_tags t ON t.product_id = p.id;

-- Live stock = all units received minus all units sold.
CREATE OR REPLACE VIEW checkout_stock_summary AS
SELECT p.id AS product_id, p.upc, p.name, p.category, p.price,
       COALESCE(r.received, 0) AS received_total,
       COALESCE(s.sold, 0) AS sold_total,
       GREATEST(COALESCE(r.received,0) - COALESCE(s.sold,0),0) AS in_stock,
       COALESCE(t.tags,0) AS epcs_registered
FROM checkout_products p
LEFT JOIN (SELECT product_id, SUM(quantity_received) AS received
           FROM checkout_stock_receptions GROUP BY product_id) r ON r.product_id = p.id
LEFT JOIN (SELECT product_id, SUM(quantity) AS sold
           FROM checkout_order_items WHERE product_id IS NOT NULL GROUP BY product_id) s ON s.product_id = p.id
LEFT JOIN (SELECT product_id, COUNT(*) AS tags
           FROM checkout_rfid_tags GROUP BY product_id) t ON t.product_id = p.id;
