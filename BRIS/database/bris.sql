CREATE DATABASE IF NOT EXISTS bris_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bris_db;
CREATE TABLE IF NOT EXISTS households(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,household_code VARCHAR(30) NOT NULL UNIQUE,address VARCHAR(255) NOT NULL,head_name VARCHAR(160) DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS residents(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,first_name VARCHAR(80) NOT NULL,middle_name VARCHAR(80) DEFAULT NULL,last_name VARCHAR(80) NOT NULL,birth_date DATE DEFAULT NULL,sex VARCHAR(30) DEFAULT NULL,phone VARCHAR(30) DEFAULT NULL,email VARCHAR(150) DEFAULT NULL,address VARCHAR(255) NOT NULL,household_id INT UNSIGNED DEFAULT NULL,status ENUM('Active','Inactive','Moved','Deceased') NOT NULL DEFAULT 'Active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,CONSTRAINT fk_resident_household FOREIGN KEY(household_id) REFERENCES households(id) ON DELETE SET NULL,INDEX idx_resident_name(last_name,first_name),INDEX idx_resident_status(status)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS barangay_officials(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,full_name VARCHAR(160) NOT NULL,position VARCHAR(100) NOT NULL,term_start DATE DEFAULT NULL,term_end DATE DEFAULT NULL,status ENUM('Active','Inactive') DEFAULT 'Active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS resident_documents(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,resident_id INT UNSIGNED NOT NULL,document_type VARCHAR(100) NOT NULL,document_number VARCHAR(100) DEFAULT NULL,issued_date DATE DEFAULT NULL,notes TEXT DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_document_resident FOREIGN KEY(resident_id) REFERENCES residents(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(40) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('staff','user') NOT NULL DEFAULT 'user',
    resident_id INT UNSIGNED DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_users_resident (resident_id),
    INDEX idx_users_role (role),
    CONSTRAINT fk_users_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB;
INSERT IGNORE INTO households(household_code,address,head_name) VALUES('HH-0001','123 Sample Street, Barangay Sample','Juan Dela Cruz'),('HH-0002','45 Community Road, Barangay Sample','Maria Santos');
INSERT INTO residents(first_name,last_name,birth_date,sex,phone,email,address,household_id,status) SELECT 'Juan','Dela Cruz','1990-01-15','Male','09170000001','juan@example.com','123 Sample Street, Barangay Sample',id,'Active' FROM households WHERE household_code='HH-0001' AND NOT EXISTS(SELECT 1 FROM residents WHERE first_name='Juan' AND last_name='Dela Cruz' AND address='123 Sample Street, Barangay Sample');
INSERT INTO residents(first_name,last_name,birth_date,sex,phone,email,address,household_id,status) SELECT 'Maria','Santos','1992-06-20','Female','09170000002','maria@example.com','45 Community Road, Barangay Sample',id,'Active' FROM households WHERE household_code='HH-0002' AND NOT EXISTS(SELECT 1 FROM residents WHERE first_name='Maria' AND last_name='Santos' AND address='45 Community Road, Barangay Sample');
