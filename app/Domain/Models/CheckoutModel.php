<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;
use RuntimeException;
use Throwable;

/** Customer shopping cart, EPC scanning, simulated orders and loyalty points. */
class CheckoutModel extends BaseModel
{
    private const STATION = 'main';
    private const DEMO_TAX_RATE = 0.14975; // Demo estimate only. Some groceries are tax-exempt.

    public function __construct(PDOService $db) { parent::__construct($db); }

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

    /** Group a physical-item cart into one visible row per UPC. */
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

    public function cartFingerprint(): string
    {
        // Includes the exact unit rows, not only the total. This catches a reader
        // adding another physical EPC while the customer is confirming.
        return hash('sha256',json_encode($this->cart(),JSON_THROW_ON_ERROR));
    }

    public function removedTags(): array
    {
        return $this->selectAll('SELECT epc FROM checkout_removed_tags WHERE station=? ORDER BY epc', [self::STATION]);
    }

    public function totals(array $cart): array
    {
        $subtotal = 0;
        foreach ($cart as $item) $subtotal += (float)$item['line_total'];
        $subtotal = round($subtotal,2);
        $tax = round($subtotal * self::DEMO_TAX_RATE,2);
        return ['subtotal'=>$subtotal,'tax'=>$tax,'total'=>round($subtotal+$tax,2)];
    }

    public function addUpc(string $upc): void
    {
        if (!preg_match('/^[0-9]{1,13}$/D',$upc)) throw new RuntimeException('UPC must be 1 to 13 digits.');
        $product = $this->selectOne('SELECT id FROM checkout_products WHERE upc=? AND active=1', [$upc]);
        if (!$product) throw new RuntimeException('Unknown UPC: '.$upc);
        // Each UPC scan represents one more unit. Without an EPC we cannot tell
        // WHICH exact physical unit was scanned.
        $this->execute('INSERT INTO checkout_cart_items(station,product_id,quantity) VALUES(?,?,1)',
            [self::STATION,$product['id']]);
    }

    /** A reader reports multiple distinct physical EPCs in one batch. */
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
            $rows=$this->execute('INSERT IGNORE INTO checkout_cart_items(station,product_id,epc,quantity)
                                  VALUES(?,?,?,1)',[self::STATION,$product['id'],$epc]);
            if ($rows) $added[]=$epc; else $ignored[]=$epc;
        }
        return ['added'=>$added,'unknown'=>$unknown,'ignored'=>$ignored];
    }

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

    public function restoreTag(string $epc): void
    {
        $this->execute('DELETE FROM checkout_removed_tags WHERE station=? AND epc=?',[self::STATION,$epc]);
    }

    public function clear(): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->execute('DELETE FROM checkout_cart_items WHERE station=?',[self::STATION]);
            $this->execute('DELETE FROM checkout_removed_tags WHERE station=?',[self::STATION]);
            $this->pdo->commit();
        } catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }

    public function memberByCode(string $code): array|false
    {
        if (!preg_match('/^[0-9]{6}$/D',$code)) return false;
        return $this->selectOne('SELECT customer_id,customer_name,membership_code
                                  FROM customers WHERE membership_code=?',[$code]);
    }

    public function memberById(int $id): array|false
    {
        return $this->selectOne('SELECT customer_id,customer_name,membership_code
                                  FROM customers WHERE customer_id=?',[$id]);
    }

    /**
     * One payment per checkout, atomic stock check and membership-point award.
     * Inventory is based on received quantities minus the sum of sold quantities.
     */
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
            $points=$customerId ? (int)floor($totals['subtotal']) : 0; // 1 point per $1 before tax.
            $member=null;
            if ($customerId !== null) {
                $member=$this->selectOne('SELECT customer_id,membership_code FROM customers WHERE customer_id=? FOR UPDATE',[$customerId]);
                if (!$member) throw new RuntimeException('Membership account not found.');
            }
            $this->execute(
                'INSERT INTO checkout_orders(station,customer_id,membership_code_snapshot,points_earned,subtotal,tax,total)
                 VALUES(?,?,?,?,?,?,?)',
                [self::STATION,$customerId,$member['membership_code']??null,$points,
                 $totals['subtotal'],$totals['tax'],$totals['total']]);
            $orderId=(int)$this->lastInsertId();
            foreach ($items as $item) {
                $this->execute('INSERT INTO checkout_order_items(order_id,product_id,product_name,quantity,unit_price)
                                VALUES(?,?,?,?,?)',
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

    public function orderForSuccess(int $id): array|false
    {
        return $this->selectOne('SELECT id,customer_id,points_earned,total,created_at
                                  FROM checkout_orders WHERE id=?',[$id]);
    }
}
