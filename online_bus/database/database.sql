-- Shreenathji Travels - Database Schema
-- Database: shreenathji_travels

CREATE DATABASE IF NOT EXISTS `shreenathji_travels` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shreenathji_travels`;

-- 1. Indian Cities Table
CREATE TABLE IF NOT EXISTS `indian_cities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `is_popular` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_city_name` (`name`),
    INDEX `idx_city_state` (`state`),
    INDEX `idx_city_popular` (`is_popular`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Buses Table
CREATE TABLE IF NOT EXISTS `buses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bus_name` VARCHAR(150) NOT NULL,
    `bus_number` VARCHAR(50) NOT NULL UNIQUE,
    `operator_name` VARCHAR(100) DEFAULT 'Shreenathji Travels',
    `bus_type` VARCHAR(100) NOT NULL, -- 'AC Sleeper (2+1)', 'BharatBenz AC Seater (2+2)', 'Volvo Multi-Axle AC Sleeper', 'Non-AC Sleeper', 'Non-AC Seater'
    `seating_layout` ENUM('seater_2x2', 'sleeper_2x1', 'mixed_seater_sleeper') NOT NULL DEFAULT 'sleeper_2x1',
    `total_seats` INT NOT NULL DEFAULT 36,
    `primary_image` VARCHAR(500) NULL,
    `image_type` ENUM('upload', 'url') DEFAULT 'url',
    `amenities` TEXT NULL, -- JSON array of strings
    `cancellation_policy` TEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bus_active` (`is_active`),
    INDEX `idx_bus_layout` (`seating_layout`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bus Seats Table (Static Layout Definitions per Bus)
CREATE TABLE IF NOT EXISTS `bus_seats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bus_id` INT NOT NULL,
    `seat_number` VARCHAR(10) NOT NULL,
    `deck` ENUM('lower', 'upper') DEFAULT 'lower',
    `seat_type` ENUM('seater', 'sleeper') DEFAULT 'seater',
    `row_num` INT NOT NULL,
    `col_num` INT NOT NULL,
    `is_ladies_preferred` TINYINT(1) DEFAULT 0,
    `price_extra` DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (`bus_id`) REFERENCES `buses`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_bus_seat` (`bus_id`, `seat_number`),
    INDEX `idx_bus_deck` (`bus_id`, `deck`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Routes Table
CREATE TABLE IF NOT EXISTS `routes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `from_city_id` INT NOT NULL,
    `to_city_id` INT NOT NULL,
    `distance_km` INT DEFAULT 0,
    `estimated_duration` VARCHAR(50) NOT NULL,
    `duration_minutes` INT NOT NULL DEFAULT 360,
    `boarding_points` TEXT NULL, -- JSON array of [{name, time_offset, landmark, address}]
    `dropping_points` TEXT NULL, -- JSON array of [{name, time_offset, landmark, address}]
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`from_city_id`) REFERENCES `indian_cities`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`to_city_id`) REFERENCES `indian_cities`(`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_route` (`from_city_id`, `to_city_id`),
    INDEX `idx_route_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Schedules Table
CREATE TABLE IF NOT EXISTS `schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bus_id` INT NOT NULL,
    `route_id` INT NOT NULL,
    `departure_date` DATE NOT NULL,
    `departure_time` TIME NOT NULL,
    `arrival_date` DATE NOT NULL,
    `arrival_time` TIME NOT NULL,
    `base_fare` DECIMAL(10,2) NOT NULL,
    `sleeper_fare` DECIMAL(10,2) NULL,
    `status` ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`bus_id`) REFERENCES `buses`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`route_id`) REFERENCES `routes`(`id`) ON DELETE CASCADE,
    INDEX `idx_sched_search` (`route_id`, `departure_date`, `status`),
    INDEX `idx_sched_bus_date` (`bus_id`, `departure_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Blocked Seats (Admin manual maintenance / VIP holds)
CREATE TABLE IF NOT EXISTS `blocked_seats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` INT NOT NULL,
    `seat_number` VARCHAR(10) NOT NULL,
    `reason` VARCHAR(255) DEFAULT 'Maintenance / VIP',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_sched_seat_block` (`schedule_id`, `seat_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Seat Locks (Concurrency protection during booking)
CREATE TABLE IF NOT EXISTS `seat_locks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` INT NOT NULL,
    `seat_number` VARCHAR(10) NOT NULL,
    `session_id` VARCHAR(128) NOT NULL,
    `user_id` INT NULL,
    `locked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_sched_seat_lock` (`schedule_id`, `seat_number`),
    INDEX `idx_lock_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `mobile` VARCHAR(15) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `gender` ENUM('male', 'female', 'other') NULL,
    `address` TEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_email` (`email`),
    INDEX `idx_user_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'manager', 'staff') DEFAULT 'super_admin',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pnr` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT NULL,
    `schedule_id` INT NOT NULL,
    `journey_date` DATE NOT NULL,
    `contact_name` VARCHAR(100) NOT NULL,
    `contact_email` VARCHAR(120) NOT NULL,
    `contact_mobile` VARCHAR(15) NOT NULL,
    `boarding_point` VARCHAR(255) NOT NULL,
    `dropping_point` VARCHAR(255) NOT NULL,
    `total_seats` INT NOT NULL,
    `base_amount` DECIMAL(10,2) NOT NULL,
    `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
    `convenience_fee` DECIMAL(10,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    `booking_status` ENUM('confirmed', 'pending_payment', 'cancelled', 'completed') DEFAULT 'pending_payment',
    `payment_method` VARCHAR(50) DEFAULT 'razorpay',
    `cancellation_reason` TEXT NULL,
    `cancelled_at` DATETIME NULL,
    `refund_amount` DECIMAL(10,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE RESTRICT,
    INDEX `idx_booking_pnr` (`pnr`),
    INDEX `idx_booking_user` (`user_id`),
    INDEX `idx_booking_date` (`journey_date`),
    INDEX `idx_booking_status` (`booking_status`, `payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Booking Passengers & Seats
CREATE TABLE IF NOT EXISTS `booking_passengers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `seat_number` VARCHAR(10) NOT NULL,
    `passenger_name` VARCHAR(100) NOT NULL,
    `age` INT NOT NULL,
    `gender` ENUM('male', 'female', 'other') NOT NULL,
    `seat_fare` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    INDEX `idx_passenger_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `razorpay_order_id` VARCHAR(100) NULL,
    `razorpay_payment_id` VARCHAR(100) NULL,
    `razorpay_signature` VARCHAR(255) NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `payment_method` VARCHAR(50) NULL,
    `status` ENUM('created', 'authorized', 'captured', 'failed', 'refunded') DEFAULT 'created',
    `error_description` TEXT NULL,
    `raw_response` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    INDEX `idx_pay_order` (`razorpay_order_id`),
    INDEX `idx_pay_payment` (`razorpay_payment_id`),
    INDEX `idx_pay_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Payment Webhook Events
CREATE TABLE IF NOT EXISTS `payment_webhook_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(100) NOT NULL UNIQUE,
    `event_type` VARCHAR(100) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `processed` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Website Settings Table
CREATE TABLE IF NOT EXISTS `website_settings` (
    `key_name` VARCHAR(50) PRIMARY KEY,
    `key_value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. FAQs Table
CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question` VARCHAR(255) NOT NULL,
    `answer` TEXT NOT NULL,
    `category` VARCHAR(50) DEFAULT 'General',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Admin Activity Logs
CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
    INDEX `idx_log_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
