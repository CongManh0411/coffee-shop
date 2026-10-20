<?php
// pages/ops/action.php — khai báo trang "Vận hành"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Vận hành';
$css = array('menu.css', 'ops.css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'notifications.css', 'cashier.css');
$scripts = array('cashier-core.js', 'waiter-pay.js', 'notifications.js', 'menu-data.js', 'inventory.js', 'ops.js');
