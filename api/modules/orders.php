<?php
function orders_item_row($row)
{
    return array(
        'id' => (int) $row['order_item_id'],
        'menuId' => $row['menu_item_id'] === null ? null : (int) $row['menu_item_id'],
        'name' => $row['item_name'],
        'price' => (int) $row['unit_price'],
        'quantity' => (int) $row['quantity'],
        'note' => $row['note'] ?? '',
        'status' => $row['status'],
        'confirmedAt' => $row['confirmed_at'],
        'startedAt' => $row['started_at'],
        'doneAt' => $row['done_at'],
    );
}

function orders_list($status = 'open')
{
    $orders = db()->fetchAll(
        "SELECT order_id, order_code, table_id, table_name, order_type, customer, address,
                status, subtotal, discount, total, created_by, created_at, paid_at,
                payment_method, payment_ref
         FROM orders WHERE status = ? ORDER BY created_at, order_id",
        array($status)
    );
    foreach ($orders as &$order) {
        $order['id'] = (int) $order['order_id'];
        $order['tableId'] = $order['table_id'] === null ? null : (int) $order['table_id'];
        $order['subtotal'] = (int) $order['subtotal'];
        $order['discount'] = (int) $order['discount'];
        $order['total'] = (int) $order['total'];
        $order['items'] = array_map('orders_item_row', db()->fetchAll(
            "SELECT order_item_id, menu_item_id, item_name, unit_price, quantity, note, status,
                    confirmed_at, started_at, done_at
             FROM order_items WHERE order_id = ? AND status <> 'cancelled' ORDER BY order_item_id",
            array((int) $order['order_id'])
        ));
    }
    unset($order);
    return $orders;
}

function orders_invoice_list($status = 'all')
{
    $where = $status === 'all' ? "o.status IN ('paid','cancelled')" : 'o.status = ?';
    $params = $status === 'all' ? array() : array($status);
    $rows = db()->fetchAll(
        "SELECT o.order_id, o.order_code, o.table_name, o.order_type, o.subtotal, o.discount,
                o.total, o.voucher, o.payment_method, o.payment_ref, o.created_by,
                o.paid_at, s.full_name AS cashier_name, c.reason AS cancel_reason,
                c.created_at AS cancel_at, cs.full_name AS cancel_by_name,
                r.reason AS refund_reason, r.created_at AS refund_at, rs.full_name AS refund_by_name
         FROM orders o
         LEFT JOIN staff s ON s.staff_id = o.paid_by
         LEFT JOIN order_cancellations c ON c.cancellation_id = (
             SELECT MAX(c2.cancellation_id) FROM order_cancellations c2 WHERE c2.order_id = o.order_id
         )
         LEFT JOIN staff cs ON cs.staff_id = c.cancelled_by
         LEFT JOIN order_refunds r ON r.refund_id = (
             SELECT MAX(r2.refund_id) FROM order_refunds r2 WHERE r2.order_id = o.order_id
         )
         LEFT JOIN staff rs ON rs.staff_id = r.refunded_by
         WHERE $where AND o.subtotal > 0 ORDER BY o.paid_at DESC, o.order_id DESC",
        $params
    );
    foreach ($rows as &$row) {
        $row['id'] = $row['order_code'];
        $row['orderId'] = (int) $row['order_id'];
        $row['tableName'] = $row['table_name'];
        $row['status'] = ($row['cancel_reason'] === null && $row['refund_reason'] === null) ? 'active' : 'cancelled';
        $row['createdAt'] = $row['paid_at'];
        $row['cancelReason'] = $row['cancel_reason'] !== null ? $row['cancel_reason'] : ($row['refund_reason'] !== null ? 'Hoàn tiền: ' . $row['refund_reason'] : null);
        $row['cancelledAt'] = $row['cancel_at'] ?: $row['refund_at'];
        $row['cancelledBy'] = $row['cancel_by_name'] ?: $row['refund_by_name'];
        $row['createdBy'] = $row['cashier_name'];
        $row['paymentMethod'] = in_array($row['payment_method'], array('qr','ewallet','transfer'), true)
            ? 'transfer' : $row['payment_method'];
        $row['items'] = array_map(function ($item) {
            return array('id' => $item['menu_item_id'], 'name' => $item['item_name'],
                'qty' => (int) $item['quantity'], 'price' => (int) $item['unit_price']);
        }, db()->fetchAll(
            "SELECT menu_item_id, item_name, unit_price, quantity FROM order_items WHERE order_id = ? AND status <> 'cancelled' ORDER BY order_item_id",
            array((int) $row['order_id'])
        ));
    }
    unset($row);
    return $rows;
}

