<?php
// pages/staff/action.php — khai báo trang "Nhân viên"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Nhân viên';
$css = array('staff.css');
$scripts = array('staff.js');
