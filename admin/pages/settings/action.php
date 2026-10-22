<?php
// admin/pages/settings/action.php — Khai báo và xử lý trang Cài đặt Website & Quản lý Logo
$title = 'Cài đặt Website';
$bodyClass = 'admin-settings';
$css = array('settings.css');
$scripts = array();

$savedMessage = '';
$errorMessage = '';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST' && isset($_POST['save_settings'])) {
    $success = true;

    // 1. Xử lý đặt lại logo mặc định
    if (!empty($_POST['reset_logo'])) {
        update_setting('shop_logo', 'assets/images/images.jpg');
    }
    // 2. Xử lý tải lên tệp ảnh logo mới
    elseif (!empty($_FILES['shop_logo_file']) && $_FILES['shop_logo_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['shop_logo_file'];
        $allowed = array(
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/gif'  => 'gif'
        );

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            $errorMessage = 'Định dạng ảnh logo không hợp lệ. Vui lòng chọn ảnh JPG, PNG, WEBP hoặc SVG.';
            $success = false;
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errorMessage = 'Ảnh logo quá lớn (tối đa 5MB). Vui lòng chọn ảnh nhẹ hơn.';
            $success = false;
        } else {
            $ext = $allowed[$mime];
            $uploadDir = __DIR__ . '/../../../assets/images/uploads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $filename = 'logo_' . time() . '.' . $ext;
            $destPath = $uploadDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                update_setting('shop_logo', 'assets/images/uploads/' . $filename);
            } else {
                $errorMessage = 'Không thể lưu tệp ảnh tải lên. Vui lòng kiểm tra quyền ghi thư mục assets/images/uploads.';
                $success = false;
            }
        }
    }

    // 3. Xử lý các trường thông tin văn bản
    $keys = array(
        'brand_name',
        'brand_tagline',
        'hero_badge',
        'hero_title',
        'hero_subtitle',
        'hero_lede',
        'hero_suggestion',
        'hero_suggestion_desc',
        'story_title',
        'story_subtitle',
        'story_quote',
        'story_desc',
        'shop_address',
        'shop_phone',
        'shop_email',
        'shop_hours',
        'shop_hours_weekend'
    );

    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $val = trim((string) $_POST[$k]);
            update_setting($k, $val);
        }
    }

    if ($success && empty($errorMessage)) {
        $savedMessage = 'Đã lưu cài đặt website và cập nhật logo thành công! Các thay đổi đã được áp dụng trực tiếp.';
    }
}
