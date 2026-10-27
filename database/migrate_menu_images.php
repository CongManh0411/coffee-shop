<?php
// Chuyển ảnh món đang lưu trong database (data:image/...;base64) thành file trong assets/images/menu/.
// Chạy một lần:  php database/migrate_menu_images.php
// Sau khi chạy, cột menu_items.image chỉ còn đường dẫn, ví dụ assets/images/menu/item_ab12cd34ef56.jpg
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../functions.php';

$rows = db()->fetchAll("SELECT item_id, item_name, image FROM menu_items WHERE image LIKE 'data:image/%'");
$done = 0;
$failed = 0;
foreach ($rows as $row) {
    list($path, $err) = menu_store_data_image($row['image']);
    if ($err !== '') {
        fwrite(STDERR, "Bo qua mon #{$row['item_id']} ({$row['item_name']}): $err\n");
        $failed++;
        continue;
    }
    db()->execute('UPDATE menu_items SET image = ? WHERE item_id = ?', array($path, (int) $row['item_id']));
    echo "Mon #{$row['item_id']} ({$row['item_name']}) -> $path\n";
    $done++;
}
echo "Xong: $done mon da chuyen thanh file, $failed loi.\n";
