<?php
// pages/warehouse/action.php — khai báo trang "Kho"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Kho';
$css = array('warehouse.css');
$scripts = array('menu-data.js', 'inventory.js', 'warehouse.js');
