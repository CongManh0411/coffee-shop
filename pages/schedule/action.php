<?php
// pages/schedule/action.php — khai báo trang "Lịch ca"
// Chạy TRƯỚC khi Router in header, nên có thể redirect() được.
// Trang này xử lý dữ liệu bằng JS gọi api/index.php nên chỉ cần khai báo thông tin trang.
$title = 'Lịch ca';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'notifications.css', 'schedule.css', 'cashier.css');
$roleKey = $user['roleKey'] ?? '';
$scripts = array();
if ($roleKey === 'cashier' || $roleKey === 'waiter') {
    $scripts = array('menu-data.js', 'inventory.js', 'cashier-core.js', 'notifications.js');
} elseif ($roleKey === 'kitchen') {
    $scripts = array('notifications.js');
}
$scripts[] = 'schedule.js';
if ($roleKey === 'cashier') {
    $scripts[] = 'shift-register.js';
}
