<?php
// pages/logs/action.php — khai báo trang "Nhật ký"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Nhật ký';
$css = array('history.css', 'cashier.css');
$scripts = array('cashier-core.js', 'menu-data.js', 'inventory.js', 'logs.js');
