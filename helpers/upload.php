<?php
// helpers/upload.php — upload ảnh dùng chung (thay cho 3 đoạn upload rải rác trong menu.php, settings/action.php).
// Trả về array(đường dẫn tương đối từ gốc dự án, lỗi). Không chọn file => array(null, '').

// Thư mục đích được whitelist; khóa là tên logic, giá trị là đường dẫn từ gốc dự án.
// Thư mục đều đã có .htaccess chặn thực thi PHP.
const UPLOAD_DIRS = array(
    'menu' => 'assets/images/menu',
    'site' => 'assets/images/uploads',
);

function upload_project_root()
{
    return dirname(__DIR__); // helpers/ nằm ngay dưới gốc dự án
}

// Kiểu ảnh cho phép: MIME => đuôi file. Đuôi do server quyết định, không lấy từ client.
function upload_allowed_types($allowGif = true)
{
    $types = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
    if ($allowGif) $types['image/gif'] = 'gif';
    return $types;
}

// Kiểm tra và lưu 1 ảnh từ $_FILES['x'].
// $dirKey: 'menu' | 'site'. $prefix: tiền tố tên file (vd 'item_', 'logo_').
function upload_image_file($file, $dirKey, $prefix = '', $maxSize = 5242880, $allowGif = true)
{
    if (!isset(UPLOAD_DIRS[$dirKey])) return array(null, 'Thư mục upload không hợp lệ.');
    if (!preg_match('/^[a-z_]{0,20}$/', (string) $prefix)) return array(null, 'Tiền tố tên file không hợp lệ.');

    $error = is_array($file) ? ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) return array(null, '');
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return array(null, 'Ảnh vượt giới hạn upload của PHP (upload_max_filesize).');
    }
    if ($error !== UPLOAD_ERR_OK) return array(null, 'Tải ảnh thất bại (mã lỗi ' . (int) $error . ').');

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return array(null, 'Ảnh không được tải lên bằng biểu mẫu hợp lệ.');
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > $maxSize) {
        return array(null, 'Dung lượng ảnh phải từ 1 byte đến ' . round($maxSize / 1048576, 1) . ' MB.');
    }

    $bytes = file_get_contents($file['tmp_name']);
    return upload_image_bytes($bytes === false ? '' : $bytes, $dirKey, $prefix, $maxSize, $allowGif);
}

// Lưu ảnh từ chuỗi byte (dùng cho ảnh data:image base64 qua API). Kiểm tra nội dung thật, không tin client.
function upload_image_bytes($bytes, $dirKey, $prefix = '', $maxSize = 5242880, $allowGif = true)
{
    if (!isset(UPLOAD_DIRS[$dirKey])) return array(null, 'Thư mục upload không hợp lệ.');
    if (!preg_match('/^[a-z_]{0,20}$/', (string) $prefix)) return array(null, 'Tiền tố tên file không hợp lệ.');
    if ($bytes === '' || strlen($bytes) > $maxSize) {
        return array(null, 'Dung lượng ảnh phải từ 1 byte đến ' . round($maxSize / 1048576, 1) . ' MB.');
    }

    $types = upload_allowed_types($allowGif);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
    $info = @getimagesizefromstring($bytes);
    // MIME theo finfo phải khớp MIME theo getimagesize, nếu không là file giả ảnh.
    if (!isset($types[$mime]) || $info === false || $info['mime'] !== $mime) {
        return array(null, 'Ảnh phải là ' . strtoupper(implode(', ', array_values($types))) . ' hợp lệ.');
    }
    // Chặn "decompression bomb": cạnh > 7000px hoặc tổng > 20 triệu pixel.
    if ($info[0] > 7000 || $info[1] > 7000 || ($info[0] * $info[1]) > 20000000) {
        return array(null, 'Kích thước ảnh quá lớn (tối đa 7000px mỗi cạnh, 20 triệu pixel).');
    }

    $directory = upload_project_root() . '/' . UPLOAD_DIRS[$dirKey];
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        return array(null, 'Không tạo được thư mục ' . UPLOAD_DIRS[$dirKey] . '.');
    }
    // Tên ngẫu nhiên, đuôi theo MIME đã xác thực.
    $name = $prefix . bin2hex(random_bytes(12)) . '.' . $types[$mime];
    if (@file_put_contents($directory . '/' . $name, $bytes) === false) {
        return array(null, 'Không lưu được ảnh. Kiểm tra quyền ghi thư mục ' . UPLOAD_DIRS[$dirKey] . '.');
    }
    return array(UPLOAD_DIRS[$dirKey] . '/' . $name, '');
}

// Xóa ảnh do hệ thống tạo ra (đúng thư mục whitelist + tên ngẫu nhiên). Không đụng ảnh mẫu 01..10, logo mặc định.
function upload_delete_files($paths)
{
    foreach ((array) $paths as $path) {
        if (!is_string($path)) continue;
        foreach (UPLOAD_DIRS as $dir) {
            if (preg_match('#^' . preg_quote($dir, '#') . '/([a-z_]{0,20}[a-f0-9]{12,48}\.(?:jpg|png|webp|gif))$#', $path, $m)) {
                $file = upload_project_root() . '/' . $dir . '/' . $m[1];
                if (is_file($file)) @unlink($file);
                break;
            }
        }
    }
}

// Chuyển $_FILES['images'] (nhiều file) thành danh sách file đơn để lặp foreach.
function upload_files_list($files)
{
    if (empty($files['name']) || !is_array($files['name'])) return array();
    $result = array();
    foreach ($files['name'] as $i => $name) {
        $result[] = array(
            'name' => $name,
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        );
    }
    return $result;
}
