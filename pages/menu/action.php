<?php
require_once __DIR__ . '/../../functions.php';

$menuItems = array();
$menuCategories = array();
$selectedCategory = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
if ($selectedCategory === false || $selectedCategory === null || $selectedCategory < 0) {
    $selectedCategory = 0;
}
try {
    $menuCategories = array_values(array_filter(public_menu_category_summary(), function ($c) {
        return (int) $c['item_count'] > 0;
    }));
    $menuItems = public_menu_items($selectedCategory);
} catch (Throwable $e) {
    // Render a friendly empty state when the local database is unavailable.
}

$title = 'Thực đơn | ' . brand_full();
$bodyClass = 'public-catalog-page';
$css = array('public-catalog.css');
