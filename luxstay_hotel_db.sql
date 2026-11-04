-- LuxStay Online Hotel Management System
-- Based on the supplied ER diagram | MySQL 8.0+ | InnoDB

CREATE DATABASE IF NOT EXISTS luxstay_hotel_db
 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luxstay_hotel_db;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS payment;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS booking;
DROP TABLE IF EXISTS room;
DROP TABLE IF EXISTS hotel;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admin;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE admin (
 admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 admin_name VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE hotel (
 hotel_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 admin_id INT UNSIGNED NOT NULL,
 hotel_name VARCHAR(150) NOT NULL,
 hotel_address VARCHAR(255) NOT NULL,
 hotel_type VARCHAR(80) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_hotel_admin FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
 ON UPDATE CASCADE ON DELETE RESTRICT,
 INDEX idx_hotel_admin(admin_id)
) ENGINE=InnoDB;

CREATE TABLE room (
 room_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 hotel_id INT UNSIGNED NOT NULL,
 room_number VARCHAR(20) NOT NULL,
 room_type VARCHAR(80) NOT NULL,
 room_description TEXT,
 price DECIMAL(10,2) NOT NULL,
 status ENUM('available','occupied','maintenance','inactive') NOT NULL DEFAULT 'available',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_room_hotel FOREIGN KEY (hotel_id) REFERENCES hotel(hotel_id)
 ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT uq_room_hotel_number UNIQUE(hotel_id,room_number),
 CONSTRAINT chk_room_price CHECK(price>=0),
 INDEX idx_room_hotel_status(hotel_id,status)
) ENGINE=InnoDB;

CREATE TABLE users (
 user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_name VARCHAR(100) NOT NULL,
 user_email VARCHAR(150) NOT NULL UNIQUE,
 user_address VARCHAR(255),
 password VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE booking (
 booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 room_id INT UNSIGNED NOT NULL,
 booking_type VARCHAR(50) NOT NULL DEFAULT 'online',
 check_in_date DATE NOT NULL,
 check_out_date DATE NOT NULL,
 booking_status ENUM('pending','confirmed','cancelled','checked_in','checked_out') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_booking_user FOREIGN KEY(user_id) REFERENCES users(user_id)
 ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_booking_room FOREIGN KEY(room_id) REFERENCES room(room_id)
 ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT chk_booking_dates CHECK(check_out_date>check_in_date),
 INDEX idx_booking_user(user_id),
 INDEX idx_booking_room_dates(room_id,check_in_date,check_out_date),
 INDEX idx_booking_status(booking_status)
) ENGINE=InnoDB;

CREATE TABLE feedback (
 feedback_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 message TEXT NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_feedback_user FOREIGN KEY(user_id) REFERENCES users(user_id)
 ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT chk_feedback_rating CHECK(rating BETWEEN 1 AND 5),
 INDEX idx_feedback_user(user_id)
) ENGINE=InnoDB;

CREATE TABLE payment (
 payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id INT UNSIGNED NOT NULL UNIQUE,
 amount DECIMAL(10,2) NOT NULL,
 payment_date DATETIME NULL,
 payment_method ENUM('cash','card','bkash','nagad','bank_transfer','other') NOT NULL DEFAULT 'cash',
 payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
 transaction_reference VARCHAR(120),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_payment_booking FOREIGN KEY(booking_id) REFERENCES booking(booking_id)
 ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT chk_payment_amount CHECK(amount>=0),
 INDEX idx_payment_status(payment_status)
) ENGINE=InnoDB;

-- Optional demo records. Passwords below are placeholders; replace with password_hash() values.
INSERT INTO admin(admin_name,email,password)
VALUES('System Administrator','admin@luxstay.local','REPLACE_WITH_PASSWORD_HASH');

INSERT INTO hotel(admin_id,hotel_name,hotel_address,hotel_type)
VALUES(1,'LuxStay Grand Hotel','Dhaka, Bangladesh','Luxury');

INSERT INTO room(hotel_id,room_number,room_type,room_description,price,status) VALUES
(1,'101','Single','Comfortable single room.',2500.00,'available'),
(1,'102','Double','Spacious double room.',3800.00,'available'),
(1,'201','Deluxe','Premium deluxe room.',5500.00,'available');

INSERT INTO users(user_name,user_email,user_address,password)
VALUES('Demo Customer','customer@example.com','Dhaka, Bangladesh','REPLACE_WITH_PASSWORD_HASH');

-- ER mapping:
-- Admin 1:N Hotel; Hotel 1:N Room; Room 1:N Booking;
-- User 1:N Booking; User 1:N Feedback; Booking 1:0..1 Payment.
-- Payment.booking_id UNIQUE enforces at most one payment per booking.
-- Availability overlap: existing.check_in_date < requested_check_out
-- AND existing.check_out_date > requested_check_in; ignore cancelled bookings.
