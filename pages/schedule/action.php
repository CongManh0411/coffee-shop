<?php
// pages/schedule/action.php — trang "Lịch ca" (Public)
// Chạy TRƯỚC khi Router in header: lấy dữ liệu từ MySQL bằng hàm nghiệp vụ trong functions.php.
require_once __DIR__ . '/../../functions.php';

$title = 'Lịch ca';
$css = array('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'notifications.css', 'schedule.css', 'cashier.css');
$roleKey = $user['roleKey'] ?? '';
$scripts = array();
// Bảng lịch tuần do PHP in ra từ MySQL. Thu ngân tự đăng ký ca bằng form POST (shifts_register()), không dùng JavaScript.

// Tuần đang xem: ?week=YYYY-MM-DD (sai định dạng thì lấy tuần hiện tại).
$weekInput = isset($_GET['week']) ? $_GET['week'] : null;
$weekStart = schedule_week_start($weekInput);
$weekEnd = schedule_add_days($weekStart, 6);
$prevWeek = schedule_add_days($weekStart, -7);
$nextWeek = schedule_add_days($weekStart, 7);
$thisWeek = schedule_week_start(null);

$weekDays = array();
$dayLabels = array('Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN');
for ($i = 0; $i < 7; $i++) {
    $weekDays[] = schedule_add_days($weekStart, $i);
}

$today = date('Y-m-d');
$nowTs = time();
$myStaffId = (int) ($user['staffId'] ?? 0);

// Bảng tuần: nhân viên của mình lên đầu, còn lại giữ thứ tự theo tên.
$scheduleStaff = schedule_group_week(public_schedule_week($weekStart, $weekEnd));
if ($myStaffId && isset($scheduleStaff[$myStaffId])) {
    $scheduleStaff = array($myStaffId => $scheduleStaff[$myStaffId]) + $scheduleStaff;
}

// Hộp đầu trang: ai đang trong ca / chưa vào ca (hôm nay).
$inShift = array();
$waitingShift = array();
foreach (public_shifts_on($today) as $row) {
    $state = schedule_shift_state($row, $today, $nowTs);
    if ($state === 'working') {
        $inShift[] = $row;
    } elseif ($state === 'upcoming' || $state === 'noshow') {
        $row['state'] = $state;
        $waitingShift[] = $row;
    }
}

// "Ca của tôi": ca kế tiếp và tổng giờ làm trong tuần đang xem.
$myNextShift = $myStaffId ? public_next_shift($myStaffId, $today, date('H:i:s', $nowTs)) : null;
$myWeekMinutes = isset($scheduleStaff[$myStaffId]) ? $scheduleStaff[$myStaffId]['minutes'] : 0;


// ---- Thu ngân tự đăng ký / hủy ca (form POST có CSRF, xử lý bằng shifts_register()) ----
$regSlots = array(
    'sang' => array('label' => 'Ca sáng', 'start' => '06:30', 'end' => '14:00'),
    'chieu' => array('label' => 'Ca chiều', 'start' => '11:00', 'end' => '19:00'),
    'toi' => array('label' => 'Ca tối', 'start' => '17:00', 'end' => '22:00'),
);
$regDaysAhead = 14;
$regMessage = '';
$regError = '';
if ($roleKey === 'cashier' && $myStaffId) {
    $regFrom = $today;
    $regTo = schedule_add_days($today, $regDaysAhead - 1);
    $regLoad = function () use ($regFrom, $regTo) {
        return db()->fetchAll(
            "SELECT s.staff_id, s.shift_date, s.start_time, s.end_time, s.registered, st.role
             FROM shifts s JOIN staff st ON st.staff_id = s.staff_id WHERE s.shift_date BETWEEN ? AND ?",
            array($regFrom, $regTo)
        );
    };
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['reg_submit'])) {
        if (!post_csrf_ok()) {
            $_SESSION['reg_flash'] = array('error' => 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.');
        } else {
            $wanted = array(); // "YYYY-MM-DD|slotKey" => true
            foreach ((array) ($_POST['reg'] ?? array()) as $v) $wanted[(string) $v] = true;
            $rows = $regLoad();
            $added = 0; $removed = 0; $skipped = array();
            for ($i = 0; $i < $regDaysAhead; $i++) {
                $key = schedule_add_days($today, $i);
                $mine = null;
                foreach ($rows as $r) if ((int) $r['staff_id'] === $myStaffId && $r['shift_date'] === $key) $mine = $r;
                foreach ($regSlots as $sk => $sl) {
                    $isMine = $mine && schedule_clock($mine['start_time']) === $sl['start'] && schedule_clock($mine['end_time']) === $sl['end'];
                    $want = isset($wanted[$key . '|' . $sk]);
                    if ($want && !$isMine) {
                        $err = shifts_register($myStaffId, array('date' => $key, 'start' => $sl['start'], 'end' => $sl['end'], 'register' => true));
                        if ($err) $skipped[] = date('d/m', strtotime($key)) . ' ' . $sl['label'] . ': ' . $err; else $added++;
                    } elseif (!$want && $isMine && (int) $mine['registered'] === 1) {
                        $err = shifts_register($myStaffId, array('date' => $key, 'start' => $sl['start'], 'end' => $sl['end'], 'register' => false));
                        if ($err) $skipped[] = date('d/m', strtotime($key)) . ' ' . $sl['label'] . ': ' . $err; else $removed++;
                    }
                }
            }
            $_SESSION['reg_flash'] = array('ok' => 'Đã xếp ' . $added . ' ca, hủy ' . $removed . ' ca' . ($skipped ? ' • bỏ qua ' . count($skipped) . ' (' . implode('; ', array_slice($skipped, 0, 3)) . (count($skipped) > 3 ? '…' : '') . ')' : '') . '.');
        }
        redirect(url('schedule', array('week' => $weekStart)));
    }
    $regFlash = $_SESSION['reg_flash'] ?? null;
    unset($_SESSION['reg_flash']);
    $regMessage = $regFlash['ok'] ?? '';
    $regError = $regFlash['error'] ?? '';

    // Trạng thái từng ô: [ngày][slot] => array(checked, disabled, note)
    $regRows = $regLoad();
    $regGrid = array();
    for ($i = 0; $i < $regDaysAhead; $i++) {
        $key = schedule_add_days($today, $i);
        $mine = null;
        foreach ($regRows as $r) if ((int) $r['staff_id'] === $myStaffId && $r['shift_date'] === $key) $mine = $r;
        foreach ($regSlots as $sk => $sl) {
            $isMine = $mine && schedule_clock($mine['start_time']) === $sl['start'] && schedule_clock($mine['end_time']) === $sl['end'];
            $lockedByManager = $isMine && (int) $mine['registered'] !== 1;
            $other = $mine && !$isMine;
            $cashiers = 0;
            foreach ($regRows as $r) {
                if ($r['shift_date'] === $key && schedule_clock($r['start_time']) === $sl['start'] && schedule_clock($r['end_time']) === $sl['end']
                    && (int) $r['staff_id'] !== $myStaffId && $r['role'] === 'cashier') $cashiers++;
            }
            $full = $cashiers >= SHIFT_MAX_CASHIER_PER_SLOT;
            $past = strtotime($key . ' ' . $sl['start'] . ':00') <= time();
            $disabled = $past || $lockedByManager || (!$isMine && ($other || $full));
            $note = $lockedByManager ? 'Quản lý xếp' : ($past ? 'đã qua' : ($other ? 'đã có ca khác' : ((!$isMine && $full) ? 'đã đủ người' : '')));
            $regGrid[$key][$sk] = array('checked' => (bool) $isMine, 'disabled' => $disabled, 'note' => $note);
        }
    }
}
