<?php
// admin/pages/shifts/action.php — trang "Xếp lịch ca" (Admin)
require_once __DIR__ . '/../../../functions.php';

$title = 'Lịch ca';
$css = array('schedule.css', 'shifts.css');
$scripts = array('shifts.js'); // lưới xếp ca (thêm/sửa/xóa ca) vẫn do shifts.js gọi API

// Bảng "Danh sách ca sắp tới": đọc trực tiếp từ MySQL (JOIN shifts với staff), PHP in bằng foreach.
$upcomingShifts = admin_shifts_upcoming(date('Y-m-d'), 50);
