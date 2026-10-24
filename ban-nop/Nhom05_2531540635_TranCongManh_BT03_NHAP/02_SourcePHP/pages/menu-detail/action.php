<?php
require_once __DIR__ . '/../../functions.php';

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$menuItem = ($itemId && $itemId > 0) ? get_menu_item($itemId) : null;
$relatedMenuItems = $menuItem
    ? public_related_menu_items($menuItem['category_id'], $menuItem['item_id'], 3)
    : array();
if (!$menuItem) {
    http_response_code(404);
}
$title = $menuItem ? $menuItem['item_name'] . ' | Góc Nhỏ Coffee & Tea' : 'Không tìm thấy món';
$bodyClass = 'public-catalog-page';
$css = array('public-catalog.css');
