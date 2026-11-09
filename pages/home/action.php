<?php
require_once __DIR__ . '/../../functions.php';
// Đặt bàn: form POST (có CSRF + ô bẫy spam) do PHP kiểm tra và INSERT, rồi redirect về #booking (Post/Redirect/Get).
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['reservation_submit'])) {
    $resError = '';
    if (!post_csrf_ok()) {
        $resError = 'Phiên làm việc đã hết hạn. Vui lòng thử gửi lại.';
    } else {
        list($resId, $resError) = reservation_create_public(array(
            'name'       => $_POST['name'] ?? '',
            'phone'      => $_POST['phone'] ?? '',
            'reservedAt' => $_POST['reservedAt'] ?? '',
            'partySize'  => $_POST['partySize'] ?? '',
            'note'       => $_POST['note'] ?? '',
            'website'    => $_POST['website'] ?? '',
        ));
    }
    if ($resError) {
        $_SESSION['reservation_flash'] = array('type' => 'error', 'text' => $resError, 'old' => array(
            'name' => (string) ($_POST['name'] ?? ''), 'phone' => (string) ($_POST['phone'] ?? ''),
            'reservedAt' => (string) ($_POST['reservedAt'] ?? ''), 'partySize' => (string) ($_POST['partySize'] ?? '2'),
            'note' => (string) ($_POST['note'] ?? ''),
        ));
    } else {
        $_SESSION['reservation_flash'] = array('type' => 'success', 'text' => '🎉 Quán đã nhận yêu cầu đặt bàn thành công! Nhân viên sẽ sớm liên hệ qua số điện thoại để xác nhận giữ bàn cho bạn.');
    }
    redirect('index.php?page=home#booking');
}
$reservationFlash = $_SESSION['reservation_flash'] ?? null;
unset($_SESSION['reservation_flash']);
$resOld = ($reservationFlash['old'] ?? null) ?: array('name' => '', 'phone' => '', 'reservedAt' => date('Y-m-d\TH:i', (int) (ceil((time() + 3600) / 900) * 900)), 'partySize' => '2', 'note' => '');

$dbMenuItems = array();
try {
    $dbMenuItems = public_featured_menu_items(6);
} catch (Throwable $e) {
    $dbMenuItems = array();
}
$title = brand_full() . ' | Cà phê rang mộc & Không gian thư giãn';
$bodyClass = 'public-home';
$css = array('home.css');
$scripts = array('home.js');
