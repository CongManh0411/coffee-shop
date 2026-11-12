<?php
// pages/orders/action.php — trang "Đơn hàng" (Thu ngân). Hoàn tiền là form POST (có CSRF); chuyển tab bằng link ?tab=.
require_once __DIR__ . '/../../functions.php';
require_once __DIR__ . '/../../helpers/pos.php';

$title = 'Đơn hàng';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'history.css', 'notifications.css', 'cashier.css');
$scripts = array('ajax-pager.js'); // đổi trang hóa đơn (tab Hoàn thành / Đã hủy) bằng AJAX

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['pos'])) {
    $_POST['back'] = 'orders';
    pos_run_post($user);
}
$flash = pos_take_flash();
$ordTab = in_array((string) ($_GET['tab'] ?? ''), array('done', 'cancelled'), true) ? (string) $_GET['tab'] : 'processing';
if ($ordTab === 'processing') $metaRefresh = 15;

$ordProcessing = array();
$ordInvoices = array();
$today = date('Y-m-d');
if ($ordTab === 'processing') {
    $ordProcessing = array_values(array_filter(pos_open_orders(), function ($o) { return $o['count'] > 0; }));
} else {
    // Chỉ lấy hóa đơn của hôm nay ngay trong SQL (trước đây tải toàn bộ hóa đơn rồi mới lọc bằng PHP)
    $ordFilter = $ordTab === 'cancelled' ? array('activityOn' => $today) : array('paidOn' => $today);
    foreach (orders_invoice_list('all', $ordFilter) as $inv) {
        if ($ordTab === 'done' && $inv['status'] === 'active') $ordInvoices[] = $inv;
        if ($ordTab === 'cancelled' && $inv['status'] === 'cancelled') $ordInvoices[] = $inv;
    }
}
// Tổng/đếm tính trên toàn bộ hóa đơn trong ngày, rồi mới cắt 10 hóa đơn/trang
$ordTotalCount = count($ordInvoices);
$ordTotalMoney = array_sum(array_column($ordInvoices, 'total'));
list($ordInvoices, $ordPager) = paginate_rows($ordInvoices, request_page('p'), 10);