function orders_refund_paid($orderId, $reason, $user)
{
    $reason = trim((string) $reason);
    if ($orderId <= 0 || $reason === '' || strlen($reason) > 500) return 'Cần mã hóa đơn và lý do hoàn tiền (tối đa 500 ký tự).';
    return db()->transaction(function () use ($orderId, $reason, $user) {
        $order = db()->fetchOne("SELECT order_id, total FROM orders WHERE order_id = ? AND status = 'paid' FOR UPDATE", array($orderId));
        if (!$order) return 'Không tìm thấy hóa đơn còn hiệu lực.';
        db()->execute("INSERT INTO order_refunds (order_id, amount, reason, refunded_by) VALUES (?, ?, ?, ?)",
            array($orderId, (int) $order['total'], $reason, (int) $user['staffId']));
        db()->execute("UPDATE orders SET status = 'cancelled' WHERE order_id = ?", array($orderId));
        return '';
    });
}

function orders_cancel_paid($orderId, $reason, $user)
{
    $reason = trim((string) $reason);
    if ($orderId <= 0 || $reason === '' || strlen($reason) > 500) return 'Cáº§n mÃ£ hÃ³a Ä‘Æ¡n vÃ  lÃ½ do há»§y (tá»‘i Ä‘a 500 kÃ½ tá»±).';
    return db()->transaction(function () use ($orderId, $reason, $user) {
        $order = db()->fetchOne("SELECT order_id FROM orders WHERE order_id = ? AND status = 'paid' FOR UPDATE", array($orderId));
        if (!$order) return 'KhÃ´ng tÃ¬m tháº¥y hÃ³a Ä‘Æ¡n cÃ²n hiá»‡u lá»±c.';
        db()->execute("UPDATE orders SET status = 'cancelled' WHERE order_id = ?", array($orderId));
        db()->execute("INSERT INTO order_cancellations (order_id, reason, cancelled_by) VALUES (?, ?, ?)",
            array($orderId, $reason, (int) $user['staffId']));
        return '';
    });
}

function orders_create($data, $user)
{
    $type = $data['type'] ?? 'dinein';
    if (!in_array($type, array('dinein', 'takeaway', 'delivery'), true)) {
        return array(null, 'Loại đơn hàng không hợp lệ.');
    }
    $tableId = isset($data['tableId']) ? (int) $data['tableId'] : 0;
    $tableName = trim((string) ($data['tableName'] ?? ''));
    if ($type === 'dinein' && $tableId <= 0) return array(null, 'Vui lòng chọn bàn.');
    if ($type === 'delivery' && (trim($data['customer'] ?? '') === '' || trim($data['address'] ?? '') === '')) {
        return array(null, 'Đơn giao hàng cần tên khách và địa chỉ.');
    }

    return db()->transaction(function () use ($type, $tableId, $tableName, $data, $user) {
        if ($type === 'dinein') {
            $table = db()->fetchOne(
                "SELECT table_id, table_name FROM dining_tables WHERE table_id = ? AND is_active = 1 FOR UPDATE",
                array($tableId)
            );
            if (!$table) return array(null, 'Không tìm thấy bàn đang hoạt động.');
            $exists = db()->fetchOne("SELECT order_id FROM orders WHERE table_id = ? AND status = 'open' FOR UPDATE", array($tableId));
            if ($exists) return array((int) $exists['order_id'], '');
            $tableName = $table['table_name'];
        } elseif ($tableName === '') {
            $tableName = $type === 'delivery' ? 'Giao hàng' : 'Mang đi';
        }
        db()->execute(
            "INSERT INTO orders (table_id, table_name, order_type, customer, address, created_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            array($type === 'dinein' ? $tableId : null, $tableName, $type,
                trim($data['customer'] ?? '') ?: null, trim($data['address'] ?? '') ?: null, (int) $user['staffId'])
        );
        $id = db()->insertId();
        $code = 'DH' . date('ymd') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
        $displayName = $type === 'dinein' ? $tableName : $tableName . ' #' . $id;
        db()->execute("UPDATE orders SET order_code = ?, table_name = ? WHERE order_id = ?", array($code, $displayName, $id));
        return array($id, '');
    });
}

