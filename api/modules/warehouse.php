<?php
// api/modules/warehouse.php — Quản lý kho theo số ly / số phần kết nối MySQL

// Bound quantities to the signed INT used by quantity_delta; reject floats/bools/arrays.
function warehouse_integer($value, $minimum = 0)
{
    if (!is_int($value) && !(is_string($value) && preg_match('/^[0-9]+$/D', $value))) return null;
    $validated = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => $minimum, 'max_range' => 2147483647)));
    return $validated === false ? null : $validated;
}

function warehouse_ensure_stock_rows()
{
    db()->execute("INSERT IGNORE INTO menu_stock (menu_item_id, quantity, min_quantity) SELECT item_id, 0, 10 FROM menu_items");
}

function warehouse_list()
{
    warehouse_ensure_stock_rows();
    $sql = "SELECT 
                m.item_id AS id,
                m.item_name AS name,
                c.category_name AS category,
                CASE WHEN c.category_name = 'Bánh' THEN 'phần' ELSE 'ly' END AS unit,
                COALESCE(s.quantity, 0) AS stock,
                COALESCE(s.min_quantity, 10) AS minLevel,
                s.updated_at AS updatedAt,
                m.is_hidden AS isHidden,
                COALESCE(r.reserved_qty, 0) AS reserved
            FROM menu_items m
            JOIN categories c ON c.category_id = m.category_id
            LEFT JOIN menu_stock s ON s.menu_item_id = m.item_id
            LEFT JOIN (
                SELECT oi.menu_item_id, SUM(oi.quantity) AS reserved_qty
                FROM order_items oi
                JOIN orders o ON o.order_id = oi.order_id
                WHERE o.status = 'open' AND oi.status <> 'cancelled'
                GROUP BY oi.menu_item_id
            ) r ON r.menu_item_id = m.item_id
            ORDER BY c.category_id, m.item_name";

    $items = db()->fetchAll($sql);
    $outItems = array();
    $totalStock = 0;
    $underMinLevel = 0;
    $outOfStock = 0;

    foreach ($items as $row) {
        $stock = (int) $row['stock'];
        $reserved = (int) $row['reserved'];
        $minLevel = (int) $row['minLevel'];
        $available = max(0, $stock - $reserved);

        if ($stock <= 0) {
            $outOfStock++;
        } elseif ($stock <= $minLevel) {
            $underMinLevel++;
        }
        $totalStock += $stock;

        $outItems[] = array(
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'unit' => $row['unit'],
            'stock' => $stock,
            'reserved' => $reserved,
            'available' => $available,
            'minLevel' => $minLevel,
            'updatedAt' => $row['updatedAt'],
            'isHidden' => (bool) $row['isHidden']
        );
    }

    $stats = array(
        'totalIngredients' => count($outItems),
        'underMinLevel' => $underMinLevel,
        'outOfStock' => $outOfStock,
        'totalStock' => $totalStock
    );

    return array(
        'items' => $outItems,
        'stats' => $stats
    );
}

