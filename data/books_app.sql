-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 25, 2026 at 02:47 AM
-- Server version: 11.8.5-MariaDB-log
-- PHP Version: 8.5.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `books_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `author_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `bio` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`author_id`, `name`, `bio`) VALUES
(1, 'Emily Carter', 'Mystery writer'),
(2, 'Daniel Lee', 'Tech writer'),
(3, 'Sophia Brown', 'Romance author'),
(4, 'Michael Stone', 'Sci-fi expert'),
(5, 'Laura Green', 'Chef'),
(6, 'Chris Adams', 'Historian'),
(7, 'Nina White', 'Poet'),
(8, 'Tom Black', 'Business writer'),
(9, 'Sarah Blue', 'Health coach'),
(10, 'Kevin Hart', 'Educator');

-- --------------------------------------------------------

--
-- Table structure for table `author_books`
--

CREATE TABLE `author_books` (
  `author_books_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `author_books`
--

INSERT INTO `author_books` (`author_books_id`, `author_id`, `book_id`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 3),
(4, 4, 4),
(5, 5, 5),
(6, 2, 6),
(7, 1, 7),
(8, 4, 8),
(9, 6, 9),
(10, 10, 10),
(11, 8, 14),
(12, 7, 15),
(13, 9, 13),
(14, 6, 18),
(15, 4, 12),
(16, 2, 17),
(17, 9, 19),
(18, 10, 20),
(19, 3, 11),
(20, 5, 16);

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `ISBN` char(13) NOT NULL,
  `summary` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `categories` varchar(50) NOT NULL,
  `status` varchar(25) NOT NULL,
  `stock` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `ISBN`, `summary`, `price`, `categories`, `status`, `stock`) VALUES
(1, 'The Silent Forest', '9780000000001', 'Mystery story', 19.99, 'Mystery', 'available', 50),
(2, 'Future Code', '9780000000002', 'AI & tech', 29.99, 'Tech', 'available', 30),
(3, 'Love & Time', '9780000000003', 'Romance', 14.99, 'Romance', 'available', 40),
(4, 'The Last War', '9780000000004', 'Sci-fi war', 24.99, 'Sci-Fi', 'available', 25),
(5, 'Cooking Easy', '9780000000005', 'Recipes', 18.50, 'Cooking', 'available', 60),
(6, 'Mindset Mastery', '9780000000006', 'Self-help', 21.00, 'Self-help', 'available', 45),
(7, 'Dark Nights', '9780000000007', 'Thriller', 22.99, 'Thriller', 'available', 20),
(8, 'Ocean Depths', '9780000000008', 'Adventure', 17.99, 'Adventure', 'available', 35),
(9, 'History of Rome', '9780000000009', 'History book', 26.99, 'History', 'available', 15),
(10, 'Physics Basics', '9780000000010', 'Education', 30.00, 'Education', 'available', 10),
(11, 'Art of War II', '9780000000011', 'Strategy', 19.00, 'Strategy', 'available', 22),
(12, 'Galaxy Riders', '9780000000012', 'Sci-fi', 27.50, 'Sci-Fi', 'available', 18),
(13, 'Healthy Living', '9780000000013', 'Health tips', 16.75, 'Health', 'available', 40),
(14, 'Startup Guide', '9780000000014', 'Business', 23.00, 'Business', 'available', 28),
(15, 'Poetry Nights', '9780000000015', 'Poems', 12.99, 'Poetry', 'available', 33),
(16, 'Wild Survival', '9780000000016', 'Survival', 20.50, 'Adventure', 'available', 19),
(17, 'Coding 101', '9780000000017', 'Programming', 35.00, 'Tech', 'available', 12),
(18, 'Ancient Egypt', '9780000000018', 'History', 28.99, 'History', 'available', 14),
(19, 'Space Exploration', '9780000000019', 'Science', 31.99, 'Science', 'available', 16),
(20, 'Psychology Intro', '9780000000020', 'Psychology', 22.00, 'Education', 'available', 21);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `last_modified_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_price`, `last_modified_date`) VALUES
(1, 1, 39.98, '2024-04-01 10:00:00'),
(2, 2, 29.99, '2024-04-01 11:00:00'),
(3, 3, 14.99, '2024-04-02 09:00:00'),
(4, 4, 44.98, '2024-04-02 10:30:00'),
(5, 5, 18.50, '2024-04-03 12:00:00'),
(6, 6, 21.00, '2024-04-03 13:00:00'),
(7, 7, 22.99, '2024-04-04 14:00:00'),
(8, 8, 17.99, '2024-04-04 15:00:00'),
(9, 9, 26.99, '2024-04-05 16:00:00'),
(10, 10, 30.00, '2024-04-05 17:00:00'),
(11, 11, 19.00, '2024-04-06 10:00:00'),
(12, 12, 27.50, '2024-04-06 11:00:00'),
(13, 13, 16.75, '2024-04-07 12:00:00'),
(14, 14, 23.00, '2024-04-07 13:00:00'),
(15, 15, 12.99, '2024-04-08 14:00:00'),
(16, 16, 20.50, '2024-04-08 15:00:00'),
(17, 17, 35.00, '2024-04-09 16:00:00'),
(18, 18, 28.99, '2024-04-09 17:00:00'),
(19, 19, 31.99, '2024-04-10 18:00:00'),
(20, 20, 22.00, '2024-04-10 19:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_items_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_items_id`, `order_id`, `book_id`, `quantity`, `purchase_price`) VALUES
(1, 1, 1, 2, 19.99),
(2, 2, 2, 1, 29.99),
(3, 3, 3, 1, 14.99),
(4, 4, 4, 2, 24.99),
(5, 5, 5, 1, 18.50),
(6, 6, 6, 1, 21.00),
(7, 7, 7, 1, 22.99),
(8, 8, 8, 1, 17.99),
(9, 9, 9, 1, 26.99),
(10, 10, 10, 1, 30.00),
(11, 11, 11, 1, 19.00),
(12, 12, 12, 1, 27.50),
(13, 13, 13, 1, 16.75),
(14, 14, 14, 1, 23.00),
(15, 15, 15, 1, 12.99),
(16, 16, 16, 1, 20.50),
(17, 17, 17, 1, 35.00),
(18, 18, 18, 1, 28.99),
(19, 19, 19, 1, 31.99),
(20, 20, 20, 1, 22.00),
(21, 1, 2, 1, 29.99),
(22, 2, 3, 2, 14.99),
(23, 3, 4, 1, 24.99),
(24, 4, 5, 2, 18.50),
(25, 5, 6, 1, 21.00),
(26, 6, 7, 2, 22.99),
(27, 7, 8, 1, 17.99),
(28, 8, 9, 2, 26.99),
(29, 9, 10, 1, 30.00),
(30, 10, 1, 1, 19.99);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `rating` char(2) NOT NULL,
  `text` varchar(255) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `book_id`, `rating`, `text`, `date`) VALUES
