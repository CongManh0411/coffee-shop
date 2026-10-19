<?php
// Ca làm: trả về đúng dạng giao diện đang dùng {id, staffId, date:'YYYY-MM-DD', start:'HH:MM', end:'HH:MM'}
define('SHIFT_OPEN', 6 * 60 + 30);
define('SHIFT_CLOSE', 22 * 60);

function shifts_to_min($t)
{
    $p = explode(':', (string) $t);
    return ((int) $p[0]) * 60 + (int) ($p[1] ?? 0);
}

function shifts_row($r)
{
    return array(
        'id'      => (int) $r['shift_id'],
        'staffId' => (int) $r['staff_id'],
        'date'    => $r['shift_date'],
        'start'   => substr($r['start_time'], 0, 5),
        'end'     => substr($r['end_time'], 0, 5),
    );
}

function shifts_list()
{
    $rows = db()->fetchAll("SELECT shift_id, staff_id, shift_date, start_time, end_time
                            FROM shifts ORDER BY shift_date, start_time");
    return array_map('shifts_row', $rows);
}

function shifts_valid_date($d)
{
    return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
}

function shifts_validate($staffId, $date, $start, $end, $exceptId = 0)
{
    if ($staffId <= 0) return 'Thiếu nhân viên.';
    if (!shifts_valid_date($date)) return 'Ngày không hợp lệ.';
    if (!preg_match('/^\d{2}:\d{2}$/', (string) $start) || !preg_match('/^\d{2}:\d{2}$/', (string) $end)) {
        return 'Vui lòng nhập giờ bắt đầu và kết thúc.';
    }
    $s = shifts_to_min($start);
    $e = shifts_to_min($end);
    if ($e <= $s) return 'Giờ kết thúc phải sau giờ bắt đầu.';
    if ($s < SHIFT_OPEN || $e > SHIFT_CLOSE) return 'Ca phải nằm trong giờ mở cửa 6h30 – 22h.';
    $rows = db()->fetchAll(
        "SELECT shift_id, start_time, end_time FROM shifts WHERE staff_id = ? AND shift_date = ? AND shift_id <> ?",
        array($staffId, $date, (int) $exceptId)
    );
    foreach ($rows as $r) {
        $rs = shifts_to_min($r['start_time']);
        $re = shifts_to_min($r['end_time']);
        if ($s < $re && $rs < $e) {
            return 'Trùng với ca ' . substr($r['start_time'], 0, 5) . ' – ' . substr($r['end_time'], 0, 5) . ' của người này.';
        }
    }
    return '';
}

function shifts_create($d)
{
    $staffId = (int) ($d['staffId'] ?? 0);
    $date  = $d['date'] ?? '';
    $start = $d['start'] ?? '';
    $end   = $d['end'] ?? '';
    $err = shifts_validate($staffId, $date, $start, $end);
    if ($err) return array(null, $err);
    db()->execute(
        "INSERT INTO shifts (staff_id, shift_date, start_time, end_time) VALUES (?, ?, ?, ?)",
        array($staffId, $date, $start . ':00', $end . ':00')
    );
    return array(db()->insertId(), '');
}

function shifts_update($id, $d)
{
    $cur = db()->fetchOne("SELECT staff_id, shift_date FROM shifts WHERE shift_id = ?", array((int) $id));
    if (!$cur) return 'Không tìm thấy ca.';
    $start = $d['start'] ?? '';
    $end   = $d['end'] ?? '';
    $err = shifts_validate((int) $cur['staff_id'], $cur['shift_date'], $start, $end, $id);
    if ($err) return $err;
    db()->execute(
        "UPDATE shifts SET start_time = ?, end_time = ? WHERE shift_id = ?",
        array($start . ':00', $end . ':00', (int) $id)
    );
    return '';
}

function shifts_delete($id)
{
    db()->execute("DELETE FROM shifts WHERE shift_id = ?", array((int) $id));
}

// Sao chép toàn bộ ca của tuần (weekStart = thứ 2, YYYY-MM-DD) sang tuần kế tiếp. Trả về số ca đã thêm.
function shifts_copy_week($weekStart)
{
    if (!shifts_valid_date($weekStart)) return array(0, 'Ngày không hợp lệ.');
    $prevStart = date('Y-m-d', strtotime($weekStart . ' -7 days'));
    $prevEnd   = date('Y-m-d', strtotime($weekStart . ' -1 day'));
    $rows = db()->fetchAll(
        "SELECT staff_id, shift_date, start_time, end_time FROM shifts WHERE shift_date BETWEEN ? AND ?",
        array($prevStart, $prevEnd)
    );
    if (!$rows) return array(0, 'Tuần trước chưa có ca nào để sao chép.');
    $added = 0;
    foreach ($rows as $r) {
        $date = date('Y-m-d', strtotime($r['shift_date'] . ' +7 days'));
        $start = substr($r['start_time'], 0, 5);
        $end   = substr($r['end_time'], 0, 5);
        if (shifts_validate((int) $r['staff_id'], $date, $start, $end) !== '') continue; // trùng -> bỏ qua
        db()->execute(
            "INSERT INTO shifts (staff_id, shift_date, start_time, end_time) VALUES (?, ?, ?, ?)",
            array((int) $r['staff_id'], $date, $start . ':00', $end . ':00')
        );
        $added++;
    }
    return array($added, '');
}

function shifts_clear_week($weekStart)
{
    if (!shifts_valid_date($weekStart)) return array(0, 'Ngày không hợp lệ.');
    $end = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    $n = db()->execute("DELETE FROM shifts WHERE shift_date BETWEEN ? AND ?", array($weekStart, $end));
    return array($n, '');
}
