<?php
// pages/ops/action.php — trang "Vận hành" (Thu ngân)
// Mọi thao tác (đặt bàn, hàng chờ, khách thân thiết, khuyến mãi, kho nâng cao, giao hàng) là form POST có CSRF do PHP xử lý
// (gọi lại ops_action() / reservation_create() / reservation_update()) rồi redirect. Chuyển tab bằng link ?tab=. Không còn JavaScript gọi API.
require_once __DIR__ . '/../../functions.php';

$title = 'Vận hành';
$css = array('menu.css', 'ops.css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'notifications.css', 'cashier.css');
$scripts = array('ajax-pager.js'); // đổi trang các danh sách dài bằng AJAX

$opsTabs = array('res' => 'Đặt bàn & hàng chờ', 'crm' => 'Khách thân thiết', 'promo' => 'Khuyến mãi', 'stock' => 'Kho nâng cao', 'deliv' => 'Giao hàng');
$opsTab = (string) ($_GET['tab'] ?? $_POST['tab'] ?? 'res');
if (!isset($opsTabs[$opsTab])) $opsTab = 'res';
$opsMe = current_user();
$opsError = '';

if ($opsMe && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['ops_do'])) {
    $op = (string) $_POST['ops_do'];
    $msg = 'Đã lưu.';
    if (!post_csrf_ok()) {
        $opsError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    } elseif ($op === 'reservation_create') {
        list($rid, $opsError) = reservation_create(array(
            'name' => $_POST['name'] ?? '', 'phone' => $_POST['phone'] ?? '', 'partySize' => $_POST['partySize'] ?? '',
            'reservedAt' => $_POST['reservedAt'] ?? '', 'tableId' => $_POST['tableId'] ?? '', 'note' => $_POST['note'] ?? '',
        ), (int) $opsMe['staffId'], false);
        $opsError = (string) $opsError;
        $msg = 'Đã tạo đặt bàn.';
    } elseif ($op === 'reservation_status') {
        $opsError = reservation_update((int) ($_POST['id'] ?? 0), array('status' => $_POST['status'] ?? ''));
        $msg = 'Đã cập nhật trạng thái đặt bàn.';
    } else {
        // Các thao tác còn lại đi qua ops_action(): op_method = POST | PUT | DELETE, action = waitlist | customer | promotion | ...
        $method = (string) ($_POST['op_method'] ?? 'POST');
        if (!in_array($method, array('POST', 'PUT', 'DELETE'), true)) $method = 'POST';
        $data = array();
        foreach ($_POST as $k => $v) {
            if (is_string($v) && !in_array($k, array('csrf_token', 'ops_do', 'op_method', 'tab'), true)) $data[$k] = trim($v);
        }
        if (($data['action'] ?? '') === 'waitlist' && $method === 'PUT' && ($data['status'] ?? '') === 'seated') {
            $w = db()->fetchOne("SELECT customer_name, phone FROM waitlist_entries WHERE waitlist_id = ?", array((int) ($data['id'] ?? 0)));
            if ($w) $msg = 'Mời khách ' . $w['customer_name'] . ($w['phone'] ? ' (' . $w['phone'] . ')' : '') . ' vào bàn.';
        }
        $opsError = ops_action($data, $method, $opsMe);
    }
    if ($opsError === '') {
        $_SESSION['ops_saved'] = $msg;
        redirect('index.php?' . http_build_query(array_filter(array('page' => 'ops', 'tab' => $opsTab, 'p' => request_page('p') > 1 ? request_page('p') : null))));
    }
}
$opsSaved = $_SESSION['ops_saved'] ?? '';
unset($_SESSION['ops_saved']);

$opsData = ops_snapshot();
$opsReservations = $opsTab === 'res' ? reservations_list() : array();
$opsTables = $opsTab === 'res' ? tables_list() : array();

// Phân trang 10 dòng/trang cho danh sách chính của từng tab (đặt bàn, khách, mã khuyến mãi, phiếu kho, đơn giao hàng); trang nằm trên URL (?p=2)
$opsPager = paginate(0, 1, 10);
$opsPaged = array();
if ($opsTab === 'res') {
    list($opsReservations, $opsPager) = paginate_rows($opsReservations, request_page('p'), 10);
} elseif ($opsTab === 'crm') {
    list($opsPaged, $opsPager) = paginate_rows($opsData['customers'], request_page('p'), 10);
} elseif ($opsTab === 'promo') {
    list($opsPaged, $opsPager) = paginate_rows($opsData['promotions'], request_page('p'), 10);
} elseif ($opsTab === 'stock') {
    list($opsPaged, $opsPager) = paginate_rows($opsData['movements'], request_page('p'), 10);
} else {
    list($opsPaged, $opsPager) = paginate_rows($opsData['deliveries'], request_page('p'), 10);
}
$opsPageQs = $opsPager['page'] > 1 ? '&p=' . $opsPager['page'] : ''; // form POST quay lại đúng trang đang xem

// Dựng form ẩn chung: csrf + hành động + tab hiện tại
function ops_hidden($op, $extra = array())
{
    $h = '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '"><input type="hidden" name="ops_do" value="' . e($op) . '"><input type="hidden" name="tab" value="' . e($GLOBALS['opsTab']) . '">';
    foreach ($extra as $k => $v) $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    return $h;
}
function ops_row_form($op, $extra, $label, $cls = '', $confirm = '')
{
    return '<form method="post" action="index.php?page=ops&tab=' . e($GLOBALS['opsTab']) . e($GLOBALS['opsPageQs']) . '" style="display:inline"' . ($confirm ? ' onsubmit="return confirm(\'' . e($confirm) . '\');"' : '') . '>'
        . ops_hidden($op, $extra) . '<button class="ops-btn sm ' . e($cls) . '" type="submit">' . e($label) . '</button></form>';
}
