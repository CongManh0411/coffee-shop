<?php
// pages/cashshift/action.php — trang "Ca & Quỹ" (Thu ngân)
// Mở ca, ghi thu/chi và kết ca là form POST (có CSRF) do PHP xử lý rồi redirect (Post/Redirect/Get). Không còn JavaScript gọi API.
require_once __DIR__ . '/../../functions.php';
require_once __DIR__ . '/../../helpers/shift_report.php';

$title = 'Ca & Quỹ';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'history.css', 'notifications.css', 'cashier.css');
$scripts = array('notifications.js');

$me = current_user();
$csError = '';
$csErrorBox = ''; // 'open' | 'movement' | 'close' — form nào hiện thông báo lỗi
$csSaved = '';
$vnd = function ($n) { return money($n); };

// Ghi nhật ký thao tác cho Quản lý (không để lỗi nhật ký làm hỏng thao tác chính)
function cs_log($me, $type, $text, $amount = 0, $flag = false)
{
    try { auditlog_create(array('type' => $type, 'text' => $text, 'amount' => $amount, 'flag' => $flag), $me); } catch (Throwable $e) { error_log($e); }
}

if ($me && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['cs_action'])) {
    $act = (string) $_POST['cs_action'];
    $redirectTo = 'index.php?page=cashshift';
    try {
        if (!post_csrf_ok()) {
            $csError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
        } elseif ($act === 'open') {
            $csErrorBox = 'open';
            $opening = (string) ($_POST['openingCash'] ?? '');
            cashshift_open(array('openingCash' => $opening), $me);
            cs_log($me, 'shift_open', 'Mở ca với tiền đầu ca ' . money((int) round((float) $opening)), (int) round((float) $opening));
            $csSaved = 'Đã mở ca.';
        } elseif ($act === 'movement') {
            $csErrorBox = 'movement';
            list($kind, $category) = array_pad(explode('|', (string) ($_POST['mv'] ?? ''), 2), 2, '');
            $amount = (string) ($_POST['amount'] ?? '');
            $note = trim((string) ($_POST['note'] ?? ''));
            cashshift_movement(array('kind' => $kind, 'category' => $category, 'amount' => $amount, 'note' => $note), $me);
            $amt = (int) round((float) $amount);
            cs_log($me, $kind === 'in' ? 'cash_in' : 'cash_out', ($kind === 'in' ? 'Thu' : 'Chi') . ' quỹ — ' . $category . ': ' . $note, $amt, $amt >= 200000);
            $csSaved = 'Đã ghi sổ.';
        } elseif ($act === 'close') {
            $csErrorBox = 'close';
            $counted = (string) ($_POST['countedCash'] ?? '');
            $reason = trim((string) ($_POST['reason'] ?? ''));
            $done = cashshift_close(array('countedCash' => $counted, 'reason' => $reason), $me);
            $sm = $done['summary'];
            cs_log($me, 'shift_close', 'Kết ca ' . date('H:i', strtotime($done['openedAt'])) . '–' . date('H:i', strtotime($done['closedAt'])) . ': doanh thu ' . money($sm['revenue']) . ', ' . (int) $sm['orders'] . ' đơn', $sm['revenue']);
            if ((int) $done['diff'] !== 0) {
                cs_log($me, 'shift_diff', 'Tiền đếm ' . money($done['countedCash']) . ' so với hệ thống ' . money($done['expectedCash']) . ' (' . ($done['diff'] > 0 ? 'thừa' : 'thiếu') . ' ' . money(abs($done['diff'])) . '). Lý do: ' . $done['reason'], abs($done['diff']), true);
            }
            $redirectTo .= '&report=' . rawurlencode($done['id']);
            $csSaved = 'Đã kết ca.';
        } else {
            $csError = 'Thao tác không hợp lệ.';
        }
    } catch (ApiException $e) {
        $csError = $e->getMessage();
    }
    if ($csError === '') {
        $_SESSION['cs_saved'] = $csSaved;
        redirect($redirectTo);
    }
}
$csSavedMessage = $_SESSION['cs_saved'] ?? '';
unset($_SESSION['cs_saved']);

$csSnap = $me ? cashshift_snapshot($me) : array('current' => null, 'history' => array());
$csCurrent = $csSnap['current'];
$csHistory = array_reverse($csSnap['history']); // mới -> cũ
$csMine = array_slice(array_values(array_filter($csHistory, function ($s) use ($me) {
    return (int) $s['cashierId'] === (int) $me['staffId'] || (int) ($s['closedById'] ?? 0) === (int) $me['staffId'];
})), 0, 8);
$csReport = null;
$repId = (string) ($_GET['report'] ?? '');
foreach ($csHistory as $s) if ($s['id'] === $repId) $csReport = $s;
