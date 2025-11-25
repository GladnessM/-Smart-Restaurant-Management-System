-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 08:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `restaurant`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `points` int(11) DEFAULT 0,
  `table_number` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `password_hash`, `points`, `table_number`, `created_at`) VALUES
(1, 'Gladness', 'glad@gmail.com', '$2y$10$GP0g7L.yuG/Iz2NkCs6uT.jJGOX2cFlDBj75EMVgXLPiy20okHclO', 0, NULL, '2025-11-24 20:53:58'),
(2, 'lucy', 'l@gmail.com', '$2y$10$zvt51KQ3mQ29wg2rujoNgerEpVIkz.7PZ4UGcIndQvGzLuHmRq.SW', 0, NULL, '2025-11-24 21:03:10'),
(3, 'glad', 'g@gmail.com', '$2y$10$c6iVji0rSUkvvJcyGy8x3.dZEaQIyyHUdAnAeifqCm03wdT02IhZG', 0, '4', '2025-11-24 21:42:47'),
(4, 'user', 'test@gmail.com', '$2y$10$2HYkvZ.y3exaCgUrHBAuOud9vcEFepHqKzcFiFkuWn4N1/i/cTgPm', 10, '2', '2025-11-24 21:50:49'),
(5, 'New user', 'new@gmail.com', '$2y$10$xCi9QwIqp6eH.KpaBCO9mO5.l6PG6sQJAXRHjl8tIerD6DYH/Ruva', 10, '6', '2025-11-25 19:01:16'),
(6, 'lucy', 'lucy@gmail.com', '$2y$10$8NuIY8xM3DIMMiWTsHI8Pu.qnXQgnW4KRB0npHvtawCe0VXisS9QK', 10, '5', '2025-11-25 19:05:21');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('main','drinks','desserts') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `category`, `price`, `img_url`, `created_at`) VALUES
(1, 'Chips', 'main', 2000.00, 'img/1764074053_WhatsApp Image 2023-04-05 at 11.48.23.jpg', '2025-11-25 12:34:13'),
(2, 'Beaf steak', 'main', 3500.00, 'img/1764077668_steak.jpg', '2025-11-25 13:34:28'),
(3, 'Berry cake', 'desserts', 500.00, 'img/1764078173_berry cake.jpg', '2025-11-25 13:42:53'),
(4, 'chicken', 'main', 1500.00, 'img/1764078199_chicken.jpg', '2025-11-25 13:43:19'),
(5, 'French fries', 'main', 2000.00, 'img/1764078223_french fries.jpg', '2025-11-25 13:43:43'),
(6, 'Coffee', 'drinks', 500.00, 'img/1764078245_coffee.jpg', '2025-11-25 13:44:05'),
(7, 'Juice', 'drinks', 200.00, 'img/1764078260_juice.jpg', '2025-11-25 13:44:20'),
(8, 'Coke', 'drinks', 250.00, 'img/1764078282_coke.jpg', '2025-11-25 13:44:42'),
(9, 'Pasta', 'main', 2000.00, 'img/1764078302_pasta.jpg', '2025-11-25 13:45:02'),
(10, 'Chocolate Cake', 'desserts', 1000.00, 'img/1764078333_chocolate cake.jpg', '2025-11-25 13:45:33'),
(11, 'Water', 'drinks', 60.00, '1764079833_juice.jpg', '2025-11-25 14:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `table_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(100) NOT NULL,
  `status` enum('pending','preparing','served','paid','ready') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `target_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `table_id`, `total`, `payment_method`, `status`, `created_at`, `target_time`) VALUES
(15, NULL, 1, 5500.00, 'cash', 'paid', '2025-11-25 15:18:06', '2025-11-25 19:33:49'),
(16, NULL, 1, 4000.00, 'cash', 'paid', '2025-11-25 15:47:34', '2025-11-25 22:30:32'),
(22, NULL, 1, 3750.00, 'cash', 'paid', '2025-11-25 16:41:16', '2025-11-25 19:57:24'),
(23, 4, 1, 3500.00, 'cash', 'paid', '2025-11-25 18:01:15', '2025-11-25 22:30:26'),
(24, NULL, 1, 2250.00, 'cash', 'paid', '2025-11-25 18:13:32', '2025-11-25 21:28:59'),
(25, 6, 6, 3560.00, 'cash', 'paid', '2025-11-25 19:08:12', '2025-11-25 22:23:51'),
(26, 5, 4, 2750.00, 'card', 'paid', '2025-11-25 19:46:04', '2025-11-25 23:06:40'),
(27, NULL, 6, 1500.00, 'mpesa', 'paid', '2025-11-25 19:59:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `item_status` enum('pending','preparing','ready','served') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price`, `item_status`) VALUES
(20, 21, 9, 1, 2000.00, 'served'),
(21, 21, 11, 1, 60.00, 'served'),
(22, 22, 2, 1, 3500.00, 'served'),
(23, 22, 8, 1, 250.00, 'served'),
(24, 23, 4, 1, 1500.00, 'served'),
(25, 23, 9, 1, 2000.00, 'served'),
(26, 24, 9, 1, 2000.00, 'served'),
(27, 24, 8, 1, 250.00, 'served'),
(28, 25, 4, 1, 1500.00, 'served'),
(29, 25, 11, 1, 60.00, 'served'),
(30, 25, 5, 1, 2000.00, 'served'),
(31, 26, 9, 1, 2000.00, 'served'),
(32, 26, 8, 1, 250.00, 'served'),
(33, 26, 3, 1, 500.00, 'served'),
(34, 27, 6, 3, 500.00, 'served');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('Manager','Waiter','Chef','Bartender','Cashier') NOT NULL,
  `login_code` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `full_name`, `role`, `login_code`, `active`, `created_at`) VALUES
