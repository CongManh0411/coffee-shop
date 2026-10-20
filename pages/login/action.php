<?php
// pages/login/action.php — khai báo trang "Đăng nhập"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Đăng nhập';
$bodyClass = 'login-page';
$css = array('login.css');
$scripts = array();
