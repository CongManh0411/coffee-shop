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
    );
}

function shifts_list()
{
    $rows = db()->fetchAll("SELECT shift_id, staff_id, shift_date, start_time, end_time, registered
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
// Thu ngân tự đăng ký ca (thay cho shift-register.js ghi thẳng vào trình duyệt).
// Quy tắc: mỗi người tối đa 1 ca/ngày, mỗi khung giờ tối đa SHIFT_MAX_CASHIER_PER_SLOT thu ngân,
// không đăng ký / hủy được ca đã bắt đầu, ca do Quản lý xếp thì khóa.
// ---------------------------------------------------------------
define('SHIFT_REGISTER_DAYS', 14);
define('SHIFT_MAX_CASHIER_PER_SLOT', 1);

function shifts_register_slots()
{
    return array(
        array('key' => 'sang',  'label' => 'Ca sáng',  'start' => '06:30', 'end' => '14:00'),
        array('key' => 'chieu', 'label' => 'Ca chiều', 'start' => '11:00', 'end' => '19:00'),
        array('key' => 'toi',   'label' => 'Ca tối',   'start' => '17:00', 'end' => '22:00'),
    );
}

function shifts_register_slot_ok($start, $end)
{
    foreach (shifts_register_slots() as $s) {
        if ($s['start'] === $start && $s['end'] === $end) return true;
    }
    return false;
}

function shifts_register_started($date, $start)
{
    return strtotime($date . ' ' . $start . ':00') <= time();
}

// Ca của chính người đăng nhập + các khung giờ đã đủ thu ngân (do người khác giữ) trong 14 ngày tới.
function shifts_register_state($me)
{
    $from = date('Y-m-d');
    $to = date('Y-m-d', strtotime($from . ' +' . (SHIFT_REGISTER_DAYS - 1) . ' days'));
    $mine = db()->fetchAll(
        "SELECT shift_id, staff_id, shift_date, start_time, end_time, registered
         FROM shifts WHERE staff_id = ? AND shift_date BETWEEN ? AND ? ORDER BY shift_date, start_time",
        array((int) $me['staffId'], $from, $to)
    );
    $others = db()->fetchAll(
        "SELECT sh.shift_date, sh.start_time, sh.end_time, COUNT(*) AS c
         FROM shifts sh JOIN staff st ON st.staff_id = sh.staff_id
         WHERE st.role = 'cashier' AND sh.staff_id <> ? AND sh.shift_date BETWEEN ? AND ?
         GROUP BY sh.shift_date, sh.start_time, sh.end_time",
        array((int) $me['staffId'], $from, $to)
    );
    $full = array();
    foreach ($others as $o) {
        if ((int) $o['c'] >= SHIFT_MAX_CASHIER_PER_SLOT) {
            $full[] = $o['shift_date'] . '|' . substr($o['start_time'], 0, 5) . '|' . substr($o['end_time'], 0, 5);
        }
    }
    return array(
        'days'   => SHIFT_REGISTER_DAYS,
        'slots'  => shifts_register_slots(),
        'mine'   => array_map('shifts_row', $mine),
        'full'   => $full,
        'maxPerSlot' => SHIFT_MAX_CASHIER_PER_SLOT,
    );
}

// $changes: [{date, start, end, checked}]. Trả về [added, removed, skipped[]].
function shifts_register_apply($changes, $me)
{
    if (!is_array($changes)) return array(0, 0, array(), 'Dữ liệu đăng ký không hợp lệ.');
    $staffId = (int) $me['staffId'];
    $lockName = 'coffee_shift_register';
    db()->fetchOne("SELECT GET_LOCK('" . $lockName . "', 5) AS l");
    try {
        return db()->transaction(function () use ($changes, $staffId) {
            $added = 0; $removed = 0; $skipped = array();
            $today = date('Y-m-d');
            $last = date('Y-m-d', strtotime($today . ' +' . (SHIFT_REGISTER_DAYS - 1) . ' days'));
            foreach ($changes as $c) {
                $date = is_array($c) ? ($c['date'] ?? '') : '';
                $start = is_array($c) ? ($c['start'] ?? '') : '';
                $end = is_array($c) ? ($c['end'] ?? '') : '';
                $checked = is_array($c) && !empty($c['checked']);
                if (!shifts_valid_date($date) || $date < $today || $date > $last || !shifts_register_slot_ok($start, $end)) continue;
                if (shifts_register_started($date, $start)) continue;

                $mine = db()->fetchOne(
                    "SELECT shift_id, start_time, end_time, registered FROM shifts WHERE staff_id = ? AND shift_date = ? FOR UPDATE",
                    array($staffId, $date)
                );
                $isMineSlot = $mine && substr($mine['start_time'], 0, 5) === $start && substr($mine['end_time'], 0, 5) === $end;

                if ($checked && !$isMineSlot) {
                    if ($mine) { $skipped[] = $date . ': đã có ca khác'; continue; }
                    $row = db()->fetchOne(
                        "SELECT COUNT(*) AS c FROM shifts sh JOIN staff st ON st.staff_id = sh.staff_id
                         WHERE sh.shift_date = ? AND sh.start_time = ? AND sh.end_time = ? AND st.role = 'cashier'",
                        array($date, $start . ':00', $end . ':00')
                    );
                    if ((int) $row['c'] >= SHIFT_MAX_CASHIER_PER_SLOT) { $skipped[] = $date . ' ' . $start . '–' . $end . ': đã đủ người'; continue; }
                    $err = shifts_lock_staff($staffId);
                    if ($err) throw new ApiException($err, 422);
                    $err = shifts_validate($staffId, $date, $start, $end);
                    if ($err) { $skipped[] = $date . ': ' . $err; continue; }
                    db()->execute(
                        "INSERT INTO shifts (staff_id, shift_date, start_time, end_time, registered) VALUES (?, ?, ?, ?, 1)",
                        array($staffId, $date, $start . ':00', $end . ':00')
                    );
                    $added++;
                } elseif (!$checked && $isMineSlot && (int) $mine['registered'] === 1) {
                    db()->execute("DELETE FROM shifts WHERE shift_id = ?", array((int) $mine['shift_id']));
                    $removed++;
                }
            }
            return array($added, $removed, $skipped, '');
        });
    } finally {
        db()->fetchOne("SELECT RELEASE_LOCK('" . $lockName . "') AS l");
    }
}
