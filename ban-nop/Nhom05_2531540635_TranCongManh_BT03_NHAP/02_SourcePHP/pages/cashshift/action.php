<?php
// pages/cashshift/action.php — khai báo trang "Ca & Quỹ"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Ca & Quỹ';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'history.css', 'notifications.css', 'cashier.css');
$scripts = array('cashier-core.js', 'menu-data.js', 'inventory.js', 'waiter-pay.js', 'notifications.js', 'shift.js');