(1, 'Mike', 'Waiter', 1704, 1, '2025-11-25 12:31:01'),
(2, 'Sarah', 'Waiter', 9696, 1, '2025-11-25 12:31:38'),
(3, 'Peter', 'Chef', 1084, 1, '2025-11-25 12:31:49'),
(4, 'Lucy', 'Chef', 9032, 1, '2025-11-25 12:31:58'),
(5, 'Dee', 'Bartender', 7003, 1, '2025-11-25 12:32:15'),
(6, 'Phil', 'Bartender', 6534, 1, '2025-11-25 12:32:23'),
(7, 'Sofi', 'Cashier', 8433, 1, '2025-11-25 12:32:32'),
(8, 'Patience', 'Waiter', 1240, 1, '2025-11-25 12:32:48'),
(9, 'Gabby', 'Waiter', 3686, 1, '2025-11-25 14:22:10'),
(10, 'Naomi', 'Waiter', 5258, 1, '2025-11-25 14:34:19'),
(11, 'Gladness', 'Manager', 1234, 1, '2025-11-25 18:46:01');

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_number` int(11) NOT NULL,
  `seats` int(11) NOT NULL DEFAULT 4,
  `status` enum('free','occupied') NOT NULL DEFAULT 'free',
  `waiter_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `seats`, `status`, `waiter_id`, `created_at`) VALUES
(1, 1, 2, 'free', 1, '2025-11-25 12:05:42'),
(2, 2, 3, 'free', 2, '2025-11-25 12:05:58'),
(3, 3, 4, 'free', 8, '2025-11-25 12:33:25'),
(5, 5, 6, 'free', 9, '2025-11-25 12:46:39'),
(6, 6, 3, 'free', 1, '2025-11-25 14:07:50'),
(7, 7, 2, 'free', 2, '2025-11-25 14:15:40'),
(8, 8, 2, 'free', 9, '2025-11-25 14:37:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login_code` (`login_code`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`),
  ADD KEY `tables_ibfk_1` (`waiter_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);

--
-- Constraints for table `tables`
--
ALTER TABLE `tables`
  ADD CONSTRAINT `tables_ibfk_1` FOREIGN KEY (`waiter_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
