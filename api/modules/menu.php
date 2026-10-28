<?php
// Trả về đúng dạng giao diện đang dùng: [{category, items:[{id,name,desc,price,img,hidden}]}]
function menu_list()
{
    $cats  = db()->fetchAll("SELECT category_id, category_name FROM categories ORDER BY category_id");
    $items = db()->fetchAll("SELECT item_id, category_id, item_name, description, price, image, is_hidden
                             FROM menu_items ORDER BY item_id");
    $out = array();
    $pos = array();
    foreach ($cats as $c) {
        $pos[$c['category_id']] = count($out);
        $out[] = array('category' => $c['category_name'], 'items' => array());
    }
    foreach ($items as $i) {
        $out[$pos[$i['category_id']]]['items'][] = array(
            'id'     => (int) $i['item_id'],
            'name'   => $i['item_name'],
            'desc'   => $i['description'] ?? '',
            'price'  => (int) $i['price'],
            'img'    => $i['image'] ?? '',
            'hidden' => (int) $i['is_hidden'] === 1,
        );
    }
    return $out;
}

function menu_resolve_category($d)
{
    $new  = trim($d['newCategory'] ?? '');
    $name = $new !== '' ? $new : trim($d['category'] ?? '');
    if ($name === '') return array(null, 'Vui lòng chọn hoặc tạo một danh mục.');

    $row = db()->fetchOne("SELECT category_id FROM categories WHERE category_name = ?", array($name));
    if ($row) return array((int) $row['category_id'], '');
    if ($new === '') return array(null, 'Danh mục không tồn tại.');

    db()->execute("INSERT INTO categories (category_name) VALUES (?)", array($name));
    return array(db()->insertId(), '');
}

function menu_validate($name, $price, $categoryId, $exceptId = 0)
{
    if ($name === '') return 'Vui lòng nhập tên món.';
    if (!(is_int($price) || (is_string($price) && preg_match('/^[0-9]+$/', $price))) || (int) $price <= 0) {
        return 'Giá món phải là số nguyên lớn hơn 0.';
    }
    $dup = db()->fetchOne(
        "SELECT item_id FROM menu_items WHERE category_id = ? AND item_name = ? AND item_id <> ?",
        array((int) $categoryId, $name, (int) $exceptId)
    );
    if ($dup) return 'Món "' . $name . '" đã tồn tại trong danh mục này.';
    return '';
}

function menu_check_image($img)
{
    if ($img !== null && strlen($img) > 2000000) return 'Ảnh quá lớn.';
    return '';
}

// ---------------------------------------------------------------
// Ảnh món lưu thành FILE trong assets/images/menu/, database chỉ giữ đường dẫn
// (ví dụ: assets/images/menu/item_ab12cd34ef56.jpg).
// ---------------------------------------------------------------
function menu_image_dir()
{
    return __DIR__ . '/../../assets/images/menu';
}

function menu_image_extension($mime)
{
    $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif');
    return isset($allowed[$mime]) ? $allowed[$mime] : '';
}

// Ghi nội dung ảnh ($bytes) ra file mới; trả về array(đường dẫn, lỗi)
function menu_write_image($bytes)
{
    if (strlen($bytes) > 5 * 1024 * 1024) return array(null, 'Ảnh quá lớn (tối đa 5MB).');
    $info = @getimagesizefromstring($bytes);
    $ext = $info ? menu_image_extension($info['mime']) : '';
    if ($ext === '') return array(null, 'Ảnh phải là JPG, PNG, WEBP hoặc GIF.');
    $dir = menu_image_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return array(null, 'Không tạo được thư mục assets/images/menu.');
    $name = 'item_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (@file_put_contents($dir . '/' . $name, $bytes) === false) {
        return array(null, 'Không lưu được ảnh. Hãy kiểm tra quyền ghi thư mục assets/images/menu.');
    }
    return array('assets/images/menu/' . $name, '');
}

// Ảnh người dùng chọn từ form ($_FILES['...'])
function menu_store_uploaded_image($file)
{
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return array(null, '');
    if ($file['error'] !== UPLOAD_ERR_OK) return array(null, 'Tải ảnh lên không thành công. Vui lòng thử lại.');
    if (!is_uploaded_file($file['tmp_name'])) return array(null, 'Tệp ảnh không hợp lệ.');
    return menu_write_image((string) file_get_contents($file['tmp_name']));
}

// Tương thích cũ: ảnh dạng data:image/...;base64 (gửi qua API) cũng được đổi thành file
function menu_store_data_image($data)
{
    if (!preg_match('#^data:image/(?:jpeg|png|webp|gif);base64,([A-Za-z0-9+/=\s]+)$#', $data, $m)) {
        return array(null, 'Ảnh không hợp lệ.');
    }
    $bytes = base64_decode($m[1], true);
    if ($bytes === false) return array(null, 'Ảnh không hợp lệ.');
    return menu_write_image($bytes);
}

