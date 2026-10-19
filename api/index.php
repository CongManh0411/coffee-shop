<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/modules/auth.php';
require_once __DIR__ . '/modules/staff.php';
require_once __DIR__ . '/modules/menu.php';
require_once __DIR__ . '/modules/tables.php';
require_once __DIR__ . '/modules/shifts.php';

$route  = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($route) {
        case 'auth/login':
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $in = json_input();
            $user = auth_login($in['username'] ?? '', $in['password'] ?? '');
            if (!$user) json_error('Sai tên đăng nhập hoặc mật khẩu.', 401);
            json_ok($user);
            break;

        case 'auth/logout':
            auth_logout();
            json_ok();
            break;

        case 'auth/me':
            json_ok(require_login());
            break;
        case 'staff':
            $me = require_role(array('manager'));
            if ($method === 'GET') {
                json_ok(staff_list());
            }
            if ($method === 'POST') {
                list($id, $err) = staff_create(json_input());
                if ($err) json_error($err);
                json_ok(array('id' => $id));
            }
            if ($method === 'PUT') {
                $err = staff_update((int) ($_GET['id'] ?? 0), json_input());
                if ($err) json_error($err);
                json_ok();
            }
            if ($method === 'DELETE') {
                $id = (int) ($_GET['id'] ?? 0);
                if ($id === $me['staffId']) json_error('Bạn không thể xóa tài khoản đang đăng nhập.');
                staff_delete($id);
                json_ok();
            }
            json_error('Sai phương thức.', 405);
            break;
        case 'menu':
            if ($method === 'GET') {
                require_login();                 // mọi vai trò đều xem được thực đơn
                json_ok(menu_list());
            }
            require_role(array('manager'));      // chỉ Quản lý được thêm/sửa/xóa
            if ($method === 'POST') {
                list($id, $err) = menu_create(json_input());
                if ($err) json_error($err);
                json_ok(array('id' => $id));
            }
            if ($method === 'PUT') {
                $err = menu_update((int) ($_GET['id'] ?? 0), json_input());
                if ($err) json_error($err);
                json_ok();
            }
            if ($method === 'DELETE') {
                menu_delete((int) ($_GET['id'] ?? 0));
                json_ok();
            }
            json_error('Sai phương thức.', 405);
            break;
        case 'tables':
            if ($method === 'GET') {
                require_login();                 // mọi vai trò đều xem được danh sách bàn
                json_ok(tables_list());
            }
            require_role(array('manager'));      // chỉ Quản lý được thêm/sửa/xóa bàn
            if ($method === 'POST') {
                list($id, $err) = tables_create(json_input());
                if ($err) json_error($err);
                json_ok(array('id' => $id));
            }
            if ($method === 'PUT') {
                $err = tables_update((int) ($_GET['id'] ?? 0), json_input());
                if ($err) json_error($err);
                json_ok();
            }
            if ($method === 'DELETE') {
                tables_delete((int) ($_GET['id'] ?? 0));
                json_ok();
            }
            json_error('Sai phương thức.', 405);
            break;
        case 'shifts':
            if ($method === 'GET') {
                require_login();
                json_ok(shifts_list());
            }
            require_role(array('manager'));      // chỉ Quản lý xếp ca
            if ($method === 'POST') {
                list($id, $err) = shifts_create(json_input());
                if ($err) json_error($err);
                json_ok(array('id' => $id));
            }
            if ($method === 'PUT') {
                $err = shifts_update((int) ($_GET['id'] ?? 0), json_input());
                if ($err) json_error($err);
                json_ok();
            }
            if ($method === 'DELETE') {
                shifts_delete((int) ($_GET['id'] ?? 0));
                json_ok();
            }
            json_error('Sai phương thức.', 405);
            break;
        case 'shifts/copy':
            require_role(array('manager'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $in = json_input();
            list($n, $err) = shifts_copy_week($in['weekStart'] ?? '');
            if ($err) json_error($err);
            json_ok(array('added' => $n));
            break;
        case 'shifts/clear':
            require_role(array('manager'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $in = json_input();
            list($n, $err) = shifts_clear_week($in['weekStart'] ?? '');
            if ($err) json_error($err);
            json_ok(array('removed' => $n));
            break;
        default:
            json_error('Không tìm thấy API.', 404);
    }
} catch (Throwable $e) {
    json_error('Lỗi máy chủ: ' . $e->getMessage(), 500);
}
