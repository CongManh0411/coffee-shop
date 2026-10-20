<?php
// pages/history/action.php — khai báo trang "Lịch sử"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Lịch sử';
$css = array('history.css');
$scripts = array('history.js');
