<?php

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;

class AddingUserModel extends BaseModel
{
    public function __construct(PDOService $db_service)
    {
        parent::__construct($db_service);
    }

    // Insert User
    public function customerInsertion(array $data): int
    {
        $sql = "
            INSERT INTO customers
            (
                customer_name,
                address,
                telephone_number,
                email_address
            )
            VALUES
            (
                :customer_name,
                :address,
                :telephone_number,
                :email_address
            )
        ";

        $this->execute($sql, [
            ':customer_name' => $data['customer_name'],
            ':address' => $data['address'],
            ':telephone_number' => $data['telephone_number'],
            ':email_address' => $data['email_address'],
        ]);

        return (int) $this->lastInsertId();
    }

    // Check whether an email already exists
    public function emailExists(string $email): bool
    {
        $sql = "
            SELECT *
            FROM customers
            WHERE email_address = :email_address
            LIMIT 1
        ";

        return $this->count($sql, [
            ':email_address' => $email
        ]) > 0;
    }

    // Check whether a customer name already exists
    public function nameExists(string $customerName): bool
    {
        $sql = "
            SELECT *
            FROM customers
            WHERE customer_name = :customer_name
            LIMIT 1
        ";

        return $this->count($sql, [
            ':customer_name' => $customerName
        ]) > 0;
    }

    // Check whether a phone number already exists
    public function phoneNumberExists(string $phoneNumber): bool
    {
        $sql = "
            SELECT *
            FROM customers
            WHERE telephone_number = :telephone_number
            LIMIT 1
        ";

        return $this->count($sql, [
            ':telephone_number' => $phoneNumber
        ]) > 0;
    }
}