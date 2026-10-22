<?php
require_once __DIR__ . '/../../functions.php';
$dbMenuItems = array();
try {
    $dbMenuItems = public_featured_menu_items(6);
} catch (Throwable $e) {
    $dbMenuItems = array();
}
$title = 'Góc Nhỏ Coffee & Tea | Cà phê rang mộc & Không gian thư giãn';
$bodyClass = 'public-home';
$css = array('home.css');
$scripts = array('home.js');
