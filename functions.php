<?php
// Shared business functions used by Public, Admin, and API routes.
require_once __DIR__ . '/api/modules/auth.php';
require_once __DIR__ . '/api/modules/staff.php';
require_once __DIR__ . '/api/modules/menu.php';
require_once __DIR__ . '/api/modules/tables.php';
require_once __DIR__ . '/api/modules/shifts.php';
require_once __DIR__ . '/api/modules/orders.php';
require_once __DIR__ . '/api/modules/reservations.php';
require_once __DIR__ . '/api/modules/ops.php';

// Lấy toàn bộ món đang hiển thị để đưa vào thực đơn trên trang chủ.
function public_menu_items()
{
    return db()->fetchAll(
        "SELECT m.item_id, m.item_name, m.price, m.image, m.description, c.category_name
         FROM menu_items m
         JOIN categories c ON c.category_id = m.category_id
         WHERE m.is_hidden = 0
         ORDER BY m.item_id ASC"
    );
}

