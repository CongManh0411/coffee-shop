<?php
// pages/tables/action.php — trang "Bàn / Bán hàng" (Phục vụ, Thu ngân)
// Mọi thao tác (gọi món, gửi bếp, chuyển / gộp / tách bàn, thanh toán, báo hết món) là form POST có CSRF do PHP xử lý rồi redirect.
require_once __DIR__ . '/../../functions.php';
require_once __DIR__ . '/../../helpers/pos.php';

$title = 'Bàn';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'table-order.css', 'notifications.css', 'cashier.css');
$scripts = array();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['pos'])) {
    pos_run_post($user);
}
$flash = pos_take_flash();
$isCashier = pos_is_cashier($user);

$orderParam = (int) ($_GET['order'] ?? 0);
$tableParam = (int) ($_GET['table'] ?? 0);
$receiptParam = (int) ($_GET['receipt'] ?? 0);
$payMode = !empty($_GET['pay']);
$filter = in_array((string) ($_GET['f'] ?? ''), array('empty', 'occupied', 'togo'), true) ? (string) $_GET['f'] : 'all';

$openOrders = pos_open_orders();
$tablesStatus = public_tables_with_status();
$ordersByTable = array();
foreach ($openOrders as $o) if ($o['tableId'] !== null) $ordersByTable[(int) $o['tableId']] = $o;

$mode = 'grid';
$cur = null;        // đơn đang mở
$curTable = null;   // bàn đang chọn (chưa có đơn)
$invoice = null;    // hóa đơn vừa thanh toán
$paid = null;

if ($receiptParam > 0) {
    foreach (orders_invoice_list('all') as $inv) if ((int) $inv['orderId'] === $receiptParam) $invoice = $inv;
    if ($invoice) {
        $mode = 'receipt';
        $paid = $_SESSION['pos_paid'] ?? null;
    } else {
        $flash['error'] = $flash['error'] ?: 'Không tìm thấy hóa đơn.';
    }
} elseif ($orderParam > 0) {
    $cur = pos_find_order($openOrders, $orderParam);
    if ($cur) $mode = $payMode ? 'pay' : 'order';
    else $flash['error'] = $flash['error'] ?: 'Đơn này không còn mở (đã thanh toán hoặc đã hủy).';
} elseif ($tableParam > 0) {
    if (isset($ordersByTable[$tableParam])) redirect(pos_url(array('order' => $ordersByTable[$tableParam]['id'])));
    foreach ($tablesStatus as $t) if ((int) $t['table_id'] === $tableParam) $curTable = $t;
    if ($curTable) $mode = 'order';
}

if ($mode === 'grid') {
    $metaRefresh = 15; // sơ đồ bàn tự tải lại để thấy bàn mới có khách
    $title = 'Sơ đồ bàn';
}

// ---- Dữ liệu cho màn gọi món ----
$menuGroups = array();
$availability = array();
$catOptions = array();
$catFilter = (string) ($_GET['cat'] ?? '');
$search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60, 'UTF-8');
if ($mode === 'order') {
    $availability = pos_availability();
    $catOptions = pos_category_options();
    foreach (menu_list() as $g) {
        $items = array();
        foreach ($g['items'] as $it) {
            if ($it['hidden']) continue;
            if ($search !== '' && mb_stripos($it['name'] . ' ' . $it['desc'] . ' ' . $g['category'], $search, 0, 'UTF-8') === false) continue;
            $items[] = $it;
        }
        $menuGroups[] = array('category' => $g['category'], 'items' => $items, 'all' => count($g['items']));
    }
}

// ---- Xem trước giảm giá (form GET ở màn thanh toán) ----
$payPreview = array('voucher' => '', 'discType' => 'vnd', 'discVal' => '', 'discount' => 0, 'label' => '', 'error' => '');
if ($mode === 'pay' && $isCashier) {
    $sub = (int) $cur['sent_total'];
    $v = trim((string) ($_GET['v'] ?? ''));
    $dt = (string) ($_GET['dt'] ?? 'vnd') === 'pct' ? 'pct' : 'vnd';
    $dv = (string) ($_GET['dv'] ?? '');
    $payPreview['voucher'] = $v; $payPreview['discType'] = $dt; $payPreview['discVal'] = $dv;
    if ($v !== '') {
        $vo = pos_voucher($v, $sub);
        if ($vo) { $payPreview['discount'] = $vo['amount']; $payPreview['label'] = 'Mã ' . $vo['code'] . ' — ' . $vo['label']; }
        else $payPreview['error'] = 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn.';
    } elseif ($dv !== '') {
        $payPreview['discount'] = pos_manual_discount($dt, $dv, $sub);
        $payPreview['label'] = 'Giảm thủ công';
    }
}
