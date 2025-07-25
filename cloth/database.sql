-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2025 at 04:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clothing_shop_management`
--
CREATE DATABASE IF NOT EXISTS `clothing_shop_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `clothing_shop_management`;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--
-- Creation: Jul 22, 2025 at 08:16 AM
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `categories`:
--

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `created_at`) VALUES
(6, 'Player Jerseys', 'Professional football team jerseys', '2025-07-22 11:27:32'),
(7, 'Fan Jerseys', 'NBA and basketball team jerseys', '2025-07-22 11:27:32'),
(8, 'Caps', 'Team caps and hats', '2025-07-22 11:27:32'),
(9, 'Kids Jerseys', 'Sports training shorts', '2025-07-22 11:27:32'),
(10, 'Vintage Jerseys', 'Classic and retro jerseys', '2025-07-22 11:27:32');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--
-- Creation: Jul 24, 2025 at 05:57 AM
-- Last update: Jul 25, 2025 at 12:28 PM
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `size` varchar(10) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `products`:
--   `category_id`
--       `categories` -> `category_id`
--   `supplier_id`
--       `suppliers` -> `supplier_id`
--

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `category_id`, `size`, `color`, `image_path`, `brand`, `price`, `stock_quantity`, `supplier_id`, `created_at`) VALUES
(13, 'AC MILLAN HOME PLAYER', 6, 'S M L XL 2', 'RED BLACK', NULL, 'PUMA', 17000.00, 36, 1, '2025-07-22 14:11:17'),
(14, 'BARCA LEFT FANS', 7, 'S M L XL 2', 'PURPLE', NULL, 'NIKE', 20000.00, 24, 3, '2025-07-22 14:21:05'),
(15, 'BRASIL VINTAGE', 10, 'S M L XL 2', 'YELLOW', NULL, 'NIKE', 20000.00, 23, 2, '2025-07-22 14:28:05'),
(17, 'MAN CITY HOME FANS', 7, 'S M L XL 2', 'BLUE', NULL, 'PUMA', 17000.00, 34, 3, '2025-07-22 15:16:39'),
(18, 'MAN CITY AWAY FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'PUMA', 17000.00, 29, 3, '2025-07-22 15:17:44'),
(19, 'REAL MADRID AWAY PLAYER', 6, 'S M L XL 2', 'BLUE', NULL, 'PUMA', 17000.00, 2, 3, '2025-07-22 15:18:53'),
(20, 'BAYERN MUNCHEN HOME FANS', 7, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 17000.00, 4, 3, '2025-07-22 15:19:35'),
(21, 'BAYERN MUNCHEN AWAY FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 38, 3, '2025-07-22 15:19:59'),
(22, 'BAYERN MUNCHEN AWAY PLAYER', 6, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 26, 3, '2025-07-22 15:20:38'),
(23, 'ARSENAL HOME PLAYER', 6, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 17000.00, 19, 3, '2025-07-22 15:21:30'),
(24, 'ARSENAL HOME FANS', 7, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 17000.00, 16, 3, '2025-07-22 15:21:51'),
(25, 'SANTOS HOME FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'UMBRO', 17000.00, 0, 3, '2025-07-22 15:22:36'),
(26, 'TOTTENHAM HOTSPURS HOME PLAYER', 6, 'S M L XL 2', 'WHITE', NULL, 'NIKE', 17000.00, 17, 3, '2025-07-22 15:23:35'),
(27, 'TOTTENHAM HOTSPURS AWAY PLAYER', 6, 'S M L XL 2', 'BLACK', NULL, 'NIKE', 17000.00, 0, 3, '2025-07-22 15:24:51'),
(28, 'BARCELONA HOME PLAYER', 6, 'S M L XL 2', 'PURPLE', NULL, 'NIKE', 17000.00, 20, 3, '2025-07-22 15:25:40'),
(29, 'BARCELONA HOME FANS', 7, 'S M L XL 2', 'PURPLE', NULL, 'NIKE', 17000.00, 12, 3, '2025-07-22 15:26:12'),
(30, 'AC MILLAN AWAY PLAYER', 6, 'S M L XL 2', 'WHITE', NULL, 'PUMA', 17000.00, 8, 3, '2025-07-22 15:27:36'),
(31, 'BRASIL HOME FANS', 7, 'S M L XL 2', 'YELLOW', NULL, 'NIKE', 17000.00, 0, 3, '2025-07-22 15:33:25'),
(32, 'CHELSEA HOME FANS', 7, 'S M L XL 2', 'BLUE', NULL, 'NIKE', 17000.00, 18, 3, '2025-07-22 15:34:57'),
(33, 'CHELSEA HOME PLAYER', 6, 'S M L XL 2', 'BLUE', NULL, 'NIKE', 17000.00, 7, 3, '2025-07-22 15:35:16'),
(34, 'CHELSEA AWAY PLAYER', 6, 'S M L XL 2', 'CREAM', NULL, 'NIKE', 17000.00, 0, 3, '2025-07-22 15:35:48'),
(35, 'CHELSEA AWAY FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'NIKE', 17000.00, 54, 3, '2025-07-22 15:36:16'),
(36, 'MAN U HOME FANS', 7, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 17000.00, 49, 3, '2025-07-22 15:36:52'),
(37, 'MAN U HOME PLAYER', 6, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 17000.00, 11, 3, '2025-07-22 15:37:13'),
(38, 'MAN U AWAY PLAYER', 6, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 2, 3, '2025-07-22 15:38:09'),
(39, 'MAN U AWAY FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 0, 3, '2025-07-22 15:38:28'),
(40, 'LIVERPOOL HOME FANS', 7, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 17000.00, 15, 3, '2025-07-22 15:39:15'),
(41, 'LIVERPOOL HOME PLAYER', 6, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 17000.00, 14, 3, '2025-07-22 15:39:46'),
(42, 'LIVERPOOL AWAY FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 20, 3, '2025-07-22 15:40:56'),
(43, 'LIVERPOOL AWAY PLAYER', 6, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 0, 3, '2025-07-22 15:41:27'),
(44, 'MAN U BLACK FANS', 7, 'S M L XL 2', 'BLACK', NULL, 'ADIDAS', 17000.00, 20, 3, '2025-07-22 15:42:03'),
(45, 'JUVENTUS HOME FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 35, 3, '2025-07-22 15:42:50'),
(46, 'JUVENTUS HOME PLAYER', 6, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 18, 3, '2025-07-22 15:43:08'),
(47, 'JUVENTUS AWAY FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 0, 3, '2025-07-22 15:43:31'),
(48, 'JUVENTUS AWAY PLAYER', 6, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 0, 3, '2025-07-22 15:43:48'),
(49, 'REAL MADRID HOME FANS', 7, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 68, 3, '2025-07-22 15:44:46'),
(50, 'REAL MADRID HOME PLAYER', 6, 'S M L XL 2', 'WHITE', NULL, 'ADIDAS', 17000.00, 1, 3, '2025-07-22 15:45:16'),
(51, 'INTERMILLAN HOME FANS', 7, 'S M L XL 2', 'BLUE', NULL, 'ADIDAS', 17000.00, 20, 3, '2025-07-22 15:46:00'),
(52, 'SANTOS BLUE VINTAGE', 10, 'S M L XL 2', 'BLUE', NULL, 'NIKE', 20000.00, 10, 3, '2025-07-22 15:48:10'),
(53, 'SANTOS BLACK VINTAGE', 10, 'S M L XL 2', 'BLACK', NULL, 'NIKE', 20000.00, 6, 3, '2025-07-22 15:48:32'),
(54, 'BARCELONA CACTUS TRAVIS SCOTT', 10, 'S M L XL 2', 'BLUE', NULL, 'NIKE', 20000.00, 123, 1, '2025-07-22 15:52:10'),
(55, 'PSG HOME FANS', 7, 'S M L XL 2', 'BLUE', NULL, 'NIKE', 17000.00, 45, 1, '2025-07-22 15:53:20'),
(56, 'PSG HOME PLAYER', 6, 'S M L XL 2', 'BLUE', NULL, 'NIKE', 17000.00, 0, 3, '2025-07-22 15:53:51'),
(57, 'REAL MADRID HOME WHITE KIDS', 9, '16 18 20 2', 'WHITE', NULL, 'ADIDAS', 15000.00, 0, 1, '2025-07-22 15:57:36'),
(58, 'INTERMILLAN HOME WATOTO', 9, '16 18 20 2', 'BLUE', NULL, 'NIKE', 15000.00, 40, 2, '2025-07-22 15:59:12'),
(59, 'BRASIL YA YESU NJANO WATOTO', 9, '16 18 20 2', 'YELLOW', NULL, 'NIKE', 15000.00, 12, 1, '2025-07-22 16:00:26'),
(60, 'BRASIL YA YESU NYEUSI WATOTO', 9, '16 18 20 2', 'BLACK', NULL, 'NIKE', 15000.00, 11, 3, '2025-07-22 16:00:55'),
(61, 'SANTOS HOME PLAYER', 6, '16 18 20 2', 'WHITE', NULL, 'UMBRO', 17000.00, 20, 3, '2025-07-24 09:45:28'),
(62, 'SANTOS HOME FANS', 7, 'S M L XL X', 'WHITE', NULL, 'UMBRO', 17000.00, 10, 3, '2025-07-24 09:52:40'),
(63, 'MAN CITY PLAYER', 6, 'S M L XL X', 'WHITE', NULL, 'PUMA', 17000.00, 5, 3, '2025-07-24 09:56:16'),
(64, 'SANTOS WHITE VINTAGE', 10, 'S M L XL X', 'WHITE', NULL, 'UMBRO', 20000.00, 7, 3, '2025-07-24 10:04:08'),
(65, 'INTERMILLAN HOME PLAYER', 6, 'S M L XL X', 'BLUE', NULL, 'NIKE', 17000.00, 20, 3, '2025-07-24 10:14:51'),
(66, 'PSG 24/25', 7, 'S M L XL X', 'BLUE', NULL, 'NIKE', 17000.00, 4, 3, '2025-07-24 10:17:29'),
(67, 'PORTUGAL AWAY WATOTO', 9, 'S M L XL 2', 'WHITE', NULL, 'PUMA', 15000.00, 6, 3, '2025-07-24 10:25:09'),
(68, 'INTERMILLAN AWAY WATOTO', 9, 'S M L XL 2', 'WHITE', NULL, 'PUMA', 15000.00, 4, 3, '2025-07-24 10:27:31'),
(69, 'INTERMIAMI HOME WATOTO', 9, 'S M L XL 2', 'BLUE', NULL, 'PUMA', 15000.00, 5, 3, '2025-07-24 10:28:23'),
(70, 'BRASIL YA NJANO WATOTO', 9, '16 18 20 2', 'YELLOW', NULL, 'NIKE', 15000.00, 15, 3, '2025-07-24 10:31:14'),
(71, 'CAM MKUBWA', 7, 'S M L XL 2', 'BLACK', NULL, 'ADIDAS', 17000.00, 8, 3, '2025-07-24 10:32:02'),
(72, 'CAM MDOGO', 9, 'S M L XL 2', 'BLACK', NULL, 'ADIDAS', 15000.00, 5, 3, '2025-07-24 10:32:38'),
(73, 'INTERMILLAN HOME PINK WATOTO', 9, '16 18 20 2', 'PINK', NULL, 'NIKE', 15000.00, 3, 1, '2025-07-24 10:33:29'),
(74, 'MAN CITY NYEUPE MTOTO', 9, '16 18 20 2', 'WHITE', NULL, 'PUMA', 15000.00, 2, 3, '2025-07-24 10:34:16'),
(75, 'ARGENTINA MTOTO', 9, '16 18 20 2', 'WHITE', NULL, 'ADIDAS', 15000.00, 4, 3, '2025-07-24 10:34:59'),
(76, 'PSG WATOTO 24/25', 9, '16 18 20 2', 'WHITE', NULL, 'ADIDAS', 15000.00, 11, 3, '2025-07-24 10:37:23'),
(77, 'JUVENTUS WATOTO', 9, '16 18 20 2', 'WHITE', NULL, 'ADIDAS', 15000.00, 1, 3, '2025-07-24 10:38:06'),
(78, 'ATLETICO MADRID NYEKUNDU WATOTO', 9, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 15000.00, 9, 3, '2025-07-24 10:41:02'),
(79, 'BAYERN AWAY WATOTO', 9, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 15000.00, 5, 1, '2025-07-24 10:41:31'),
(80, 'CHELSEA HOME MTOTO', 9, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 15000.00, 25, 3, '2025-07-24 10:42:53'),
(81, 'BARCELONA HOME WATOTO', 9, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 15000.00, 3, 3, '2025-07-24 10:43:32'),
(82, 'BARCA LEFT MTOTO', 9, 'S M L XL 2', 'RED', NULL, 'NIKE', 15000.00, 2, 3, '2025-07-24 10:44:16'),
(83, 'REAL MADRID BLUE WATOTO', 9, 'S M L XL 2', 'BLUE', NULL, 'ADIDAS', 15000.00, 14, 3, '2025-07-24 10:45:17'),
(84, 'MAN U HOME WATOTO', 9, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 15000.00, 10, 3, '2025-07-24 10:47:21'),
(85, 'ARSENAL WATOTO 24/25', 9, 'S M L XL 2', 'RED', NULL, 'ADIDAS', 15000.00, 32, 3, '2025-07-24 10:48:13'),
(86, 'MAN U BLACK WATOTO', 9, 'S M L XL 2', 'BLACK', NULL, 'ADIDAS', 15000.00, 10, 3, '2025-07-24 10:56:40'),
(87, 'LIVERPOOL GREEN FANS', 7, 'S M L XL 2', 'GREEN', NULL, 'ADIDAS', 17000.00, 30, 3, '2025-07-24 10:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--
-- Creation: Jul 24, 2025 at 11:48 AM
-- Last update: Jul 25, 2025 at 12:28 PM
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `sale_status` enum('completed','pending','cancelled') DEFAULT 'completed',
  `winger_name` varchar(100) DEFAULT NULL,
  `winger_contact` varchar(20) DEFAULT NULL,
  `bond_item` varchar(200) DEFAULT NULL,
  `bond_value` decimal(10,2) DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'CASH',
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `sales`:
--   `product_id`
--       `products` -> `product_id`
--   `confirmed_by`
--       `users` -> `user_id`
--   `confirmed_by`
--       `users` -> `user_id`
--

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `product_id`, `quantity`, `total_price`, `sale_status`, `winger_name`, `winger_contact`, `bond_item`, `bond_value`, `expected_return_date`, `notes`, `confirmed_at`, `confirmed_by`, `payment_method`, `sale_date`) VALUES
(12, 80, 1, 15000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 10:52:06'),
(13, 49, 1, 17000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 11:13:47'),
(14, 35, 1, 17000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 11:30:01'),
(15, 40, 2, 34000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 11:30:53'),
(16, 36, 1, 17000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 11:43:22'),
(17, 41, 2, 34000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 12:35:10'),
(18, 54, 1, 20000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 12:53:58'),
(20, 30, 1, 17000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'LIPA NUMBER', '2025-07-24 13:05:02'),
(21, 15, 1, 20000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 13:17:42'),
(22, 23, 2, 34000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 14:39:47'),
(23, 37, 1, 17000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 14:44:40'),
(24, 32, 1, 17000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 14:45:15'),
(25, 85, 3, 45000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-24 14:45:55'),
(26, 80, 1, 15000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'LIPA NUMBER', '2025-07-24 14:46:15'),
(27, 32, 1, 17000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'LIPA NUMBER', '2025-07-24 15:37:46'),
(28, 30, 2, 34000.00, 'completed', 'mondinho', '+255123456788', 'phone', 30000.00, '2025-07-26', '', '2025-07-25 08:47:31', 1, 'CASH', '2025-07-25 08:36:08'),
(29, 20, 5, 90000.00, 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CASH', '2025-07-25 12:13:33'),
(30, 24, 2, 34000.00, 'completed', 'obama', '07676666666', 'simu', 50000.00, '2025-07-26', '', '2025-07-25 12:25:42', 1, 'CASH', '2025-07-25 12:17:59'),
(31, 70, 2, 34000.00, 'pending', 'obama', '07676666666', 'phone', 50000.00, '2025-07-26', '', NULL, NULL, 'CASH', '2025-07-25 12:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--
-- Creation: Jul 24, 2025 at 11:48 AM
-- Last update: Jul 25, 2025 at 12:28 PM
--

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `movement_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `movement_type` enum('in','out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `stock_movements`:
--   `product_id`
--       `products` -> `product_id`
--   `sale_id`
--       `sales` -> `sale_id`
--   `sale_id`
--       `sales` -> `sale_id`
--

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`movement_id`, `product_id`, `sale_id`, `movement_type`, `quantity`, `reason`, `created_at`) VALUES
(1, NULL, NULL, 'in', 45, 'New Purchase', '2025-07-22 14:00:22'),
(2, NULL, NULL, 'in', 10, 'New Purchase', '2025-07-22 14:24:52'),
(3, 15, NULL, 'in', 35, 'Manual adjustment', '2025-07-22 14:28:48'),
(4, 15, NULL, 'in', 12, 'New Purchase', '2025-07-22 14:29:48'),
(5, 51, NULL, 'in', 20, 'New Purchase', '2025-07-24 09:22:13'),
(6, 49, NULL, 'in', 18, 'New Purchase', '2025-07-24 09:27:27'),
(7, 50, NULL, 'in', 1, 'New Purchase', '2025-07-24 09:28:00'),
(8, 32, NULL, 'in', 15, 'New Purchase', '2025-07-24 09:28:24'),
(9, 32, NULL, 'in', 1, 'Manual adjustment', '2025-07-24 09:29:10'),
(10, 23, NULL, 'in', 23, 'New Purchase', '2025-07-24 09:33:19'),
(11, 45, NULL, 'in', 10, 'New Purchase', '2025-07-24 09:33:57'),
(12, 28, NULL, 'in', 10, 'New Purchase', '2025-07-24 09:34:20'),
(13, 41, NULL, 'in', 16, 'New Purchase', '2025-07-24 09:35:51'),
(14, 21, NULL, 'in', 23, 'New Purchase', '2025-07-24 09:39:16'),
(15, 53, NULL, 'in', 6, 'New Purchase', '2025-07-24 09:40:18'),
(16, 33, NULL, 'in', 7, 'New Purchase', '2025-07-24 09:41:14'),
(17, 22, NULL, 'in', 26, 'New Purchase', '2025-07-24 09:41:33'),
(18, 20, NULL, 'in', 9, 'New Purchase', '2025-07-24 09:42:59'),
(19, 45, NULL, 'in', 14, 'Manual adjustment', '2025-07-24 09:47:20'),
(20, 26, NULL, 'in', 17, 'Manual adjustment', '2025-07-24 09:49:58'),
(21, 21, NULL, 'in', 15, 'Manual adjustment', '2025-07-24 09:50:33'),
(22, 29, NULL, 'in', 12, 'New Purchase', '2025-07-24 09:51:06'),
(23, 23, NULL, 'out', 2, 'Manual adjustment', '2025-07-24 09:54:12'),
(24, 17, NULL, 'in', 4, 'Manual adjustment', '2025-07-24 09:55:17'),
(25, 28, NULL, 'in', 1, 'Manual adjustment', '2025-07-24 09:58:07'),
(26, 28, NULL, 'in', 9, 'Manual adjustment', '2025-07-24 09:59:24'),
(27, 30, NULL, 'in', 11, 'New Purchase', '2025-07-24 10:00:59'),
(28, 37, NULL, 'in', 12, 'Manual adjustment', '2025-07-24 10:04:28'),
(29, 38, NULL, 'in', 2, 'Manual adjustment', '2025-07-24 10:05:38'),
(30, 52, NULL, 'in', 10, 'New Purchase', '2025-07-24 10:06:24'),
(31, 45, NULL, 'in', 11, 'Manual adjustment', '2025-07-24 10:08:25'),
(32, 40, NULL, 'in', 17, 'Manual adjustment', '2025-07-24 10:08:57'),
(33, 32, NULL, 'in', 2, 'Manual adjustment', '2025-07-24 10:09:43'),
(34, 36, NULL, 'in', 14, 'Manual adjustment', '2025-07-24 10:10:11'),
(35, 36, NULL, 'in', 6, 'Manual adjustment', '2025-07-24 10:11:14'),
(36, 46, NULL, 'in', 18, 'Manual adjustment', '2025-07-24 10:11:58'),
(37, 24, NULL, 'in', 17, 'New Purchase', '2025-07-24 10:15:21'),
(38, 19, NULL, 'in', 2, 'New Purchase', '2025-07-24 10:15:54'),
(39, 55, NULL, 'in', 4, 'New Purchase', '2025-07-24 10:16:25'),
(40, 54, NULL, 'in', 22, 'New Purchase', '2025-07-24 10:19:05'),
(41, 54, NULL, 'in', 15, 'Manual adjustment', '2025-07-24 10:20:00'),
(42, 54, NULL, 'in', 87, 'Manual adjustment', '2025-07-24 10:22:40'),
(43, 58, NULL, 'in', 40, 'New Purchase', '2025-07-24 10:27:02'),
(44, 32, NULL, 'in', 2, 'Manual adjustment', '2025-07-24 10:29:30'),
(45, 59, NULL, 'in', 12, 'Manual adjustment', '2025-07-24 10:38:31'),
(46, 76, NULL, 'in', 5, 'Manual adjustment', '2025-07-24 10:38:58'),
(47, 60, NULL, 'in', 2, 'Manual adjustment', '2025-07-24 10:39:14'),
(48, 80, NULL, 'in', 10, 'Manual adjustment', '2025-07-24 10:45:52'),
(49, 60, NULL, 'in', 9, 'Manual adjustment', '2025-07-24 10:46:09'),
(50, 83, NULL, 'in', 9, 'Manual adjustment', '2025-07-24 10:46:39'),
(51, 85, NULL, 'in', 6, 'Manual adjustment', '2025-07-24 10:54:05'),
(52, 85, NULL, 'in', 14, 'Manual adjustment', '2025-07-24 10:54:29'),
(53, 55, NULL, 'in', 26, 'New Purchase', '2025-07-24 10:58:32'),
(54, 55, NULL, 'in', 10, 'Manual adjustment', '2025-07-24 11:01:35'),
(55, 35, NULL, 'in', 40, 'Manual adjustment', '2025-07-24 11:02:04'),
(56, 15, NULL, 'out', 44, 'Manual adjustment', '2025-07-24 11:03:40'),
(57, 35, NULL, 'in', 15, 'Manual adjustment', '2025-07-24 11:04:06'),
(58, 55, NULL, 'in', 5, 'Manual adjustment', '2025-07-24 11:04:45'),
(59, 49, NULL, 'in', 31, 'Manual adjustment', '2025-07-24 11:09:09'),
(60, 42, NULL, 'in', 20, 'Manual adjustment', '2025-07-24 11:09:52'),
(61, 49, NULL, 'in', 10, 'Manual adjustment', '2025-07-24 11:10:37'),
(62, 49, NULL, 'in', 10, 'Manual adjustment', '2025-07-24 11:11:03'),
(63, 18, NULL, 'in', 20, 'Manual adjustment', '2025-07-24 11:23:14'),
(64, 18, NULL, 'in', 9, 'Manual adjustment', '2025-07-24 11:32:52'),
(65, 17, NULL, 'in', 30, 'Manual adjustment', '2025-07-24 11:34:16'),
(66, 14, NULL, 'in', 22, 'Manual adjustment', '2025-07-24 11:36:11'),
(67, 44, NULL, 'in', 20, 'Manual adjustment', '2025-07-24 11:38:52'),
(68, 36, NULL, 'in', 30, 'Manual adjustment', '2025-07-24 11:42:40'),
(69, 14, NULL, 'in', 1, 'Return to supplier', '2025-07-24 14:50:15'),
(70, 30, 28, 'out', 2, 'Confirmed winger sale - mondinho', '2025-07-25 08:36:08'),
(71, 20, 29, 'out', 5, 'Regular sale', '2025-07-25 12:13:33'),
(72, 24, 30, 'out', 2, 'Confirmed winger sale - obama', '2025-07-25 12:17:59'),
(73, 24, NULL, 'in', 1, 'Customer Return', '2025-07-25 12:26:55'),
(74, 70, 31, 'out', 2, 'Winger sale (pending) - obama', '2025-07-25 12:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--
-- Creation: Jul 22, 2025 at 08:16 AM
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `suppliers`:
--

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `contact`, `email`, `address`, `created_at`) VALUES
(1, 'Fashion Forward Ltd', '+1-555-0101', 'contact@fashionforward.com', '123 Fashion Street, NY', '2025-07-22 08:16:50'),
(2, 'Style Source Inc', '+1-555-0102', 'sales@stylesource.com', '456 Trend Avenue, CA', '2025-07-22 08:16:50'),
(3, 'Clothing Co', '+1-555-0103', 'info@clothingco.com', '789 Apparel Road, TX', '2025-07-22 08:16:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Creation: Jul 22, 2025 at 08:16 AM
-- Last update: Jul 23, 2025 at 05:03 PM
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee') DEFAULT 'employee',
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `users`:
--

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$dBpZlqwF69rySkzO6huh3eo5u7Ahin9Cf1xerVs5/bkKOxAz2cbvG', 'admin', 'admin@clothingshop.com', '2025-07-22 08:16:50');

-- --------------------------------------------------------

--
-- Table structure for table `wingers`
--
-- Creation: Jul 24, 2025 at 11:48 AM
-- Last update: Jul 25, 2025 at 12:28 PM
--

DROP TABLE IF EXISTS `wingers`;
CREATE TABLE `wingers` (
  `winger_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `total_pending_value` decimal(10,2) DEFAULT 0.00,
  `total_completed_sales` decimal(10,2) DEFAULT 0.00,
  `reliability_score` decimal(3,2) DEFAULT 5.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `wingers`:
--

--
-- Dumping data for table `wingers`
--

INSERT INTO `wingers` (`winger_id`, `name`, `contact`, `address`, `total_pending_value`, `total_completed_sales`, `reliability_score`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'John Mwalimu', '+255123456789', 'Kariakoo Market, Dar es Salaam', 0.00, 0.00, 4.50, 'Regular customer, usually returns within 2 days', '2025-07-24 11:48:14', '2025-07-24 11:48:14'),
(2, 'Mary Shangwe', '+255987654321', 'Mwenge Market, Dar es Salaam', 0.00, 0.00, 4.80, 'Very reliable, deals with jerseys mainly', '2025-07-24 11:48:14', '2025-07-24 11:48:14'),
(3, 'Peter Msomba', '+255456789123', 'Magomeni, Dar es Salaam', 0.00, 0.00, 3.20, 'Sometimes delays return, requires follow-up', '2025-07-24 11:48:14', '2025-07-24 11:48:14'),
(4, 'John Mwalimu', '+255123456789', 'Kariakoo Market, Dar es Salaam', 0.00, 0.00, 4.50, 'Regular customer, usually returns within 2 days', '2025-07-25 08:32:29', '2025-07-25 08:32:29'),
(5, 'Mary Shangwe', '+255987654321', 'Mwenge Market, Dar es Salaam', 0.00, 0.00, 4.80, 'Very reliable, deals with jerseys mainly', '2025-07-25 08:32:29', '2025-07-25 08:32:29'),
(6, 'Peter Msomba', '+255456789123', 'Magomeni, Dar es Salaam', 0.00, 0.00, 3.20, 'Sometimes delays return, requires follow-up', '2025-07-25 08:32:29', '2025-07-25 08:32:29'),
(7, 'mondinho', '+255123456788', NULL, 0.00, 34000.00, 2.90, '', '2025-07-25 08:36:08', '2025-07-25 08:47:31'),
(8, 'obama', '07676666666', NULL, 34000.00, 34000.00, 5.00, NULL, '2025-07-25 12:17:59', '2025-07-25 12:28:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_supplier` (`supplier_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `idx_sales_product` (`product_id`),
  ADD KEY `idx_sales_date` (`sale_date`),
  ADD KEY `idx_sales_status` (`sale_status`),
  ADD KEY `idx_sales_winger` (`winger_name`),
  ADD KEY `confirmed_by` (`confirmed_by`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `idx_stock_movements_product` (`product_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `wingers`
--
ALTER TABLE `wingers`
  ADD PRIMARY KEY (`winger_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wingers`
--
ALTER TABLE `wingers`
  MODIFY `winger_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_ibfk_3` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
