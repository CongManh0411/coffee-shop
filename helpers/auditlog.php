<?php
// helpers/auditlog.php — Nhật ký thao tác (bảng audit_logs). Trước đây lưu ở trình duyệt.
// Người thực hiện và vai trò luôn lấy từ phiên đăng nhập trên máy chủ, không tin dữ liệu do trình duyệt gửi.

function auditlog_types()
{
    return array(
        'discount', 'discount_big', 'void_item', 'void_done', 'refund', 'cash_in', 'cash_out',
        'shift_open', 'shift_close', 'shift_diff', 'soldout', 'cancel_order', 'table',
    );
}

function auditlog_create($d, $me)
{
    $type = is_string($d['type'] ?? null) ? $d['type'] : '';
    if (!in_array($type, auditlog_types(), true)) return 'Loại thao tác không hợp lệ.';

    $text = is_string($d['text'] ?? null) ? trim($d['text']) : '';
    if ($text === '') return 'Thiếu nội dung nhật ký.';
    if (mb_strlen($text, 'UTF-8') > 500) $text = mb_substr($text, 0, 500, 'UTF-8');

    $amount = (int) round(abs((float) ($d['amount'] ?? 0)));
    if ($amount > 2000000000) $amount = 2000000000;
    $flag = !empty($d['flag']) ? 1 : 0;

    db()->execute(
        "INSERT INTO audit_logs (staff_id, staff_name, role, type, text, amount, flag, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        array((int) $me['staffId'], (string) $me['name'], (string) $me['roleKey'], $type, $text, $amount, $flag, date('Y-m-d H:i:s'))
    );
    return '';
}

// Điều kiện lọc nhật ký, chạy ngay trong SQL. $f: type, who (tên người), flag (true = chỉ mục cần chú ý), since (datetime 'Y-m-d H:i:s' hoặc rỗng).
function auditlog_where($f = array())
{
    $where = array();
    $params = array();
    if (!empty($f['type']) && in_array($f['type'], auditlog_types(), true)) {
        $where[] = 'type = ?';
        $params[] = $f['type'];
    }
    if (isset($f['who']) && $f['who'] !== '') {
        $where[] = 'staff_name = ?';
        $params[] = (string) $f['who'];
    }
    if (!empty($f['flag'])) {
        $where[] = 'flag = 1';
    }
    if (!empty($f['since'])) {
        $where[] = 'created_at >= ?';
        $params[] = (string) $f['since'];
    }
    return array($where ? 'WHERE ' . implode(' AND ', $where) : '', $params);
}

function auditlog_count($f = array())
{
    list($whereSql, $params) = auditlog_where($f);
    $row = db()->fetchOne('SELECT COUNT(*) AS n FROM audit_logs ' . $whereSql, $params);
    return (int) ($row['n'] ?? 0);
}

// Tên người thực hiện (cho ô lọc), không phụ thuộc bộ lọc hiện tại.
function auditlog_actor_names()
{
    $rows = db()->fetchAll("SELECT DISTINCT staff_name FROM audit_logs WHERE staff_name IS NOT NULL AND staff_name <> '' ORDER BY staff_name");
    return array_map(function ($r) { return $r['staff_name']; }, $rows);
}

// Một trang nhật ký, mới -> cũ, đã lọc trong SQL.
function auditlog_page($f, $limit, $offset)
{
    list($whereSql, $params) = auditlog_where($f);
    $params[] = max(1, min(500, (int) $limit));
    $params[] = max(0, (int) $offset);
    $rows = db()->fetchAll(
        "SELECT log_id, staff_id, staff_name, role, type, text, amount, flag, created_at
         FROM audit_logs " . $whereSql . " ORDER BY log_id DESC LIMIT ? OFFSET ?",
        $params
    );
    return array_map(function ($r) {
        return array(
            'id'        => (int) $r['log_id'],
            'time'      => date('c', strtotime($r['created_at'])),
            'actorId'   => $r['staff_id'] === null ? null : (int) $r['staff_id'],
            'actorName' => $r['staff_name'] ?? '',
            'role'      => $r['role'] ?? '',
            'type'      => $r['type'],
            'text'      => $r['text'],
            'amount'    => (int) $r['amount'],
            'flag'      => (int) $r['flag'] === 1,
        );
    }, $rows);
}
