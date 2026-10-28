<?php
// Shared business functions used by Public, Admin, and API routes.
require_once __DIR__ . '/api/modules/auth.php';
require_once __DIR__ . '/api/modules/staff.php';
require_once __DIR__ . '/api/modules/menu.php';
require_once __DIR__ . '/api/modules/tables.php';
require_once __DIR__ . '/api/modules/shifts.php';
require_once __DIR__ . '/api/modules/orders.php';
require_once __DIR__ . '/api/modules/cashshift.php';
require_once __DIR__ . '/api/modules/reservations.php';
require_once __DIR__ . '/api/modules/ops.php';
require_once __DIR__ . '/api/modules/warehouse.php';

// Lấy toàn bộ món đang hiển thị để đưa vào thực đơn trên trang chủ.
function public_menu_items($categoryId = 0)
{
    $sql = "SELECT m.item_id, m.category_id, m.item_name, m.price, m.image, m.description, c.category_name
            FROM menu_items m
            JOIN categories c ON c.category_id = m.category_id
            WHERE m.is_hidden = 0";
    if ((int) $categoryId > 0) {
        return db()->fetchAll($sql . " AND m.category_id = ? ORDER BY m.item_id ASC", array((int) $categoryId));
    }
    return db()->fetchAll($sql . " ORDER BY m.item_id ASC");
}

function public_featured_menu_items($limit = 6)
{
    $items = public_menu_items();
    $byCategory = array();
    foreach ($items as $item) {
        $categoryId = (int) $item['category_id'];
        if (!isset($byCategory[$categoryId])) {
            $byCategory[$categoryId] = array();
        }
        $byCategory[$categoryId][] = $item;
    }

    $featured = array();
    for ($round = 0; count($featured) < max(0, (int) $limit); $round++) {
        $addedInRound = false;
        foreach ($byCategory as $categoryItems) {
            if (isset($categoryItems[$round])) {
                $featured[] = $categoryItems[$round];
                $addedInRound = true;
                if (count($featured) >= (int) $limit) {
                    break;
                }
            }
        }
        if (!$addedInRound) {
            break;
        }
    }
    return $featured;
}

function admin_menu_items()
{
    return db()->fetchAll(
        "SELECT m.item_id, m.item_name, m.description, m.price, m.image, m.is_hidden,
                c.category_id, c.category_name
         FROM menu_items m
         JOIN categories c ON c.category_id = m.category_id
         ORDER BY c.category_name, m.item_name"
    );
}

function public_menu_category_summary()
{
    return db()->fetchAll(
        "SELECT c.category_id, c.category_name, COUNT(m.item_id) AS item_count,
                MIN(m.price) AS min_price
         FROM categories c
         LEFT JOIN menu_items m ON m.category_id = c.category_id AND m.is_hidden = 0
         GROUP BY c.category_id, c.category_name
         ORDER BY c.category_id"
    );
}

function admin_shifts()
{
    return db()->fetchAll(
        "SELECT s.shift_id, s.shift_date, s.start_time, s.end_time,
                st.staff_id, st.full_name, st.role
         FROM shifts s
         JOIN staff st ON st.staff_id = s.staff_id
         ORDER BY s.shift_date, s.start_time, st.full_name"
    );
}

function admin_tables()
{
    return db()->fetchAll(
        "SELECT table_id, table_name, is_active
         FROM dining_tables
         ORDER BY table_id"
    );
}

function get_menu_item($id)
{
    return db()->fetchOne(
        "SELECT m.item_id, m.item_name, m.description, m.price, m.image,
                m.is_hidden, c.category_id, c.category_name
         FROM menu_items m
         JOIN categories c ON c.category_id = m.category_id
         WHERE m.item_id = ? AND m.is_hidden = 0",
        array((int) $id)
    );
}

function public_related_menu_items($categoryId, $exceptId, $limit = 3)
{
    return db()->fetchAll(
        "SELECT m.item_id, m.item_name, m.price, m.image, m.description,
                c.category_name, c.category_id
         FROM menu_items m
         JOIN categories c ON c.category_id = m.category_id
         WHERE m.is_hidden = 0 AND m.category_id = ? AND m.item_id <> ?
         ORDER BY m.item_id ASC
         LIMIT ?",
        array((int) $categoryId, (int) $exceptId, max(1, min(6, (int) $limit)))
    );
}

