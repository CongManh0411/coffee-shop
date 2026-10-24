<?php
function ops_snapshot()
{
    $waitlist = db()->fetchAll("SELECT waitlist_id, customer_name, phone, party_size, created_at FROM waitlist_entries WHERE status = 'waiting' ORDER BY created_at");
    $customers = db()->fetchAll('SELECT customer_id, full_name, phone, birth_date, points, total_spent FROM customers ORDER BY full_name');
    $promotions = db()->fetchAll('SELECT promotion_id, code, label, discount_type, discount_value, starts_on, ends_on, max_uses, used_count, hour_from, hour_to, is_active FROM promotions ORDER BY promotion_id DESC');
    $suppliers = db()->fetchAll('SELECT supplier_id, supplier_name, phone FROM suppliers WHERE is_active = 1 ORDER BY supplier_name');
    $stockItems = db()->fetchAll('SELECT stock_item_id, item_name, unit, quantity, min_quantity FROM stock_items WHERE is_active = 1 ORDER BY item_name');
    $movements = db()->fetchAll(
        'SELECT m.movement_id, m.movement_type, m.quantity, m.unit_cost, m.expires_on, m.note, m.created_at,
                i.item_name, s.supplier_name
         FROM stock_movements m JOIN stock_items i ON i.stock_item_id = m.stock_item_id
         LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id ORDER BY m.movement_id DESC LIMIT 500'
    );
    $deliveries = db()->fetchAll('SELECT delivery_id, platform, external_code, customer_name, items_summary, total, status, created_at FROM delivery_orders ORDER BY delivery_id DESC LIMIT 500');

    return array(
        'waitlist' => array_map(function ($r) { return array('id' => (int) $r['waitlist_id'], 'name' => $r['customer_name'], 'phone' => $r['phone'] ?? '', 'pax' => (int) $r['party_size'], 'at' => strtotime($r['created_at']) * 1000); }, $waitlist),
        'customers' => array_map(function ($r) { return array('id' => (int) $r['customer_id'], 'name' => $r['full_name'], 'phone' => $r['phone'], 'birth' => $r['birth_date'] ?? '', 'points' => (int) $r['points'], 'spent' => (int) $r['total_spent']); }, $customers),
        'promotions' => array_map(function ($r) { return array('id' => (int) $r['promotion_id'], 'code' => $r['code'], 'label' => $r['label'], 'type' => $r['discount_type'], 'value' => (int) $r['discount_value'], 'from' => $r['starts_on'] ?? '', 'to' => $r['ends_on'] ?? '', 'maxUses' => (int) $r['max_uses'], 'used' => (int) $r['used_count'], 'hourFrom' => $r['hour_from'] === null ? '' : (int) $r['hour_from'], 'hourTo' => $r['hour_to'] === null ? '' : (int) $r['hour_to'], 'active' => (bool) $r['is_active']); }, $promotions),
        'suppliers' => array_map(function ($r) { return array('id' => (int) $r['supplier_id'], 'name' => $r['supplier_name'], 'phone' => $r['phone'] ?? ''); }, $suppliers),
        'stockItems' => array_map(function ($r) { return array('id' => (int) $r['stock_item_id'], 'name' => $r['item_name'], 'unit' => $r['unit'], 'stock' => (float) $r['quantity'], 'minLevel' => (float) $r['min_quantity']); }, $stockItems),
        'movements' => array_map(function ($r) { return array('id' => (int) $r['movement_id'], 'type' => $r['movement_type'], 'item' => $r['item_name'], 'qty' => (float) $r['quantity'], 'price' => (int) $r['unit_cost'], 'sup' => $r['supplier_name'] ?? '', 'exp' => $r['expires_on'] ?? '', 'note' => $r['note'] ?? '', 'date' => $r['created_at']); }, $movements),
        'deliveries' => array_map(function ($r) { return array('id' => (int) $r['delivery_id'], 'plat' => $r['platform'], 'code' => $r['external_code'], 'name' => $r['customer_name'] ?? '', 'items' => $r['items_summary'] ?? '', 'total' => (int) $r['total'], 'status' => $r['status'], 'at' => $r['created_at']); }, $deliveries),
    );
}

function ops_promotion_list()
{
    $rows = db()->fetchAll('SELECT code, label, discount_type, discount_value, starts_on, ends_on, max_uses, used_count, hour_from, hour_to, is_active FROM promotions WHERE is_active = 1');
    return array_map(function ($r) {
        return array('code' => $r['code'], 'label' => $r['label'], 'type' => $r['discount_type'], 'value' => (int) $r['discount_value'],
            'from' => $r['starts_on'] ?? '', 'to' => $r['ends_on'] ?? '', 'maxUses' => (int) $r['max_uses'], 'used' => (int) $r['used_count'],
            'hourFrom' => $r['hour_from'] === null ? '' : (int) $r['hour_from'], 'hourTo' => $r['hour_to'] === null ? '' : (int) $r['hour_to'], 'active' => (bool) $r['is_active']);
    }, $rows);
}