function orders_add_item($data)
{
    $orderId = (int) ($data['orderId'] ?? 0);
    $menuId = (int) ($data['menuId'] ?? 0);
    $quantity = (int) ($data['quantity'] ?? 0);
    if ($orderId <= 0 || $menuId <= 0 || $quantity < 1 || $quantity > 100) {
        return array(null, 'Thông tin món hoặc số lượng không hợp lệ.');
    }
    return db()->transaction(function () use ($orderId, $menuId, $quantity, $data) {
        $order = db()->fetchOne("SELECT order_id FROM orders WHERE order_id = ? AND status = 'open' FOR UPDATE", array($orderId));
        if (!$order) return array(null, 'Đơn hàng không còn mở.');
        $menu = db()->fetchOne(
            "SELECT m.item_name, m.price, c.has_options FROM menu_items m
             JOIN categories c ON c.category_id = m.category_id
             WHERE m.item_id = ? AND m.is_hidden = 0 AND m.is_sold_out = 0",
            array($menuId)
        );
        if (!$menu) return array(null, 'Món không tồn tại hoặc đang tạm ngừng bán.');
        $price = (int) $menu['price'];
        $options = $data['options'] ?? array();
        if (!is_array($options)) return array(null, 'Tùy chọn món không hợp lệ.');
        if ($options) {
            if (!(int) $menu['has_options']) return array(null, 'Món này không hỗ trợ tùy chọn.');
            $sizes = array('S' => 0, 'M' => 5000, 'L' => 10000);
            $toppings = array('Trân châu' => 7000, 'Thạch' => 5000, 'Kem cheese' => 10000);
            $size = $options['size'] ?? 'M';
            if (!isset($sizes[$size])) return array(null, 'Size không hợp lệ.');
            $price += $sizes[$size];
            foreach (($options['toppings'] ?? array()) as $topping) {
                if (!isset($toppings[$topping])) return array(null, 'Topping không hợp lệ.');
                $price += $toppings[$topping];
            }
            if (!in_array($options['sugar'] ?? '50%', array('0%', '30%', '50%', '100%'), true) ||
                !in_array($options['ice'] ?? 'Ít đá', array('Không đá', 'Ít đá', 'Bình thường'), true)) {
                return array(null, 'Mức đường hoặc đá không hợp lệ.');
            }
        }
        $note = trim((string) ($data['note'] ?? ''));
        if (strlen($note) > 255) return array(null, 'Ghi chú tối đa 255 ký tự.');
        db()->execute(
            "INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, quantity, note)
             VALUES (?, ?, ?, ?, ?, ?)",
            array($orderId, $menuId, $menu['item_name'], $price, $quantity, $note ?: null)
        );
        return array(db()->insertId(), '');
    });
}

function orders_update_item($itemId, $data)
{
    $itemId = (int) $itemId;
    $quantity = (int) ($data['quantity'] ?? 0);
    $note = trim((string) ($data['note'] ?? ''));
    if ($itemId <= 0 || $quantity < 1 || $quantity > 100 || strlen($note) > 255) return 'Số lượng hoặc ghi chú không hợp lệ.';
    return db()->transaction(function () use ($itemId, $quantity, $note) {
        $item = db()->fetchOne(
            "SELECT oi.order_item_id FROM order_items oi JOIN orders o ON o.order_id = oi.order_id
             WHERE oi.order_item_id = ? AND oi.status = 'draft' AND o.status = 'open' FOR UPDATE",
            array($itemId)
        );
        if (!$item) return 'Chỉ có thể sửa món chưa gửi bếp.';
        db()->execute("UPDATE order_items SET quantity = ?, note = ? WHERE order_item_id = ?", array($quantity, $note ?: null, $itemId));
        return '';
    });
}

