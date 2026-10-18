CREATE DATABASE IF NOT EXISTS coffee_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coffee_shop;

CREATE TABLE staff (
  staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('manager','waiter','kitchen','cashier') NOT NULL,
  phone VARCHAR(20), email VARCHAR(150),
  gender ENUM('Nam','Nữ','Khác') NULL, birth_date DATE NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL UNIQUE,
  has_options TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE menu_items (
  item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  description VARCHAR(255) NULL,
  price INT UNSIGNED NOT NULL,
  image VARCHAR(255) NULL,
  is_hidden TINYINT(1) NOT NULL DEFAULT 0,
  is_sold_out TINYINT(1) NOT NULL DEFAULT 0,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  min_stock INT UNSIGNED NOT NULL DEFAULT 10,
  UNIQUE KEY uq_cat_name (category_id, item_name),
  FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB;

CREATE TABLE dining_tables (
  table_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  table_name VARCHAR(30) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  staff_id INT UNSIGNED NULL, staff_name VARCHAR(150), role VARCHAR(20),
  type VARCHAR(30) NOT NULL, text VARCHAR(500) NOT NULL,
  amount INT UNSIGNED NOT NULL DEFAULT 0, flag TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_log_time (created_at)
) ENGINE=InnoDB;

INSERT INTO staff (full_name, username, password, role) VALUES
('Nguyễn Văn A','quanly','$2y$10$.7SswrVHgpBiQXRA6SjzMeuVUJb1Vmt2N12F6G.yoIygBsFstW3jS','manager'),
('Lê Văn C','phucvu','$2y$10$.7SswrVHgpBiQXRA6SjzMeuVUJb1Vmt2N12F6G.yoIygBsFstW3jS','waiter'),
('Phạm Văn D','bep','$2y$10$.7SswrVHgpBiQXRA6SjzMeuVUJb1Vmt2N12F6G.yoIygBsFstW3jS','kitchen'),
('Trần Thị B','thungan','$2y$10$.7SswrVHgpBiQXRA6SjzMeuVUJb1Vmt2N12F6G.yoIygBsFstW3jS','cashier');