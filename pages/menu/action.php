<?php
require_once __DIR__ . '/../../functions.php';

$menuItems = array();
$menuSearch = request_search('q');
$menuPerPage = 9;
$menuPager = paginate(0, 1, $menuPerPage);
$menuCategories = array();
$selectedCategory = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
if ($selectedCategory === false || $selectedCategory === null || $selectedCategory < 0) {
    $selectedCategory = 0;
}
try {
    $menuCategories = array_values(array_filter(public_menu_category_summary(), function ($c) {
        return (int) $c['item_count'] > 0;
    }));
    // Tìm kiếm + phân trang chạy ở server; trạng thái nằm trên URL (?page=menu&q=...&category=...&p=...)
    $menuPager = paginate(public_menu_count($selectedCategory, $menuSearch), request_page('p'), $menuPerPage);
    $menuItems = public_menu_items($selectedCategory, $menuSearch, $menuPager['perPage'], $menuPager['offset']);
} catch (Throwable $e) {
    // Render a friendly empty state when the local database is unavailable.
}

$title = 'Thực đơn | ' . brand_full();
$bodyClass = 'public-catalog-page';
$css = array('public-catalog.css');
