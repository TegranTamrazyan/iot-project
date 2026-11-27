<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;
use RuntimeException;
use Throwable;

class UserModel extends BaseModel
{
    public function __construct(PDOService $db) { parent::__construct($db); }

    public function getUserByEmail(string $email): array|false
    {
        return $this->selectOne('SELECT * FROM customers WHERE email_address=? LIMIT 1',[$email]);
    }

    public function getUserById(int $id): array|false
    {
        return $this->selectOne('SELECT customer_id,customer_name,email_address,membership_code,total_points
                                  FROM customers WHERE customer_id=?',[$id]);
    }

    public function createCustomer(string $name,string $email,string $password,string $phone,string $address): int
    {
        if (strlen($name)<2 || strlen($name)>100) throw new RuntimeException('Enter your full name (2-100 characters).');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100) throw new RuntimeException('Enter a valid email.');
        if (strlen($password)<8) throw new RuntimeException('Password must be at least 8 characters.');
        if (!preg_match('/^[0-9]{10}$/D',$phone)) throw new RuntimeException('Phone must contain 10 digits.');
        if ($address==='' || strlen($address)>100) throw new RuntimeException('Enter your address (up to 100 characters).');
        if ($this->getUserByEmail($email)) throw new RuntimeException('That email is already registered.');
        $hash=password_hash($password,PASSWORD_DEFAULT); // Never store plaintext passwords.
        // Generate a six-digit membership number; retry if already assigned.
        for ($attempt=0;$attempt<20;$attempt++) {
            $code=(string)random_int(100000,999999);
            if ($this->selectOne('SELECT customer_id FROM customers WHERE membership_code=?',[$code])) continue;
            try {
                $this->execute('INSERT INTO customers(customer_name,address,telephone_number,email_address,password_hash,membership_code,total_points)
                                VALUES(?,?,?,?,?,?,0)',[$name,$address,$phone,$email,$hash,$code]);
                return (int)$this->lastInsertId();
            } catch (\PDOException $e) {
                // Duplicated email/name/phone or code: produce a readable error.
                if ($e->getCode()!=='23000') throw $e;
                if (!$this->selectOne('SELECT customer_id FROM customers WHERE membership_code=?',[$code])) {
                    throw new RuntimeException('Account already exists with that name, phone or email.');
                }
            }
        }
        throw new RuntimeException('Could not generate a membership number. Please retry.');
    }

    public function purchaseCount(int $customerId): int
    {
        $result = $this->selectOne(
            'SELECT COUNT(*) AS total FROM checkout_orders WHERE customer_id=?',
            [$customerId]
        );
        return (int)($result['total'] ?? 0);
    }

    public function purchases(
        int $customerId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $limit = null,
        int $offset = 0
    ): array
    {
        $sql = 'SELECT id,total,points_earned,created_at,status
                FROM checkout_orders WHERE customer_id=?';
        $params = [$customerId];
        if ($fromDate !== null) {
            $sql .= ' AND created_at >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== null) {
            $sql .= ' AND created_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $toDate;
        }
        $sql .= ' ORDER BY created_at DESC,id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        }
        return $this->selectAll($sql, $params);
    }

    public function purchasedItems(int $customerId, ?array $orderIds = null): array
    {
        if ($orderIds === []) return [];
        $sql = 'SELECT oi.order_id,oi.product_name,oi.quantity,oi.unit_price
                FROM checkout_order_items oi JOIN checkout_orders o ON o.id=oi.order_id
                WHERE o.customer_id=?';
        $params = [$customerId];
        if ($orderIds !== null) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $sql .= ' AND oi.order_id IN (' . $placeholders . ')';
            $params = array_merge($params, array_map('intval', $orderIds));
        }
        return $this->selectAll(
            $sql . ' ORDER BY oi.order_id DESC,oi.id',
            $params
        );
    }

    public function purchasedProductRowCount(
        int $customerId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): int {
        $sql = 'SELECT COUNT(*) AS total
                FROM checkout_orders o
                JOIN checkout_order_items oi ON oi.order_id=o.id
                WHERE o.customer_id=?';
        $params = [$customerId];
        if ($fromDate !== null) {
            $sql .= ' AND o.created_at >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== null) {
            $sql .= ' AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $toDate;
        }
        $result = $this->selectOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    public function purchasedProductRows(
        int $customerId,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $limit = 10,
        int $offset = 0
    ): array {
        $sql = 'SELECT o.id AS order_id,o.created_at,oi.product_name,oi.quantity,oi.unit_price
                FROM checkout_orders o
                JOIN checkout_order_items oi ON oi.order_id=o.id
                WHERE o.customer_id=?';
        $params = [$customerId];
        if ($fromDate !== null) {
            $sql .= ' AND o.created_at >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== null) {
            $sql .= ' AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $toDate;
        }
        $sql .= ' ORDER BY o.created_at DESC,o.id DESC,oi.id
                  LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        return $this->selectAll($sql, $params);
    }

    public function productPurchases(
        int $customerId,
        string $search,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $sql = 'SELECT o.id AS order_id,o.created_at,oi.product_name,
                       SUM(oi.quantity) AS quantity,oi.unit_price
                FROM checkout_orders o
                JOIN checkout_order_items oi ON oi.order_id=o.id
                WHERE o.customer_id=? AND oi.product_name=?';
        $params = [$customerId, $search];
        if ($fromDate !== null) {
            $sql .= ' AND o.created_at >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== null) {
            $sql .= ' AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $toDate;
        }
        $sql .= ' GROUP BY o.id,o.created_at,oi.product_name,oi.unit_price
                  ORDER BY o.created_at ASC,o.id ASC,oi.product_name';
        return $this->selectAll($sql, $params);
    }

    public function purchasedProductNames(
        int $customerId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $sql = 'SELECT DISTINCT oi.product_name
                FROM checkout_orders o
                JOIN checkout_order_items oi ON oi.order_id=o.id
                WHERE o.customer_id=?';
        $params = [$customerId];
        if ($fromDate !== null) {
            $sql .= ' AND o.created_at >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== null) {
            $sql .= ' AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $toDate;
        }
        return $this->selectAll($sql . ' ORDER BY oi.product_name', $params);
    }
}
