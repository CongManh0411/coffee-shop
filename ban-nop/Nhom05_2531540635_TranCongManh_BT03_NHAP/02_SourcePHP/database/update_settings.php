<?php
require_once __DIR__ . '/../admin/includes/bootstrap.php';

try {
    $exists = db()->fetchOne("SELECT setting_key FROM site_settings WHERE setting_key = 'shop_logo'");
    if (!$exists) {
        db()->execute(
            "INSERT INTO site_settings (setting_key, setting_value, setting_label) VALUES ('shop_logo', 'assets/images/images.jpg', 'Ảnh logo quán')"
        );
    }
    echo "shop_logo initialized: " . setting('shop_logo') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
