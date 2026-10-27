<?php
// pages/schedule/action.php — trang "Lịch ca" (Public)
// Chạy TRƯỚC khi Router in header: lấy dữ liệu từ MySQL bằng hàm nghiệp vụ trong functions.php.
require_once __DIR__ . '/../../functions.php';

$title = 'Lịch ca';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'notifications.css', 'schedule.css', 'cashier.css');
$roleKey = $user['roleKey'] ?? '';
$scripts = array();
if ($roleKey === 'cashier' || $roleKey === 'waiter') {
    $scripts = array('menu-data.js', 'inventory.js', 'cashier-core.js', 'notifications.js');
} elseif ($roleKey === 'kitchen') {
    $scripts = array('notifications.js');
}
// Bảng lịch tuần do PHP in ra từ MySQL nên không còn nạp schedule.js (bản cũ vẽ từ localStorage).
if ($roleKey === 'cashier') {
    $scripts[] = 'shift-register.js';
}

// Tuần đang xem: ?week=YYYY-MM-DD (sai định dạng thì lấy tuần hiện tại).
$weekInput = isset($_GET['week']) ? $_GET['week'] : null;
$weekStart = schedule_week_start($weekInput);
$weekEnd = schedule_add_days($weekStart, 6);
$prevWeek = schedule_add_days($weekStart, -7);
$nextWeek = schedule_add_days($weekStart, 7);
$thisWeek = schedule_week_start(null);

$weekDays = array();
$dayLabels = array('Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN');
for ($i = 0; $i < 7; $i++) {
    $weekDays[] = schedule_add_days($weekStart, $i);
}

$today = date('Y-m-d');
$nowTs = time();
$myStaffId = (int) ($user['staffId'] ?? 0);

// Bảng tuần: nhân viên của mình lên đầu, còn lại giữ thứ tự theo tên.
$scheduleStaff = schedule_group_week(public_schedule_week($weekStart, $weekEnd));
if ($myStaffId && isset($scheduleStaff[$myStaffId])) {
    $scheduleStaff = array($myStaffId => $scheduleStaff[$myStaffId]) + $scheduleStaff;
}

// Hộp đầu trang: ai đang trong ca / chưa vào ca (hôm nay).
$inShift = array();
$waitingShift = array();
foreach (public_shifts_on($today) as $row) {
    $state = schedule_shift_state($row, $today, $nowTs);
    if ($state === 'working') {
        $inShift[] = $row;
    } elseif ($state === 'upcoming' || $state === 'noshow') {
        $row['state'] = $state;
        $waitingShift[] = $row;
    }
}

// "Ca của tôi": ca kế tiếp và tổng giờ làm trong tuần đang xem.
$myNextShift = $myStaffId ? public_next_shift($myStaffId, $today, date('H:i:s', $nowTs)) : null;
$myWeekMinutes = isset($scheduleStaff[$myStaffId]) ? $scheduleStaff[$myStaffId]['minutes'] : 0;