function warehouse_adjust($data, $user)
{
    $itemId = warehouse_integer($data['itemId'] ?? null, 1);
    $mode = trim((string) ($data['mode'] ?? 'import'));
    $amount = warehouse_integer($data['amount'] ?? null, 1);
    $note = trim((string) ($data['note'] ?? ($data['reason'] ?? '')));
    $hasMinLevel = array_key_exists('minLevel', $data);
    $minLevel = $hasMinLevel ? warehouse_integer($data['minLevel']) : null;

    if ($itemId === null) {
        return 'Vui lòng chọn món cần điều chỉnh.';
    }
    if (!in_array($mode, array('import', 'adjust'), true)) {
        return 'Loại thao tác không hợp lệ (chỉ chấp nhận nhập thêm hoặc điều chỉnh giảm).';
    }
    if ($amount === null) {
        return 'Số lượng phải là số nguyên dương lớn hơn 0.';
    }
    if ($hasMinLevel && $minLevel === null) {
        return 'Mức cảnh báo sắp hết phải là số nguyên không âm.';
    }
    if (mb_strlen($note, 'UTF-8') > 500) {
        return 'Ghi chú không được vượt quá 500 ký tự.';
    }

    $staffId = (int) ($user['staffId'] ?? 0);
    if ($staffId <= 0) {
        return 'Không xác định được thông tin người thực hiện.';
    }

    $delta = ($mode === 'import') ? $amount : -$amount;
    $ref = ($mode === 'import') ? 'Nhập kho thủ công' : 'Điều chỉnh hao hụt / kiểm kê';
    if ($note === '') {
        $note = ($mode === 'import') ? 'Nhập thêm tồn kho' : 'Điều chỉnh giảm tồn kho';
    }

    return db()->transaction(function () use ($itemId, $delta, $minLevel, $ref, $note, $staffId, $mode, $amount) {
        if (!db()->fetchOne('SELECT item_id FROM menu_items WHERE item_id = ?', array($itemId))) return 'Món không tồn tại trong thực đơn.';
        db()->execute("INSERT IGNORE INTO menu_stock (menu_item_id, quantity, min_quantity) VALUES (?, 0, 10)", array($itemId));

        $item = db()->fetchOne(
            "SELECT m.item_name, c.category_name, s.quantity, s.min_quantity
             FROM menu_items m
             JOIN categories c ON c.category_id = m.category_id
             JOIN menu_stock s ON s.menu_item_id = m.item_id
             WHERE m.item_id = ? FOR UPDATE",
            array($itemId)
        );

        if (!$item) {
            return 'Món không tồn tại trong thực đơn.';
        }

        $unit = ($item['category_name'] === 'Bánh') ? 'phần' : 'ly';
        $currQty = (int) $item['quantity'];
        $newQty = $currQty + $delta;

        if ($newQty < 0) {
            return 'Kho "' . $item['item_name'] . '" chỉ còn ' . $currQty . ' ' . $unit . ', không đủ để giảm ' . $amount . ' ' . $unit . '.';
        }

        if ($newQty > 2147483647) return 'Tồn kho vượt giới hạn cho phép (2147483647).';

        $newMin = ($minLevel !== null) ? $minLevel : (int) $item['min_quantity'];

        db()->execute(
            "UPDATE menu_stock SET quantity = ?, min_quantity = ?, updated_at = NOW() WHERE menu_item_id = ?",
            array($newQty, $newMin, $itemId)
        );

        db()->execute(
            "UPDATE menu_items SET stock = ?, min_stock = ? WHERE item_id = ?",
            array($newQty, $newMin, $itemId)
        );

        db()->execute(
            "INSERT INTO menu_stock_movements (menu_item_id, movement_type, quantity_delta, reference, note, staff_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            array($itemId, $mode === 'import' ? 'import' : 'adjustment', $delta, $ref, $note, $staffId)
        );

        return '';
    });
}

function warehouse_set_min_level($data, $user)
{
    $itemId = warehouse_integer($data['itemId'] ?? null, 1);
    $minLevel = warehouse_integer($data['minLevel'] ?? null);

    if ($itemId === null) return 'Vui lòng chọn món.';
    if ($minLevel === null) return 'Mức cảnh báo sắp hết phải là số nguyên không âm.';

    return db()->transaction(function () use ($itemId, $minLevel) {
        if (!db()->fetchOne('SELECT item_id FROM menu_items WHERE item_id = ? FOR UPDATE', array($itemId))) return 'Món không tồn tại trong thực đơn.';
        db()->execute("INSERT IGNORE INTO menu_stock (menu_item_id, quantity, min_quantity) VALUES (?, 0, ?)", array($itemId, $minLevel));
        db()->execute("UPDATE menu_stock SET min_quantity = ? WHERE menu_item_id = ?", array($minLevel, $itemId));
        db()->execute("UPDATE menu_items SET min_stock = ? WHERE item_id = ?", array($minLevel, $itemId));
        return '';
    });
}

