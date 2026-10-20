<?php
// pages/revenue/action.php — khai báo trang "Doanh thu"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Doanh thu';
$css = array('revenue.css', 'dashboard.css');
$scripts = array('revenue.js', 'dashboard.js');