function orders_delete_item($itemId)
{
    $itemId = (int) $itemId;
    return db()->transaction(function () use ($itemId) {
        $item = db()->fetchOne(
            "SELECT oi.order_id FROM order_items oi JOIN orders o ON o.order_id = oi.order_id
             WHERE oi.order_item_id = ? AND oi.status = 'draft' AND o.status = 'open' FOR UPDATE",
            array($itemId)
        );
        if (!$item) return 'Chỉ có thể xóa món chưa gửi bếp.';
        db()->execute("DELETE FROM order_items WHERE order_item_id = ?", array($itemId));
        $remaining = db()->fetchOne(
            "SELECT order_item_id FROM order_items WHERE order_id = ? AND status <> 'cancelled' LIMIT 1",
            array((int) $item['order_id'])
        );
        if (!$remaining) db()->execute("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND status = 'open'", array((int) $item['order_id']));
        return '';
    });
}

function orders_cancel_item($itemId, $reason, $user)
{
    $itemId = (int) $itemId;
    $reason = trim((string) $reason);
    return db()->transaction(function () use ($itemId, $reason, $user) {
        $item = db()->fetchOne(
            "SELECT oi.status, oi.order_id, oi.quantity FROM order_items oi JOIN orders o ON o.order_id = oi.order_id
             WHERE oi.order_item_id = ? AND o.status = 'open' FOR UPDATE",
            array($itemId)
        );
        if (!$item || in_array($item['status'], array('draft', 'cancelled'), true)) return 'Không tìm thấy món đã gửi bếp.';
        if ($item['status'] === 'done' && !in_array($user['roleKey'], array('manager', 'cashier'), true)) {
            return 'Chỉ Thu ngân hoặc Quản lý được hủy món đã làm xong.';
        }
        if ($item['status'] === 'done' && $reason === '') return 'Cần nhập lý do hủy món đã làm xong.';
        if ((int) $item['quantity'] > 1) {
            db()->execute("UPDATE order_items SET quantity = quantity - 1 WHERE order_item_id = ?", array($itemId));
        } else {
            db()->execute("UPDATE order_items SET status = 'cancelled' WHERE order_item_id = ?", array($itemId));
        }
        $remaining = db()->fetchOne(
            "SELECT order_item_id FROM order_items WHERE order_id = ? AND status <> 'cancelled' LIMIT 1",
            array((int) $item['order_id'])
        );
        if (!$remaining) db()->execute("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND status = 'open'", array((int) $item['order_id']));
        return '';
    });
}

function orders_move($orderId, $tableId)
{
    return db()->transaction(function () use ($orderId, $tableId) {
        $target = db()->fetchOne("SELECT table_id, table_name FROM dining_tables WHERE table_id = ? AND is_active = 1 FOR UPDATE", array((int) $tableId));
        if (!$target) return 'Không tìm thấy bàn đích.';
        $occupied = db()->fetchOne("SELECT order_id FROM orders WHERE table_id = ? AND status = 'open' FOR UPDATE", array((int) $tableId));
        if ($occupied) return 'Bàn đích hiện không trống.';
        $order = db()->fetchOne("SELECT order_id, order_type FROM orders WHERE order_id = ? AND status = 'open' FOR UPDATE", array((int) $orderId));
        if (!$order || $order['order_type'] !== 'dinein') return 'Đơn cần chuyển không hợp lệ.';
        db()->execute("UPDATE orders SET table_id = ?, table_name = ? WHERE order_id = ?", array((int) $tableId, $target['table_name'], (int) $orderId));
        return '';
    });
}

