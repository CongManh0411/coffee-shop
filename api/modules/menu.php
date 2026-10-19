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

// Tìm danh mục theo tên, hoặc tạo mới nếu người dùng nhập "danh mục mới"
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
    if (!is_numeric($price) || (int) $price <= 0) return 'Giá món phải lớn hơn 0.';
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

function menu_create($d)
{
    $name = trim($d['name'] ?? '');
    list($catId, $err) = menu_resolve_category($d);
    if ($err) return array(null, $err);
    $err = menu_validate($name, $d['price'] ?? '', $catId);
    if ($err) return array(null, $err);
    $img = ($d['img'] ?? '') !== '' ? $d['img'] : null;
    $err = menu_check_image($img);
    if ($err) return array(null, $err);

    db()->execute(
        "INSERT INTO menu_items (category_id, item_name, price, image) VALUES (?, ?, ?, ?)",
        array($catId, $name, (int) $d['price'], $img)
    );
    return array(db()->insertId(), '');
}

// Cập nhật từng phần: chỉ trường nào gửi lên mới đổi (ẩn/hiện chỉ gửi {hidden:true})
function menu_update($id, $d)
{
    $cur = db()->fetchOne("SELECT * FROM menu_items WHERE item_id = ?", array((int) $id));
    if (!$cur) return 'Không tìm thấy món.';

    $name  = array_key_exists('name', $d) ? trim($d['name']) : $cur['item_name'];
    $price = array_key_exists('price', $d) ? $d['price'] : $cur['price'];
    $catId = (int) $cur['category_id'];
    if (trim($d['newCategory'] ?? '') !== '' || trim($d['category'] ?? '') !== '') {
        list($catId, $err) = menu_resolve_category($d);
        if ($err) return $err;
    }
    $err = menu_validate($name, $price, $catId, $id);
    if ($err) return $err;

    $img = array_key_exists('img', $d) ? ($d['img'] !== '' ? $d['img'] : null) : $cur['image'];
    $err = menu_check_image($img);
    if ($err) return $err;
    $hidden = array_key_exists('hidden', $d) ? ($d['hidden'] ? 1 : 0) : (int) $cur['is_hidden'];

    db()->execute(
        "UPDATE menu_items SET category_id = ?, item_name = ?, price = ?, image = ?, is_hidden = ? WHERE item_id = ?",
        array($catId, $name, (int) $price, $img, $hidden, (int) $id)
    );
    return '';
}

function menu_delete($id)
{
    db()->execute("DELETE FROM menu_items WHERE item_id = ?", array((int) $id));
}