function public_menu_categories()
{
    return db()->fetchAll(
        "SELECT c.category_id, c.category_name
         FROM categories c
         JOIN menu_items m ON m.category_id = c.category_id
         WHERE m.is_hidden = 0
         GROUP BY c.category_id, c.category_name
         ORDER BY c.category_id"
    );
}

// =====================================================================
// BT03 - Bàn (tables) và Lịch ca (schedule): hàm nghiệp vụ cho Public + Admin.
// Mỗi hàm quyết định câu SQL, class Database (db()) chỉ thực thi.
// Giá trị đến từ GET/POST luôn đi qua dấu ? (Prepared Statement).
// =====================================================================

function role_label($role)
{
    $labels = array('manager' => 'Quản lý', 'waiter' => 'Phục vụ', 'kitchen' => 'Bếp', 'cashier' => 'Thu ngân');
    return isset($labels[$role]) ? $labels[$role] : (string) $role;
}

// Gắn trạng thái cho từng bàn; nếu một bàn có nhiều đơn mở thì chỉ giữ đơn mới nhất.
function tables_status_rows($rows)
{
    $byTable = array();
    foreach ($rows as $row) {
        $id = (int) $row['table_id'];
        if (!isset($byTable[$id]) || (int) $row['order_id'] > (int) $byTable[$id]['order_id']) {
            $byTable[$id] = $row;
        }
    }
    $out = array();
    foreach ($byTable as $row) {
        $row['status'] = $row['order_id'] ? 'occupied' : 'empty';
        $out[] = $row;
    }
    return $out;
}

// Public: sơ đồ bàn đang hoạt động, JOIN dining_tables với orders để biết bàn nào đang có đơn mở.
function public_tables_with_status()
{
    $rows = db()->fetchAll(
        "SELECT t.table_id, t.table_name, t.is_active,
                o.order_id, o.order_code, o.total, o.created_at
         FROM dining_tables t
         LEFT JOIN orders o ON o.table_id = t.table_id AND o.status = ? AND o.order_type = ?
         WHERE t.is_active = 1
         ORDER BY t.table_id",
        array('open', 'dinein')
    );
    return tables_status_rows($rows);
}

// Admin: toàn bộ bàn (kể cả ngừng hoạt động) kèm trạng thái phục vụ.
function admin_tables_with_status()
{
    $rows = db()->fetchAll(
        "SELECT t.table_id, t.table_name, t.is_active,
                o.order_id, o.order_code, o.total, o.created_at
         FROM dining_tables t
         LEFT JOIN orders o ON o.table_id = t.table_id AND o.status = ? AND o.order_type = ?
         ORDER BY t.table_id",
        array('open', 'dinein')
    );
    return tables_status_rows($rows);
}

function get_dining_table($id)
{
    return db()->fetchOne(
        "SELECT table_id, table_name, is_active FROM dining_tables WHERE table_id = ?",
        array((int) $id)
    );
}

// ---------------------------- Lịch ca ----------------------------

// Nhận chuỗi YYYY-MM-DD (từ GET), trả về ngày Thứ Hai của tuần đó; sai định dạng thì lấy tuần hiện tại.
function schedule_week_start($input = null)
{
    $date = false;
    if (is_string($input) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
        $date = DateTime::createFromFormat('!Y-m-d', $input);
        $errors = DateTime::getLastErrors();
        if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            $date = false;
        }
    }
    if (!$date) {
        $date = new DateTime('today');
    }
    $date->modify('-' . ((int) $date->format('N') - 1) . ' days');
    return $date->format('Y-m-d');
}

function schedule_add_days($date, $days)
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    $d->modify(((int) $days >= 0 ? '+' : '-') . abs((int) $days) . ' days');
    return $d->format('Y-m-d');
}

