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

// 3000 dòng mới nhất, cũ -> mới (giống thứ tự mảng cũ trong trình duyệt).
function auditlog_list()
{
    $rows = db()->fetchAll(
        "SELECT log_id, staff_id, staff_name, role, type, text, amount, flag, created_at
         FROM audit_logs ORDER BY log_id DESC LIMIT 3000"
    );
    $rows = array_reverse($rows);
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
