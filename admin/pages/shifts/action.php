<?php
require_once __DIR__ . '/../../../functions.php';
$adminShifts = admin_shifts();
// pages/shifts/action.php — khai báo trang "Lịch ca"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Lịch ca';
$css = array('schedule.css', 'shifts.css');
$scripts = array('shifts.js');
