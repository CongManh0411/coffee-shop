<?php
// pages/tablemanage/action.php — khai báo trang "Bàn"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Bàn';
$css = array('table-manage.css');
$scripts = array('table-manage.js');
