<!-- pages/schedule/scripts.php — script của trang Lịch ca (phụ thuộc vai trò nên không khai báo trong $scripts) -->
<!-- Phục vụ / Thu ngân: có nút Thanh toán trong sidebar nên cần kho + lõi thu ngân.
     Bếp chỉ có nút Giao ca. -->
<script>
    (function () {
        var ss = AuthAPI.getSession();
        if (!ss) return;
        if (ss.roleKey === 'cashier' || ss.roleKey === 'waiter') {
            document.write('<script src="asset/js/menu-data.js"><\/script><script src="asset/js/inventory.js"><\/script><script src="asset/js/cashier-core.js"><\/script><script src="asset/js/notifications.js"><\/script>');
        } else if (ss.roleKey === 'kitchen') {
            document.write('<script src="asset/js/notifications.js"><\/script>');
        }
    })();
</script>
<script src="asset/js/schedule.js"></script>
<!-- Thu ngân: đăng ký ca (chạy sau schedule.js để có #schMe làm mốc chèn) -->
<script>
    (function () {
        var ss = AuthAPI.getSession();
        if (ss && ss.roleKey === 'cashier') document.write('<script src="asset/js/shift-register.js"><\/script>');
    })();
</script>