(1, 1, 1, '5', 'Amazing!', '2024-04-01 00:00:00'),
(2, 2, 2, '4', 'Great read', '2024-04-02 00:00:00'),
(3, 3, 3, '3', 'Good', '2024-04-03 00:00:00'),
(4, 4, 4, '5', 'Loved it', '2024-04-04 00:00:00'),
(5, 5, 5, '4', 'Very helpful', '2024-04-05 00:00:00'),
(6, 6, 6, '5', 'Life changing', '2024-04-06 00:00:00'),
(7, 7, 7, '4', 'Thrilling', '2024-04-07 00:00:00'),
(8, 8, 8, '3', 'Nice', '2024-04-08 00:00:00'),
(9, 9, 9, '5', 'Informative', '2024-04-09 00:00:00'),
(10, 10, 10, '4', 'Educational', '2024-04-10 00:00:00'),
(11, 11, 11, '3', 'Okay', '2024-04-11 00:00:00'),
(12, 12, 12, '5', 'Fantastic', '2024-04-12 00:00:00'),
(13, 13, 13, '4', 'Useful', '2024-04-13 00:00:00'),
(14, 14, 14, '5', 'Excellent', '2024-04-14 00:00:00'),
(15, 15, 15, '3', 'Decent', '2024-04-15 00:00:00'),
(16, 16, 16, '4', 'Cool', '2024-04-16 00:00:00'),
(17, 17, 17, '5', 'Perfect', '2024-04-17 00:00:00'),
(18, 18, 18, '4', 'Nice book', '2024-04-18 00:00:00'),
(19, 19, 19, '5', 'Loved it', '2024-04-19 00:00:00'),
(20, 20, 20, '4', 'Very good', '2024-04-20 00:00:00'),
(21, 1, 5, '5', 'Great recipes', '2024-04-21 00:00:00'),
(22, 2, 6, '4', 'Helpful', '2024-04-22 00:00:00'),
(23, 3, 7, '3', 'Average', '2024-04-23 00:00:00'),
(24, 4, 8, '5', 'Awesome', '2024-04-24 00:00:00'),
(25, 5, 9, '4', 'Interesting', '2024-04-25 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(25) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(25) NOT NULL,
  `join_date` datetime NOT NULL DEFAULT current_timestamp(),
  `admin` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `join_date`, `admin`) VALUES