function schedule_minutes($start, $end)
{
    return max(0, (int) round((strtotime('1970-01-01 ' . $end) - strtotime('1970-01-01 ' . $start)) / 60));
}

function schedule_clock($time)
{
    return substr((string) $time, 0, 5);
}

function schedule_duration($minutes)
{
    $minutes = max(0, (int) $minutes);
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h && $m) return $h . 'h' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    if ($h) return $h . 'h';
    return $m . ' phút';
}

// Trạng thái một ca: future | upcoming | working | done | left | noshow | absent
function schedule_shift_state($shift, $date, $nowTs = null)
{
    $nowTs = $nowTs ? $nowTs : time();
    $today = date('Y-m-d', $nowTs);
    $left = !empty($shift['left_at']);
    $registered = !empty($shift['registered']);
    if ($date > $today) return 'future';
    if ($date < $today) return $left ? 'left' : ($registered ? 'done' : 'absent');
    if ($left) return 'left';
    $now = date('H:i:s', $nowTs);
    if ($now < $shift['start_time']) return 'upcoming';
    if ($now >= $shift['end_time']) return $registered ? 'done' : 'absent';
    return $registered ? 'working' : 'noshow';
}

function schedule_state_text($state, $shift)
{
    switch ($state) {
        case 'working': return 'Đang làm';
        case 'done': return 'Đã làm xong';
        case 'left': return 'Nghỉ sớm lúc ' . date('H:i', strtotime($shift['left_at']));
        case 'upcoming': return 'Chưa tới ca';
        case 'noshow': return 'Chưa vào ca (đã trễ)';
        case 'absent': return 'Không có chấm công';
        default: return '';
    }
}

// Public: mọi nhân viên đang làm việc + ca của họ trong khoảng [$from, $to] (JOIN staff với shifts).
function public_schedule_week($from, $to)
{
    return db()->fetchAll(
        "SELECT st.staff_id, st.full_name, st.role,
                s.shift_id, s.shift_date, s.start_time, s.end_time, s.registered, s.left_at
         FROM staff st
         LEFT JOIN shifts s ON s.staff_id = st.staff_id AND s.shift_date BETWEEN ? AND ?
         WHERE st.status = 1
         ORDER BY st.full_name, s.shift_date, s.start_time",
        array($from, $to)
    );
}

// Gom các dòng của public_schedule_week() theo nhân viên và theo ngày.
function schedule_group_week($rows)
{
    $staff = array();
    foreach ($rows as $r) {
        $id = (int) $r['staff_id'];
        if (!isset($staff[$id])) {
            $staff[$id] = array(
                'staff_id' => $id, 'full_name' => $r['full_name'], 'role' => $r['role'],
                'shifts' => array(), 'minutes' => 0,
            );
        }
        if ($r['shift_id']) {
            $staff[$id]['shifts'][$r['shift_date']][] = $r;
            $staff[$id]['minutes'] += schedule_minutes($r['start_time'], $r['end_time']);
        }
    }
    return $staff;
}

// Public: các ca của một ngày (hộp "Đang trong ca / Chưa vào ca").
function public_shifts_on($date)
{
    return db()->fetchAll(
        "SELECT s.shift_id, s.start_time, s.end_time, s.registered, s.left_at,
                st.staff_id, st.full_name, st.role
         FROM shifts s
         JOIN staff st ON st.staff_id = s.staff_id
         WHERE s.shift_date = ? AND st.status = 1
         ORDER BY s.start_time, st.full_name",
        array($date)
    );
}

// Public: ca kế tiếp của một nhân viên tính từ thời điểm hiện tại.
function public_next_shift($staffId, $date, $time)
{
    return db()->fetchOne(
        "SELECT shift_date, start_time, end_time
         FROM shifts
         WHERE staff_id = ? AND (shift_date > ? OR (shift_date = ? AND end_time > ?))
         ORDER BY shift_date, start_time
         LIMIT 1",
        array((int) $staffId, $date, $date, $time)
    );
}

