<?php
// layout/footer.php — script dùng chung (api, auth, common) rồi tới script riêng của trang.
// Trang nào cần thứ tự/điều kiện riêng thì tạo pages/<trang>/scripts.php thay cho $scripts.
$scripts = isset($scripts) ? $scripts : array();
?>
<?php if (!in_array($page, array('home', 'public-menu', '404'), true) && !empty($user)): ?>
    <script>
        // Server là nơi quyết định ai đang đăng nhập -> đồng bộ sang localStorage cho auth.js/common.js đọc
        try { localStorage.setItem('coffee_auth_session_v1', <?= json_encode(json_encode($user), JSON_HEX_TAG | JSON_HEX_AMP) ?>); } catch (e) {}
    </script>
<?php endif; ?>
<?php if ($page !== '404'): ?>
    <script src="<?= e($assetPrefix) ?>js/api.js"></script>
<?php endif; ?>
<?php if (!in_array($page, array('home', 'public-menu', '404'), true)): ?>
    <script src="<?= e($assetPrefix) ?>js/auth.js"></script>
    <script src="<?= e($assetPrefix) ?>js/common.js"></script>
<?php endif; ?>
<?php
$pageScripts = __DIR__ . '/../../pages/' . $page . '/scripts.php';
if (is_file($pageScripts)):
?>
<?php require $pageScripts; ?>
<?php else: ?>
<?php foreach ($scripts as $file): ?>
    <script src="<?= e($assetPrefix) ?>js/<?= e($file) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
