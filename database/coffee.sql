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

INSERT INTO menu_items (item_id, category_id, item_name, description, price) VALUES
(1,1,'Cà phê sữa đá','Phin truyền thống',29000),
(2,1,'Cà phê đen đá','Đậm vị, không đường sữa',25000),
(3,1,'Cà phê đen nóng','Pha phin nóng',25000),
(7,1,'Cà phê bạc xỉu','Nhiều sữa, ít cà phê',29000),
(8,1,'Cà phê cappuccino','Espresso, sữa tươi, bọt sữa',45000),
(4,2,'Trà sữa trân châu','Trân châu đen',35000),
(5,2,'Trà sữa matcha','Matcha Nhật',39000),
(9,3,'Trà đào cam sả','Đào miếng, cam, sả',39000),
(10,3,'Trà vải hoa hồng','Vải, hoa hồng',39000),
(6,4,'Bánh croissant','Nướng giòn',22000),
(11,4,'Bánh tiramisu','Lát 1 phần',35000);

-- Chạy trong phpMyAdmin (tab SQL) trên database coffee

-- 1) Bàn: seed 8 bàn mặc định nếu chưa có
INSERT IGNORE INTO dining_tables (table_name, is_active) VALUES
('Bàn 01',1),('Bàn 02',1),('Bàn 03',1),('Bàn 04',1),
('Bàn 05',1),('Bàn 06',1),('Bàn 07',1),('Bàn 08',1);

-- 2) Ca làm
CREATE TABLE IF NOT EXISTS shifts (
  shift_id    INT AUTO_INCREMENT PRIMARY KEY,
  staff_id    INT NOT NULL,
  shift_date  DATE NOT NULL,
  start_time  TIME NOT NULL,
  end_time    TIME NOT NULL,
  registered  TINYINT(1) NOT NULL DEFAULT 0,
  left_at     DATETIME NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_shift_date (shift_date),
  INDEX idx_shift_staff_date (staff_id, shift_date)
  -- Nếu cột khóa chính của bảng staff là staff_id kiểu INT thì thêm:
  -- , FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;