<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;
use RuntimeException;
use Throwable;

//! Customer shopping cart, EPC scanning, simulated orders and loyalty points. 
class CheckoutModel extends BaseModel
{
    private const STATION = 'main';
    private const DEMO_TAX_RATE = 0.14975; // Demo estimate only. Some groceries are tax-exempt.

    public function __construct(PDOService $db) { parent::__construct($db); }


    //! Retrieves all products currently in the shopping cart
    public function cart(): array
    {
        return $this->selectAll(
            'SELECT c.id,c.product_id,c.epc,c.quantity,p.name,p.upc,p.price,
                    p.price*c.quantity AS line_total
             FROM checkout_cart_items c
             JOIN checkout_products p ON p.id=c.product_id
             WHERE c.station=? ORDER BY p.name,c.id', [self::STATION]
        );
    }

    //! Group a physical-item cart into one visible row per UPC
    public function groupedCart(array $cart): array
    {
        $groups = [];
        foreach ($cart as $item) {
            $id = (int)$item['product_id'];
            if (!isset($groups[$id])) {
                $groups[$id] = [
                    'name'=>$item['name'], 'upc'=>$item['upc'], 'price'=>(float)$item['price'],
                    'quantity'=>0, 'line_total'=>0.0, 'units'=>[],
                ];
            }
            $groups[$id]['quantity'] += (int)$item['quantity'];
            $groups[$id]['line_total'] += (float)$item['line_total'];
            $groups[$id]['units'][] = $item;
        }
        return array_values($groups);
    }

    //! enerates a unique hash to detect changes to the shopping cart
    public function cartFingerprint(): string
    {
        // Includes the exact unit rows, not only the total. This catches a reader
        // adding another physical EPC while the customer is confirming.
        return hash('sha256',json_encode($this->cart(),JSON_THROW_ON_ERROR));
    }

    //! Retrieves RFID tags that the customer previously removed from the cart.
    public function removedTags(): array
    {
        return $this->selectAll('SELECT epc FROM checkout_removed_tags WHERE station=? ORDER BY epc', [self::STATION]);
    }


    //! Calculates the shopping cart subtotal, taxes, and final total
    public function totals(array $cart): array
    {
        $subtotal = 0;
        foreach ($cart as $item) $subtotal += (float)$item['line_total'];
        $subtotal = round($subtotal,2);
        $tax = round($subtotal * self::DEMO_TAX_RATE,2);
        return ['subtotal'=>$subtotal,'tax'=>$tax,'total'=>round($subtotal+$tax,2)];
    }

    //! Finds a product using its UPC and adds one unit to the shopping cart
    public function addUpc(string $upc): void
    {
        if (!preg_match('/^[0-9]{1,13}$/D',$upc)) throw new RuntimeException('UPC must be 1 to 13 digits.');
        $product = $this->selectOne('SELECT id FROM checkout_products WHERE upc=? AND active=1', [$upc]);
        if (!$product) throw new RuntimeException('Unknown UPC: '.$upc);
        // Each UPC scan represents one more unit. Without an EPC we cannot tell
        // WHICH exact physical unit was scanned.
        $this->execute('INSERT INTO checkout_cart_items(id,station,product_id,quantity) VALUES(NULL,?,?,1)',
            [self::STATION,$product['id']]);
    }

