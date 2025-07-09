-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 09 Tem 2025, 08:01:50
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `teklif`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `actions`
--

CREATE TABLE `actions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `actions`
--

INSERT INTO `actions` (`id`, `name`) VALUES
(1, 'login'),
(2, 'logout'),
(3, 'create'),
(4, 'update'),
(5, 'delete');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action_time` datetime NOT NULL DEFAULT current_timestamp(),
  `table_name` varchar(50) NOT NULL,
  `column_name` varchar(50) DEFAULT NULL,
  `record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `action_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `companies`
--

CREATE TABLE `companies` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `title` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `guillotine_quotes`
--

CREATE TABLE `guillotine_quotes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `system_type` varchar(50) NOT NULL,
  `width_mm` decimal(10,2) NOT NULL,
  `height_mm` decimal(10,2) NOT NULL,
  `system_qty` int(10) UNSIGNED NOT NULL,
  `motor_system` varchar(50) DEFAULT NULL,
  `remote_system` varchar(50) DEFAULT NULL,
  `remote_qty` int(10) UNSIGNED DEFAULT NULL,
  `ral_code` varchar(50) DEFAULT NULL,
  `glass_inner_mm` decimal(6,2) DEFAULT NULL,
  `glass_gap_mm` decimal(6,2) DEFAULT NULL,
  `glass_outer_mm` decimal(6,2) DEFAULT NULL,
  `tempered` tinyint(1) DEFAULT 0,
  `gap_type` varchar(20) DEFAULT NULL,
  `glass_width_mm` decimal(10,2) DEFAULT NULL,
  `glass_height_mm` decimal(10,2) DEFAULT NULL,
  `glass_qty` int(10) UNSIGNED DEFAULT NULL,
  `glass_color` varchar(30) DEFAULT NULL,
  `sqm_system` decimal(12,2) DEFAULT NULL,
  `kg_aluminium` decimal(12,2) DEFAULT NULL,
  `sqm_glass` decimal(12,2) DEFAULT NULL,
  `delta` decimal(12,2) DEFAULT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `master_quotes`
--

CREATE TABLE `master_quotes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `quote_date` date NOT NULL,
  `prepared_by` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT 'satis@alumann.com.tr',
  `delivery_term` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_due` varchar(50) DEFAULT NULL,
  `quote_validity` varchar(50) DEFAULT NULL,
  `maturity` varchar(50) DEFAULT NULL,
  `total_amount` decimal(14,2) DEFAULT NULL,
  `discount_rate` decimal(5,2) DEFAULT NULL,
  `discount_amount` decimal(14,2) DEFAULT NULL,
  `vat_rate` decimal(5,2) DEFAULT NULL,
  `vat_amount` decimal(14,2) DEFAULT NULL,
  `taxes_included` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `permissions`
--

CREATE TABLE `permissions` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `permission_role`
--

CREATE TABLE `permission_role` (
  `role_id` smallint(5) UNSIGNED NOT NULL,
  `permission_id` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `kgmt` decimal(10,3) NOT NULL,
  `category` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `roles`
--

CREATE TABLE `roles` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(30) NOT NULL,
  `description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `role_user`
--

CREATE TABLE `role_user` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password_hash` char(60) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `password_hash`, `email`, `created_at`) VALUES
(1, 'Hakan Berke', 'İÇELLİOĞLU', 'test', '$2y$10$4gYfzNT4FUNgO7ntWIhW9.CopzdGqofl.Si2iAG48I7mMFShDIhSi', 'hakanicellioglu@gmail.com', '2025-07-07 10:58:17');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `actions`
--
ALTER TABLE `actions`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_user_time` (`user_id`,`action_time`),
  ADD KEY `fk_auditlogs_actions` (`action_id`);

--
-- Tablo için indeksler `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Tablo için indeksler `guillotine_quotes`
--
ALTER TABLE `guillotine_quotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Tablo için indeksler `master_quotes`
--
ALTER TABLE `master_quotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Tablo için indeksler `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Tablo için indeksler `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Tablo için indeksler `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Tablo için indeksler `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_settings_users` (`user_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `actions`
--
ALTER TABLE `actions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `guillotine_quotes`
--
ALTER TABLE `guillotine_quotes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `master_quotes`
--
ALTER TABLE `master_quotes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `roles`
--
ALTER TABLE `roles`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auditlogs_actions` FOREIGN KEY (`action_id`) REFERENCES `actions` (`id`) ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `guillotine_quotes`
--
ALTER TABLE `guillotine_quotes`
  ADD CONSTRAINT `guillotine_quotes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `master_quotes`
--
ALTER TABLE `master_quotes`
  ADD CONSTRAINT `master_quotes_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `permission_role_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `role_user_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `fk_settings_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
