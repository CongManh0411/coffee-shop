<?php
// pages/handover/action.php — "Giao ca": ngày công hôm nay và nút Xác nhận (form POST có CSRF) chốt giờ ra.
require_once __DIR__ . '/../../functions.php';

$title = 'Giao ca';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'notifications.css', 'cashier.css');
$scripts = array();
const HANDOVER_HOURLY_RATE = 21000; // đồng / giờ — mức chung cho cả quán

$hoError = '';
$staffId = (int) $user['staffId'];
$busy = (int) (db()->fetchOne(
    "SELECT COUNT(*) AS n FROM orders o WHERE o.status = 'open'
     AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.order_id AND oi.status <> 'cancelled')"
)['n'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['ho_action'] ?? '') === 'confirm') {
    if (!post_csrf_ok()) $hoError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    elseif ($busy > 0 && empty($_POST['confirm_busy'])) $hoError = 'Còn ' . $busy . ' bàn đang có khách hoặc chưa thanh toán. Tick "Vẫn giao ca" nếu muốn tiếp tục.';
    else { timesheet_clock_out($staffId); redirect('index.php?page=handover'); }
}

$rec = timesheet_today($staffId) ?: timesheet_clock_in($staffId);
$today = date('Y-m-d');
$myShifts = db()->fetchAll("SELECT start_time, end_time FROM shifts WHERE staff_id = ? AND shift_date = ? ORDER BY start_time", array($staffId, $today));
$clockIn = (int) ($rec['clockIn'] / 1000);
$done = !empty($rec['clockOut']);
$endTs = $done ? (int) ($rec['clockOut'] / 1000) : time();
$minutes = max(0, (int) floor(($endTs - $clockIn) / 60));
$paid = 0;
$late = 0;
$regText = array();
foreach ($myShifts as $i => $s) {
    $a = strtotime($today . ' ' . $s['start_time']); $b = strtotime($today . ' ' . $s['end_time']);
    $from = max($clockIn, $a); $to = min($endTs, $b);
    if ($to > $from) $paid += (int) floor(($to - $from) / 60);
    if ($i === 0) $late = max(0, (int) floor(($clockIn - $a) / 60));
    $regText[] = schedule_clock($s['start_time']) . ' – ' . schedule_clock($s['end_time']);
}
$outside = max(0, $minutes - $paid);
$pay = (int) round($paid * HANDOVER_HOURLY_RATE / 60);
function ho_long($min)
{
    $h = intdiv($min, 60); $m = $min % 60;
    return $h && $m ? $h . ' giờ ' . $m . ' phút' : ($h ? $h . ' giờ' : $m . ' phút');
}
