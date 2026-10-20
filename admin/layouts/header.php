<?php
// layout/header.php — phần đầu HTML dùng chung cho mọi trang.
// Biến từ action.php của từng trang: $title, $css, $bodyClass.
$title     = isset($title) ? $title : 'Quản lý quán';
$css       = isset($css) ? $css : array();
$bodyClass = isset($bodyClass) ? $bodyClass : '';
$assetPrefix = !empty($isAdmin) ? '../assets/' : 'assets/';
$rootPrefix = !empty($isAdmin) ? '../' : './';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <base href="./">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script>window.COFFEE_ROOT = <?= json_encode($rootPrefix) ?>;</script>
    <link rel="stylesheet" href="<?= e($assetPrefix) ?>css/style.css">
<?php foreach ($css as $cssFile): ?>
    <link rel="stylesheet" href="<?= e(strpos($cssFile, 'http') === 0 ? $cssFile : $assetPrefix . 'css/' . $cssFile) ?>">
<?php endforeach; ?>
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?> data-page="<?= e($page) ?>">
<?php if ($page !== 'login' && $page !== '404'): ?>
    <div id="sidebar-container">
<?php require __DIR__ . '/sidebar.php'; ?>
    </div>
<?php endif; ?>
