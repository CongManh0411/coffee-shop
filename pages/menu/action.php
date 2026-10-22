<?php
require_once __DIR__ . '/../../functions.php';

$menuItems = array();
$menuCategories = array();
$selectedCategory = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
if ($selectedCategory === false || $selectedCategory === null || $selectedCategory < 0) {
    $selectedCategory = 0;
}
try {
    $menuCategories = public_menu_categories();
    $menuItems = public_menu_items($selectedCategory);
} catch (Throwable $e) {
    // Render a friendly empty state when the local database is unavailable.
}

$title = 'Thực đơn | Góc Nhỏ Coffee & Tea';
$bodyClass = 'public-catalog-page';
$css = array('public-catalog.css');
