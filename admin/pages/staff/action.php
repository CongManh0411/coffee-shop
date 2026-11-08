<?php
// admin/pages/staff/action.php — trang "Nhân viên" (Admin)
// Thêm / sửa / xóa nhân viên bằng form POST (có CSRF) do PHP xử lý rồi redirect (Post/Redirect/Get), không còn JavaScript gọi API.
require_once __DIR__ . '/../../../functions.php';

$title = 'Nhân viên';
$css = array('staff.css');
$scripts = array();

$staffError = '';
$staffForm = null; // dữ liệu form đang mở (null = chỉ hiện danh sách)
$staffFormKeys = array('fullName', 'username', 'role', 'phone', 'email', 'gender', 'birthDate');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['staff_action'])) {
    $act = (string) $_POST['staff_action'];
    $postId = (int) ($_POST['staff_id'] ?? 0);
    $ok = '';
    if (!post_csrf_ok()) {
        $staffError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    } elseif ($act === 'save') {
        $data = array(
            'fullName'  => trim((string) ($_POST['fullName'] ?? '')),
            'username'  => trim((string) ($_POST['username'] ?? '')),
            'password'  => (string) ($_POST['password'] ?? ''),
            'role'      => (string) ($_POST['role'] ?? ''),
            'phone'     => preg_replace('/[\s.\-]/', '', (string) ($_POST['phone'] ?? '')),
            'email'     => trim((string) ($_POST['email'] ?? '')),
            'gender'    => (string) ($_POST['gender'] ?? ''),
            'birthDate' => (string) ($_POST['birthDate'] ?? ''),
        );
        if ($data['birthDate'] !== '') {
            $b = DateTime::createFromFormat('!Y-m-d', $data['birthDate']);
            $age = $b ? (int) $b->diff(new DateTime('today'))->y : -1;
            if (!$b || $b > new DateTime('today') || $age < 16 || $age > 80) {
                $staffError = 'Ngày sinh không hợp lệ (nhân viên phải từ 16 đến 80 tuổi).';
            }
        }
        if ($staffError === '') {
            if ($postId > 0) {
                $staffError = staff_update($postId, $data);
                $ok = 'Đã lưu thay đổi nhân viên.';
            } else {
                list($newId, $staffError) = staff_create($data);
                $ok = 'Đã tạo tài khoản nhân viên.';
            }
        }
        if ($staffError !== '') {
            unset($data['password']);
            $staffForm = $data + array('id' => $postId);
        }
    } elseif ($act === 'delete') {
        if ($postId === (int) ($_SESSION['user']['staffId'] ?? 0)) {
            $staffError = 'Bạn không thể xóa tài khoản đang đăng nhập.';
        } else {
            staff_delete($postId);
            $ok = 'Đã xóa nhân viên.';
        }
    } else {
        $staffError = 'Thao tác không hợp lệ.';
    }
    if ($staffError === '') {
        $_SESSION['staff_saved'] = $ok;
        redirect(admin_url('staff'));
    }
}
$staffSavedMessage = $_SESSION['staff_saved'] ?? '';
unset($_SESSION['staff_saved']);

$staffRows = staff_list();

// Mở form bằng link ?add=1 hoặc ?edit=ID (khi POST lỗi thì giữ lại dữ liệu vừa nhập)
if ($staffForm === null) {
    if (isset($_GET['edit'])) {
        foreach ($staffRows as $r) {
            if ((int) $r['id'] === (int) $_GET['edit']) $staffForm = $r;
        }
    } elseif (isset($_GET['add'])) {
        $staffForm = array('id' => 0, 'fullName' => '', 'username' => '', 'role' => '', 'phone' => '', 'email' => '', 'gender' => '', 'birthDate' => '');
    }
}

function staff_age_of($iso)
{
    $b = DateTime::createFromFormat('!Y-m-d', (string) $iso);
    return $b ? (int) $b->diff(new DateTime('today'))->y : 0;
}
