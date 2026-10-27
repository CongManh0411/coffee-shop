<?php
// pages/tables/action.php — trang "Bàn" (Public)
// Chạy TRƯỚC khi Router in header: lấy dữ liệu bằng hàm nghiệp vụ trong functions.php.
require_once __DIR__ . '/../../functions.php';

$title = 'Bàn';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'table-order.css', 'notifications.css', 'cashier.css');
// table-order.js chỉ còn lo phần tương tác gọi món / hóa đơn; sơ đồ bàn lần đầu do PHP in ra từ MySQL.
$scripts = array('menu-data.js', 'inventory.js', 'cashier-core.js', 'table-order.js', 'notifications.js');

$tablesStatus = public_tables_with_status();
$tablesTotal = count($tablesStatus);
$tablesOccupied = 0;
foreach ($tablesStatus as $tableRow) {
    if ($tableRow['status'] === 'occupied') {
        $tablesOccupied++;
    }
}
$tablesEmpty = $tablesTotal - $tablesOccupied;
