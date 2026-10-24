-- =====================================================================
-- coffee_shop: schema + du lieu mau, sap xep theo thu tu phu thuoc
-- (bang cha truoc, bang con sau; du lieu mau o cuoi)
-- Chay trong phpMyAdmin (tab SQL) hoac mysql CLI. Tao admin dau tien bang
-- database/create_manager.php (CLI only).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS coffee_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coffee_shop;

-- =====================================================================
-- PHAN 1: BANG GOC (khong phu thuoc bang nao)
-- =====================================================================

CREATE TABLE IF NOT EXISTS staff (
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

CREATE TABLE IF NOT EXISTS categories (
  category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL UNIQUE,
  has_options TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dining_tables (
  table_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  table_name VARCHAR(30) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  customer_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL UNIQUE,
  birth_date DATE NULL,
  points INT UNSIGNED NOT NULL DEFAULT 0,
  total_spent BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS promotions (
  promotion_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  label VARCHAR(150) NOT NULL,
  discount_type ENUM('pct','vnd') NOT NULL,
  discount_value INT UNSIGNED NOT NULL,
  starts_on DATE NULL,
  ends_on DATE NULL,
  max_uses INT UNSIGNED NOT NULL DEFAULT 0,
  used_count INT UNSIGNED NOT NULL DEFAULT 0,
  hour_from TINYINT UNSIGNED NULL,
  hour_to TINYINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS suppliers (
  supplier_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supplier_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_items (
  stock_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(150) NOT NULL UNIQUE,
  unit VARCHAR(30) NOT NULL,
  quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  min_quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL,
  setting_label VARCHAR(255) NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- PHAN 2: BANG PHU THUOC CAP 1 (staff, categories, ...)
-- =====================================================================

-- menu_items -> categories (image da gop thanh MEDIUMTEXT)
CREATE TABLE IF NOT EXISTS menu_items (
  item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  description VARCHAR(255) NULL,
  price INT UNSIGNED NOT NULL,
  image MEDIUMTEXT NULL,
  is_hidden TINYINT(1) NOT NULL DEFAULT 0,
  is_sold_out TINYINT(1) NOT NULL DEFAULT 0,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  min_stock INT UNSIGNED NOT NULL DEFAULT 10,
  UNIQUE KEY uq_cat_name (category_id, item_name),
  FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB;

-- audit_logs -> staff (xoa nhan vien thi giu log, staff_id thanh NULL)
CREATE TABLE IF NOT EXISTS audit_logs (
  log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  staff_id INT UNSIGNED NULL, staff_name VARCHAR(150), role VARCHAR(20),
  type VARCHAR(30) NOT NULL, text VARCHAR(500) NOT NULL,
  amount INT UNSIGNED NOT NULL DEFAULT 0, flag TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_time (created_at),
  CONSTRAINT fk_audit_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- shifts -> staff (xoa nhan vien thi xoa luon ca lam)
CREATE TABLE IF NOT EXISTS shifts (
  shift_id    INT AUTO_INCREMENT PRIMARY KEY,
  staff_id    INT UNSIGNED NOT NULL,
  shift_date  DATE NOT NULL,
  start_time  TIME NOT NULL,
  end_time    TIME NOT NULL,
  registered  TINYINT(1) NOT NULL DEFAULT 0,
  left_at     DATETIME NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_shift_date (shift_date),
  INDEX idx_shift_staff_date (staff_id, shift_date),
  CONSTRAINT fk_shifts_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- orders -> dining_tables, staff, customers, promotions
CREATE TABLE IF NOT EXISTS orders (
  order_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(32) NULL UNIQUE,
  table_id INT UNSIGNED NULL,
  table_name VARCHAR(80) NOT NULL,
  order_type ENUM('dinein','takeaway','delivery') NOT NULL DEFAULT 'dinein',
  customer VARCHAR(150) NULL,
  address VARCHAR(255) NULL,
  customer_id BIGINT UNSIGNED NULL,
  status ENUM('open','paid','cancelled') NOT NULL DEFAULT 'open',
  subtotal INT UNSIGNED NOT NULL DEFAULT 0,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL DEFAULT 0,
  voucher VARCHAR(80) NULL,
  promotion_id BIGINT UNSIGNED NULL,
  payment_method ENUM('cash','qr','card','ewallet','transfer') NULL,
  payment_ref VARCHAR(120) NULL,
  created_by INT UNSIGNED NULL,
  paid_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  INDEX idx_orders_status_created (status, created_at),
  INDEX idx_orders_table_status (table_id, status),
  CONSTRAINT fk_orders_table FOREIGN KEY (table_id) REFERENCES dining_tables(table_id) ON DELETE SET NULL,
  CONSTRAINT fk_orders_created_by FOREIGN KEY (created_by) REFERENCES staff(staff_id) ON DELETE SET NULL,
  CONSTRAINT fk_orders_paid_by FOREIGN KEY (paid_by) REFERENCES staff(staff_id) ON DELETE SET NULL,
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
  CONSTRAINT fk_orders_promo FOREIGN KEY (promotion_id) REFERENCES promotions(promotion_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservations (
  reservation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NULL,
  party_size SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  reserved_at DATETIME NOT NULL,
  table_id INT UNSIGNED NULL,
  note VARCHAR(500) NULL,
  status ENUM('booked','arrived','cancelled','no_show') NOT NULL DEFAULT 'booked',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reservations_time (reserved_at, status),
  CONSTRAINT fk_reservations_table FOREIGN KEY (table_id) REFERENCES dining_tables(table_id) ON DELETE SET NULL,
  CONSTRAINT fk_reservations_staff FOREIGN KEY (created_by) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS waitlist_entries (
  waitlist_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NULL,
  party_size SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('waiting','seated','cancelled') NOT NULL DEFAULT 'waiting',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  seated_at DATETIME NULL,
  INDEX idx_waitlist_status_time (status, created_at),
  CONSTRAINT fk_waitlist_staff FOREIGN KEY (created_by) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_movements (
  movement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  stock_item_id BIGINT UNSIGNED NOT NULL,
  supplier_id BIGINT UNSIGNED NULL,
  movement_type ENUM('import','export','waste','adjustment','sale','sale_rollback') NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_cost BIGINT UNSIGNED NOT NULL DEFAULT 0,
  expires_on DATE NULL,
  note VARCHAR(500) NULL,
  staff_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_stock_move_item_time (stock_item_id, created_at),
  CONSTRAINT fk_stock_move_item FOREIGN KEY (stock_item_id) REFERENCES stock_items(stock_item_id),
  CONSTRAINT fk_stock_move_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
  CONSTRAINT fk_stock_move_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_stock (
  menu_item_id INT UNSIGNED PRIMARY KEY,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  min_quantity INT UNSIGNED NOT NULL DEFAULT 10,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_menu_stock_item FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_stock_movements (
  movement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menu_item_id INT UNSIGNED NOT NULL,
  movement_type ENUM('import','adjustment','sale','sale_rollback') NOT NULL,
  quantity_delta INT NOT NULL,
  reference VARCHAR(120) NULL,
  note VARCHAR(500) NULL,
  staff_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_menu_stock_move_item_time (menu_item_id, created_at),
  CONSTRAINT fk_menu_stock_move_item FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id),
  CONSTRAINT fk_menu_stock_move_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cash_shifts (
  cash_shift_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  staff_id INT UNSIGNED NOT NULL,
  opening_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
  closing_amount BIGINT UNSIGNED NULL,
  expected_amount BIGINT UNSIGNED NULL,
  opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  INDEX idx_cash_shift_staff_open (staff_id, closed_at),
  CONSTRAINT fk_cash_shift_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- PHAN 3: BANG PHU THUOC CAP 2 (orders, shifts, cash_shifts, ...)
-- =====================================================================

CREATE TABLE IF NOT EXISTS order_items (
  order_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  menu_item_id INT UNSIGNED NULL,
  item_name VARCHAR(150) NOT NULL,
  unit_price INT UNSIGNED NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  note VARCHAR(255) NULL,
  status ENUM('draft','waiting','making','done','cancelled') NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmed_at DATETIME NULL,
  started_at DATETIME NULL,
  done_at DATETIME NULL,
  INDEX idx_order_items_order_status (order_id, status),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS delivery_orders (
  delivery_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  platform ENUM('GrabFood','ShopeeFood','BeFood') NOT NULL,
  external_code VARCHAR(100) NOT NULL,
  customer_name VARCHAR(150) NULL,
  items_summary VARCHAR(500) NULL,
  total BIGINT UNSIGNED NOT NULL,
  status ENUM('new','making','delivering','completed','cancelled') NOT NULL DEFAULT 'new',
  order_id BIGINT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  UNIQUE KEY uq_delivery_platform_code (platform, external_code),
  INDEX idx_delivery_status_time (status, created_at),
  CONSTRAINT fk_delivery_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
  CONSTRAINT fk_delivery_staff FOREIGN KEY (created_by) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_refunds (
  refund_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  amount BIGINT UNSIGNED NOT NULL,
  reason VARCHAR(500) NOT NULL,
  refunded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_refunds_order_time (order_id, created_at),
  CONSTRAINT fk_refund_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
  CONSTRAINT fk_refund_staff FOREIGN KEY (refunded_by) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_cancellations (
  cancellation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  reason VARCHAR(500) NOT NULL,
  cancelled_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cancellation_order_time (order_id, created_at),
  CONSTRAINT fk_cancellation_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
  CONSTRAINT fk_cancellation_staff FOREIGN KEY (cancelled_by) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cash_transactions (
  cash_transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cash_shift_id BIGINT UNSIGNED NULL,
  transaction_type ENUM('cash_in','cash_out','refund','correction') NOT NULL,
  amount BIGINT UNSIGNED NOT NULL,
  note VARCHAR(500) NULL,
  staff_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cash_transaction_time (created_at),
  CONSTRAINT fk_cash_transaction_shift FOREIGN KEY (cash_shift_id) REFERENCES cash_shifts(cash_shift_id) ON DELETE SET NULL,
  CONSTRAINT fk_cash_transaction_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance_records (
  attendance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shift_id INT NOT NULL,
  staff_id INT UNSIGNED NOT NULL,
  clock_in DATETIME NULL,
  clock_out DATETIME NULL,
  note VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attendance_shift_staff (shift_id, staff_id),
  CONSTRAINT fk_attendance_shift FOREIGN KEY (shift_id) REFERENCES shifts(shift_id) ON DELETE CASCADE,
  CONSTRAINT fk_attendance_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- PHAN 4: DU LIEU MAU (chay sau khi tao xong toan bo bang)
-- =====================================================================

-- 4.1 Danh muc (phai co truoc menu_items)
INSERT IGNORE INTO categories (category_id, category_name, has_options) VALUES
(1,'Cà phê',1),(2,'Trà sữa',1),(3,'Trà trái cây',1),(4,'Bánh',0);

-- 4.2 Thuc don
INSERT IGNORE INTO menu_items (item_id, category_id, item_name, description, price) VALUES
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

-- 4.3 Ban an (8 ban mac dinh)
INSERT IGNORE INTO dining_tables (table_name, is_active) VALUES
('Bàn 01',1),('Bàn 02',1),('Bàn 03',1),('Bàn 04',1),
('Bàn 05',1),('Bàn 06',1),('Bàn 07',1),('Bàn 08',1);

-- 4.4 Ton kho mon (sau khi menu_items da co du lieu)
INSERT IGNORE INTO menu_stock (menu_item_id, quantity, min_quantity)
SELECT item_id, stock, min_stock FROM menu_items;

-- 4.5 Cai dat website & trang chu
INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_label) VALUES
('brand_name', 'Góc Nhỏ', 'Tên thương hiệu quán'),
('brand_tagline', 'COFFEE & TEA • EST. 2024', 'Khẩu hiệu dưới logo'),
('hero_badge', 'CHÀO MỪNG BẠN ĐẾN VỚI GÓC NHỎ', 'Huy hiệu mở đầu Hero'),
('hero_title', 'Cà phê thơm đượm vị.', 'Tiêu đề chính Hero dòng 1'),
('hero_subtitle', 'Chuyện trò thật an yên.', 'Tiêu đề chính Hero dòng 2'),
('hero_lede', 'Một chiếc bàn thân quen ngập tràn ánh nắng, tách cà phê rang mộc chuẩn gu và những khoảng thời gian thư thái dành riêng cho bạn. Ghé Góc Nhỏ để tìm lại nhịp thở chậm giữa phố thị tất bật.', 'Đoạn giới thiệu ngắn Hero'),
('hero_suggestion', 'Cà phê sữa đá phin', 'Món gợi ý hôm nay'),
('hero_suggestion_desc', 'Phin truyền thống đậm đà, ngọt béo.', 'Mô tả ngắn món gợi ý'),
('story_title', 'Đến vì hương vị cà phê,', 'Tiêu đề câu chuyện dòng 1'),
('story_subtitle', 'ở lại vì cảm giác thân quen.', 'Tiêu đề câu chuyện dòng 2'),
('story_quote', '“Chúng tôi tin rằng ly cà phê tuyệt vời nhất là ly cà phê được thưởng thức trong sự an yên, thoải mái và trọn vẹn nhất.”', 'Trích dẫn triết lý quán'),
('story_desc', 'Góc Nhỏ được xây dựng từ niềm đam mê với những hạt cà phê Việt Nam và khát khao tạo ra một không gian dừng chân ấm áp. Từng góc bàn, chậu cây cho đến từng giai điệu lofi êm dịu đều được chăm chút kỹ lưỡng để mang lại cảm giác thân quen như trở về nhà.', 'Đoạn văn câu chuyện quán'),
('shop_address', '123 Đường Cà Phê, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', 'Địa chỉ quán'),
('shop_phone', '0901 234 567', 'Số điện thoại hotline đặt bàn'),
('shop_email', 'contact@gocnhocoffee.vn', 'Email liên hệ'),
('shop_hours', '07:00 – 22:30', 'Giờ mở cửa ngày thường'),
('shop_hours_weekend', '06:30 – 23:00', 'Giờ mở cửa cuối tuần');