function orders_merge($sourceOrderId, $targetOrderId)
{
    $ids = array((int) $sourceOrderId, (int) $targetOrderId);
    sort($ids, SORT_NUMERIC);
    return db()->transaction(function () use ($ids, $sourceOrderId, $targetOrderId) {
        $locked = array();
        foreach ($ids as $id) {
            $row = db()->fetchOne("SELECT order_id, status, order_type FROM orders WHERE order_id = ? FOR UPDATE", array($id));
            if (!$row || $row['status'] !== 'open' || $row['order_type'] !== 'dinein') return 'Không tìm thấy đơn cần gộp.';
            $locked[$id] = $row;
        }
        if ((int) $sourceOrderId === (int) $targetOrderId) return 'Không thể gộp đơn vào chính nó.';
        db()->execute("UPDATE order_items SET order_id = ? WHERE order_id = ?", array((int) $targetOrderId, (int) $sourceOrderId));
        db()->execute("UPDATE orders SET status = 'cancelled' WHERE order_id = ?", array((int) $sourceOrderId));
        return '';
    });
}

function orders_split($sourceOrderId, $tableId, $itemIds, $user)
{
    $itemIds = array_values(array_unique(array_filter(array_map('intval', (array) $itemIds), function ($id) { return $id > 0; })));
    if (!$itemIds) return array(null, 'Chọn ít nhất một dòng món để tách.');
    return db()->transaction(function () use ($sourceOrderId, $tableId, $itemIds, $user) {
        $target = db()->fetchOne("SELECT table_id, table_name FROM dining_tables WHERE table_id = ? AND is_active = 1 FOR UPDATE", array((int) $tableId));
        if (!$target) return array(null, 'Không tìm thấy bàn đích.');
        $occupied = db()->fetchOne("SELECT order_id FROM orders WHERE table_id = ? AND status = 'open' FOR UPDATE", array((int) $tableId));
        if ($occupied) return array(null, 'Bàn đích hiện không trống.');
        $source = db()->fetchOne("SELECT * FROM orders WHERE order_id = ? AND status = 'open' FOR UPDATE", array((int) $sourceOrderId));
        if (!$source || $source['order_type'] !== 'dinein' || (int) $source['table_id'] === (int) $tableId) {
            return array(null, 'Đơn nguồn hoặc bàn đích không hợp lệ.');
        }
        foreach ($itemIds as $itemId) {
            $line = db()->fetchOne(
                "SELECT order_item_id FROM order_items WHERE order_item_id = ? AND order_id = ? AND status <> 'cancelled' FOR UPDATE",
                array($itemId, (int) $sourceOrderId)
            );
            if (!$line) return array(null, 'Một hoặc nhiều dòng món không còn thuộc đơn nguồn.');
        }
        db()->execute(
            "INSERT INTO orders (table_id, table_name, order_type, created_by) VALUES (?, ?, 'dinein', ?)",
            array((int) $tableId, $target['table_name'], (int) $user['staffId'])
        );
        $newId = db()->insertId();
        $code = 'DH' . date('ymd') . '-' . str_pad((string) $newId, 5, '0', STR_PAD_LEFT);
        db()->execute("UPDATE orders SET order_code = ? WHERE order_id = ?", array($code, $newId));
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        db()->execute("UPDATE order_items SET order_id = ? WHERE order_id = ? AND order_item_id IN ($placeholders)", array_merge(array($newId, (int) $sourceOrderId), $itemIds));
        return array(array('id' => $newId, 'code' => $code), '');
    });
}

function orders_confirm($orderId)
{
    if ($orderId <= 0) return 'Đơn hàng không hợp lệ.';
    return db()->transaction(function () use ($orderId) {
        $order = db()->fetchOne("SELECT order_id FROM orders WHERE order_id = ? AND status = 'open' FOR UPDATE", array($orderId));
        if (!$order) return 'Đơn hàng không còn mở.';
        db()->execute(
            "UPDATE order_items SET status = 'waiting', confirmed_at = NOW()
             WHERE order_id = ? AND status = 'draft'",
            array($orderId)
        );
        return '';
    });
}

function orders_cancel_empty($orderId)
{
    $orderId = (int) $orderId;
    return db()->transaction(function () use ($orderId) {
        $order = db()->fetchOne("SELECT order_id FROM orders WHERE order_id = ? AND status = 'open' FOR UPDATE", array($orderId));
        if (!$order) return '';
        $items = db()->fetchOne("SELECT order_item_id FROM order_items WHERE order_id = ? AND status <> 'cancelled' LIMIT 1", array($orderId));
        if ($items) return 'Không thể hủy đơn đang có món.';
        db()->execute("UPDATE orders SET status = 'cancelled' WHERE order_id = ?", array($orderId));
        return '';
    });
}

