<?php
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value)
{
    return number_format((float) $value, 0, ',', '.') . ' đ';
}

// Đường dẫn tới một trang qua Router: url('menu') => index.php?page=menu
function url($page)
{
    return 'index.php?page=' . rawurlencode($page);
}

// URL dùng trong khu Admin, nơi base URL là admin/.
function admin_url($page)
{
    return 'index.php?page=' . rawurlencode($page);
}

// Chuyển trang rồi dừng (dùng trong action.php, trước khi in header)
function redirect($location)
{
    header('Location: ' . $location);
    exit;
}

// Lấy tất cả cài đặt website từ bảng site_settings
function get_all_settings($refresh = false)
{
    static $settings = null;
    if ($settings !== null && !$refresh) {
        return $settings;
    }
    $settings = array();
    try {
        $rows = db()->fetchAll('SELECT setting_key, setting_value FROM site_settings');
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
    } catch (\Throwable $e) {
        // Fallback im lặng nếu chưa có bảng
    }
    return $settings;
}

// Lấy một giá trị cài đặt cụ thể
function setting($key, $default = '')
{
    $all = get_all_settings();
    return isset($all[$key]) && $all[$key] !== '' && $all[$key] !== null ? $all[$key] : $default;
}

// Cập nhật giá trị cài đặt
function update_setting($key, $value)
{
    try {
        db()->execute(
            'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?',
            array($key, $value, $value)
        );
        get_all_settings(true); // Làm mới bộ nhớ đệm
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}

// Lấy đường dẫn logo quán cho trang Public hoặc trang Admin
function shop_logo($isAdmin = false)
{
    $logo = setting('shop_logo', 'assets/images/images.jpg');
    if (!$logo) {
        $logo = 'assets/images/images.jpg';
    }
    // Nếu là ảnh base64 hoặc URL tuyệt đối
    if (strpos($logo, 'data:image') === 0 || strpos($logo, 'http://') === 0 || strpos($logo, 'https://') === 0) {
        return $logo;
    }
    // Xóa tiền tố thừa để chuẩn hóa
    $cleanPath = ltrim(str_replace('../', '', $logo), '/');
    if ($isAdmin) {
        return '../' . $cleanPath;
    }
    return $cleanPath;
}

// Lấy đường dẫn ảnh hoặc ảnh base64 của món ăn
function menu_item_image($item)
{
    if (!empty($item['image'])) {
        return $item['image'];
    }
    $nameMap = array(
        'Cà phê sữa đá' => 'assets/images/menu/01-ca-phe-sua-da.jpg',
        'Cà phê đen đá' => 'assets/images/menu/02-ca-phe-den-da.jpg',
        'Cà phê đen nóng' => 'assets/images/menu/03-ca-phe-den-nong.jpg',
        'Cà phê bạc xỉu' => 'assets/images/menu/04-ca-phe-bac-xiu.jpg',
        'Cà phê cappuccino' => 'assets/images/menu/05-ca-phe-cappuccino.jpg',
        'Trà sữa trân châu' => 'assets/images/menu/06-tra-sua-tran-chau.jpg',
        'Trà sữa matcha' => 'assets/images/menu/07-tra-sua-matcha.jpg',
        'Trà đào cam sả' => 'assets/images/menu/08-tra-dao-cam-sa.jpg',
        'Trà vải hoa hồng' => 'assets/images/menu/09-tra-vai-hoa-hong.jpg',
        'Bánh tiramisu' => 'assets/images/menu/10-banh-tiramisu.jpg',
        'Bánh croissant' => 'assets/images/menu/10-banh-tiramisu.jpg',
    );
    $name = trim($item['item_name'] ?? '');
    if (isset($nameMap[$name])) {
        return $nameMap[$name];
    }
    return 'assets/images/images.jpg';
}
