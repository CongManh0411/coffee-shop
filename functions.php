<?php
// Shared business functions used by Public, Admin, and API routes.
require_once __DIR__ . '/api/modules/auth.php';
require_once __DIR__ . '/api/modules/staff.php';
require_once __DIR__ . '/api/modules/menu.php';
require_once __DIR__ . '/api/modules/tables.php';
require_once __DIR__ . '/api/modules/shifts.php';
require_once __DIR__ . '/api/modules/orders.php';
require_once __DIR__ . '/api/modules/cashshift.php';
require_once __DIR__ . '/api/modules/reservations.php';
require_once __DIR__ . '/api/modules/ops.php';

// Lấy toàn bộ món đang hiển thị để đưa vào thực đơn trên trang chủ.
function public_menu_items($categoryId = 0)
{
    $sql = "SELECT m.item_id, m.category_id, m.item_name, m.price, m.image, m.description, c.category_name
            FROM menu_items m
            JOIN categories c ON c.category_id = m.category_id
            WHERE m.is_hidden = 0";
    if ((int) $categoryId > 0) {
        return db()->fetchAll($sql . " AND m.category_id = ? ORDER BY m.item_id ASC", array((int) $categoryId));
    }
    return db()->fetchAll($sql . " ORDER BY m.item_id ASC");
}

function public_featured_menu_items($limit = 6)
{
    $items = public_menu_items();
    $byCategory = array();
    foreach ($items as $item) {
        $categoryId = (int) $item['category_id'];
        if (!isset($byCategory[$categoryId])) {
            $byCategory[$categoryId] = array();
        }
        $byCategory[$categoryId][] = $item;
    }

    $featured = array();
    for ($round = 0; count($featured) < max(0, (int) $limit); $round++) {
        $addedInRound = false;
        foreach ($byCategory as $categoryItems) {
            if (isset($categoryItems[$round])) {
                $featured[] = $categoryItems[$round];
                $addedInRound = true;
                if (count($featured) >= (int) $limit) {
                    break;
                }
            }
        }
        if (!$addedInRound) {
            break;
        }
    }
    return $featured;
}

function admin_menu_items()
{
    return db()->fetchAll(
        "SELECT m.item_id, m.item_name, m.description, m.price, m.image, m.is_hidden,
                c.category_id, c.category_name
         FROM menu_items m
         JOIN categories c ON c.category_id = m.category_id
         ORDER BY c.category_name, m.item_name"
    );
}

function public_menu_category_summary()
{
    return db()->fetchAll(
        "SELECT c.category_id, c.category_name, COUNT(m.item_id) AS item_count,
                MIN(m.price) AS min_price
         FROM categories c
         LEFT JOIN menu_items m ON m.category_id = c.category_id AND m.is_hidden = 0
         GROUP BY c.category_id, c.category_name
         ORDER BY c.category_id"
    );
}

function admin_shifts()
{
    return db()->fetchAll(
        "SELECT s.shift_id, s.shift_date, s.start_time, s.end_time,
                st.staff_id, st.full_name, st.role
         FROM shifts s
         JOIN staff st ON st.staff_id = s.staff_id
         ORDER BY s.shift_date, s.start_time, st.full_name"
    );
}

function admin_tables()
{
    return db()->fetchAll(
        "SELECT table_id, table_name, is_active
         FROM dining_tables
         ORDER BY table_id"
    );
}

function get_menu_item($id)
{
    return db()->fetchOne(
        "SELECT m.item_id, m.item_name, m.description, m.price, m.image,
                m.is_hidden, c.category_id, c.category_name
         FROM menu_items m
         JOIN categories c ON c.category_id = m.category_id
         WHERE m.item_id = ? AND m.is_hidden = 0",
        array((int) $id)
    );
}

function public_related_menu_items($categoryId, $exceptId, $limit = 3)
{
    return db()->fetchAll(
        "SELECT m.item_id, m.item_name, m.price, m.image, m.description,
                c.category_name, c.category_id
         FROM menu_items m
         JOIN categories c ON c.category_id = m.category_id
         WHERE m.is_hidden = 0 AND m.category_id = ? AND m.item_id <> ?
         ORDER BY m.item_id ASC
         LIMIT ?",
        array((int) $categoryId, (int) $exceptId, max(1, min(6, (int) $limit)))
    );
}

function public_menu_categories()
{
    return db()->fetchAll(
        "SELECT c.category_id, c.category_name
         FROM categories c
         JOIN menu_items m ON m.category_id = c.category_id
         WHERE m.is_hidden = 0
         GROUP BY c.category_id, c.category_name
         ORDER BY c.category_id"
    );
}

