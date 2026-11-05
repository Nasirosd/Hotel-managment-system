<<<<<<< HEAD
# Online Hotel Management System - HTML + Bootstrap

## Included Pages
- index.html
- rooms.html
- booking.html
- about.html
- contact.html
- login.html
- dashboard.html

## Technologies
- HTML5
- Bootstrap 5.3.3 (CDN)
- Custom CSS
- Vanilla JavaScript

## How to Run
1. Extract the ZIP file.
2. Open `index.html` in Chrome/Edge/Firefox.
3. Internet connection is required for Bootstrap CDN and demo images.

## Important
This is a frontend template only. Booking, login, database, payment, admin CRUD, and authentication are demo UI and are not connected to a backend.
=======
# LuxStay — Online Hotel Management System

## Requirements
- XAMPP (Apache + MySQL), PHP 8+, MySQL 8+
- VS Code (recommended)

## Install
1. Extract this folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/phpmyadmin`.
4. Import `database/luxstay_hotel_db.sql`.
5. Check database credentials in `config/database.php`.
6. Open `http://localhost/luxstay_hotel_management/`.

## ER diagram mapping
- Admin 1:N Hotel (`hotel.admin_id`)
- Hotel 1:N Room (`room.hotel_id`)
- Room 1:N Booking (`booking.room_id`)
- User 1:N Booking (`booking.user_id`)
- User 1:N Feedback (`feedback.user_id`)
- Booking 1:0..1 Payment (`payment.booking_id` is UNIQUE)

## Important
This is an academic starter scaffold, not production-ready software. The SQL schema is complete for the supplied ER diagram. Add full admin CRUD, role-based admin authentication, CSRF protection, cancellation rules, robust transaction/locking for simultaneous booking attempts, and comprehensive testing before real deployment. Demo password values in SQL are placeholders. Customer registration stores passwords using `password_hash()` and login uses `password_verify()`. Payment table stores transaction status only; do not store card numbers or CVV.
>>>>>>> 50e4a3f9b4074848a74b25770f9ddf648f84ed0f
