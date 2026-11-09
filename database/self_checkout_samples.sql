-- OPTIONAL demo data. 10 product types, 20 unique individual EPCs, initial stock.
-- Fictional UPCs/EPCs, replace with codes printed on your own hardware.
-- Safe to repeat: no duplicate products/tags or receptions for these examples.
USE iot;
INSERT IGNORE INTO checkout_products (name,category,price,upc,producer_company) VALUES
('Banana','Produce',0.79,'001','Example Farm'),
('Coca-Cola 500ml','Beverages',2.49,'200000000028','Coca-Cola'),
('Pepsi 500ml','Beverages',2.29,'200000000035','PepsiCo'),
('Water Bottle 500ml','Beverages',1.49,'200000000042','Example Water'),
('Milk 1L','Dairy',3.79,'200000000059','Lactantia'),
('White Bread','Bakery',3.49,'200000000066','Wonder'),
('Eggs 12-pack','Dairy',4.99,'200000000073','Burnbrae Farms'),
('Potato Chips 200g','Snacks',3.99,'200000000080','Lay''s'),
('Apples 1kg','Produce',4.49,'200000000097','Example Farm'),
('Pasta 500g','Pantry',2.79,'200000000103','Barilla');

-- Each demo product starts with a reception of 10 units.
-- The specific reference makes reimporting this script safe.
INSERT INTO checkout_stock_receptions(product_id,quantity_received,reference_note)
SELECT p.id,10,'DEMO_INITIAL_STOCK_2026'
FROM checkout_products p
WHERE p.upc IN ('001','200000000028','200000000035','200000000042','200000000059',
                '200000000066','200000000073','200000000080','200000000097','200000000103')
AND NOT EXISTS (
 SELECT 1 FROM checkout_stock_receptions r
 WHERE r.product_id=p.id AND r.reference_note='DEMO_INITIAL_STOCK_2026'
);

-- Exactly two unique EPCs per UPC. EPC can have its own physical printed value.
INSERT IGNORE INTO checkout_rfid_tags (epc,product_id)
SELECT CONCAT('300833B2DDD9',LPAD(HEX(p.id),12,'0')),p.id
FROM checkout_products p
WHERE p.upc IN ('001','200000000028','200000000035','200000000042','200000000059',
                '200000000066','200000000073','200000000080','200000000097','200000000103')
AND p.upc <> '001';
INSERT IGNORE INTO checkout_rfid_tags (epc,product_id)
SELECT CONCAT('300833B2DDDA',LPAD(HEX(p.id),12,'0')),p.id
FROM checkout_products p
WHERE p.upc IN ('001','200000000028','200000000035','200000000042','200000000059',
                '200000000066','200000000073','200000000080','200000000097','200000000103')
AND p.upc <> '001';
-- The exact banana example: one UPC, two individual physical EPCs.
INSERT IGNORE INTO checkout_rfid_tags (epc,product_id)
SELECT '000001',id FROM checkout_products WHERE upc='001';
INSERT IGNORE INTO checkout_rfid_tags (epc,product_id)
SELECT '000002',id FROM checkout_products WHERE upc='001';
SELECT * FROM checkout_stock_summary ORDER BY name;
