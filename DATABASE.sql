SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `isdn` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `isdn`;

DROP TABLE IF EXISTS `deliveries`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','rdc','customer') NOT NULL DEFAULT 'customer',
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `rdc_location` VARCHAR(50) NULL,
  `profile_image` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `rdc_location` VARCHAR(50) NOT NULL,
  `image` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category`),
  KEY `idx_products_rdc` (`rdc_location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','dispatched','delivered') NOT NULL DEFAULT 'pending',
  `order_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_customer` (`customer_id`),
  CONSTRAINT `fk_orders_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_items_order` (`order_id`),
  KEY `idx_items_product` (`product_id`),
  CONSTRAINT `fk_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `deliveries` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `rdc_staff_id` INT NULL,
  `status` ENUM('pending','dispatched','delivered') NOT NULL DEFAULT 'pending',
  `assigned_date` TIMESTAMP NULL DEFAULT NULL,
  `delivered_date` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deliveries_order` (`order_id`),
  KEY `idx_deliveries_staff` (`rdc_staff_id`),
  CONSTRAINT `fk_deliveries_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_deliveries_staff`
    FOREIGN KEY (`rdc_staff_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`username`,`password`,`role`,`name`,`email`,`phone`,`rdc_location`)
VALUES
('admin', MD5('admin123'), 'admin', 'ISDN Head Office Admin', 'admin@isdn.test', '000-0000000', NULL),
('rdc_staff', MD5('rdc123'), 'rdc', 'RDC Staff Member', 'rdc@isdn.test', '000-0000001', 'Colombo RDC'),
('customer', MD5('customer123'), 'customer', 'Retail Customer', 'customer@isdn.test', '000-0000002', NULL);

INSERT INTO `products` (`name`,`category`,`price`,`stock`,`rdc_location`,`image`)
VALUES
('IslandLink Premium Rice 10kg', 'Grocery', 18.50, 120, 'Colombo RDC', NULL),
('OceanFresh Tuna Pack', 'Grocery', 6.25, 260, 'Galle RDC', NULL),
('Luxury Sparkling Water (12)', 'Beverages', 9.99, 140, 'Colombo RDC', NULL),
('SunGold Cooking Oil 5L', 'Grocery', 15.75, 90, 'Kandy RDC', NULL),
('Heritage Tea Collection', 'Beverages', 12.40, 75, 'Kandy RDC', NULL);

COMMIT;
