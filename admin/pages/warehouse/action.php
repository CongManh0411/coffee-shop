<?php
// pages/warehouse/action.php — khai báo trang "Kho"
require_once __DIR__ . '/../../../functions.php';

$title = 'Kho';
$css = array('warehouse.css');
$scripts = array('inventory.js', 'warehouse.js');

// Lấy dữ liệu kho và lịch sử biến động từ MySQL để render ngay khi mở trang
$warehouseData = warehouse_list();
$stockMovements = warehouse_movements(array());