// Admin: các ca từ ngày $fromDate trở đi (JOIN shifts với staff).
function admin_shifts_upcoming($fromDate, $limit = 50)
{
    return db()->fetchAll(
        "SELECT s.shift_id, s.shift_date, s.start_time, s.end_time, s.registered,
                st.staff_id, st.full_name, st.role
         FROM shifts s
         JOIN staff st ON st.staff_id = s.staff_id
         WHERE s.shift_date >= ?
         ORDER BY s.shift_date, s.start_time, st.full_name
         LIMIT ?",
        array($fromDate, max(1, min(200, (int) $limit)))
    );
}

// =====================================================================
// BT03 - Doanh thu (revenue), Lịch sử hóa đơn (history) và Bếp (kitchen).
// Mỗi hàm quyết định câu SQL, class Database (db()) chỉ thực thi.
// Giá trị đến từ GET/POST luôn đi qua dấu ? (Prepared Statement).
// =====================================================================

// Kiểm tra token CSRF của form POST (dùng chung cho Lịch sử và Bếp).
function post_csrf_ok()
{
    $sent = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    return $sent !== '' && hash_equals(csrf_token(), $sent);
}

function is_valid_ymd($value)
{
    if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) return false;
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

function payment_method_label($method)
{
    if ($method === 'card') return 'Thẻ ATM';
    if (in_array($method, array('qr', 'ewallet', 'transfer'), true)) return 'Chuyển khoản';
    return 'Tiền mặt';
}

function order_channel_label($orderType)
{
    if ($orderType === 'takeaway') return 'Mang đi';
    if ($orderType === 'delivery') return 'Giao hàng';
    return 'Tại bàn';
}

// ---------------------------- Doanh thu ----------------------------

// Từ ?period=...&from=...&to=... trả về khoảng ngày cần xem; sai dữ liệu thì lấy "Hôm nay" và báo lỗi.
function revenue_period_range($period, $from = '', $to = '')
{
    $today = date('Y-m-d');
    $error = '';
    $label = 'Doanh thu hôm nay';
    $start = $today;
    $end = $today;
    $monthNow = (int) date('n');
    $yearNow = (int) date('Y');

    switch ($period) {
        case 'week':
            $start = schedule_week_start(null);
            $end = schedule_add_days($start, 6);
            $label = 'Doanh thu tuần này';
            break;
        case 'month':
            $start = date('Y-m-01');
            $end = date('Y-m-t');
            $label = 'Doanh thu tháng này';
            break;
        case 'quarter':
            $q = intdiv($monthNow - 1, 3);
            $start = sprintf('%04d-%02d-01', $yearNow, $q * 3 + 1);
            $end = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $yearNow, $q * 3 + 3)));
            $label = 'Doanh thu quý ' . ($q + 1);
            break;
        case 'year':
            $start = $yearNow . '-01-01';
            $end = $yearNow . '-12-31';
            $label = 'Doanh thu năm nay';
            break;
        case 'custom':
            if (is_valid_ymd($from) && is_valid_ymd($to) && $from <= $to) {
                $start = $from;
                $end = $to;
                $label = 'Doanh thu ' . $from . ' → ' . $to;
            } else {
                $error = 'Khoảng ngày không hợp lệ (cần định dạng YYYY-MM-DD và ngày bắt đầu không sau ngày kết thúc).';
            }
            break;
        default:
            $period = 'today';
    }
    if ($error !== '') {
        $period = 'custom';
    }
    return array(
        'period' => $period, 'from' => $start, 'to' => $end,
        'start' => $start . ' 00:00:00', 'end' => $end . ' 23:59:59',
        'label' => $label, 'error' => $error,
    );
}

// Admin: số hóa đơn + tổng tiền theo trạng thái ('paid' = hợp lệ, 'cancelled' = đã hủy/hoàn) trong khoảng thời gian.
function admin_revenue_totals($status, $start, $end)
{
    $row = db()->fetchOne(
        "SELECT COUNT(*) AS invoice_count, COALESCE(SUM(total), 0) AS total
         FROM orders
         WHERE status = ? AND subtotal > 0 AND paid_at BETWEEN ? AND ?",
        array($status === 'cancelled' ? 'cancelled' : 'paid', $start, $end)
    );
    return array('count' => (int) $row['invoice_count'], 'total' => (int) $row['total']);
}

