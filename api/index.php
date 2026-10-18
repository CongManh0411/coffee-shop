<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/modules/auth.php';
require_once __DIR__ . '/modules/staff.php';

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

        default:
            json_error('Không tìm thấy API.', 404);
    }
} catch (Throwable $e) {
    json_error('Lỗi máy chủ: ' . $e->getMessage(), 500);
}
