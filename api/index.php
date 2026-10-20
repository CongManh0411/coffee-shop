<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/modules/auth.php';
require_once __DIR__ . '/modules/staff.php';
require_once __DIR__ . '/modules/menu.php';
require_once __DIR__ . '/modules/tables.php';
require_once __DIR__ . '/modules/shifts.php';
require_once __DIR__ . '/modules/orders.php';
require_once __DIR__ . '/modules/reservations.php';
require_once __DIR__ . '/modules/ops.php';

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
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            auth_logout();
            json_ok();
            break;

        case 'auth/me':
            json_ok(require_login());
            break;
        case 'menu/public':
            if ($method !== 'GET') json_error('Sai phương thức.', 405);
            $publicMenu = array();
            foreach (menu_list() as $group) {
                $visibleItems = array();
                foreach ($group['items'] as $item) {
                    if ($item['hidden']) continue;
                    unset($item['hidden']);
                    $visibleItems[] = $item;
                }
                if ($visibleItems) {
                    $publicMenu[] = array('category' => $group['category'], 'items' => $visibleItems);
                }
            }
            json_ok($publicMenu);
            break;
        case 'reservations/public':
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            list($reservationId, $err) = reservation_create_public(json_input());
            if ($err) json_error($err, 422);
            json_ok(array('reservationId' => $reservationId));
            break;
        case 'reservations':
            $me = require_role(array('manager', 'cashier'));
            if ($method === 'GET') {
                json_ok(reservations_list());
            }
            if ($method === 'POST') {
                list($reservationId, $err) = reservation_create(json_input(), (int) $me['staffId'], false);
                if ($err) json_error($err, 422);
                json_ok(array('reservationId' => $reservationId));
            }
            if ($method === 'PUT') {
                $err = reservation_update((int) ($_GET['id'] ?? 0), json_input());
                if ($err) json_error($err, 422);
                json_ok();
            }
            json_error('Sai phương thức.', 405);
            break;
        case 'ops':
            $me = require_role(array('manager', 'cashier'));
            if ($method === 'GET') json_ok(ops_snapshot());
            $err = ops_action(json_input(), $method, $me);
            if ($err) json_error($err, 422);
            json_ok();
            break;
        case 'ops/promotions':
            require_role(array('manager', 'cashier', 'waiter'));
            if ($method !== 'GET') json_error('Sai phương thức.', 405);
            json_ok(ops_promotion_list());
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
            if ($method === 'GET') { require_login(); json_ok(tables_list()); }
            require_role(array('manager'));
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
            if ($method === 'DELETE') { tables_delete((int)($_GET['id'] ?? 0)); json_ok(); }
            api_fail('Sai phương thức.', 405);
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
        case 'orders':
            $me = require_login();
            if ($method === 'GET') {
                $status = $_GET['status'] ?? 'open';
                if (!in_array($status, array('open', 'paid', 'cancelled'), true)) json_error('Trạng thái đơn không hợp lệ.');
                if ($status !== 'open' && !in_array($me['roleKey'], array('manager', 'cashier'), true)) {
                    json_error('Bạn không có quyền xem hóa đơn đã chốt.', 403);
                }
                json_ok(orders_list($status));
            }
            if ($method === 'POST') {
                $me = require_role(array('manager', 'waiter', 'cashier'));
                list($id, $err) = orders_create(json_input(), $me);
                if ($err) json_error($err);
                json_ok(array('id' => $id));
            }
            json_error('Sai phương thức.', 405);
            break;
        case 'orders/invoices':
            $me = require_role(array('manager', 'cashier'));
            if ($method !== 'GET') json_error('Sai phÆ°Æ¡ng thá»©c.', 405);
            $status = $_GET['status'] ?? 'all';
            if (!in_array($status, array('all', 'paid', 'cancelled'), true)) json_error('Tráº¡ng thÃ¡i hÃ³a Ä‘Æ¡n khÃ´ng há»£p lá»‡.');
            json_ok(orders_invoice_list($status));
            break;
        case 'orders/invoices/cancel':
            $me = require_role(array('manager'));
            if ($method !== 'POST') json_error('Sai phÆ°Æ¡ng thá»©c.', 405);
            $in = json_input();
            $err = orders_cancel_paid((int) ($in['orderId'] ?? 0), $in['reason'] ?? '', $me);
            if ($err) json_error($err);
            json_ok();
            break;
        case 'orders/invoices/refund':
            $me = require_role(array('manager', 'cashier'));
            if ($method !== 'POST') json_error('Sai phÆ°Æ¡ng thá»©c.', 405);
            $in = json_input();
            $err = orders_refund_paid((int) ($in['orderId'] ?? 0), $in['reason'] ?? '', $me);
            if ($err) json_error($err);
            json_ok();
            break;
        case 'orders/items':
            require_role(array('manager', 'waiter', 'cashier'));
            if ($method === 'POST') {
                list($id, $err) = orders_add_item(json_input());
                if ($err) json_error($err);
                json_ok(array('id' => $id));
            }
            $itemId = (int) ($_GET['id'] ?? 0);
            if ($method === 'PUT') {
                $err = orders_update_item($itemId, json_input());
                if ($err) json_error($err);
                json_ok();
            }
            if ($method === 'DELETE') {
                $err = orders_delete_item($itemId);
                if ($err) json_error($err);
                json_ok();
            }
            json_error('Sai phương thức.', 405);
            break;
        case 'orders/confirm':
            require_role(array('manager', 'waiter', 'cashier'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $err = orders_confirm((int) (json_input()['orderId'] ?? 0));
            if ($err) json_error($err);
            json_ok();
            break;
        case 'orders/cancel':
            require_role(array('manager', 'waiter', 'cashier'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $err = orders_cancel_empty((int) (json_input()['orderId'] ?? 0));
            if ($err) json_error($err);
            json_ok();
            break;
        case 'orders/items/cancel':
            $me = require_role(array('manager', 'waiter', 'cashier'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $in = json_input();
            $err = orders_cancel_item((int) ($in['itemId'] ?? 0), $in['reason'] ?? '', $me);
            if ($err) json_error($err);
            json_ok();
            break;
        case 'orders/move':
            require_role(array('manager', 'waiter', 'cashier'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $in = json_input();
            $err = orders_move((int) ($in['orderId'] ?? 0), (int) ($in['tableId'] ?? 0));
            if ($err) json_error($err);
            json_ok();
            break;
        case 'orders/merge':
            require_role(array('manager', 'waiter', 'cashier'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $in = json_input();
            $err = orders_merge((int) ($in['sourceOrderId'] ?? 0), (int) ($in['targetOrderId'] ?? 0));
            if ($err) json_error($err);
            json_ok();
            break;
        case 'orders/split':
            $me = require_role(array('manager', 'waiter', 'cashier'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            $in = json_input();
            list($newOrder, $err) = orders_split((int) ($in['sourceOrderId'] ?? 0), (int) ($in['tableId'] ?? 0), $in['itemIds'] ?? array(), $me);
            if ($err) json_error($err);
            json_ok($newOrder);
            break;
        case 'orders/kitchen':
            require_role(array('manager', 'kitchen'));
            if ($method !== 'PUT') json_error('Sai phương thức.', 405);
            $err = orders_kitchen_status(json_input());
            if ($err) json_error($err);
            json_ok();
            break;
        case 'orders/pay':
            $me = require_role(array('manager', 'cashier', 'waiter'));
            if ($method !== 'POST') json_error('Sai phương thức.', 405);
            list($invoice, $err) = orders_pay(json_input(), $me);
            if ($err) json_error($err);
            json_ok($invoice);
            break;
        default:
            json_error('Không tìm thấy API.', 404);
    }
} catch (ApiException $e) {
    json_error($e->getMessage(), $e->status);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        json_error('Dữ liệu bị trùng.', 409);
    }
    error_log($e);
    json_error('Đã xảy ra lỗi máy chủ.', 500);
} catch (Throwable $e) {
    error_log($e);
    json_error('Đã xảy ra lỗi máy chủ.', 500);
}
