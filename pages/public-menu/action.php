<?php
// pages/public-menu/action.php — Thực đơn công khai. PHP đọc MySQL, lọc danh mục / tìm kiếm / phân trang bằng link và form GET ; tìm kiếm / đổi danh mục / phân trang chạy bằng AJAX (public-menu.js + ajax-pager.js), không có JS vẫn dùng được.
require_once __DIR__ . '/../../functions.php';

$title = 'Thực đơn | ' . brand_full();
$bodyClass = 'public-home public-menu-page';
$css = array('home.css', 'public-menu.css');
$scripts = array('ajax-pager.js', 'public-menu.js');

$pmPerPage = 9;
$pmSearch = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100, 'UTF-8');
$pmCategories = array();
$pmCategoryName = trim((string) ($_GET['category'] ?? ''));
$pmCategoryId = 0;
$pmItems = array();
$pmPager = paginate(0, 1, $pmPerPage);
$pmError = '';
try {
    $pmCategories = array_values(array_filter(public_menu_category_summary(), function ($c) { return (int) $c['item_count'] > 0; }));
    foreach ($pmCategories as $c) {
        if ($c['category_name'] === $pmCategoryName) $pmCategoryId = (int) $c['category_id'];
    }
    if ($pmCategoryId === 0) $pmCategoryName = '';
    $pmTotal = public_menu_count($pmCategoryId, $pmSearch);
    $pmPager = paginate($pmTotal, (int) ($_GET['p'] ?? 1), $pmPerPage);
    $pmItems = public_menu_items($pmCategoryId, $pmSearch, $pmPerPage, $pmPager['offset']);
} catch (Throwable $e) {
    $pmError = 'Chưa tải được thực đơn. Vui lòng thử tải lại trang.';
}
// Tham số giữ nguyên trên URL khi chuyển danh mục / trang
function pm_url($category, $search, $page = 1)
{
    $q = array('page' => 'public-menu');
    if ($category !== '') $q['category'] = $category;
    if ($search !== '') $q['q'] = $search;
    if ($page > 1) $q['p'] = $page;
    return 'index.php?' . http_build_query($q);
}
