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
        'registered' => (int) ($r['registered'] ?? 0) === 1,
        'leftAt'  => !empty($r['left_at']) ? substr($r['left_at'], 11, 5) : null,
    );
}

function shifts_list()
{
    $rows = db()->fetchAll("SELECT shift_id, staff_id, shift_date, start_time, end_time, registered, left_at
                            FROM shifts ORDER BY shift_date, start_time");
    return array_map('shifts_row', $rows);
}

function shifts_valid_date($d)
{
    if (!is_string($d) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $matches)) {
        return false;
    }
    return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
}

// Serialize shift writes for one employee by holding the staff row lock until commit.
function shifts_lock_staff($staffId)
{
    $staff = db()->fetchOne(
        "SELECT staff_id FROM staff WHERE staff_id = ? AND status = 1 FOR UPDATE",
        array((int) $staffId)
    );
    return $staff ? '' : 'Không tìm thấy nhân viên đang hoạt động.';
}

function shifts_validate($staffId, $date, $start, $end, $exceptId = 0)
{
    if ($staffId <= 0) return 'Thiếu nhân viên.';
    if (!shifts_valid_date($date)) return 'Ngày không hợp lệ.';
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $start) ||
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $end)) {
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
    return db()->transaction(function () use ($staffId, $date, $start, $end) {
        $err = shifts_lock_staff($staffId);
        if ($err) return array(null, $err);
        $err = shifts_validate($staffId, $date, $start, $end);
        if ($err) return array(null, $err);
        db()->execute(
            "INSERT INTO shifts (staff_id, shift_date, start_time, end_time) VALUES (?, ?, ?, ?)",
            array($staffId, $date, $start . ':00', $end . ':00')
        );
        return array(db()->insertId(), '');
    });
}

function shifts_update($id, $d)
{
    $start = $d['start'] ?? '';
    $end   = $d['end'] ?? '';
    $id = (int) $id;
    $initial = db()->fetchOne("SELECT staff_id FROM shifts WHERE shift_id = ?", array($id));
    if (!$initial) return 'Không tìm thấy ca.';
    $staffId = (int) $initial['staff_id'];

    return db()->transaction(function () use ($id, $staffId, $start, $end) {
        $err = shifts_lock_staff($staffId);
        if ($err) return $err;
        $cur = db()->fetchOne("SELECT staff_id, shift_date FROM shifts WHERE shift_id = ? FOR UPDATE", array($id));
        if (!$cur) return 'Không tìm thấy ca.';
        $err = shifts_validate($staffId, $cur['shift_date'], $start, $end, $id);
        if ($err) return $err;
        db()->execute(
            "UPDATE shifts SET start_time = ?, end_time = ? WHERE shift_id = ?",
            array($start . ':00', $end . ':00', $id)
        );
        return '';
    });
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
        "SELECT staff_id, shift_date, start_time, end_time FROM shifts WHERE shift_date BETWEEN ? AND ? ORDER BY staff_id, shift_date, start_time",
        array($prevStart, $prevEnd)
    );
    if (!$rows) return array(0, 'Tuần trước chưa có ca nào để sao chép.');
    return db()->transaction(function () use ($rows) {
        $lockedStaff = array();
        foreach ($rows as $r) {
            $staffId = (int) $r['staff_id'];
            if (!array_key_exists($staffId, $lockedStaff)) {
                $lockedStaff[$staffId] = shifts_lock_staff($staffId) === '';
            }
        }

        $added = 0;
        foreach ($rows as $r) {
            $staffId = (int) $r['staff_id'];
            if (!$lockedStaff[$staffId]) continue;
            $date = date('Y-m-d', strtotime($r['shift_date'] . ' +7 days'));
            $start = substr($r['start_time'], 0, 5);
            $end   = substr($r['end_time'], 0, 5);
            if (shifts_validate($staffId, $date, $start, $end) !== '') continue; // trùng -> bỏ qua
            db()->execute(
                "INSERT INTO shifts (staff_id, shift_date, start_time, end_time) VALUES (?, ?, ?, ?)",
                array($staffId, $date, $start . ':00', $end . ':00')
            );
            $added++;
        }
        return array($added, '');
    });
}

function shifts_clear_week($weekStart)
{
    if (!shifts_valid_date($weekStart)) return array(0, 'Ngày không hợp lệ.');
    $end = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    $n = db()->execute("DELETE FROM shifts WHERE shift_date BETWEEN ? AND ?", array($weekStart, $end));
    return array($n, '');
}

// ---------------------------------------------------------------
// Thu ngân tự đăng ký / hủy ca theo 3 khung giờ cố định (thay cho shift-register.js ghi localStorage).
// Quy tắc: mỗi người tối đa 1 ca / ngày; mỗi khung giờ nhận tối đa SHIFT_MAX_CASHIER_PER_SLOT thu ngân;
// không đăng ký / hủy ca đã bắt đầu; ca do Quản lý xếp (registered = 0) thì không tự hủy được.
// ---------------------------------------------------------------
define('SHIFT_MAX_CASHIER_PER_SLOT', 1);

function shifts_register_slots()
{
    return array('06:30-14:00', '11:00-19:00', '17:00-22:00');
}

// $d = {date:'YYYY-MM-DD', start:'HH:MM', end:'HH:MM', register:true|false}. Trả về chuỗi lỗi hoặc ''.
function shifts_register($staffId, $d)
{
    $staffId = (int) $staffId;
    $date  = (string) ($d['date'] ?? '');
    $start = (string) ($d['start'] ?? '');
    $end   = (string) ($d['end'] ?? '');
    $register = !empty($d['register']);
    if (!shifts_valid_date($date)) return 'Ngày không hợp lệ.';
    if (!in_array($start . '-' . $end, shifts_register_slots(), true)) return 'Khung giờ không hợp lệ.';
    if (strtotime($date . ' ' . $start . ':00') <= time()) return 'Ca đã bắt đầu hoặc đã qua.';

    return db()->transaction(function () use ($staffId, $date, $start, $end, $register) {
        $err = shifts_lock_staff($staffId);
        if ($err) return $err;
        if (!$register) {
            db()->execute(
                "DELETE FROM shifts WHERE staff_id = ? AND shift_date = ? AND start_time = ? AND end_time = ? AND registered = 1",
                array($staffId, $date, $start . ':00', $end . ':00')
            );
            return '';
        }
        $mine = db()->fetchOne(
            "SELECT shift_id, start_time, end_time FROM shifts WHERE staff_id = ? AND shift_date = ?",
            array($staffId, $date)
        );
        if ($mine) {
            if (substr($mine['start_time'], 0, 5) === $start && substr($mine['end_time'], 0, 5) === $end) return '';
            return 'Đã có ca khác trong ngày.';
        }
        $row = db()->fetchOne(
            "SELECT COUNT(*) AS n FROM shifts s JOIN staff st ON st.staff_id = s.staff_id
             WHERE s.shift_date = ? AND s.start_time = ? AND s.end_time = ? AND st.role = 'cashier'",
            array($date, $start . ':00', $end . ':00')
        );
        if ((int) ($row['n'] ?? 0) >= SHIFT_MAX_CASHIER_PER_SLOT) return 'Khung giờ này đã đủ người.';
        $err = shifts_validate($staffId, $date, $start, $end);
        if ($err) return $err;
        db()->execute(
            "INSERT INTO shifts (staff_id, shift_date, start_time, end_time, registered) VALUES (?, ?, ?, ?, 1)",
            array($staffId, $date, $start . ':00', $end . ':00')
        );
        return '';
    });
}
