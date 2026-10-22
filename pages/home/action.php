<?php
require_once __DIR__ . '/../../functions.php';
$dbMenuItems = array();
try {
    $dbMenuItems = public_menu_items();
} catch (Throwable $e) {
    $dbMenuItems = array();
}
$title = 'Góc Nhỏ Coffee & Tea | Cà phê rang mộc & Không gian thư giãn';
$bodyClass = 'public-home';
$css = array('home.css');
$scripts = array('home.js');
