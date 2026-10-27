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