function warehouse_movements($filters = array())
{
    $where = array();
    $params = array();

    if (!empty($filters['itemId']) && (int) $filters['itemId'] > 0) {
        $where[] = "m.menu_item_id = ?";
        $params[] = (int) $filters['itemId'];
    }

    if (!empty($filters['type'])) {
        $type = trim($filters['type']);
        if (in_array($type, array('import', 'adjustment', 'sale', 'sale_rollback'), true)) {
            $where[] = "m.movement_type = ?";
            $params[] = $type;
        }
    }

    if (!empty($filters['dateFrom'])) {
        $dateFrom = trim($filters['dateFrom']);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = "m.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
    }

    if (!empty($filters['dateTo'])) {
        $dateTo = trim($filters['dateTo']);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = "m.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT 
                m.movement_id AS id,
                m.menu_item_id AS itemId,
                mi.item_name AS name,
                c.category_name AS category,
                CASE WHEN c.category_name = 'Bánh' THEN 'phần' ELSE 'ly' END AS unit,
                m.movement_type AS type,
                m.quantity_delta AS delta,
                m.reference AS ref,
                m.note,
                m.created_at AS ts,
                m.staff_id AS staffId,
                COALESCE(s.full_name, 'Hệ thống') AS actor
            FROM menu_stock_movements m
            JOIN menu_items mi ON mi.item_id = m.menu_item_id
            JOIN categories c ON c.category_id = mi.category_id
            LEFT JOIN staff s ON s.staff_id = m.staff_id
            {$whereSql}
            ORDER BY m.movement_id DESC
            LIMIT 500";

    $rows = db()->fetchAll($sql, $params);
    $typeNames = array(
        'import' => 'Nhập kho',
        'adjustment' => 'Điều chỉnh',
        'sale' => 'Bán hàng',
        'sale_rollback' => 'Hoàn tác'
    );

    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['itemId'] = (int) $r['itemId'];
        $r['delta'] = (int) $r['delta'];
        $r['typeName'] = $typeNames[$r['type']] ?? $r['type'];
        $r['ref'] = $r['ref'] ?? '';
        $r['note'] = $r['note'] ?? '';
    }
    unset($r);

    return $rows;
}

function warehouse_deduct_sale($orderId, $orderCode, $user)
{
    $orderItems = db()->fetchAll(
        "SELECT menu_item_id, item_name, quantity
         FROM order_items
         WHERE order_id = ? AND status <> 'cancelled' AND status <> 'draft' AND menu_item_id IS NOT NULL",
        array((int) $orderId)
    );

    if (!$orderItems) {
        return '';
    }

    // Nhóm số lượng theo từng món
    $stockNeeded = array();
    foreach ($orderItems as $item) {
        $mId = (int) $item['menu_item_id'];
        if (!isset($stockNeeded[$mId])) {
            $stockNeeded[$mId] = array(
                'qty' => 0,
                'name' => $item['item_name']
            );
        }
        $stockNeeded[$mId]['qty'] += (int) $item['quantity'];
    }

    ksort($stockNeeded, SORT_NUMERIC);
    foreach (array_keys($stockNeeded) as $mId) {
        db()->execute("INSERT IGNORE INTO menu_stock (menu_item_id, quantity, min_quantity) VALUES (?, 0, 10)", array($mId));
    }

    // Khóa dòng và kiểm tra tồn kho từng món
    foreach ($stockNeeded as $mId => $req) {
        $stockRow = db()->fetchOne(
            "SELECT s.quantity, c.category_name
             FROM menu_stock s
             JOIN menu_items m ON m.item_id = s.menu_item_id
             JOIN categories c ON c.category_id = m.category_id
             WHERE s.menu_item_id = ? FOR UPDATE",
            array($mId)
        );

        $currStock = $stockRow ? (int) $stockRow['quantity'] : 0;
        $unit = ($stockRow && $stockRow['category_name'] === 'Bánh') ? 'phần' : 'ly';

        if ($currStock < $req['qty']) {
            return 'Món "' . $req['name'] . '" chỉ còn ' . $currStock . ' ' . $unit . ' trong kho, không đủ để thanh toán ' . $req['qty'] . ' ' . $unit . '. Vui lòng kiểm tra lại tồn kho.';
        }
        $stockNeeded[$mId]['current'] = $currStock;
    }

    // Trừ kho và ghi nhận lịch sử biến động trong cùng giao dịch
    $staffId = (int) ($user['staffId'] ?? 0);
    foreach ($stockNeeded as $mId => $req) {
        $newQty = max(0, $req['current'] - $req['qty']);
        db()->execute(
            "UPDATE menu_stock SET quantity = ?, updated_at = NOW() WHERE menu_item_id = ?",
            array($newQty, $mId)
        );

        db()->execute(
            "UPDATE menu_items SET stock = ? WHERE item_id = ?",
            array($newQty, $mId)
        );

        db()->execute(
            "INSERT INTO menu_stock_movements (menu_item_id, movement_type, quantity_delta, reference, note, staff_id, created_at)
             VALUES (?, 'sale', ?, ?, ?, ?, NOW())",
            array($mId, -$req['qty'], $orderCode, 'Thanh toán hóa đơn ' . $orderCode, $staffId > 0 ? $staffId : null)
        );
    }

    return '';
}
