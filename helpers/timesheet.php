<?php
// helpers/timesheet.php — Chấm công theo ngày (bảng timesheets).
// Mỗi nhân viên có tối đa 1 bản ghi / ngày: giờ vào ghi lần đầu mở trang, giờ ra ghi khi bấm "Giao ca".

function timesheet_row($r)
{
    if (!$r) return null;
    return array(
        'staffId'  => (int) $r['staff_id'],
        'date'     => $r['work_date'],
        'clockIn'  => strtotime($r['clock_in']) * 1000,
        'clockOut' => !empty($r['clock_out']) ? strtotime($r['clock_out']) * 1000 : null,
    );
}

function timesheet_today($staffId)
{
    return timesheet_row(db()->fetchOne(
        "SELECT staff_id, work_date, clock_in, clock_out FROM timesheets WHERE staff_id = ? AND work_date = ?",
        array((int) $staffId, date('Y-m-d'))
    ));
}

// Idempotent: đã có bản ghi hôm nay (kể cả đã giao ca) thì giữ nguyên.
function timesheet_clock_in($staffId)
{
    db()->execute(
        "INSERT IGNORE INTO timesheets (staff_id, work_date, clock_in) VALUES (?, ?, ?)",
        array((int) $staffId, date('Y-m-d'), date('Y-m-d H:i:s'))
    );
    return timesheet_today($staffId);
}

function timesheet_clock_out($staffId)
{
    timesheet_clock_in($staffId); // phòng khi chưa có bản ghi hôm nay
    db()->execute(
        "UPDATE timesheets SET clock_out = ? WHERE staff_id = ? AND work_date = ? AND clock_out IS NULL",
        array(date('Y-m-d H:i:s'), (int) $staffId, date('Y-m-d'))
    );
    return timesheet_today($staffId);
}
