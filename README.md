## 🗄️ **DATABASE SQL**

Buat database `uprpl_db` di phpMyAdmin, lalu jalankan SQL ini:

```sql
CREATE DATABASE IF NOT EXISTS uprpl_db;
USE uprpl_db;

-- ==================== TABEL users ====================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20),
    `role` ENUM('super_admin', 'admin_up') DEFAULT 'admin_up',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `password_expiry` DATETIME,
    `last_login` DATETIME,
    `last_ip` VARCHAR(45),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TABEL pending_passwords ====================
CREATE TABLE IF NOT EXISTS `pending_passwords` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `expiry_time` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TABEL orders ====================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(20) UNIQUE NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_class` VARCHAR(50) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_email` VARCHAR(100) NOT NULL,
    `service` VARCHAR(50) NOT NULL,
    `service_name` VARCHAR(100) NOT NULL,
    `jumlah` INT NOT NULL,
    `ukuran` VARCHAR(10),
    `jenis_sablon` VARCHAR(50),
    `warna_kaos` VARCHAR(20),
    `ukuran_kertas` VARCHAR(10),
    `drive_link` TEXT NOT NULL,
    `catatan` TEXT,
    `harga_satuan` INT NOT NULL,
    `subtotal` INT NOT NULL,
    `unique_code` INT NOT NULL,
    `total` INT NOT NULL,
    `status` ENUM('pending_payment', 'pending', 'process', 'success', 'cancelled') DEFAULT 'pending_payment',
    `payment_status` ENUM('belum_bayar', 'paid', 'verified') DEFAULT 'belum_bayar',
    `payment_method` VARCHAR(20),
    `payment_time` DATETIME,
    `assigned_to` VARCHAR(50),
    `assigned_at` DATETIME,
    `completed_at` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TABEL payments ====================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(20) NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_email` VARCHAR(100) NOT NULL,
    `amount` INT NOT NULL,
    `unique_code` INT NOT NULL,
    `method` VARCHAR(20) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    `verified` BOOLEAN DEFAULT FALSE,
    `verified_by` INT,
    `verified_at` DATETIME,
    `payment_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TABEL activity_logs ====================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `username` VARCHAR(50),
    `action` VARCHAR(100),
    `level` ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    `message` TEXT,
    `ip` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TABEL admin_logs ====================
CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `username` VARCHAR(50),
    `action` VARCHAR(100),
    `details` TEXT,
    `ip` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TABEL email_logs ====================
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `recipient` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    `response` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TABEL notifications ====================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    `target` ENUM('all', 'super_admin', 'admin_up') DEFAULT 'all',
    `order_number` VARCHAR(20),
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TABEL settings ====================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) UNIQUE NOT NULL,
    `setting_value` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== INSERT DEFAULT SETTINGS ====================
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('payment_expiry', '10'),
('password_expiry', '2'),
('session_duration', '8'),
('max_login_attempts', '5'),
('site_name', 'Unit Produksi RPL SMKN 24 JKT'),
('school_name', 'SMK Negeri 24 Jakarta'),
('admin_email', 'nmurtadho1905@gmail.com'),
('admin_phone', '0857107855244'),
('dana_number', '0857107855244'),
('dana_name', 'UNIT PRODUKSI RPL');

-- ==================== INSERT DEFAULT ADMIN PUSAT ====================
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `name`, `role`, `status`, `password_expiry`) VALUES
('naufal19smkn24', 'nmurtadho1905@gmail.com', '$2y$12$8K3Xk9QwL5pR7sT2vW4xY.6nF7gH8iJ9kL0mN1bV2cX3zA4sD5fG6hJ7kL8m', 'Admin Pusat', 'super_admin', 'active', DATE_ADD(NOW(), INTERVAL 90 DAY));
```