function orders_kitchen_status($data)
{
    $itemId = (int) ($data['itemId'] ?? 0);
    $status = $data['status'] ?? '';
    if ($itemId <= 0 || !in_array($status, array('waiting', 'making', 'done'), true)) return 'Trạng thái món không hợp lệ.';
    return db()->transaction(function () use ($itemId, $status) {
        $item = db()->fetchOne(
            "SELECT oi.order_item_id, oi.status, oi.order_id FROM order_items oi
             JOIN orders o ON o.order_id = oi.order_id
             WHERE oi.order_item_id = ? AND o.status = 'open' FOR UPDATE",
            array($itemId)
        );
        if (!$item || $item['status'] === 'draft' || $item['status'] === 'cancelled') return 'Không tìm thấy món đang chờ bếp.';
        $startedAt = $status === 'making' ? 'NOW()' : ($status === 'done' ? 'COALESCE(started_at, NOW())' : 'NULL');
        $doneAt = $status === 'done' ? 'NOW()' : 'NULL';
        db()->execute(
            "UPDATE order_items SET status = ?, started_at = " . $startedAt . ", done_at = " . $doneAt . " WHERE order_item_id = ?",
            array($status, $itemId)
        );
        return '';
    });
}

function orders_pay($data, $user)
{
    $orderId = (int) ($data['orderId'] ?? 0);
    $method = $data['paymentMethod'] ?? '';
    $discount = (int) ($data['discount'] ?? 0);
    $expectedSubtotal = (int) ($data['expectedSubtotal'] ?? -1);
    $voucherCode = strtoupper(trim((string) ($data['voucher'] ?? '')));
    if ($orderId <= 0 || !in_array($method, array('cash', 'qr', 'card', 'ewallet', 'transfer'), true) || $discount < 0) {
        return array(null, 'Thông tin thanh toán không hợp lệ.');
    }
    if ($user['roleKey'] === 'waiter' && $discount > 0 && $voucherCode === '') {
        return array(null, 'Phục vụ không có quyền áp dụng giảm giá.');
    }
    $snapshot = db()->fetchOne("SELECT table_id FROM orders WHERE order_id = ? AND status = 'open'", array($orderId));
    if (!$snapshot) return array(null, 'Đơn hàng không còn mở.');
    return db()->transaction(function () use ($orderId, $method, $discount, $expectedSubtotal, $data, $user, $snapshot, $voucherCode) {
        if ($snapshot['table_id'] !== null) {
            db()->fetchOne("SELECT table_id FROM dining_tables WHERE table_id = ? FOR UPDATE", array((int) $snapshot['table_id']));
        }
        $order = db()->fetchOne(
            "SELECT o.*, s.full_name AS creator_name FROM orders o
             LEFT JOIN staff s ON s.staff_id = o.created_by
             WHERE o.order_id = ? AND o.status = 'open' FOR UPDATE",
            array($orderId)
        );
        if (!$order) return array(null, 'Đơn hàng không còn mở.');
        $sum = db()->fetchOne(
            "SELECT COALESCE(SUM(unit_price * quantity), 0) AS subtotal
             FROM order_items WHERE order_id = ? AND status <> 'cancelled' AND status <> 'draft'",
            array($orderId)
        );
        $subtotal = (int) $sum['subtotal'];
        if ($subtotal <= 0 || $discount > $subtotal) return array(null, 'Tổng tiền hoặc mức giảm giá không hợp lệ.');
        if ($expectedSubtotal !== $subtotal) return array(null, 'Đơn hàng vừa thay đổi. Hãy tải lại hóa đơn trước khi thanh toán.');
        $promotionId = null;
        if ($voucherCode !== '') {
            $promotion = db()->fetchOne('SELECT promotion_id, discount_type, discount_value, starts_on, ends_on, max_uses, used_count, hour_from, hour_to, is_active FROM promotions WHERE code = ? FOR UPDATE', array($voucherCode));
            $type = null;
            $value = null;
            if ($promotion) {
                $now = new DateTime();
                $today = $now->format('Y-m-d');
                $hour = (int) $now->format('G');
                if (!(int) $promotion['is_active']
                    || ($promotion['starts_on'] && $today < $promotion['starts_on'])
                    || ($promotion['ends_on'] && $today > $promotion['ends_on'])
                    || ((int) $promotion['max_uses'] > 0 && (int) $promotion['used_count'] >= (int) $promotion['max_uses'])
                    || ($promotion['hour_from'] !== null && ($hour < (int) $promotion['hour_from'] || $hour >= (int) $promotion['hour_to']))) {
                    return array(null, 'Mã khuyến mãi đã hết hạn hoặc không còn áp dụng.');
                }
                $type = $promotion['discount_type'];
                $value = (int) $promotion['discount_value'];
                $promotionId = (int) $promotion['promotion_id'];
            } else {
                $fixedVouchers = array('GIAM10' => array('pct', 10), 'GIAM20' => array('pct', 20), 'GIAM5K' => array('vnd', 5000), 'GIAM20K' => array('vnd', 20000));
                if (!isset($fixedVouchers[$voucherCode])) return array(null, 'Mã khuyến mãi không hợp lệ.');
                list($type, $value) = $fixedVouchers[$voucherCode];
            }
            $expectedDiscount = min($subtotal, $type === 'pct' ? (int) round($subtotal * $value / 100) : $value);
            if ($discount !== $expectedDiscount) return array(null, 'Mức giảm giá của mã không hợp lệ. Hãy áp dụng lại mã.');
        }
        $total = $subtotal - $discount;
        $items = array_map(function ($item) {
            return array(
                'id' => $item['menu_item_id'] === null ? null : (int) $item['menu_item_id'],
                'name' => $item['item_name'],
                'qty' => (int) $item['quantity'],
                'price' => (int) $item['unit_price'],
                'note' => $item['note'] ?? '',
            );
        }, db()->fetchAll(
            "SELECT menu_item_id, item_name, unit_price, quantity, note FROM order_items
             WHERE order_id = ? AND status <> 'cancelled' AND status <> 'draft' ORDER BY order_item_id",
            array($orderId)
        ));
        db()->execute(
            "UPDATE orders SET status = 'paid', subtotal = ?, discount = ?, total = ?, voucher = ?, promotion_id = ?,
             payment_method = ?, payment_ref = ?, paid_by = ?, paid_at = NOW() WHERE order_id = ?",
            array($subtotal, $discount, $total, $voucherCode !== '' ? $voucherCode : null, $promotionId,
                $method, trim((string) ($data['paymentRef'] ?? '')) ?: null, (int) $user['staffId'], $orderId)
        );
        if ($promotionId !== null) db()->execute('UPDATE promotions SET used_count = used_count + 1 WHERE promotion_id = ?', array($promotionId));
        $nextOrder = null;
        $drafts = db()->fetchAll("SELECT order_item_id FROM order_items WHERE order_id = ? AND status = 'draft'", array($orderId));
        if ($drafts) {
            db()->execute(
                "INSERT INTO orders (table_id, table_name, order_type, customer, address, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)",
                array($order['table_id'], $order['table_name'], $order['order_type'], $order['customer'],
                    $order['address'], (int) $user['staffId'])
            );
            $nextId = db()->insertId();
            $nextCode = 'DH' . date('ymd') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
            db()->execute("UPDATE orders SET order_code = ? WHERE order_id = ?", array($nextCode, $nextId));
            db()->execute("UPDATE order_items SET order_id = ? WHERE order_id = ? AND status = 'draft'", array($nextId, $orderId));
            $nextOrder = array('id' => $nextId, 'code' => $nextCode);
        }
        return array(array(
            'id' => $order['order_code'], 'orderId' => $orderId,
            'tableName' => $order['table_name'], 'orderType' => $order['order_type'],
            'subtotal' => $subtotal, 'discount' => $discount, 'total' => $total,
            'paymentMethod' => $method, 'createdAt' => date('c'), 'status' => 'active',
            'createdById' => (int) $order['created_by'], 'createdBy' => $order['creator_name'] ?? '',
            'items' => $items, 'nextOrder' => $nextOrder,
        ), '');
    });
}
