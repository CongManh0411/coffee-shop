<?php
// pages/tables/action.php — khai báo trang "Bàn"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Bàn';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'table-order.css', 'notifications.css', 'cashier.css');
$scripts = array('menu-data.js', 'inventory.js', 'cashier-core.js', 'table-order.js', 'notifications.js');
