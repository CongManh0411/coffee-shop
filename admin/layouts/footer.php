<?php
// layout/footer.php — script dùng chung (api, auth, common) rồi tới script riêng của trang.
// Trang nào cần thứ tự/điều kiện riêng thì tạo pages/<trang>/scripts.php thay cho $scripts.
$scripts = isset($scripts) ? $scripts : array();
?>
<script src="<?= e($assetPrefix) ?>js/api.js"></script>
    <script src="<?= e($assetPrefix) ?>js/auth.js"></script>
    <script src="<?= e($assetPrefix) ?>js/common.js"></script>
<?php
$pageScripts = __DIR__ . '/../../pages/' . $page . '/scripts.php';
if (!is_file($pageScripts)) $pageScripts = __DIR__ . '/../pages/' . $page . '/scripts.php';
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
