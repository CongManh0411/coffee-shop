<?php
// pages/menu/action.php — khai báo trang "Thực đơn"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Thực đơn';
$css = array('menu.css');
$scripts = array('menu-data.js', 'inventory.js', 'menu.js');
