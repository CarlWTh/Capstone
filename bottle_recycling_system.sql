-- Database: bottle_recycling_system
CREATE DATABASE IF NOT EXISTS bottle_recycling_system;
USE bottle_recycling_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(100),
    reset_token_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trash bins table
CREATE TABLE TrashBin (
    bin_id INT AUTO_INCREMENT PRIMARY KEY,
    capacity DECIMAL(5,2) NOT NULL,
    current_level DECIMAL(5,2) NOT NULL,
    status ENUM('empty', 'partial', 'full', 'maintenance') NOT NULL,
    sensor_config TEXT
);

-- Student sessions table
CREATE TABLE StudentSession (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    anonymous_token VARCHAR(64) NOT NULL UNIQUE,
    device_mac_address VARCHAR(17),
    first_access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bottle deposits table
CREATE TABLE BottleDeposit (   
    deposit_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    bin_id INT ,
    bottle_count INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'processed', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (session_id) REFERENCES StudentSession(session_id),
    FOREIGN KEY (bin_id) REFERENCES TrashBin(bin_id)
);

-- Vouchers table
CREATE TABLE Voucher (    
    voucher_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    deposit_id INT NOT NULL,
    expiry_time DATETIME ,
    is_used BOOLEAN DEFAULT FALSE,    
    FOREIGN KEY (deposit_id) REFERENCES BottleDeposit(deposit_id)
);

-- Internet sessions table
CREATE TABLE InternetSession (
    internet_session_id INT AUTO_INCREMENT PRIMARY KEY,
    anonymous_token VARCHAR(64) NOT NULL,
    minutes INT,
    voucher_id INT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (anonymous_token) REFERENCES StudentSession(anonymous_token) ON DELETE CASCADE,
    FOREIGN KEY (voucher_id) REFERENCES Voucher(voucher_id) ON DELETE CASCADE
);

-- Admin activity logs
CREATE TABLE AdminActivityLog (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- System settings table
CREATE TABLE SystemSettings(
   id INT AUTO_INCREMENT PRIMARY KEY,
   minutes_per_bottle INT DEFAULT 2
);


INSERT INTO SystemSettings (minutes_per_bottle) VALUES (2);
-- Create an initial admin user (password: admin123)
INSERT INTO users (username, email, phone, password_hash, is_admin)
VALUES ('admin', 'admin@example.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);