// Admin: doanh thu từng ngày, tách theo phương thức thanh toán.
function admin_revenue_daily($start, $end)
{
    return db()->fetchAll(
        "SELECT DATE(paid_at) AS day, COUNT(*) AS invoice_count,
                SUM(CASE WHEN payment_method IS NULL OR payment_method = 'cash' THEN total ELSE 0 END) AS cash,
                SUM(CASE WHEN payment_method IN ('qr','ewallet','transfer') THEN total ELSE 0 END) AS transfer,
                SUM(CASE WHEN payment_method = 'card' THEN total ELSE 0 END) AS card,
                SUM(total) AS total
         FROM orders
         WHERE status = 'paid' AND subtotal > 0 AND paid_at BETWEEN ? AND ?
         GROUP BY DATE(paid_at)
         ORDER BY day DESC",
        array($start, $end)
    );
}

// Admin: doanh thu theo giờ trong ngày (biểu đồ cột).
function admin_revenue_by_hour($start, $end)
{
    return db()->fetchAll(
        "SELECT HOUR(paid_at) AS hour_of_day, SUM(total) AS total
         FROM orders
         WHERE status = 'paid' AND subtotal > 0 AND paid_at BETWEEN ? AND ?
         GROUP BY HOUR(paid_at)",
        array($start, $end)
    );
}

// Admin: top món bán chạy — JOIN order_items với orders.
function admin_revenue_top_items($start, $end, $limit = 5)
{
    return db()->fetchAll(
        "SELECT oi.item_name, SUM(oi.quantity) AS qty
         FROM order_items oi
         JOIN orders o ON o.order_id = oi.order_id
         WHERE o.status = 'paid' AND o.subtotal > 0 AND oi.status <> 'cancelled'
           AND o.paid_at BETWEEN ? AND ?
         GROUP BY oi.item_name
         ORDER BY qty DESC, oi.item_name
         LIMIT ?",
        array($start, $end, max(1, min(20, (int) $limit)))
    );
}

// Admin: doanh thu theo kênh bán (tại bàn / mang đi / giao hàng).
function admin_revenue_by_channel($start, $end)
{
    return db()->fetchAll(
        "SELECT order_type, SUM(total) AS total
         FROM orders
         WHERE status = 'paid' AND subtotal > 0 AND paid_at BETWEEN ? AND ?
         GROUP BY order_type
         ORDER BY total DESC",
        array($start, $end)
    );
}

// Admin: doanh thu từng ngày kể từ $fromDate (dùng so sánh 7 ngày gần nhất với 7 ngày trước đó).
function admin_revenue_per_day_since($fromDate)
{
    return db()->fetchAll(
        "SELECT DATE(paid_at) AS day, SUM(total) AS total
         FROM orders
         WHERE status = 'paid' AND subtotal > 0 AND paid_at >= ?
         GROUP BY DATE(paid_at)",
        array($fromDate . ' 00:00:00')
    );
}

// ---------------------------- Lịch sử hóa đơn ----------------------------

