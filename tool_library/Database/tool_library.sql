-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 07 مايو 2026 الساعة 23:54
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tool_library`
--

-- --------------------------------------------------------

--
-- بنية الجدول `disputes`
--

CREATE TABLE `disputes` (
  `dispute_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `opened_by` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('open','resolved') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(20) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `disputes`
--

INSERT INTO `disputes` (`dispute_id`, `reservation_id`, `opened_by`, `reason`, `status`, `created_at`, `type`) VALUES
(1, 5, 4, 'tool damaged', 'resolved', '2026-05-07 15:24:57', 'general');

-- --------------------------------------------------------

--
-- بنية الجدول `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `issue_description` text NOT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `tool_id`, `reported_by`, `issue_description`, `status`, `created_at`) VALUES
(1, 4, 4, 'Damage reported after rental', 'completed', '2026-05-07 21:29:20'),
(2, 4, 4, 'Damage reported after rental', 'completed', '2026-05-07 21:34:44');

-- --------------------------------------------------------

--
-- بنية الجدول `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message_text`, `created_at`) VALUES
(1, 3, 4, 'hello', '2026-05-02 18:51:16'),
(2, 3, 4, 'hello', '2026-05-02 18:52:18');

-- --------------------------------------------------------

--
-- بنية الجدول `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `created_at`, `is_read`) VALUES
(1, 6, NULL, '✅ TOTAL DIGITAL MULTIMETER 600V 4000 returned in good condition.', '2026-05-07 15:17:12', 0),
(2, 4, NULL, '✅ You successfully returned TOTAL DIGITAL MULTIMETER 600V 4000.', '2026-05-07 15:17:12', 0),
(3, 5, NULL, '✅ Your tool \'heavy duty pressure\' is now approved and listed!', '2026-05-07 21:35:44', 0);

-- --------------------------------------------------------

--
-- بنية الجدول `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('rental','deposit','refund','deduction') DEFAULT NULL,
  `status` enum('paid','held','refunded','deducted') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `payments`
--

INSERT INTO `payments` (`payment_id`, `reservation_id`, `user_id`, `amount`, `payment_type`, `status`, `created_at`) VALUES
(1, 10, 7, 450.00, 'rental', 'paid', '2026-05-07 21:46:15'),
(2, 10, 7, 135.00, 'deposit', 'held', '2026-05-07 21:46:15');

-- --------------------------------------------------------

--
-- بنية الجدول `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `new_user_id` int(11) NOT NULL,
  `reward_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','active','completed','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `rental_type` enum('hour','day','week') DEFAULT 'day'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `tool_id`, `start_date`, `end_date`, `total_price`, `status`, `created_at`, `deposit_amount`, `payment_status`, `rental_type`) VALUES
(1, 5, 1, '2026-05-01', '2026-05-02', 0.00, 'approved', '2026-04-29 21:17:48', 0.00, 'paid', 'day'),
(4, 4, 2, '2026-05-20', '2026-05-21', 1200.00, 'completed', '2026-05-02 18:53:26', 1200.00, 'paid', 'day'),
(5, 4, 4, '2026-05-23', '2026-05-26', 1400.00, 'completed', '2026-05-02 19:00:48', 700.00, '', 'day'),
(6, 6, 1, '2026-05-08', '2026-05-09', 1000.00, 'approved', '2026-05-07 13:25:28', 1000.00, 'paid', 'day'),
(8, 6, 4, '2026-05-29', '2026-05-29', 40.00, '', '2026-05-07 14:12:20', 12.00, 'refunded', 'hour'),
(9, 4, 6, '2026-05-07', '2026-05-07', 14.99, 'completed', '2026-05-07 14:52:21', 4.50, 'paid', 'hour'),
(10, 7, 5, '2026-05-13', '2026-05-14', 450.00, 'pending', '2026-05-07 21:45:59', 135.00, 'paid', 'day');

-- --------------------------------------------------------

--
-- بنية الجدول `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewed_user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `reviews`
--

