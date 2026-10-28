<?php
// admin/pages/history/action.php — trang "Lịch sử hóa đơn" (Admin)
// Chạy TRƯỚC khi Router in header: xử lý form POST (hủy / hoàn tiền) rồi redirect (Post/Redirect/Get),
// sau đó đọc danh sách từ MySQL bằng hàm trong functions.php.
require_once __DIR__ . '/../../../functions.php';

$title = 'Lịch sử';
$css = array('history.css');
$scripts = array(); // không còn history.js: lọc, xem chi tiết, hủy/hoàn tiền đều chạy bằng PHP

$statusFilter = isset($_GET['status']) && in_array($_GET['status'], array('active', 'cancelled'), true) ? $_GET['status'] : 'all';
$keyword = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

$formError = '';
$formOrderId = 0;
$formMode = 'cancel';
$formReason = '';

// ---- Form POST: hủy hóa đơn hoặc hoàn tiền ----
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['invoice_action'])) {
    $formOrderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $formMode = isset($_POST['mode']) && $_POST['mode'] === 'refund' ? 'refund' : 'cancel';
    $formReason = isset($_POST['reason']) && is_string($_POST['reason']) ? trim($_POST['reason']) : '';

    if (!post_csrf_ok()) {
        $formError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    } elseif (!isset($_POST['mode']) || !in_array($_POST['mode'], array('cancel', 'refund'), true)) {
        $formError = 'Vui lòng chọn Hủy hóa đơn hoặc Hoàn tiền.';
    } else {
        $formError = invoice_cancel_or_refund($formOrderId, $_POST['mode'], $formReason, $user);
    }

    if ($formError === '') {
        $_SESSION['history_saved'] = $formMode === 'refund' ? 'Đã hoàn tiền hóa đơn.' : 'Đã hủy hóa đơn.';
        redirect(url('history', array('status' => $statusFilter, 'q' => $keyword)));
    }
    // Sai dữ liệu: không ghi gì vào CSDL, mở lại hộp thoại kèm thông báo lỗi.
}
$savedMessage = isset($_SESSION['history_saved']) ? $_SESSION['history_saved'] : '';
unset($_SESSION['history_saved']);

$invoices = admin_invoices($statusFilter, $keyword);

// ---- Hộp thoại: ?view=ID (chi tiết) hoặc ?cancel=ID (hủy/hoàn tiền) ----
$viewInvoice = null;
$viewItems = array();
$cancelInvoice = null;
$modalNotFound = false;
$viewId = isset($_GET['view']) ? (int) $_GET['view'] : 0;
$cancelId = $formOrderId > 0 ? $formOrderId : (isset($_GET['cancel']) ? (int) $_GET['cancel'] : 0);
if ($viewId > 0) {
    $viewInvoice = admin_invoice($viewId);
    if ($viewInvoice) $viewItems = admin_invoice_items($viewId);
    else $modalNotFound = true;
} elseif ($cancelId > 0) {
    $cancelInvoice = admin_invoice($cancelId);
    if (!$cancelInvoice || $cancelInvoice['status'] !== 'paid') {
        $modalNotFound = true;
        $cancelInvoice = null;
    }
}
