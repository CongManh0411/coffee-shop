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
    elseif (!empty($_FILES['shop_logo_file']) && $_FILES['shop_logo_file']['error'] === UPLOAD_ERR_OK) {
        $uploadFile = $_FILES['shop_logo_file'];
        $allowed = array(
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        );

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $uploadFile['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            $errorMessage = 'Định dạng ảnh logo không hợp lệ. Vui lòng chọn ảnh JPG, PNG, WEBP hoặc GIF.';
            $success = false;
        } elseif ($uploadFile['size'] > 5 * 1024 * 1024) {
            $errorMessage = 'Ảnh logo quá lớn (tối đa 5MB). Vui lòng chọn ảnh nhẹ hơn.';
            $success = false;
        } else {
            $ext = $allowed[$mime];
            $uploadDir = __DIR__ . '/../../../assets/images/uploads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0750, true);
            }
            $uploadFilename = 'logo_' . time() . '.' . $ext;
            $destPath = $uploadDir . '/' . $uploadFilename;

            if (move_uploaded_file($uploadFile['tmp_name'], $destPath)) {
                update_setting('shop_logo', 'assets/images/uploads/' . $uploadFilename);
            } else {
                $errorMessage = 'Không thể lưu tệp ảnh tải lên. Vui lòng kiểm tra quyền ghi thư mục assets/images/uploads.';
                $success = false;
            }
        }
    }

    // Ảnh trang chủ và ba góc không gian
    foreach (array('space_garden', 'space_work', 'space_sofa', 'hero_main', 'hero_small', 'story') as $imageKey) {
        $field = $imageKey . '_image_file';
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) continue;
        $uploadFile = $_FILES[$field];
        if ($uploadFile['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = 'Không thể nhận ảnh tải lên.';
            $success = false;
            continue;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $uploadFile['tmp_name']);
        finfo_close($finfo);
        $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
        if (!isset($allowed[$mime]) || $uploadFile['size'] > 5 * 1024 * 1024) {
            $errorMessage = 'Ảnh phải là JPG, PNG hoặc WEBP và tối đa 5 MB.';
            $success = false;
            continue;
        }
        $uploadDir = __DIR__ . '/../../../assets/images/uploads';
        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
            $errorMessage = 'Không thể tạo thư mục lưu ảnh tải lên.';
            $success = false;
            continue;
        }
        $uploadFilename = $imageKey . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        if (move_uploaded_file($uploadFile['tmp_name'], $uploadDir . '/' . $uploadFilename)) {
            update_setting($imageKey . '_image', 'assets/images/uploads/' . $uploadFilename);
        } else {
            $errorMessage = 'Không thể lưu ảnh tải lên.';
            $success = false;
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
        'space_eyebrow', 'space_title', 'space_description',
        'space_garden_title', 'space_garden_tag', 'space_garden_description', 'space_garden_alt', 'space_garden_perks',
        'space_work_title', 'space_work_tag', 'space_work_description', 'space_work_alt', 'space_work_perks',
        'space_sofa_title', 'space_sofa_tag', 'space_sofa_description', 'space_sofa_alt', 'space_sofa_perks',
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
}