// Admin: danh sách hóa đơn (JOIN orders với staff, order_cancellations, order_refunds).
// $status: all | active | cancelled. $keyword tìm theo mã HĐ hoặc tên bàn.
function admin_invoices($status = 'all', $keyword = '', $start = null, $end = null, $limit = 300)
{
    $where = array("o.status IN ('paid','cancelled')", 'o.subtotal > 0');
    $params = array();
    if ($status === 'active') {
        $where[] = "o.status = 'paid'";
    } elseif ($status === 'cancelled') {
        $where[] = "o.status = 'cancelled'";
    }
    $keyword = trim((string) $keyword);
    if ($keyword !== '') {
        $like = '%' . addcslashes($keyword, '%_\\') . '%';
        $where[] = '(o.order_code LIKE ? OR o.table_name LIKE ?)';
        $params[] = $like;
        $params[] = $like;
    }
    if ($start !== null && $end !== null) {
        $where[] = 'o.paid_at BETWEEN ? AND ?';
        $params[] = $start;
        $params[] = $end;
    }
    $params[] = max(1, min(1000, (int) $limit));
    return db()->fetchAll(
        "SELECT o.order_id, o.order_code, o.table_name, o.order_type, o.subtotal, o.discount, o.total,
                o.payment_method, o.status, o.paid_at,
                s.full_name AS cashier_name,
                c.reason AS cancel_reason, c.created_at AS cancel_at, cs.full_name AS cancel_by,
                r.reason AS refund_reason, r.created_at AS refund_at, rs.full_name AS refund_by,
                (SELECT GROUP_CONCAT(CONCAT(oi.item_name, ' x', oi.quantity) ORDER BY oi.order_item_id SEPARATOR ', ')
                 FROM order_items oi WHERE oi.order_id = o.order_id AND oi.status <> 'cancelled') AS items_summary
         FROM orders o
         LEFT JOIN staff s ON s.staff_id = o.paid_by
         LEFT JOIN order_cancellations c ON c.cancellation_id = (
             SELECT MAX(c2.cancellation_id) FROM order_cancellations c2 WHERE c2.order_id = o.order_id)
         LEFT JOIN staff cs ON cs.staff_id = c.cancelled_by
         LEFT JOIN order_refunds r ON r.refund_id = (
             SELECT MAX(r2.refund_id) FROM order_refunds r2 WHERE r2.order_id = o.order_id)
         LEFT JOIN staff rs ON rs.staff_id = r.refunded_by
         WHERE " . implode(' AND ', $where) . "
         ORDER BY o.paid_at DESC, o.order_id DESC
         LIMIT ?",
        $params
    );
}

// Admin: một hóa đơn theo order_id (null nếu không có / chưa thanh toán).
function admin_invoice($orderId)
{
    $rows = db()->fetchAll(
        "SELECT o.order_id, o.order_code, o.table_name, o.total, o.payment_method, o.status, o.paid_at,
                c.reason AS cancel_reason, c.created_at AS cancel_at, cs.full_name AS cancel_by,
                r.reason AS refund_reason, r.created_at AS refund_at, rs.full_name AS refund_by
         FROM orders o
         LEFT JOIN order_cancellations c ON c.cancellation_id = (
             SELECT MAX(c2.cancellation_id) FROM order_cancellations c2 WHERE c2.order_id = o.order_id)
         LEFT JOIN staff cs ON cs.staff_id = c.cancelled_by
         LEFT JOIN order_refunds r ON r.refund_id = (
             SELECT MAX(r2.refund_id) FROM order_refunds r2 WHERE r2.order_id = o.order_id)
         LEFT JOIN staff rs ON rs.staff_id = r.refunded_by
         WHERE o.order_id = ? AND o.status IN ('paid','cancelled') AND o.subtotal > 0",
        array((int) $orderId)
    );
    return $rows ? $rows[0] : null;
}

function admin_invoice_items($orderId)
{
    return db()->fetchAll(
        "SELECT item_name, quantity, unit_price
         FROM order_items
         WHERE order_id = ? AND status <> 'cancelled'
         ORDER BY order_item_id",
        array((int) $orderId)
    );
}

// Admin: hủy hóa đơn hoặc hoàn tiền (UPDATE orders + INSERT order_cancellations / order_refunds). Trả '' nếu ổn.
function invoice_cancel_or_refund($orderId, $mode, $reason, $user)
{
    $orderId = (int) $orderId;
    $reason = trim((string) $reason);
    if ($orderId <= 0) return 'Hóa đơn không hợp lệ.';
    if ($reason === '') return 'Vui lòng nhập lý do.';
    if (function_exists('mb_strlen') ? mb_strlen($reason, 'UTF-8') > 500 : strlen($reason) > 500) return 'Lý do tối đa 500 ký tự.';
    if ($mode === 'refund') return orders_refund_paid($orderId, $reason, $user);
    if ($mode === 'cancel') return orders_cancel_paid($orderId, $reason, $user);
    return 'Thao tác không hợp lệ.';
}

// ---------------------------- Bếp ----------------------------