// Chỉ xóa file ảnh do chức năng này tạo ra (item_*.ext); không đụng tới ảnh mẫu 01..10
function menu_remove_image_file($path)
{
    if (is_string($path) && preg_match('#^assets/images/menu/(item_[a-f0-9]+\.(?:jpg|png|webp|gif))$#', $path, $m)) {
        @unlink(menu_image_dir() . '/' . $m[1]);
    }
}

// Nhận chuỗi ảnh từ form/API: đường dẫn assets/... giữ nguyên, data:image đổi thành file
function menu_prepare_image($img)
{
    if ($img === null || $img === '') return array(null, '');
    if (strpos($img, 'data:image/') === 0) return menu_store_data_image($img);
    if (preg_match('#^assets/images/[A-Za-z0-9_./-]+$#', $img) && strpos($img, '..') === false) return array($img, '');
    return array(null, 'Đường dẫn ảnh không hợp lệ.');
}

function menu_create($d)
{
    $name = trim($d['name'] ?? '');
    $description = trim((string) ($d['desc'] ?? ''));
    $price = $d['price'] ?? '';
    if ($name === '') return array(null, 'Vui lòng nhập tên món.');
    if (!(is_int($price) || (is_string($price) && preg_match('/^[0-9]+$/', $price))) || (int) $price <= 0) {
        return array(null, 'Giá món phải là số nguyên lớn hơn 0.');
    }
    if (mb_strlen($description, 'UTF-8') > 255) return array(null, 'Mô tả tối đa 255 ký tự.');
    $img = ($d['img'] ?? '') !== '' ? $d['img'] : null;
    $err = menu_check_image($img);
    if ($err) return array(null, $err);
    list($img, $err) = menu_prepare_image($img);
    if ($err) return array(null, $err);

    list($catId, $err) = menu_resolve_category($d);
    if ($err) return array(null, $err);
    $err = menu_validate($name, $price, $catId);
    if ($err) {
        menu_remove_image_file($img);
        return array(null, $err);
    }

    db()->execute(
        "INSERT INTO menu_items (category_id, item_name, description, price, image) VALUES (?, ?, ?, ?, ?)",
        array($catId, $name, $description !== '' ? $description : null, (int) $d['price'], $img)
    );
    $newId = db()->insertId();
    db()->execute("INSERT IGNORE INTO menu_stock (menu_item_id, quantity, min_quantity) VALUES (?, 0, 10)", array($newId));
    return array($newId, '');
}

// Cập nhật từng phần: chỉ trường nào gửi lên mới đổi (ẩn/hiện chỉ gửi {hidden:true})
function menu_update($id, $d)
{
    $cur = db()->fetchOne("SELECT * FROM menu_items WHERE item_id = ?", array((int) $id));
    if (!$cur) return 'Không tìm thấy món.';

    $name  = array_key_exists('name', $d) ? trim($d['name']) : $cur['item_name'];
    $description = array_key_exists('desc', $d) ? trim((string) $d['desc']) : ($cur['description'] ?? '');
    $price = array_key_exists('price', $d) ? $d['price'] : $cur['price'];
    if ($name === '') return 'Vui lòng nhập tên món.';
    if (!(is_int($price) || (is_string($price) && preg_match('/^[0-9]+$/', (string) $price))) || (int) $price <= 0) {
        return 'Giá món phải là số nguyên lớn hơn 0.';
    }
    if (mb_strlen($description, 'UTF-8') > 255) return 'Mô tả tối đa 255 ký tự.';
    $catId = (int) $cur['category_id'];
    if (trim($d['newCategory'] ?? '') !== '' || trim($d['category'] ?? '') !== '') {
        list($catId, $err) = menu_resolve_category($d);
        if ($err) return $err;
    }
    $err = menu_validate($name, $price, $catId, $id);
    if ($err) return $err;
    if (mb_strlen($description, 'UTF-8') > 255) return 'Mô tả tối đa 255 ký tự.';

    $img = array_key_exists('img', $d) ? ($d['img'] !== '' ? $d['img'] : null) : $cur['image'];
    $err = menu_check_image($img);
    if ($err) return $err;
    if (array_key_exists('img', $d) && $img !== $cur['image']) {
        list($img, $err) = menu_prepare_image($img);
        if ($err) return $err;
    }
    $hidden = array_key_exists('hidden', $d) ? ($d['hidden'] ? 1 : 0) : (int) $cur['is_hidden'];

    db()->execute(
        "UPDATE menu_items SET category_id = ?, item_name = ?, description = ?, price = ?, image = ?, is_hidden = ? WHERE item_id = ?",
        array($catId, $name, $description !== '' ? $description : null, (int) $price, $img, $hidden, (int) $id)
    );
    if ($img !== $cur['image']) menu_remove_image_file($cur['image']);
    return '';
}

function menu_delete($id)
{
    $cur = db()->fetchOne("SELECT image FROM menu_items WHERE item_id = ?", array((int) $id));
    db()->execute("DELETE FROM menu_items WHERE item_id = ?", array((int) $id));
    if ($cur) menu_remove_image_file($cur['image']);
}
