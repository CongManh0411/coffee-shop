<?php
// api/modules/attendance.php — Chấm công & giao ca (bảng timesheets, mỗi nhân viên một dòng mỗi ngày).
// Thay cho 'coffee_timesheet_v1' trước đây lưu ở trình duyệt. Giờ vào / giờ ra do máy chủ ghi.

function attendance_row($r)
{
    return array(
        'staffId'  => (int) $r['staff_id'],
        'date'     => $r['work_date'],
        'clockIn'  => strtotime($r['clock_in']) * 1000,
        'clockOut' => $r['clock_out'] ? strtotime($r['clock_out']) * 1000 : null,
    );
}

function attendance_today($me)
{
    $row = db()->fetchOne(
        "SELECT staff_id, work_date, clock_in, clock_out FROM timesheets WHERE staff_id = ? AND work_date = ?",
        array((int) $me['staffId'], date('Y-m-d'))
    );
    return $row ? attendance_row($row) : null;
}

// Mỗi ngày chỉ có một bản ghi: đã có (kể cả đã giao ca) thì giữ nguyên.
function attendance_clock_in($me)
{
    db()->execute(
        "INSERT INTO timesheets (staff_id, work_date, clock_in) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE staff_id = staff_id",
        array((int) $me['staffId'], date('Y-m-d'), date('Y-m-d H:i:s'))
    );
    return attendance_today($me);
}

// Chốt giờ ra: chỉ ghi khi chưa giao ca.
function attendance_clock_out($me)
{
    attendance_clock_in($me); // phòng khi chưa có bản ghi hôm nay
    db()->execute(
        "UPDATE timesheets SET clock_out = ? WHERE staff_id = ? AND work_date = ? AND clock_out IS NULL",
        array(date('Y-m-d H:i:s'), (int) $me['staffId'], date('Y-m-d'))
    );
    return attendance_today($me);
}
