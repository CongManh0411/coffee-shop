<?php
// admin/pages/logs/action.php — trang "Nhật ký" (Admin, chỉ đọc)
// PHP đọc MySQL (audit_logs, cash_shifts, menu_stock_movements) rồi in bảng bằng foreach. Bộ lọc là form GET, không dùng JavaScript.
require_once __DIR__ . '/../../../functions.php';
require_once __DIR__ . '/../../../helpers/shift_report.php';

$title = 'Nhật ký';
$css = array('history.css', 'cashier.css');
$scripts = array('ajax-pager.js'); // đổi trang bằng AJAX; bộ lọc vẫn là form GET

$logTypes = array(
    'discount' => 'Giảm giá', 'discount_big' => 'Giảm giá lớn', 'void_item' => 'Hủy món', 'void_done' => 'Hủy món đã làm',
    'refund' => 'Hoàn tiền', 'cash_in' => 'Thu quỹ', 'cash_out' => 'Chi quỹ', 'shift_open' => 'Mở ca', 'shift_close' => 'Kết ca',
    'shift_diff' => 'Chênh lệch kết ca', 'soldout' => 'Hết món', 'cancel_order' => 'Hủy đơn', 'table' => 'Thao tác bàn',
);
$roleNames = array('manager' => 'Quản lý', 'waiter' => 'Phục vụ', 'cashier' => 'Thu ngân', 'kitchen' => 'Bếp');

$tab = (string) ($_GET['tab'] ?? 'logs');
if (!in_array($tab, array('logs', 'shifts', 'stock'), true)) $tab = 'logs';

$fType = (string) ($_GET['type'] ?? '');
$fRange = (string) ($_GET['range'] ?? '1');
if (!in_array($fRange, array('1', '7', '30', '0'), true)) $fRange = '1';
$fWho = (string) ($_GET['who'] ?? '');
$fFlag = !empty($_GET['flag']);

$logRows = array();
$logWho = array();
$cashHistory = array();
$cashCurrent = null;
$reportShift = null;
$stockRows = array();

if ($tab === 'logs') {
    $all = auditlog_list(); // cũ -> mới
    foreach ($all as $l) $logWho[$l['actorName']] = true;
    $logWho = array_keys($logWho);
    $since = $fRange === '0' ? 0 : strtotime('today') - ((int) $fRange - 1) * 86400;
    foreach (array_reverse($all) as $l) {
        if ($fType !== '' && $l['type'] !== $fType) continue;
        if ($fWho !== '' && $l['actorName'] !== $fWho) continue;
        if ($fFlag && empty($l['flag'])) continue;
        if (strtotime($l['time']) < $since) continue;
        $logRows[] = $l;
    }
} elseif ($tab === 'shifts') {
    $snap = cashshift_snapshot(array('staffId' => (int) ($_SESSION['user']['staffId'] ?? 0)));
    $cashCurrent = $snap['current'];
    $cashHistory = array_reverse($snap['history']); // mới -> cũ
    $repId = (string) ($_GET['report'] ?? '');
    foreach ($cashHistory as $s) if ($s['id'] === $repId) $reportShift = $s;
} else {
    $stockRows = warehouse_movements();
}

// Phân trang 15 dòng/trang cho cả 3 tab (dữ liệu đã nằm trong mảng PHP nên cắt bằng paginate_rows). Giữ nguyên bộ lọc trên link.
$logPage = request_page('p');
$logPager = paginate(0, 1, 15);
$logPagerQuery = array('page' => 'logs', 'tab' => $tab);
if ($tab === 'logs') {
    list($logRows, $logPager) = paginate_rows($logRows, $logPage, 15);
    $logPagerQuery += array_filter(array('type' => $fType, 'range' => $fRange === '1' ? '' : $fRange, 'who' => $fWho, 'flag' => $fFlag ? '1' : ''), 'strlen');
} elseif ($tab === 'shifts') {
    list($cashHistory, $logPager) = paginate_rows($cashHistory, $logPage, 15);
} else {
    list($stockRows, $logPager) = paginate_rows($stockRows, $logPage, 15);
}

function logs_url($tab, $extra = array())
{
    return admin_url('logs', array('tab' => $tab) + $extra);
}
function logs_dt($iso)
{
    return date('d/m/Y H:i', strtotime($iso));
}
