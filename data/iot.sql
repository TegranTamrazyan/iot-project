-- phpMyAdmin SQL Dump
-- version 5.2.1deb1+deb12u1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 21, 2026 at 04:12 AM
-- Server version: 10.11.18-MariaDB-0+deb12u1
-- PHP Version: 8.2.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iot`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE DATABASE IF NOT EXISTS iot;
USE iot;

CREATE TABLE IF NOT EXISTS customers (
  customer_id int(11) NOT NULL,
  customer_name varchar(100) NOT NULL,
  address varchar(100) NOT NULL,
  telephone_number varchar(100) NOT NULL,
  email_address varchar(100) NOT NULL,
  password_hash varchar(255) NOT NULL,
  membership_code varchar(6) NOT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS receipts (
  receipt_id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  receipt_date datetime NOT NULL,
  total_amount decimal(10,2) NOT NULL,
  PRIMARY KEY (receipt_id),
  FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS receipt_items (
  receipt_item_id int(11) NOT NULL AUTO_INCREMENT,
  receipt_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  quantity int(11) NOT NULL,
  unit_price decimal(10,2) NOT NULL,
  subtotal decimal(10,2) NOT NULL,
  PRIMARY KEY (receipt_item_id),
  FOREIGN KEY (receipt_id) REFERENCES receipts(receipt_id),
  FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS sensor_readings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fridge VARCHAR(10) NOT NULL,
    temperature DECIMAL(5,2) NOT NULL,
    humidity DECIMAL(5,2) NOT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fridge_time (fridge, recorded_at)
);

CREATE TABLE IF NOT EXISTS thresholds (
    fridge VARCHAR(10) NOT NULL PRIMARY KEY,
    max_temperature DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO thresholds (fridge, max_temperature)
VALUES ('Frig1', 5.00), ('Frig2', 5.00)
ON DUPLICATE KEY UPDATE fridge = VALUES(fridge);

CREATE TABLE IF NOT EXISTS fan_state (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    status ENUM('ON','OFF') NOT NULL DEFAULT 'OFF',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

--
-- Dumping data for table `customers`
--

-- INSERT INTO `customers` (`customer_id`, `customer_name`, `address`, `telephone_number`, `email_address`) VALUES
-- (1, 'Tegran', '2222', '1231231234', 'teg@gmail.com'),
-- (3, 'TegranT', '122', '1112223333', 'teg@gmail.com2');

INSERT INTO fan_state (id, status) VALUES (1, 'OFF')
ON DUPLICATE KEY UPDATE id = VALUES(id);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `unique_customers_customer_name` (`customer_name`),
  ADD UNIQUE KEY `unique_customers_telephonenumber` (`telephone_number`),
  ADD UNIQUE KEY `unique_customers_emailaddress` (`email_address`);
  ADD UNIQUE KEY `unique_customers_membership_code` (`membership_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

