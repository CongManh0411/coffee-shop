<?php
// Bàn ăn: chỉ lưu danh sách (id + tên). Trạng thái "đang phục vụ" sẽ chuyển vào DB ở bước đơn hàng (2b).
function tables_list()
{
    $rows = db()->fetchAll("SELECT table_id, table_name FROM dining_tables WHERE is_active = 1 ORDER BY table_id");
    $out = array();
    foreach ($rows as $r) {
        $out[] = array('id' => (int) $r['table_id'], 'name' => $r['table_name']);
    }
    return $out;
}

function tables_clean_name($d)
{
    return trim(preg_replace('/\s+/u', ' ', (string) ($d['name'] ?? '')));
}

function tables_validate($name, $exceptId = 0)
{
    if ($name === '') return 'Vui lòng nhập tên bàn.';
    if (mb_strlen($name, 'UTF-8') > 30) return 'Tên bàn tối đa 30 ký tự.';
    $dup = db()->fetchOne(
        "SELECT table_id FROM dining_tables WHERE table_name = ? AND table_id <> ?",
        array($name, (int) $exceptId)
    );
    if ($dup) return 'Đã có bàn tên "' . $name . '". Hãy chọn tên khác.';
    return '';
}

function tables_create($d)
{
    $name = tables_clean_name($d);
    $err = tables_validate($name);
    if ($err) return array(null, $err);
    db()->execute("INSERT INTO dining_tables (table_name, is_active) VALUES (?, 1)", array($name));
    return array(db()->insertId(), '');
}

function tables_update($id, $d)
{
    $cur = db()->fetchOne("SELECT table_id FROM dining_tables WHERE table_id = ?", array((int) $id));
    if (!$cur) return 'Không tìm thấy bàn.';
    $name = tables_clean_name($d);
    $err = tables_validate($name, $id);
    if ($err) return $err;
    db()->execute("UPDATE dining_tables SET table_name = ? WHERE table_id = ?", array($name, (int) $id));
    return '';
}

// Xóa bàn an toàn ở phía máy chủ để xử lý cả trạng thái trình duyệt đã cũ.
function tables_delete($id)
{
    $id = (int) $id;
    if ($id <= 0) return 'Mã bàn không hợp lệ.';

    return db()->transaction(function () use ($id) {
        $table = db()->fetchOne(
            "SELECT table_id FROM dining_tables WHERE table_id = ? FOR UPDATE",
            array($id)
        );
        if (!$table) return 'Không tìm thấy bàn.';

        $openOrder = db()->fetchOne(
            "SELECT order_id FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1",
            array($id)
        );
        if ($openOrder) return 'Không thể xóa bàn đang có đơn hàng mở.';

        db()->execute("DELETE FROM dining_tables WHERE table_id = ?", array($id));
        return '';
    });
}
