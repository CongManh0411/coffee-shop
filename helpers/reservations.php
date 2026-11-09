<?php
function reservation_validate($data, $requirePhone = true)
{
    $name = trim((string) ($data['name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $digits = preg_replace('/\D+/', '', $phone);
    $partySize = filter_var($data['partySize'] ?? null, FILTER_VALIDATE_INT);
    $reservedInput = trim((string) ($data['reservedAt'] ?? ''));
    $note = trim((string) ($data['note'] ?? ''));

    if ($name === '' || mb_strlen($name, 'UTF-8') > 150) return array(null, 'Vui lòng nhập họ tên hợp lệ.');
    if (($requirePhone || $phone !== '') && (!preg_match('/^[0-9+\s().-]{8,30}$/', $phone) || strlen($digits) < 8 || strlen($digits) > 15)) {
        return array(null, 'Vui lòng nhập số điện thoại hợp lệ.');
    }
    if ($partySize === false || $partySize < 1 || $partySize > 20) return array(null, 'Số khách cần từ 1 đến 20.');
    if (mb_strlen($note, 'UTF-8') > 500) return array(null, 'Ghi chú tối đa 500 ký tự.');

    $reservedAt = DateTime::createFromFormat('!Y-m-d\\TH:i', $reservedInput);
    $dateErrors = DateTime::getLastErrors();
    if (!$reservedAt || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count']))
        || $reservedAt->format('Y-m-d\\TH:i') !== $reservedInput) {
        return array(null, 'Vui lòng chọn ngày và giờ hợp lệ.');
    }
    if ($reservedAt < new DateTime('+30 minutes')) return array(null, 'Thời gian đặt bàn cần trước ít nhất 30 phút.');

    $tableId = null;
    if (!empty($data['tableId'])) {
        $tableId = filter_var($data['tableId'], FILTER_VALIDATE_INT);
        if (!$tableId || !db()->fetchOne('SELECT table_id FROM dining_tables WHERE table_id = ? AND is_active = 1', array($tableId))) {
            return array(null, 'Bàn đã chọn không hợp lệ.');
        }
    }

    return array(array(
        'name' => $name,
        'phone' => $phone !== '' ? $phone : null,
        'partySize' => (int) $partySize,
        'reservedAt' => $reservedAt->format('Y-m-d H:i:s'),
        'note' => $note !== '' ? $note : null,
        'tableId' => $tableId,
    ), null);
}

function reservation_create($data, $createdBy = null, $requirePhone = true)
{
    list($reservation, $err) = reservation_validate($data, $requirePhone);
    if ($err) return array(null, $err);

    db()->execute(
        "INSERT INTO reservations (customer_name, phone, party_size, reserved_at, table_id, note, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, 'booked', ?)",
        array($reservation['name'], $reservation['phone'], $reservation['partySize'], $reservation['reservedAt'],
            $reservation['tableId'], $reservation['note'], $createdBy)
    );
    return array(db()->insertId(), null);
}

function reservation_create_public($data)
{
    if (!empty($data['website'])) return array(null, 'Không thể gửi yêu cầu đặt bàn.');
    return reservation_create($data, null, true);
}

function reservations_list()
{
    $rows = db()->fetchAll(
        "SELECT r.reservation_id, r.customer_name, r.phone, r.party_size, r.reserved_at,
                r.table_id, t.table_name, r.note, r.status, r.created_at
         FROM reservations r LEFT JOIN dining_tables t ON t.table_id = r.table_id
         ORDER BY (r.reserved_at < NOW()), r.reserved_at, r.reservation_id DESC"
    );
    $out = array();
    foreach ($rows as $row) {
        $out[] = array(
            'id' => (int) $row['reservation_id'],
            'name' => $row['customer_name'],
            'phone' => $row['phone'] ?? '',
            'partySize' => (int) $row['party_size'],
            'reservedAt' => $row['reserved_at'],
            'tableId' => $row['table_id'] !== null ? (int) $row['table_id'] : null,
            'tableName' => $row['table_name'] ?? '',
            'note' => $row['note'] ?? '',
            'status' => $row['status'],
        );
    }
    return $out;
}

function reservation_update($id, $data)
{
    $status = (string) ($data['status'] ?? '');
    $allowed = array('booked', 'arrived', 'cancelled', 'no_show');
    if (!in_array($status, $allowed, true)) return 'Trạng thái đặt bàn không hợp lệ.';
    $exists = db()->fetchOne('SELECT reservation_id FROM reservations WHERE reservation_id = ?', array((int) $id));
    if (!$exists) return 'Không tìm thấy yêu cầu đặt bàn.';
    db()->execute('UPDATE reservations SET status = ? WHERE reservation_id = ?', array($status, (int) $id));
    return '';
}