(1, 'john_doe', 'john@example.com', 'pass', '2024-01-01 10:00:00', 0),
(2, 'jane_smith', 'jane@example.com', 'pass', '2024-01-05 11:00:00', 1),
(3, 'alex99', 'alex@example.com', 'pass', '2024-01-10 09:00:00', 0),
(4, 'maria_l', 'maria@example.com', 'pass', '2024-01-15 14:00:00', 0),
(5, 'kevinb', 'kevin@example.com', 'pass', '2024-01-20 18:00:00', 0),
(6, 'lucas_w', 'lucas@example.com', 'pass', '2024-01-25 12:00:00', 0),
(7, 'emma_k', 'emma@example.com', 'pass', '2024-02-01 13:00:00', 0),
(8, 'oliver_p', 'oliver@example.com', 'pass', '2024-02-05 15:00:00', 0),
(9, 'ava_m', 'ava@example.com', 'pass', '2024-02-10 16:00:00', 0),
(10, 'liam_t', 'liam@example.com', 'pass', '2024-02-15 17:00:00', 0),
(11, 'noah_r', 'noah@example.com', 'pass', '2024-02-20 10:00:00', 0),
(12, 'mia_s', 'mia@example.com', 'pass', '2024-02-25 11:30:00', 0),
(13, 'ethan_d', 'ethan@example.com', 'pass', '2024-03-01 09:20:00', 0),
(14, 'isabella_f', 'isa@example.com', 'pass', '2024-03-05 08:45:00', 0),
(15, 'logan_g', 'logan@example.com', 'pass', '2024-03-10 12:10:00', 0),
(16, 'amelia_h', 'amelia@example.com', 'pass', '2024-03-15 14:25:00', 0),
(17, 'james_k', 'james@example.com', 'pass', '2024-03-20 16:40:00', 0),
(18, 'charlotte_l', 'charlotte@example.com', 'pass', '2024-03-25 18:55:00', 0),
(19, 'ben_m', 'ben@example.com', 'pass', '2024-03-30 19:10:00', 0),
(20, 'lucy_n', 'lucy@example.com', 'pass', '2024-04-01 20:20:00', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`author_id`);

--
-- Indexes for table `author_books`
--
ALTER TABLE `author_books`
  ADD PRIMARY KEY (`author_books_id`),
  ADD KEY `FK_authors_books_authors_autthor_books_id` (`author_id`),
  ADD KEY `FK_authors_books_books_book_id` (`book_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `ISBN` (`ISBN`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `FK_orders_users_user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_items_id`),
  ADD KEY `FK_order_items_orders_order_id` (`order_id`),
  ADD KEY `FK_order_items_books_book_id` (`book_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `FK_reviews_users_user_id` (`user_id`),
  ADD KEY `FK_reviews_books_book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `author_books`
--
ALTER TABLE `author_books`
  MODIFY `author_books_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_items_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `author_books`
--
ALTER TABLE `author_books`
  ADD CONSTRAINT `FK_authors_books_authors_autthor_books_id` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`),
  ADD CONSTRAINT `FK_authors_books_books_book_id` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `FK_orders_users_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `FK_order_items_books_book_id` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `FK_order_items_orders_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `FK_reviews_books_book_id` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `FK_reviews_users_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
