<?php
// api/modules/cashshift.php — Ca & Quỹ tiền mặt của Thu ngân (bảng cash_shifts, cash_transactions).
// Hóa đơn được gắn vào ca qua orders.cash_shift_id (gán lúc thanh toán trong orders_pay).

function cashshift_len($text)
{
    return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

function cashshift_method_key($method)
{
    if ($method === 'cash') return 'cash';
    if ($method === 'card') return 'card';
    if ($method === 'ewallet') return 'ewallet';
    return 'qr'; // qr + transfer
}

function cashshift_iso($value)
{
    return $value ? date('c', strtotime($value)) : null;
}

function cashshift_empty_stats()
{
    return array(
        'orders' => 0, 'revenue' => 0, 'discount' => 0,
        'by' => array('cash' => 0, 'qr' => 0, 'card' => 0, 'ewallet' => 0),
        'byType' => array('dinein' => 0, 'takeaway' => 0, 'delivery' => 0),
        'cancelledCount' => 0, 'cancelledTotal' => 0,
    );
}

// Thống kê 1 ca. $staffId != null: chỉ tính hóa đơn do người đó thu (mục "Doanh thu của bạn").
function cashshift_stats($shiftId, $openingCash, $moves, $staffId = null)
{
    $rows = db()->fetchAll(
        "SELECT order_type, payment_method, paid_by, status, total, discount
         FROM orders WHERE cash_shift_id = ? AND status IN ('paid', 'cancelled')",
        array((int) $shiftId)
    );
    $s = cashshift_empty_stats();
    $cashSales = 0;
    foreach ($rows as $r) {
        $total = (int) $r['total'];
        $key = cashshift_method_key($r['payment_method']);
        if ($r['status'] === 'paid' && $key === 'cash') $cashSales += $total; // tiền két tính cả ca
        if ($staffId !== null && (int) $r['paid_by'] !== (int) $staffId) continue;
        if ($r['status'] === 'cancelled') {
            $s['cancelledCount']++;
            $s['cancelledTotal'] += $total;
            continue;
        }
        $s['orders']++;
        $s['revenue'] += $total;
        $s['discount'] += (int) $r['discount'];
        $s['by'][$key] += $total;
        $s['byType'][$r['order_type']] = ($s['byType'][$r['order_type']] ?? 0) + 1;
    }
    $cashIn = 0;
    $cashOut = 0;
    foreach ($moves as $m) {
        if ($m['kind'] === 'in') $cashIn += $m['amount']; else $cashOut += $m['amount'];
    }
    $s['cashSales'] = $cashSales;
    $s['cashIn'] = $cashIn;
    $s['cashOut'] = $cashOut;
    $s['expectedCash'] = (int) $openingCash + $cashSales + $cashIn - $cashOut;
    return $s;
}

function cashshift_moves($shiftId)
{
    $rows = db()->fetchAll(
        "SELECT t.cash_transaction_id, t.transaction_type, t.category, t.amount, t.note, t.created_at, s.full_name
         FROM cash_transactions t LEFT JOIN staff s ON s.staff_id = t.staff_id
         WHERE t.cash_shift_id = ? AND t.transaction_type IN ('cash_in', 'cash_out')
         ORDER BY t.cash_transaction_id",
        array((int) $shiftId)
    );
    return array_map(function ($r) {
        return array(
            'id' => 'M' . $r['cash_transaction_id'],
            'time' => cashshift_iso($r['created_at']),
            'kind' => $r['transaction_type'] === 'cash_in' ? 'in' : 'out',
            'category' => $r['category'] ?? '',
            'amount' => (int) $r['amount'],
            'note' => $r['note'] ?? '',
            'by' => $r['full_name'] ?? '',
        );
    }, $rows);
}

// Chuyển 1 dòng cash_shifts sang đúng cấu trúc mà cashier-core.js / shift.js đang dùng.
function cashshift_shape($row, $viewerId = null)
{
    $moves = cashshift_moves($row['cash_shift_id']);
    $opening = (int) $row['opening_amount'];
    $shift = array(
        'id' => (string) $row['cash_shift_id'],
        'cashierId' => (int) $row['staff_id'],
        'cashierName' => $row['cashier_name'] ?? '',
        'openedAt' => cashshift_iso($row['opened_at']),
        'openingCash' => $opening,
        'movements' => $moves,
    );
    if ($row['closed_at'] === null) {
        $shift['summary'] = cashshift_stats($row['cash_shift_id'], $opening, $moves);
        if ($viewerId !== null) {
            $shift['mine'] = cashshift_stats($row['cash_shift_id'], $opening, $moves, $viewerId);
        }
        return $shift;
    }
    $summary = $row['summary_json'] ? json_decode($row['summary_json'], true) : null;
    if (!is_array($summary)) $summary = cashshift_stats($row['cash_shift_id'], $opening, $moves);
    $shift['closedAt'] = cashshift_iso($row['closed_at']);
    $shift['closedById'] = $row['closed_by'] === null ? null : (int) $row['closed_by'];
    $shift['closedBy'] = $row['closer_name'] ?? $shift['cashierName'];
    $shift['countedCash'] = (int) $row['closing_amount'];
    $shift['expectedCash'] = (int) $row['expected_amount'];
    $shift['diff'] = (int) $row['difference'];
    $shift['reason'] = $row['diff_reason'] ?? '';
    $shift['summary'] = $summary;
    return $shift;
}

function cashshift_select($where, $params, $tail = '')
{
    return db()->fetchAll(
        "SELECT c.*, s.full_name AS cashier_name, k.full_name AS closer_name
         FROM cash_shifts c
         LEFT JOIN staff s ON s.staff_id = c.staff_id
         LEFT JOIN staff k ON k.staff_id = c.closed_by
         WHERE $where $tail",
        $params
    );
}

function cashshift_open_row()
{
    $rows = cashshift_select('c.closed_at IS NULL', array(), 'ORDER BY c.cash_shift_id DESC LIMIT 1');
    return $rows ? $rows[0] : null;
}

function cashshift_snapshot($user)
{
    $current = cashshift_open_row();
    $history = cashshift_select('c.closed_at IS NOT NULL', array(), 'ORDER BY c.cash_shift_id DESC LIMIT 50');
    $history = array_reverse($history); // cũ -> mới, giống cách JS đang dùng
    return array(
        'current' => $current ? cashshift_shape($current, (int) $user['staffId']) : null,
        'history' => array_map('cashshift_shape', $history),
    );
}

function cashshift_open($data, $user)
{
    $opening = $data['openingCash'] ?? null;
    if (!is_numeric($opening) || (float) $opening < 0 || (float) $opening > 1000000000) {
        api_fail('Tiền đầu ca không hợp lệ.');
    }
    $opening = (int) round((float) $opening);
    db()->fetchOne("SELECT GET_LOCK('coffee_cash_shift_open', 5) AS l");
    try {
        $existing = cashshift_open_row();
        if ($existing) {
            api_fail('Ca của ' . $existing['cashier_name'] . ' đang mở (từ ' . date('H:i', strtotime($existing['opened_at'])) . '). Hãy kết ca đó trước.', 409);
        }
        db()->execute('INSERT INTO cash_shifts (staff_id, opening_amount) VALUES (?, ?)', array((int) $user['staffId'], $opening));
        $id = db()->insertId();
    } finally {
        db()->fetchOne("SELECT RELEASE_LOCK('coffee_cash_shift_open') AS l");
    }
    return cashshift_shape(cashshift_select('c.cash_shift_id = ?', array($id))[0], (int) $user['staffId']);
}

function cashshift_movement($data, $user)
{
    $kind = $data['kind'] ?? '';
    $category = trim((string) ($data['category'] ?? ''));
    $note = trim((string) ($data['note'] ?? ''));
    $allowed = array('in' => array('Tiền tip', 'Thu khác'), 'out' => array('Mua đá', 'Đồ lặt vặt', 'Chi khác'));
    if (!isset($allowed[$kind]) || !in_array($category, $allowed[$kind], true)) api_fail('Loại thu/chi không hợp lệ.');
    $amount = $data['amount'] ?? null;
    if (!is_numeric($amount) || (int) round((float) $amount) <= 0) api_fail('Số tiền phải lớn hơn 0.');
    $amount = (int) round((float) $amount);
    if ($note === '') api_fail('Vui lòng ghi chú nội dung thu/chi.');
    if (cashshift_len($note) > 200) api_fail('Ghi chú tối đa 200 ký tự.');
    return db()->transaction(function () use ($kind, $category, $note, $amount, $user) {
        $row = db()->fetchOne('SELECT cash_shift_id, opening_amount FROM cash_shifts WHERE closed_at IS NULL ORDER BY cash_shift_id DESC LIMIT 1 FOR UPDATE');
        if (!$row) api_fail('Chưa mở ca.', 409);
        if ($kind === 'out') {
            $stats = cashshift_stats($row['cash_shift_id'], $row['opening_amount'], cashshift_moves($row['cash_shift_id']));
            if ($amount > $stats['expectedCash']) api_fail('Số tiền chi lớn hơn tiền mặt đang có trong két.');
        }
        db()->execute(
            'INSERT INTO cash_transactions (cash_shift_id, transaction_type, category, amount, note, staff_id) VALUES (?, ?, ?, ?, ?, ?)',
            array((int) $row['cash_shift_id'], $kind === 'in' ? 'cash_in' : 'cash_out', $category, $amount, $note, (int) $user['staffId'])
        );
        return array('amount' => $amount, 'kind' => $kind);
    });
}

function cashshift_close($data, $user)
{
    $counted = $data['countedCash'] ?? null;
    if (!is_numeric($counted) || (float) $counted < 0 || (float) $counted > 1000000000) api_fail('Tiền đếm thực tế không hợp lệ.');
    $counted = (int) round((float) $counted);
    $reason = trim((string) ($data['reason'] ?? ''));
    if (cashshift_len($reason) > 500) api_fail('Lý do tối đa 500 ký tự.');
    $id = db()->transaction(function () use ($counted, $reason, $user) {
        $row = db()->fetchOne('SELECT cash_shift_id, opening_amount FROM cash_shifts WHERE closed_at IS NULL ORDER BY cash_shift_id DESC LIMIT 1 FOR UPDATE');
        if (!$row) api_fail('Chưa mở ca.', 409);
        $stats = cashshift_stats($row['cash_shift_id'], $row['opening_amount'], cashshift_moves($row['cash_shift_id']));
        $diff = $counted - $stats['expectedCash'];
        if ($diff !== 0 && $reason === '') api_fail('Tiền đếm lệch so với hệ thống — bắt buộc ghi lý do.');
        db()->execute(
            'UPDATE cash_shifts SET closed_at = NOW(), closed_by = ?, closing_amount = ?, expected_amount = ?,
             difference = ?, diff_reason = ?, summary_json = ? WHERE cash_shift_id = ?',
            array((int) $user['staffId'], $counted, (int) $stats['expectedCash'], $diff,
                $diff !== 0 ? $reason : null, json_encode($stats, JSON_UNESCAPED_UNICODE), (int) $row['cash_shift_id'])
        );
        return (int) $row['cash_shift_id'];
    });
    return cashshift_shape(cashshift_select('c.cash_shift_id = ?', array($id))[0]);
}
