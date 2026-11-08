<?php
// pages/notify/action.php — "Thông báo món" cho Phục vụ / Thu ngân. PHP đọc trạng thái món do Bếp đặt; trang tự tải lại mỗi 10 giây.
require_once __DIR__ . '/../../functions.php';
require_once __DIR__ . '/../../helpers/pos.php';

$title = 'Thông báo';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'notifications.css', 'cashier.css');
$scripts = array();
$metaRefresh = 10;

$notifyOrders = array();
foreach (pos_open_orders() as $o) {
    $lines = array_values(array_filter($o['items'], function ($i) { return $i['status'] !== 'draft'; }));
    if (!$lines) continue;
    $done = count(array_filter($lines, function ($i) { return $i['status'] === 'done'; }));
    $o['lines'] = $lines;
    $o['done'] = $done;
    $o['missing'] = count($lines) - $done;
    $o['complete'] = $o['missing'] === 0;
    $notifyOrders[] = $o;
}
// Bàn đủ món lên đầu
usort($notifyOrders, function ($a, $b) { return (int) $b['complete'] - (int) $a['complete']; });
