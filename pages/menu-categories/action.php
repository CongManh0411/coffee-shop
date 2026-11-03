<?php
require_once __DIR__ . '/../../functions.php';
$categorySummaries = public_menu_category_summary();
$title = 'Danh mục thực đơn | ' . brand_full();
$bodyClass = 'public-catalog-page';
$css = array('public-catalog.css');
