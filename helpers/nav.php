<?php
// helpers/nav.php — menu thanh bên theo vai trò, do PHP in (thay cho PAGES_BY_ROLE trong JavaScript).

const NAV_ROLE_LABELS = array('manager' => 'Quản lý', 'waiter' => 'Phục vụ', 'kitchen' => 'Bếp', 'cashier' => 'Thu ngân');

// Mỗi mục: page (tham số ?page=), label, id (id thẻ a, dùng cho CSS cũ)
function nav_items($roleKey)
{
    $all = array(
        'manager' => array(
            array('tablemanage', 'Bàn', 'navTables'), array('menu', 'Thực đơn', 'navMenu'), array('revenue', 'Doanh thu', 'navRevenue'),
            array('history', 'Lịch sử', 'navHistory'), array('staff', 'Nhân viên', 'navStaff'), array('shifts', 'Lịch ca', 'navShifts'),
            array('warehouse', 'Kho', 'navWarehouse'), array('ops', 'Vận hành', 'navOps'), array('logs', 'Nhật ký', 'navLogs'),
            array('settings', 'Cài đặt Web', 'navSettings'),
        ),
        'waiter' => array(
            array('tables', 'Bàn', ''), array('notify', 'Thông báo', 'navNotif'), array('schedule', 'Lịch ca', 'navShift'), array('handover', 'Giao ca', 'navHandover'),
        ),
        'cashier' => array(
            array('tables', 'Bán hàng', 'navPOS'), array('notify', 'Thông báo', 'navNotif'), array('ops', 'Vận hành', 'navOps'), array('orders', 'Đơn hàng', 'navOrders'),
            array('cashshift', 'Ca & Quỹ', 'navCashShift'), array('schedule', 'Lịch ca', 'navShift'), array('handover', 'Giao ca', 'navHandover'),
        ),
        'kitchen' => array(
            array('kitchen', 'Bếp', 'navKitchen'), array('schedule', 'Lịch ca', 'navShift'), array('handover', 'Giao ca', 'navHandover'),
        ),
    );
    return $all[$roleKey] ?? array();
}

// Số bàn đã đủ món (chờ mang ra) — hiện làm số trên mục "Thông báo"
function nav_ready_count()
{
    $row = db()->fetchOne(
        "SELECT COUNT(*) AS n FROM (
            SELECT oi.order_id FROM order_items oi JOIN orders o ON o.order_id = oi.order_id
            WHERE o.status = 'open' AND oi.status IN ('waiting','making','done')
            GROUP BY oi.order_id HAVING SUM(oi.status <> 'done') = 0
         ) t"
    );
    return (int) ($row['n'] ?? 0);
}

// Menu thanh bên (aside.sidebar) dùng chung cho Admin và trang nhân viên
function render_sidebar($user, $page, $isAdmin)
{
    $roleKey = $user['roleKey'] ?? '';
    $assets = $isAdmin ? '../assets/' : 'assets/';
    $logoutAction = ($isAdmin ? '' : 'admin/') . 'index.php?page=login';
    ob_start();
    ?>
<aside class="sidebar">
    <div class="sidebar-shop"><img src="<?= e($assets) ?>images/images.jpg" alt="Coffee Logo" class="shop-logo" id="shopLogo"></div>
    <div class="sidebar-user">
        <div class="user-name" id="userName"><?= e($user['name'] ?? 'Người dùng') ?></div>
        <div class="user-role" id="userRole"><?= e(NAV_ROLE_LABELS[$roleKey] ?? '—') ?></div>
    </div>
    <div class="sidebar-nav"><div class="sidebar-menu"><div class="sidebar-menu" id="sidebarMenu">
        <?php foreach (nav_items($roleKey) as $it): ?>
            <a class="tab<?= $it[0] === $page ? ' active' : '' ?>" href="index.php?page=<?= e($it[0]) ?>" data-view="<?= e($it[0]) ?>"<?= $it[2] !== '' ? ' id="' . e($it[2]) . '"' : '' ?>><?= e($it[1]) ?><?php
                if ($it[0] === 'notify' && ($n = nav_ready_count()) > 0): ?> <span class="notif-badge"><?= (int) $n ?></span><?php endif; ?></a>
        <?php endforeach; ?>
    </div></div></div>
    <div class="sidebar-footer">
        <form method="post" action="<?= e($logoutAction) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="auth_action" value="logout">
            <button type="submit" class="btn-logout"><i class="ri-logout-box-r-line"></i> Đăng xuất</button>
        </form>
    </div>
</aside>
<?php
    return ob_get_clean();
}

// Trang chủ theo vai trò (sau khi đăng nhập), tính từ thư mục admin/
function nav_home_from_login($roleKey)
{
    $items = nav_items($roleKey);
    if (!$items) return 'index.php?page=login';
    return $roleKey === 'manager' ? 'index.php?page=' . $items[0][0] : '../index.php?page=' . $items[0][0];
}
