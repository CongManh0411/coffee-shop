<?php
// admin/pages/settings/action.php — Khai báo và xử lý trang Cài đặt Website & Quản lý Logo
require_once __DIR__ . '/../../../functions.php';
require_once __DIR__ . '/../../../helpers/upload.php';
$title = 'Cài đặt Website';
$bodyClass = 'admin-settings';
$css = array('settings.css');
$scripts = array();

$savedMessage = '';
$errorMessage = '';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST' && isset($_POST['save_settings'])) {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if ($submittedToken === '' || !hash_equals(csrf_token(), $submittedToken)) {
        $errorMessage = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    } else {
    $success = true;

    // 1. Xử lý đặt lại logo mặc định
    if (!empty($_POST['reset_logo'])) {
        update_setting('shop_logo', 'assets/images/images.jpg');
    }
    // 2. Xử lý tải lên tệp ảnh logo mới
    elseif (!empty($_FILES['shop_logo_file']) && $_FILES['shop_logo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Logo: JPG, PNG, WEBP hoặc GIF, tối đa 5MB (kiểm tra nội dung thật trong helpers/upload.php)
        list($logoPath, $uploadError) = upload_image_file($_FILES['shop_logo_file'], 'site', 'logo_');
        if ($uploadError !== '') {
            $errorMessage = $uploadError;
            $success = false;
        } elseif ($logoPath !== null) {
            update_setting('shop_logo', $logoPath);
        }
    }

    // Ảnh trang chủ và ba góc không gian (JPG, PNG, WEBP; không nhận GIF)
    foreach (array('space_garden', 'space_work', 'space_sofa', 'hero_main', 'hero_small', 'story') as $imageKey) {
        $field = $imageKey . '_image_file';
        if (empty($_FILES[$field])) continue;
        list($imagePath, $uploadError) = upload_image_file($_FILES[$field], 'site', $imageKey . '_', 5 * 1024 * 1024, false);
        if ($uploadError !== '') {
            $errorMessage = $uploadError;
            $success = false;
        } elseif ($imagePath !== null) {
            update_setting($imageKey . '_image', $imagePath);
        }
    }
    // 3. Xử lý các trường thông tin văn bản
    $keys = array(
        'brand_name',
        'brand_tagline',
        'brand_subtitle',
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
        'space_eyebrow', 'space_title', 'space_description',
        'space_garden_title', 'space_garden_tag', 'space_garden_description', 'space_garden_alt', 'space_garden_perks',
        'space_work_title', 'space_work_tag', 'space_work_description', 'space_work_alt', 'space_work_perks',
        'space_sofa_title', 'space_sofa_tag', 'space_sofa_description', 'space_sofa_alt', 'space_sofa_perks',
        'shop_address',
        'shop_phone',
        'shop_email',
        'shop_hours',
        'shop_hours_weekend',
        'shop_parking_note', 'shop_holiday_note', 'shop_social_handle',
        'social_facebook', 'social_instagram', 'social_tiktok',
        'footer_desc',
        'hero_eyebrow', 'hero_rating_title', 'hero_rating_note',
        'story_hl1_title', 'story_hl1_desc', 'story_hl2_title', 'story_hl2_desc',
        'story_hl3_title', 'story_hl3_desc', 'story_hl4_title', 'story_hl4_desc'
    );

    // Lương mỗi giờ: phải là số nguyên không âm (đồng), tối đa 1.000.000
    if (isset($_POST['handover_hourly_rate']) && trim((string) $_POST['handover_hourly_rate']) !== '') {
        $rate = trim((string) $_POST['handover_hourly_rate']);
        if (!ctype_digit($rate) || (int) $rate > 1000000) {
            $errorMessage = 'Lương mỗi giờ phải là số nguyên từ 0 đến 1.000.000 đồng.';
            $success = false;
        } else {
            update_setting('handover_hourly_rate', (string) (int) $rate);
        }
    }

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
}
