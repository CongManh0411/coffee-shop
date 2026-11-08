<?php
// admin/pages/shifts/action.php — trang "Xếp lịch ca" (Admin)
// Xếp ca bằng form POST thường (có CSRF) do PHP xử lý rồi redirect (Post/Redirect/Get), không còn JavaScript gọi API.
require_once __DIR__ . '/../../../functions.php';

$title = 'Lịch ca';
$css = array('schedule.css', 'shifts.css');
$scripts = array();

// ---- Tuần đang xem: ?week=YYYY-MM-DD (về thứ 2 của tuần đó) ----
$weekParam = (string) ($_POST['week'] ?? $_GET['week'] ?? '');
$baseDate = shifts_valid_date($weekParam) ? $weekParam : date('Y-m-d');
$weekStart = date('Y-m-d', strtotime($baseDate . ' -' . ((int) date('N', strtotime($baseDate)) - 1) . ' days'));
$weekDays = array();
for ($i = 0; $i < 7; $i++) $weekDays[] = date('Y-m-d', strtotime($weekStart . ' +' . $i . ' days'));
$weekEnd = $weekDays[6];
$dayNames = array('Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN');

$shiftError = '';
$shiftPanel = null; // dữ liệu form Thêm/Sửa ca đang mở

// ---- Xử lý POST ----
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['shift_action'])) {
    $act = (string) $_POST['shift_action'];
    $ok = '';
    if (!post_csrf_ok()) {
        $shiftError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    } elseif ($act === 'save') {
        $id = (int) ($_POST['shift_id'] ?? 0);
        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $date = (string) ($_POST['date'] ?? '');
        $start = (string) ($_POST['start'] ?? '');
        $end = (string) ($_POST['end'] ?? '');
        if ($id > 0) {
            $shiftError = shifts_update($id, array('start' => $start, 'end' => $end));
        } else {
            $res = shifts_create(array('staffId' => $staffId, 'date' => $date, 'start' => $start, 'end' => $end));
            $shiftError = (string) $res[1];
        }
        if ($shiftError === '') $ok = $id > 0 ? 'Đã cập nhật ca.' : 'Đã thêm ca.';
        else $shiftPanel = array('id' => $id, 'staff_id' => $staffId, 'date' => $date, 'start' => $start, 'end' => $end);
    } elseif ($act === 'delete') {
        shifts_delete((int) ($_POST['shift_id'] ?? 0));
        $ok = 'Đã xóa ca.';
    } elseif ($act === 'copy') {
        $res = shifts_copy_week($weekStart);
        $shiftError = (string) $res[1];
        if ($shiftError === '') $ok = $res[0] ? 'Đã sao chép ' . (int) $res[0] . ' ca từ tuần trước.' : 'Các ca tuần này đã có sẵn, không sao chép thêm.';
    } elseif ($act === 'clear') {
        $res = shifts_clear_week($weekStart);
        $shiftError = (string) $res[1];
        if ($shiftError === '') $ok = $res[0] ? 'Đã xóa ' . (int) $res[0] . ' ca của tuần này.' : 'Tuần này chưa có ca nào.';
    } else {
        $shiftError = 'Thao tác không hợp lệ.';
    }
    if ($shiftError === '') {
        $_SESSION['shift_saved'] = $ok;
        redirect(admin_url('shifts', array('week' => $weekStart)));
    }
}
$shiftSavedMessage = $_SESSION['shift_saved'] ?? '';
unset($_SESSION['shift_saved']);

// ---- Dữ liệu đọc từ MySQL ----
$shiftStaff = db()->fetchAll("SELECT staff_id, full_name, role FROM staff WHERE role <> 'manager' AND status = 1 ORDER BY full_name");
$weekShifts = db()->fetchAll(
    "SELECT shift_id, staff_id, shift_date, start_time, end_time FROM shifts WHERE shift_date BETWEEN ? AND ? ORDER BY shift_date, start_time",
    array($weekStart, $weekEnd)
);
$shiftGrid = array(); // [staff_id][date] => danh sách ca
$dayCount = array();  // [date][staff_id] => true
foreach ($weekShifts as $sh) {
    $shiftGrid[(int) $sh['staff_id']][$sh['shift_date']][] = $sh;
    $dayCount[$sh['shift_date']][(int) $sh['staff_id']] = true;
}

// ---- Mở form Thêm/Sửa ca bằng link ?edit=ID hoặc ?add_staff=ID&add_date=YYYY-MM-DD ----
if ($shiftPanel === null) {
    if (isset($_GET['edit'])) {
        $r = db()->fetchOne("SELECT shift_id, staff_id, shift_date, start_time, end_time FROM shifts WHERE shift_id = ?", array((int) $_GET['edit']));
        if ($r) $shiftPanel = array('id' => (int) $r['shift_id'], 'staff_id' => (int) $r['staff_id'], 'date' => $r['shift_date'], 'start' => schedule_clock($r['start_time']), 'end' => schedule_clock($r['end_time']));
    } elseif (isset($_GET['add_staff'], $_GET['add_date']) && shifts_valid_date((string) $_GET['add_date'])) {
        $shiftPanel = array('id' => 0, 'staff_id' => (int) $_GET['add_staff'], 'date' => (string) $_GET['add_date'], 'start' => '06:30', 'end' => '14:00');
    }
}
$shiftPanelStaff = '';
if ($shiftPanel) {
    foreach ($shiftStaff as $s) if ((int) $s['staff_id'] === (int) $shiftPanel['staff_id']) $shiftPanelStaff = $s['full_name'];
    if ($shiftPanelStaff === '') $shiftPanel = null;
}

// Bảng "Danh sách ca sắp tới": đọc trực tiếp từ MySQL (JOIN shifts với staff), PHP in bằng foreach.
// Danh sách ca sắp tới: phân trang 10 ca/trang, trang nằm trên URL (?page=shifts&p=2)
$upcomingFrom = date('Y-m-d');
$upcomingPager = paginate(admin_shifts_upcoming_count($upcomingFrom), request_page('p'), 10);
$upcomingShifts = admin_shifts_upcoming($upcomingFrom, $upcomingPager['perPage'], $upcomingPager['offset']);