// Bếp: các món đã gửi bếp của đơn đang mở (JOIN order_items với orders). Cột "Đã xong" chỉ giữ 30 phút gần nhất.
// Thời gian chờ tính bằng giây ngay trong SQL để không lệch múi giờ giữa PHP và MySQL.
function kitchen_items($doneKeepSeconds = 1800)
{
    return db()->fetchAll(
        "SELECT oi.order_item_id, oi.order_id, oi.item_name, oi.quantity, oi.note, oi.status,
                o.table_id, o.table_name,
                COALESCE(TIMESTAMPDIFF(SECOND, oi.confirmed_at, NOW()), 0) AS since_sec,
                COALESCE(TIMESTAMPDIFF(SECOND, oi.started_at, NOW()), 0) AS making_sec,
                COALESCE(TIMESTAMPDIFF(SECOND, oi.done_at, NOW()), 0) AS done_sec,
                oi.confirmed_at
         FROM order_items oi
         JOIN orders o ON o.order_id = oi.order_id
         WHERE o.status = ? AND oi.status IN ('waiting','making','done')
           AND (oi.status <> 'done' OR oi.done_at IS NULL OR oi.done_at >= DATE_SUB(NOW(), INTERVAL ? SECOND))
         ORDER BY oi.confirmed_at, oi.order_item_id",
        array('open', max(60, (int) $doneKeepSeconds))
    );
}

// Gom các món cùng bàn + cùng lúc gửi thành 1 phiếu, chia 3 cột waiting | making | done.
function kitchen_group_tickets($rows)
{
    $tickets = array('waiting' => array(), 'making' => array(), 'done' => array());
    $items = array('waiting' => 0, 'making' => 0, 'done' => 0);
    foreach ($rows as $row) {
        $status = $row['status'];
        $tableKey = $row['table_id'] === null ? -((int) $row['order_id']) : (int) $row['table_id'];
        $key = $tableKey . ':' . $row['confirmed_at'];
        if (!isset($tickets[$status][$key])) {
            $tickets[$status][$key] = array(
                'key' => $key, 'table_name' => $row['table_name'],
                'since_sec' => (int) $row['since_sec'], 'done_sec' => (int) $row['done_sec'], 'items' => array(),
            );
        }
        $tickets[$status][$key]['items'][] = $row;
        $tickets[$status][$key]['done_sec'] = min($tickets[$status][$key]['done_sec'], (int) $row['done_sec']);
        $items[$status]++;
    }
    foreach ($tickets as $status => $list) {
        $list = array_values($list);
        usort($list, function ($a, $b) use ($status) {
            return $status === 'done' ? $a['done_sec'] - $b['done_sec'] : $b['since_sec'] - $a['since_sec'];
        });
        $tickets[$status] = $list;
    }
    return array('tickets' => $tickets, 'counts' => $items);
}

function kitchen_urgency($seconds)
{
    if ($seconds > 300) return 'late';
    if ($seconds > 180) return 'warn';
    return '';
}

function kitchen_format_elapsed($seconds)
{
    $m = intdiv(max(0, (int) $seconds), 60);
    if ($m < 1) return 'vừa gửi';
    if ($m < 60) return $m . ' phút';
    return intdiv($m, 60) . 'h' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT);
}

function kitchen_format_mmss($seconds)
{
    $s = max(0, (int) $seconds);
    return intdiv($s, 60) . ':' . str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT);
}

// Bếp: đổi trạng thái nhiều món (UPDATE order_items). Trả '' nếu ổn, ngược lại là thông báo lỗi.
function kitchen_set_status($ids, $status)
{
    if (!in_array($status, array('waiting', 'making', 'done'), true)) return 'Trạng thái món không hợp lệ.';
    $clean = array();
    foreach ((array) $ids as $id) {
        $id = (int) $id;
        if ($id > 0) $clean[$id] = $id;
    }
    if (!$clean || count($clean) > 50) return 'Chưa chọn món hợp lệ.';
    foreach ($clean as $id) {
        $error = orders_kitchen_status(array('itemId' => $id, 'status' => $status));
        if ($error !== '') return $error;
    }
    return '';
}
