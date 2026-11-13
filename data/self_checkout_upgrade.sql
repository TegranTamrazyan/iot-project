-- For people who already imported the earlier checkout SQL.
-- BACK UP the iot database before using this script. Run only once.
-- Existing fridge tables, products, carts and orders are not dropped.
USE iot;

ALTER TABLE customers
  ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS membership_code VARCHAR(6) NULL,
  ADD COLUMN IF NOT EXISTS total_points INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE checkout_products
  ADD COLUMN IF NOT EXISTS category VARCHAR(100) NOT NULL DEFAULT 'Uncategorized' AFTER name,
  ADD COLUMN IF NOT EXISTS producer_company VARCHAR(160) NOT NULL DEFAULT 'Unknown' AFTER upc;

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

ALTER TABLE checkout_orders
  ADD COLUMN IF NOT EXISTS customer_id INT NULL,
  ADD COLUMN IF NOT EXISTS membership_code_snapshot VARCHAR(6) NULL,
  ADD COLUMN IF NOT EXISTS points_earned INT NOT NULL DEFAULT 0;

ALTER TABLE checkout_order_items
  ADD COLUMN IF NOT EXISTS product_id INT NULL;

-- Link new customer and product references to their parent records.
-- This migration is intended to run ONCE on the earlier checkout schema.
ALTER TABLE checkout_orders
  ADD CONSTRAINT fk_checkout_order_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id);

ALTER TABLE checkout_order_items
  ADD CONSTRAINT fk_checkout_order_item_product FOREIGN KEY (product_id) REFERENCES checkout_products(id);

CREATE TABLE IF NOT EXISTS checkout_sold_epcs (
    epc VARCHAR(24) NOT NULL PRIMARY KEY,
    order_id INT NOT NULL,
    CONSTRAINT fk_checkout_sold_epc_order FOREIGN KEY (order_id) REFERENCES checkout_orders(id),
    CONSTRAINT fk_checkout_sold_epc_tag FOREIGN KEY (epc) REFERENCES checkout_rfid_tags(epc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS checkout_removed_tags (
    station VARCHAR(50) NOT NULL, epc VARCHAR(24) NOT NULL,
    PRIMARY KEY (station,epc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE OR REPLACE VIEW checkout_product_details AS
SELECT p.id AS product_id,p.name,p.category,p.price,p.upc,t.epc,p.producer_company
FROM checkout_products p
LEFT JOIN checkout_rfid_tags t ON t.product_id=p.id;

CREATE OR REPLACE VIEW checkout_stock_summary AS
SELECT p.id AS product_id,p.upc,p.name,p.category,p.price,
       COALESCE(r.received,0) AS received_total,
       COALESCE(s.sold,0) AS sold_total,
       GREATEST(COALESCE(r.received,0)-COALESCE(s.sold,0),0) AS in_stock,
       COALESCE(t.tags,0) AS epcs_registered
FROM checkout_products p
LEFT JOIN (SELECT product_id,SUM(quantity_received) AS received
           FROM checkout_stock_receptions GROUP BY product_id) r ON r.product_id=p.id
LEFT JOIN (SELECT product_id,SUM(quantity) AS sold
           FROM checkout_order_items WHERE product_id IS NOT NULL GROUP BY product_id) s ON s.product_id=p.id
LEFT JOIN (SELECT product_id,COUNT(*) AS tags FROM checkout_rfid_tags GROUP BY product_id) t ON t.product_id=p.id;

-- Existing orders from the old schema might lack product_id. Their old sales
-- cannot be attributed reliably to a product without a separate audit.
-- Enter the current physical inventory as a reception once when migrating.
