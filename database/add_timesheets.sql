-- Chạy MỘT LẦN trên database coffee_shop đã có sẵn (bản mới của coffee.sql đã gồm bảng này).
USE coffee_shop;
CREATE TABLE IF NOT EXISTS timesheets (
  timesheet_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  staff_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  clock_in DATETIME NOT NULL,
  clock_out DATETIME NULL,
  UNIQUE KEY uq_timesheet_staff_date (staff_id, work_date),
  CONSTRAINT fk_timesheet_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

