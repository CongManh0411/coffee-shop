<?php
// pages/kitchen/action.php — khai báo trang "Bếp"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Bếp';
$css = array('kitchen.css', 'notifications.css');
$scripts = array('kitchen.js', 'notifications.js');