INSERT INTO `reviews` (`review_id`, `reservation_id`, `reviewer_id`, `reviewed_user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 9, 4, 6, 4, 'very good', '2026-05-07 15:19:01'),
(2, 4, 4, 3, 5, 'wooow!', '2026-05-07 15:21:44');

-- --------------------------------------------------------

--
-- بنية الجدول `tools`
--

CREATE TABLE `tools` (
  `tool_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `tool_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `availability_status` enum('pending','available','maintenance','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT NULL,
  `price_per_hour` decimal(10,2) DEFAULT 0.00,
  `price_per_week` decimal(10,2) DEFAULT 0.00,
  `deposit_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tools`
--

INSERT INTO `tools` (`tool_id`, `owner_id`, `tool_name`, `category`, `description`, `price_per_day`, `availability_status`, `created_at`, `photo`, `price_per_hour`, `price_per_week`, `deposit_amount`) VALUES
(1, 3, 'Jig Saw', 'Power Tools', 'Hand held power tool with small thin blade. Used for cutting curves and details into thinner material. Library members must purchase their own blade for all power tools. Make sure you purchase the correct blade for the material you are cutting.', 100.00, 'available', '2026-04-28 20:33:06', 'uploads/1777419186_DSCN0012.webp', 30.00, 300.00, 0.00),
(2, 3, 'Harmer Drill', 'Power Tools', 'Includes 2 batteries, charger, and side handle.\r\n\r\nWorks as a standard drill as well as a hammer drill. \r\n\r\nAs of 4/28, we cannot locate the side handle. Last user stated there was no side handle when they checked it out', 120.00, 'available', '2026-04-29 21:56:10', 'uploads/1777510570_attribute_image1183986260373148736-small@2x.jpg', 30.00, 400.00, 0.00),
(4, 5, 'heavy duty pressure', 'Cleaning', 'no description \r\n', 150.00, 'available', '2026-05-01 09:21:52', 'uploads/1777638112_heavy-duty-pressure-washer-2048w.jpg', 40.00, 500.00, 0.00),
(5, 4, '26PCS Insulated hand tools set', 'Other', '1Pcs 180mm insulated combination pliers;\r\n1Pcs 160mm insulated long nose pliers ;\r\n1Pcs 160mm insulated diagonal cutting pliers ;\r\n3Pcs insulated slotted screwdrivers:\r\nSL3.0x75mm; SL4.0x100mm; SL5.5x125mm;\r\n2Pcs insulated phillips screwdrivers:\r\n14Pcs insulated hexagon sockets 1/2\"\" (10mm, 11mm, 12mm, 13mm, 14mm, 16mm, 17mm, 18mm, 19mm, 22mm, 24mm, 27mm, |30mm, 32mm)', 150.00, 'available', '2026-05-01 09:42:10', 'uploads/THKITH2601.jpg', 35.00, 450.00, 0.00),
(6, 6, 'TOTAL DIGITAL MULTIMETER 600V 4000', 'Electronics', 'مالتي ميتر TOTAL DIGITAL MULTIMETER 600V 4000 NCV TMT516003\r\n\r\nDisplay: True RMS, 4000 counts\r\nSmart function\r\nAC/DC voltage measurement: 0.8–600V\r\nResistance: up to 600 kΩ\r\nNCV (non-contact voltage detection)\r\nContinuity test\r\nAuto range\r\nAuto power off\r\nIncludes 2 AAA R03 batteries\r\nPackaged in double blister', 39.99, 'available', '2026-05-07 14:46:28', 'uploads/1778165188_0f331fe79f553be14cb691c43f776657.webp', 14.99, 74.99, 0.00);

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('member','librarian','admin') DEFAULT 'member',
  `membership_type` enum('normal','pro') DEFAULT 'normal',
  `status` enum('approved','pending','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `trust_score` decimal(3,2) DEFAULT 5.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `phone`, `address`, `role`, `membership_type`, `status`, `created_at`, `trust_score`) VALUES
(3, 'Youssef Essam', 'abooessam701@gmail.com', '$2y$10$BccGZGHZ1GAUZgkETvsAJuIx6h2IWLw/.1rznxPE8retH.ACWyanu', '01156030585', 'Beni suef governorate, New beni suef city block 20 unit 11', 'librarian', 'pro', 'approved', '2026-04-28 16:25:22', 5.00),
(4, 'ahmed', 'ahmed@gmail.com', '$2y$10$BccGZGHZ1GAUZgkETvsAJuIx6h2IWLw/.1rznxPE8retH.ACWyanu', '01235412521', 'giza', 'member', 'normal', 'approved', '2026-04-28 20:47:07', 5.00),
(5, 'maged', 'maged@gmail.com', '$2y$10$BccGZGHZ1GAUZgkETvsAJuIx6h2IWLw/.1rznxPE8retH.ACWyanu', '01215474345', 'cairo', 'member', 'normal', 'approved', '2026-04-29 12:30:54', 5.00),
(6, 'abass', 'a@gmail.com', '$2y$10$BccGZGHZ1GAUZgkETvsAJuIx6h2IWLw/.1rznxPE8retH.ACWyanu', '012354121', 'giza', 'member', 'normal', 'approved', '2026-04-28 20:47:07', 5.00),
(7, 'hany', 'h@gmail.com', '$2y$10$BccGZGHZ1GAUZgkETvsAJuIx6h2IWLw/.1rznxPE8retH.ACWyanu', '4158441523', 'alex', 'member', 'normal', 'approved', '2026-05-06 23:57:38', 5.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`dispute_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `opened_by` (`opened_by`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `reported_by` (`reported_by`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `referrer_id` (`referrer_id`),
  ADD KEY `new_user_id` (`new_user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tool_id` (`tool_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewed_user_id` (`reviewed_user_id`);

--
-- Indexes for table `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`tool_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `dispute_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tools`
--
ALTER TABLE `tools`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`),
  ADD CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`opened_by`) REFERENCES `users` (`user_id`);

--
-- قيود الجداول `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`tool_id`),
  ADD CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`);

--
-- قيود الجداول `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- قيود الجداول `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- قيود الجداول `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`new_user_id`) REFERENCES `users` (`user_id`);

--
-- قيود الجداول `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`tool_id`);

--
-- قيود الجداول `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`reviewed_user_id`) REFERENCES `users` (`user_id`);

--
-- قيود الجداول `tools`
--
ALTER TABLE `tools`
  ADD CONSTRAINT `tools_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