    //! Processes multiple scanned EPCs and adds valid, available RFID items to the cart. 
    public function addEpcs(array $epcs): array
    {
        $added=[]; $unknown=[]; $ignored=[];
        $normalized=array_values(array_unique(array_map(
            static fn($x)=>strtoupper(trim((string)$x)), $epcs
        )));
        foreach ($normalized as $epc) {
            if ($epc === '') continue;
            if (!preg_match('/^[0-9A-F]{1,24}$/D',$epc)) { $unknown[]=$epc; continue; }
            if ($this->selectOne('SELECT 1 FROM checkout_removed_tags WHERE station=? AND epc=?', [self::STATION,$epc])) {
                $ignored[]=$epc; continue;
            }
            if ($this->selectOne('SELECT 1 FROM checkout_sold_epcs WHERE epc=?', [$epc])) {
                $ignored[]=$epc; continue;
            }
            $product=$this->selectOne(
                'SELECT p.id FROM checkout_rfid_tags t
                 JOIN checkout_products p ON p.id=t.product_id
                 WHERE t.epc=? AND p.active=1', [$epc]);
            if (!$product) { $unknown[]=$epc; continue; }
            $rows=$this->execute('INSERT IGNORE INTO checkout_cart_items(id,station,product_id,epc,quantity)
                                  VALUES(NULL,?,?,?,1)',[self::STATION,$product['id'],$epc]);
            if ($rows) $added[]=$epc; else $ignored[]=$epc;
        }
        return ['added'=>$added,'unknown'=>$unknown,'ignored'=>$ignored];
    }


    //! Removes an item from the cart and prevents its RFID tag from being immediately re-added
    public function remove(int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $item=$this->selectOne('SELECT epc FROM checkout_cart_items WHERE station=? AND id=? FOR UPDATE',
                [self::STATION,$id]);
            if ($item && $item['epc'] !== null) {
                $this->execute('INSERT IGNORE INTO checkout_removed_tags(station,epc) VALUES(?,?)',
                    [self::STATION,$item['epc']]);
            }
            $this->execute('DELETE FROM checkout_cart_items WHERE station=? AND id=?',[self::STATION,$id]);
            $this->pdo->commit();
        } catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }


    //! Allows a previously removed RFID tag to be scanned and added again
    public function restoreTag(string $epc): void
    {
        $this->execute('DELETE FROM checkout_removed_tags WHERE station=? AND epc=?',[self::STATION,$epc]);
    }

    //! Empties the shopping cart and clears the list of removed RFID tags
    public function clear(): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->execute('DELETE FROM checkout_cart_items WHERE station=?',[self::STATION]);
            $this->execute('DELETE FROM checkout_removed_tags WHERE station=?',[self::STATION]);
            $this->pdo->commit();
        } catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }

    //! Finds the matching member by his code, and retrieves info related to the customer
    public function memberByCode(string $code): array|false
    {
        if (!preg_match('/^[0-9]{6}$/D',$code)) return false;
        return $this->selectOne('SELECT customer_id,customer_name,membership_code
                                  FROM customers WHERE membership_code=?',[$code]);
    }

    //! Finds the matching member by his id (customer_id), and retrieves info related to the customer
    public function memberById(int $id): array|false
    {
        return $this->selectOne('SELECT customer_id,customer_name,email_address,membership_code
                                  FROM customers WHERE customer_id=?',[$id]);
    }

    //! One payment per checkout, atomic stock check and membership-point award.
    //! Inventory is based on received quantities minus the sum of sold quantities.

    public function pay(?int $customerId): int
    {
        $this->pdo->beginTransaction();
        try {
            $items=$this->selectAll(
                'SELECT c.product_id,c.epc,c.quantity,p.name,p.price,
                        c.quantity*p.price AS line_total
                 FROM checkout_cart_items c JOIN checkout_products p ON p.id=c.product_id
                 WHERE c.station=? ORDER BY c.product_id,c.id FOR UPDATE',[self::STATION]);
            if (!$items) throw new RuntimeException('Your shopping cart is empty.');

            $counts=[];
            foreach ($items as $item) {
                $pid=(int)$item['product_id'];
                $counts[$pid]=($counts[$pid]??0)+(int)$item['quantity'];
            }
            ksort($counts);
            // Serialize payments for the same products to prevent overselling.
            foreach (array_keys($counts) as $pid) {
                $product=$this->selectOne('SELECT id FROM checkout_products WHERE id=? AND active=1 FOR UPDATE',[$pid]);
                if (!$product) throw new RuntimeException('A product in the cart is no longer available.');
                $stock=$this->selectOne(
                    'SELECT (SELECT COALESCE(SUM(quantity_received),0)
                             FROM checkout_stock_receptions WHERE product_id=?) -
                            (SELECT COALESCE(SUM(quantity),0)
                             FROM checkout_order_items WHERE product_id=?) AS qty',[$pid,$pid]);
                if ((int)$stock['qty'] < $counts[$pid]) {
                    throw new RuntimeException('Not enough stock for one of your products.');
                }
            }
            foreach ($items as $item) {
                if ($item['epc'] !== null && $this->selectOne('SELECT 1 FROM checkout_sold_epcs WHERE epc=?',[$item['epc']])) {
                    throw new RuntimeException('An RFID unit in this cart was already sold. Remove it and retry.');
                }
            }

            $totals=$this->totals($items);
            $points = $customerId ? (int)floor($totals['subtotal'] * 100) : 0; // 100 points per $1 before tax.
            $member=null;
            if ($customerId !== null) {
                $member=$this->selectOne('SELECT customer_id,membership_code FROM customers WHERE customer_id=? FOR UPDATE',[$customerId]);
                if (!$member) throw new RuntimeException('Membership account not found.');
            }
            $this->execute(
                'INSERT INTO checkout_orders(id,station,customer_id,membership_code_snapshot,points_earned,subtotal,tax,total)
                 VALUES(NULL,?,?,?,?,?,?,?)',
                [self::STATION,$customerId,$member['membership_code']??null,$points,
                 $totals['subtotal'],$totals['tax'],$totals['total']]);
            $orderId=(int)$this->lastInsertId();
            foreach ($items as $item) {
                $this->execute('INSERT INTO checkout_order_items(id,order_id,product_id,product_name,quantity,unit_price)
                                VALUES(NULL,?,?,?,?,?)',
                    [$orderId,$item['product_id'],$item['name'],$item['quantity'],$item['price']]);
                if ($item['epc'] !== null) {
                    $this->execute('INSERT INTO checkout_sold_epcs(epc,order_id) VALUES(?,?)',[$item['epc'],$orderId]);
                }
            }
            if ($customerId !== null && $points>0) {
                $this->execute('UPDATE customers SET total_points=total_points+? WHERE customer_id=?',[$points,$customerId]);
            }
            $this->execute('DELETE FROM checkout_cart_items WHERE station=?',[self::STATION]);
            $this->execute('DELETE FROM checkout_removed_tags WHERE station=?',[self::STATION]);
            $this->pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    //! Summary of a completed order for the payment success page
    public function orderForSuccess(int $id): array|false
    {
        return $this->selectOne('SELECT id,customer_id,points_earned,total,created_at
                                  FROM checkout_orders WHERE id=?',[$id]);
    }


    //! Retrieves and groups all purchased products belonging to a specific order
    public function orderItems(int $orderId): array
    {
        return $this->selectAll(
            'SELECT product_id,
                    product_name,
                    SUM(quantity) AS quantity,
                    unit_price,
                    SUM(quantity * unit_price) AS subtotal
            FROM checkout_order_items
            WHERE order_id = ?
            GROUP BY product_id, product_name, unit_price
            ORDER BY product_name',
            [$orderId]
        );
    }    

    public function customerActivity(string $fromDate, string $toDate): array
    {
        $summary = $this->selectOne(
            'SELECT COUNT(DISTINCT period_orders.customer_id) AS total_customers,
                    COUNT(DISTINCT CASE
                        WHEN first_orders.first_purchase_at >= ?
                         AND first_orders.first_purchase_at < DATE_ADD(?, INTERVAL 1 DAY)
                        THEN period_orders.customer_id
                    END) AS new_customers,
                    COUNT(DISTINCT CASE
                        WHEN first_orders.first_purchase_at < ?
                        THEN period_orders.customer_id
                    END) AS returning_customers
             FROM checkout_orders period_orders
             JOIN (
                 SELECT customer_id, MIN(created_at) AS first_purchase_at
                 FROM checkout_orders
                 WHERE customer_id IS NOT NULL
                 GROUP BY customer_id
             ) first_orders ON first_orders.customer_id = period_orders.customer_id
             WHERE period_orders.created_at >= ?
               AND period_orders.created_at < DATE_ADD(?, INTERVAL 1 DAY)',
            [$fromDate, $toDate, $fromDate, $fromDate, $toDate]
        );

        $dailyRows = $this->selectAll(
            'SELECT DATE(created_at) AS purchase_date,
                    COUNT(DISTINCT customer_id) AS customer_count
             FROM checkout_orders
             WHERE customer_id IS NOT NULL
               AND created_at >= ?
               AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
             GROUP BY DATE(created_at)
             ORDER BY purchase_date',
            [$fromDate, $toDate]
        );

        $countsByDate = [];
        foreach ($dailyRows as $row) {
            $countsByDate[(string)$row['purchase_date']] = (int)$row['customer_count'];
        }

        $dailyActivity = [];
        $from = new \DateTimeImmutable($fromDate);
        $to = new \DateTimeImmutable($toDate);
        for ($date = $from; $date <= $to; $date = $date->modify('+1 day')) {
            $dateLabel = $date->format('Y-m-d');
            $dailyActivity[] = [
                'date' => $dateLabel,
                'customer_count' => $countsByDate[$dateLabel] ?? 0,
            ];
        }

        $totalCustomers = (int)($summary['total_customers'] ?? 0);
        $busiestDay = null;
        $leastBusyDay = null;
        if ($totalCustomers > 0) {
            $busiestDay = $dailyActivity[0];
            $leastBusyDay = $dailyActivity[0];
            foreach ($dailyActivity as $day) {
                if ($day['customer_count'] > $busiestDay['customer_count']) {
                    $busiestDay = $day;
                }
                if ($day['customer_count'] < $leastBusyDay['customer_count']) {
                    $leastBusyDay = $day;
                }
            }
        }

        return [
            'totalCustomers' => $totalCustomers,
            'newCustomers' => (int)($summary['new_customers'] ?? 0),
            'returningCustomers' => (int)($summary['returning_customers'] ?? 0),
            'dailyActivity' => $dailyActivity,
            'busiestDay' => $busiestDay,
            'leastBusyDay' => $leastBusyDay,
        ];
    }


    //! Inventory overview: current stock per product with last restock date
    public function inventoryReport(?string $category = null): array
    {
        $sql = 'SELECT p.id, p.name, p.category, p.price, p.upc,
                       COALESCE(s.in_stock, 0) AS in_stock,
                       COALESCE(s.received_total, 0) AS received_total,
                       COALESCE(s.sold_total, 0) AS sold_total,
                       COALESCE(s.epcs_registered, 0) AS epcs_registered,
                       (SELECT MAX(r.received_at)
                        FROM checkout_stock_receptions r
                        WHERE r.product_id = p.id) AS last_restocked
                FROM checkout_products p
                LEFT JOIN checkout_stock_summary s ON s.product_id = p.id
                WHERE p.active = 1';

        $params = [];
        if ($category !== null && $category !== '') {
            $sql .= ' AND p.category = ?';
            $params[] = $category;
        }

        $sql .= ' ORDER BY p.name';

        return $this->selectAll($sql, $params);
    }

    //! Distinct product categories for filter dropdowns
    public function getProductCategories(): array
    {
        return $this->selectAll(
            'SELECT DISTINCT category AS category
             FROM checkout_products
             WHERE active = 1
             ORDER BY category'
        );
    }

    //! Sold items per product within a date range, with revenue
    public function salesReport(string $fromDate, string $toDate, ?string $category = null): array
    {
        $sql = 'SELECT p.id AS product_id, p.name, p.category,
                       SUM(oi.quantity) AS quantity_sold,
                       SUM(oi.quantity * oi.unit_price) AS revenue,
                       COUNT(DISTINCT oi.order_id) AS order_count
                FROM checkout_order_items oi
                JOIN checkout_orders o ON o.id = oi.order_id
                JOIN checkout_products p ON p.id = oi.product_id
                WHERE o.created_at >= ?
                  AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';

        $params = [$fromDate, $toDate];

        if ($category !== null && $category !== '') {
            $sql .= ' AND p.category = ?';
            $params[] = $category;
        }

        $sql .= ' GROUP BY p.id, p.name, p.category
                  ORDER BY quantity_sold DESC, p.name';

        return $this->selectAll($sql, $params);
    }

    //! Daily revenue trend for line chart within a date range
    public function dailyRevenue(string $fromDate, string $toDate, ?string $category = null): array
    {
        $sql = 'SELECT DATE(o.created_at) AS sale_date,
                       SUM(oi.quantity * oi.unit_price) AS daily_revenue,
                       SUM(oi.quantity) AS daily_items
                FROM checkout_order_items oi
                JOIN checkout_orders o ON o.id = oi.order_id
                JOIN checkout_products p ON p.id = oi.product_id
                WHERE o.created_at >= ?
                  AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';

        $params = [$fromDate, $toDate];

        if ($category !== null && $category !== '') {
            $sql .= ' AND p.category = ?';
            $params[] = $category;
        }

        $sql .= ' GROUP BY DATE(o.created_at)
                  ORDER BY sale_date';

        return $this->selectAll($sql, $params);
    }

    // list all products with current stock level and associated epcs
    public function allProducts(): array
    {
        return $this->selectAll('
            SELECT p.id, p.name, p.category, p.price, p.upc, p.producer_company, p.active,
                   COALESCE(s.in_stock, 0) AS in_stock,
                   GROUP_CONCAT(DISTINCT t.epc SEPARATOR ", ") AS epcs
            FROM checkout_products p
            LEFT JOIN checkout_stock_summary s ON s.product_id = p.id
            LEFT JOIN checkout_rfid_tags t ON t.product_id = p.id
            GROUP BY p.id
            ORDER BY p.name
        ');
    }

    // get one product by id for editing
    public function getProduct(int $id): array|false
    {
        return $this->selectOne('SELECT * FROM checkout_products WHERE id=?', [$id]);
    }

    // list all stock receptions with product name and date
    public function allReceptions(): array
    {
        return $this->selectAll('
            SELECT r.id, r.quantity_received, r.received_at, p.name
            FROM checkout_stock_receptions r
            JOIN checkout_products p ON p.id = r.product_id
            ORDER BY r.received_at DESC
        ');
    }

    // insert a new product, returns the new product id
    public function createProduct(array $data): int
    {
        $this->execute(
            'INSERT INTO checkout_products(id, name, category, price, upc, producer_company, active)
             VALUES(NULL,?,?,?,?,?,1)',
            [$data['name'], $data['category'], $data['price'], $data['upc'], $data['producer_company']]
        );
        return (int) $this->lastInsertId();
    }

    // update an existing product
    public function updateProduct(int $id, array $data): void
    {
        $this->execute(
            'UPDATE checkout_products SET name=?, category=?, price=?, upc=?, producer_company=?, active=? WHERE id=?',
            [$data['name'], $data['category'], $data['price'], $data['upc'], $data['producer_company'], $data['active'] ? 1 : 0, $id]
        );
    }

    // delete a product by id
    public function deleteProduct(int $id): void
    {
        $this->execute('DELETE FROM checkout_products WHERE id=?', [$id]);
    }

    // record a stock reception for a product (increases inventory)
    public function addStock(int $productId, int $quantity): void
    {
        $this->execute(
            'INSERT INTO checkout_stock_receptions(id, product_id, quantity_received, received_at, reference_note)
             VALUES(NULL,?,?,NOW(),?)',
            [$productId, $quantity, 'manual stock adjustment']
        );
    }

    // add epcs to a product (comma separated list, hex values max 24 chars)
    public function addEpcsToProduct(int $productId, string $epcList): array
    {
        $added = [];
        $ignored = [];
        $epcs = array_values(array_unique(array_map(
            static fn($x) => strtoupper(trim((string)$x)),
            preg_split('/[\s,;]+/', $epcList, -1, PREG_SPLIT_NO_EMPTY)
        )));

        foreach ($epcs as $epc) {
            if ($epc === '') continue;
            if (!preg_match('/^[0-9A-F]{1,24}$/D', $epc)) {
                $ignored[] = $epc . ' (invalid)';
                continue;
            }
            try {
                $rows = $this->execute(
                'INSERT IGNORE INTO checkout_rfid_tags(epc, product_id) VALUES(?,?)',
                [$epc, $productId]
                );

                if ($rows > 0) {
                    $added[] = $epc;
                } else {
                    $ignored[] = $epc . ' (already registered)';
                }
            } catch (\Throwable $e) {
                $ignored[] = $epc . ' (duplicate)';
            }
        }

        return ['added' => $added, 'ignored' => $ignored];
    }
    public function countProductEpcs(int $productId): int
    {
        $result = $this->selectOne(
            'SELECT COUNT(*) AS total FROM checkout_rfid_tags WHERE product_id = ?',
            [$productId]
        );

        return (int) ($result['total'] ?? 0);
    }

    // Create a product and register its scanned RFID tags.
    public function createProductWithScans(array $data, int $stock, array $epcs): array
    {
        $this->pdo->beginTransaction();

        try {
            $productId = $this->createProduct($data);

            $result = $this->addEpcsToProduct(
                $productId,
                implode(',', $epcs)
            );

            $registered = count($result['added']);

            $stock = max($stock, $registered);

            if ($stock > 0) {
                $this->addStock($productId, $stock);
            }

            $this->pdo->commit();

            return [
                'product_id' => $productId,
                'registered' => $registered
            ];

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function getProductEpcs(int $productId): array
    {
        $rows = $this->selectAll(
            'SELECT epc FROM checkout_rfid_tags WHERE product_id = ? ORDER BY epc',
            [$productId]
        );

        return array_column($rows, 'epc');
    }

    public function clearProductEpcs(int $productId): void
    {
        $this->execute(
            'DELETE FROM checkout_rfid_tags WHERE product_id = ?',
            [$productId]
        );
    }

}