function ops_action($data, $method, $user)
{
    $action = (string) ($data['action'] ?? '');
    $id = (int) ($data['id'] ?? 0);
    if ($method === 'DELETE') {
        if ($id <= 0) return 'Mã dữ liệu không hợp lệ.';
        if ($action === 'waitlist') db()->execute("UPDATE waitlist_entries SET status = 'cancelled' WHERE waitlist_id = ? AND status = 'waiting'", array($id));
        elseif ($action === 'customer') db()->execute('DELETE FROM customers WHERE customer_id = ?', array($id));
        elseif ($action === 'promotion') db()->execute('DELETE FROM promotions WHERE promotion_id = ?', array($id));
        elseif ($action === 'supplier') db()->execute('UPDATE suppliers SET is_active = 0 WHERE supplier_id = ?', array($id));
        else return 'Thao tác xóa không hợp lệ.';
        return '';
    }
    if ($method === 'PUT') {
        if ($id <= 0) return 'Mã dữ liệu không hợp lệ.';
        if ($action === 'waitlist') {
            $status = (string) ($data['status'] ?? '');
            if (!in_array($status, array('seated', 'cancelled'), true)) return 'Trạng thái hàng chờ không hợp lệ.';
            db()->execute('UPDATE waitlist_entries SET status = ? WHERE waitlist_id = ? AND status = \'waiting\'', array($status, $id));
        } elseif ($action === 'customer-points') {
            $amount = filter_var($data['amount'] ?? null, FILTER_VALIDATE_INT);
            if (!$amount || $amount < 1) return 'Số tiền cần lớn hơn 0.';
            db()->execute('UPDATE customers SET total_spent = total_spent + ?, points = points + FLOOR(? / 10000) WHERE customer_id = ?', array($amount, $amount, $id));
        } elseif ($action === 'promotion') {
            db()->execute('UPDATE promotions SET is_active = NOT is_active WHERE promotion_id = ?', array($id));
        } elseif ($action === 'delivery') {
            $status = (string) ($data['status'] ?? '');
            if (!in_array($status, array('new', 'making', 'delivering', 'completed', 'cancelled'), true)) return 'Trạng thái giao hàng không hợp lệ.';
            db()->execute('UPDATE delivery_orders SET status = ?, completed_at = IF(? = \'completed\', COALESCE(completed_at, NOW()), NULL) WHERE delivery_id = ?', array($status, $status, $id));
        } else return 'Thao tác cập nhật không hợp lệ.';
        return '';
    }
    if ($method !== 'POST') return 'Sai phương thức.';

    if ($action === 'waitlist') {
        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $pax = filter_var($data['partySize'] ?? null, FILTER_VALIDATE_INT);
        if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || $pax === false || $pax < 1 || $pax > 20) return 'Tên khách hoặc số người không hợp lệ.';
        db()->execute("INSERT INTO waitlist_entries (customer_name, phone, party_size, created_by) VALUES (?, ?, ?, ?)", array($name, $phone !== '' ? $phone : null, $pax, (int) $user['staffId']));
    } elseif ($action === 'customer') {
        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $birth = trim((string) ($data['birth'] ?? ''));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || !preg_match('/^[0-9+(). -]{8,30}$/', $phone)) return 'Tên hoặc số điện thoại không hợp lệ.';
        if ($birth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) return 'Ngày sinh không hợp lệ.';
        db()->execute('INSERT INTO customers (full_name, phone, birth_date) VALUES (?, ?, ?)', array($name, $phone, $birth !== '' ? $birth : null));
    } elseif ($action === 'promotion') {
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $label = trim((string) ($data['label'] ?? ''));
        $type = (string) ($data['type'] ?? '');
        $value = filter_var($data['value'] ?? null, FILTER_VALIDATE_INT);
        $from = trim((string) ($data['from'] ?? ''));
        $to = trim((string) ($data['to'] ?? ''));
        $max = filter_var($data['maxUses'] ?? 0, FILTER_VALIDATE_INT);
        $h1 = $data['hourFrom'] ?? '';
        $h2 = $data['hourTo'] ?? '';
        if (!preg_match('/^[A-Z0-9_-]{2,40}$/', $code) || $label === '' || mb_strlen($label, 'UTF-8') > 150 || !in_array($type, array('pct', 'vnd'), true) || $value === false || $value < 1 || ($type === 'pct' && $value > 100)) return 'Thông tin khuyến mãi không hợp lệ.';
        if (in_array($code, array('GIAM10', 'GIAM20', 'GIAM5K', 'GIAM20K'), true)) return 'Mã này đang được dành cho mã khuyến mãi mẫu.';
        if (($from !== '' && !checkdate((int) substr($from, 5, 2), (int) substr($from, 8, 2), (int) substr($from, 0, 4)))
            || ($to !== '' && !checkdate((int) substr($to, 5, 2), (int) substr($to, 8, 2), (int) substr($to, 0, 4)))
            || ($from !== '' && $to !== '' && $from > $to)) return 'Ngày áp dụng không hợp lệ.';
        if ($max === false || $max < 0) return 'Giới hạn lượt không hợp lệ.';
        $hourFrom = $h1 === '' ? null : filter_var($h1, FILTER_VALIDATE_INT);
        $hourTo = $h2 === '' ? null : filter_var($h2, FILTER_VALIDATE_INT);
        if (($hourFrom !== null && ($hourFrom === false || $hourFrom < 0 || $hourFrom > 23)) || ($hourTo !== null && ($hourTo === false || $hourTo < 0 || $hourTo > 23)) || (($hourFrom === null) !== ($hourTo === null))) return 'Khung giờ khuyến mãi không hợp lệ.';
        db()->execute('INSERT INTO promotions (code, label, discount_type, discount_value, starts_on, ends_on, max_uses, hour_from, hour_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array($code, $label, $type, $value, $from ?: null, $to ?: null, $max, $hourFrom, $hourTo));
    } elseif ($action === 'supplier') {
        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || mb_strlen($phone, 'UTF-8') > 30) return 'Tên nhà cung cấp không hợp lệ.';
        db()->execute('INSERT INTO suppliers (supplier_name, phone) VALUES (?, ?)', array($name, $phone !== '' ? $phone : null));
    } elseif ($action === 'stock-item') {
        $name = trim((string) ($data['name'] ?? ''));
        $unit = trim((string) ($data['unit'] ?? ''));
        $minimum = filter_var($data['minLevel'] ?? 0, FILTER_VALIDATE_INT);
        if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || $unit === '' || mb_strlen($unit, 'UTF-8') > 30 || $minimum === false || $minimum < 0) return 'Thông tin mặt hàng kho không hợp lệ.';
        db()->execute('INSERT INTO stock_items (item_name, unit, min_quantity) VALUES (?, ?, ?)', array($name, $unit, $minimum));
    } elseif ($action === 'stock-movement') {
        $stockId = (int) ($data['stockId'] ?? 0);
        $type = (string) ($data['type'] ?? '');
        $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
        $cost = filter_var($data['unitCost'] ?? 0, FILTER_VALIDATE_INT);
        $supplierId = !empty($data['supplierId']) ? (int) $data['supplierId'] : null;
        $expires = trim((string) ($data['expiresOn'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));
        if ($stockId <= 0 || !in_array($type, array('import', 'export', 'waste'), true) || $quantity === false || $quantity <= 0 || $cost === false || $cost < 0 || ($type === 'import' && $cost < 1)) return 'Thông tin phiếu kho không hợp lệ.';
        if ($expires !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires) || !checkdate((int) substr($expires, 5, 2), (int) substr($expires, 8, 2), (int) substr($expires, 0, 4)))) return 'Hạn dùng không hợp lệ.';
        return db()->transaction(function () use ($stockId, $type, $quantity, $cost, $supplierId, $expires, $note, $user) {
            $item = db()->fetchOne('SELECT stock_item_id, quantity FROM stock_items WHERE stock_item_id = ? AND is_active = 1 FOR UPDATE', array($stockId));
            if (!$item) return 'Không tìm thấy mặt hàng kho.';
            $delta = $type === 'import' ? $quantity : -$quantity;
            if ((float) $item['quantity'] + $delta < 0) return 'Tồn kho không đủ để xuất số lượng này.';
            if ($supplierId !== null && !db()->fetchOne('SELECT supplier_id FROM suppliers WHERE supplier_id = ? AND is_active = 1', array($supplierId))) return 'Nhà cung cấp không hợp lệ.';
            db()->execute('UPDATE stock_items SET quantity = quantity + ? WHERE stock_item_id = ?', array($delta, $stockId));
            db()->execute('INSERT INTO stock_movements (stock_item_id, supplier_id, movement_type, quantity, unit_cost, expires_on, note, staff_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', array($stockId, $supplierId, $type, $quantity, $cost, $expires !== '' ? $expires : null, $note !== '' ? $note : null, (int) $user['staffId']));
            return '';
        });
    } elseif ($action === 'delivery') {
        $platform = (string) ($data['platform'] ?? '');
        $code = trim((string) ($data['code'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $items = trim((string) ($data['items'] ?? ''));
        $total = filter_var($data['total'] ?? null, FILTER_VALIDATE_INT);
        if (!in_array($platform, array('GrabFood', 'ShopeeFood', 'BeFood'), true) || $code === '' || mb_strlen($code, 'UTF-8') > 100 || $total === false || $total < 1 || mb_strlen($items, 'UTF-8') > 500) return 'Thông tin đơn giao hàng không hợp lệ.';
        db()->execute('INSERT INTO delivery_orders (platform, external_code, customer_name, items_summary, total, created_by) VALUES (?, ?, ?, ?, ?, ?)', array($platform, $code, $name !== '' ? $name : null, $items !== '' ? $items : null, $total, (int) $user['staffId']));
    } else return 'Thao tác không hợp lệ.';
    return '';
}
