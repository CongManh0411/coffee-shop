<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Header bảo mật cơ bản cho mọi trang web (bỏ qua khi chạy bằng dòng lệnh)
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');            // chống nhúng trang vào iframe của trang khác (clickjacking)
    header('X-Content-Type-Options: nosniff');        // trình duyệt không tự đoán kiểu file
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../helpers/database.php';
require_once __DIR__ . '/../helpers/common.php';