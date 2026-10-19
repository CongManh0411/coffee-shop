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

// Việc "chỉ xóa bàn đang trống" hiện do giao diện kiểm tra; khi có bảng đơn hàng sẽ kiểm tra ở đây.
function tables_delete($id)
{
    db()->execute("DELETE FROM dining_tables WHERE table_id = ?", array((int) $id));
}
