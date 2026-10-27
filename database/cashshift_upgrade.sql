-- ============================================================
-- Ca & Quỹ tiền mặt lưu trong database (Thu ngân)
-- Chạy được nhiều lần (MariaDB / XAMPP). Bản đã cài từ trước: nhập riêng file này.
-- ============================================================
ALTER TABLE cash_shifts
  ADD COLUMN IF NOT EXISTS closed_by INT UNSIGNED NULL AFTER closed_at,
  ADD COLUMN IF NOT EXISTS difference BIGINT NULL AFTER closed_by,
  ADD COLUMN IF NOT EXISTS diff_reason VARCHAR(500) NULL AFTER difference,
  ADD COLUMN IF NOT EXISTS summary_json TEXT NULL AFTER diff_reason;

ALTER TABLE cash_transactions
  ADD COLUMN IF NOT EXISTS category VARCHAR(80) NULL AFTER transaction_type;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS cash_shift_id BIGINT UNSIGNED NULL AFTER paid_by,
  ADD INDEX IF NOT EXISTS idx_orders_cash_shift (cash_shift_id),
  ADD FOREIGN KEY IF NOT EXISTS fk_orders_cash_shift (cash_shift_id) REFERENCES cash_shifts(cash_shift_id) ON DELETE SET NULL;
