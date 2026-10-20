<?php
// layout/footer.php — script dùng chung (api, auth, common) rồi tới script riêng của trang.
// Trang nào cần thứ tự/điều kiện riêng thì tạo pages/<trang>/scripts.php thay cho $scripts.
$scripts = isset($scripts) ? $scripts : array();
?>
<?php if ($page !== 'login' && !empty($user)): ?>
    <script>
        // Server là nơi quyết định ai đang đăng nhập -> đồng bộ sang localStorage cho auth.js/common.js đọc
        try { localStorage.setItem('coffee_auth_session_v1', <?= json_encode(json_encode($user), JSON_HEX_TAG | JSON_HEX_AMP) ?>); } catch (e) {}
    </script>
<?php endif; ?>
    <script src="asset/js/api.js"></script>
    <script src="asset/js/auth.js"></script>
    <script src="asset/js/common.js"></script>
<?php if (is_file(__DIR__ . '/../pages/' . $page . '/scripts.php')): ?>
<?php require __DIR__ . '/../pages/' . $page . '/scripts.php'; ?>
<?php else: ?>
<?php foreach ($scripts as $file): ?>
    <script src="asset/js/<?= e($file) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
