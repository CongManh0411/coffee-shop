<?php
require_once __DIR__ . '/../../../functions.php';
$adminShifts = admin_shifts();
$shiftWeekStart = date('Y-m-d', strtotime('monday this week'));
$shiftWeekEnd = date('Y-m-d', strtotime($shiftWeekStart . ' +6 days'));
$weekShifts = array_values(array_filter($adminShifts, function ($shift) use ($shiftWeekStart, $shiftWeekEnd) {
    return $shift['shift_date'] >= $shiftWeekStart && $shift['shift_date'] <= $shiftWeekEnd;
}));
$shiftRoleLabels = array('manager' => 'Quản lý', 'waiter' => 'Phục vụ', 'cashier' => 'Thu ngân', 'kitchen' => 'Bếp');
// pages/shifts/action.php — khai báo trang "Lịch ca"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// PHP xuất bảng ca từ JOIN shifts + staff; JS đồng bộ bảng khi lịch thay đổi.
$title = 'Lịch ca';
$css = array('schedule.css', 'shifts.css');
$scripts = array('shifts.js');
