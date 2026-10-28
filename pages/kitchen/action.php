<?php
// pages/kitchen/action.php — trang "Bếp" (Public/nhân viên)
// Chạy TRƯỚC khi Router in header: xử lý form POST đổi trạng thái món rồi redirect (Post/Redirect/Get),
// sau đó đọc các món chờ bếp từ MySQL bằng hàm trong functions.php.
require_once __DIR__ . '/../../functions.php';

$title = 'Bếp';
$css = array('kitchen.css', 'notifications.css');
$scripts = array('notifications.js'); // chỉ giữ chuông thông báo; kitchen.js (gọi API) đã bỏ
$metaRefresh = 10; // tự tải lại trang mỗi 10 giây để thấy phiếu mới

// ---- Form POST: Nhận làm / Xong / Trả về chờ / Hoàn tác ----
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['kitchen_action'])) {
    $error = '';
    if (!post_csrf_ok()) {
        $error = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    } else {
        $ids = isset($_POST['item_ids']) && is_string($_POST['item_ids']) ? explode(',', $_POST['item_ids']) : array();
        $to = isset($_POST['to']) && is_string($_POST['to']) ? $_POST['to'] : '';
        $error = kitchen_set_status($ids, $to);
    }
    if ($error !== '') {
        $_SESSION['kitchen_error'] = $error;
    }
    redirect(url('kitchen'));
}
$kitchenError = isset($_SESSION['kitchen_error']) ? $_SESSION['kitchen_error'] : '';
unset($_SESSION['kitchen_error']);

$grouped = kitchen_group_tickets(kitchen_items());
$tickets = $grouped['tickets'];
$counts = $grouped['counts'];
$activeTickets = count($tickets['waiting']) + count($tickets['making']);
