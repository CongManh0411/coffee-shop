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
    return reservation_insert($reservation, $createdBy);
}

function reservation_insert($reservation, $createdBy = null)
{
    db()->execute(
        "INSERT INTO reservations (customer_name, phone, party_size, reserved_at, table_id, note, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, 'booked', ?)",
        array($reservation['name'], $reservation['phone'], $reservation['partySize'], $reservation['reservedAt'],
            $reservation['tableId'], $reservation['note'], $createdBy)
    );
    return array(db()->insertId(), null);
}

// Giới hạn đặt bàn công khai: mỗi IP tối đa 3 yêu cầu thành công / giờ, mỗi số điện thoại tối đa 3 bàn đang chờ,
// không đặt trùng giờ, chỉ đặt trong giờ mở cửa và tối đa 60 ngày tới.
const RESERVATION_PUBLIC_IP_LIMIT = 3;
const RESERVATION_PUBLIC_PHONE_LIMIT = 3;
const RESERVATION_PUBLIC_MAX_DAYS = 60;

function reservation_throttle_file()
{
    $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'coffee-shop-reservation-attempts';
    if (!is_dir($directory)) @mkdir($directory, 0700, true);
    return $directory . DIRECTORY_SEPARATOR . hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) . '.json';
}

// Đếm (và nếu $record thì ghi thêm) số yêu cầu thành công của IP này trong 1 giờ gần nhất.
function reservation_throttle_count($record = false, $windowSeconds = 3600)
{
    $handle = @fopen(reservation_throttle_file(), 'c+');
    if (!$handle) return 0;
    flock($handle, LOCK_EX);
    rewind($handle);
    $events = json_decode(stream_get_contents($handle), true);
    if (!is_array($events)) $events = array();
    $cutoff = time() - $windowSeconds;
    $events = array_values(array_filter($events, function ($stamp) use ($cutoff) { return (int) $stamp > $cutoff; }));
    if ($record) $events[] = time();
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($events));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return count($events);
}

function reservation_create_public($data)
{
    if (!empty($data['website'])) return array(null, 'Không thể gửi yêu cầu đặt bàn.');
    if (reservation_throttle_count() >= RESERVATION_PUBLIC_IP_LIMIT) {
        return array(null, 'Bạn đã gửi nhiều yêu cầu đặt bàn. Vui lòng thử lại sau hoặc gọi hotline của quán.');
    }
    list($reservation, $err) = reservation_validate($data, true);
    if ($err) return array(null, $err);

    $when = strtotime($reservation['reservedAt']);
    if ($when > strtotime('+' . RESERVATION_PUBLIC_MAX_DAYS . ' days')) {
        return array(null, 'Chỉ nhận đặt bàn trong vòng ' . RESERVATION_PUBLIC_MAX_DAYS . ' ngày tới.');
    }
    list($open, $close) = shop_hours_for_date(date('Y-m-d', $when));
    $minute = (int) date('G', $when) * 60 + (int) date('i', $when);
    if ($minute < $open || $minute >= $close) {
        return array(null, 'Quán mở cửa từ ' . minutes_label($open) . ' đến ' . minutes_label($close) . ' vào ngày này. Vui lòng chọn giờ trong khoảng đó.');
    }
    $same = db()->fetchOne(
        "SELECT reservation_id FROM reservations WHERE phone = ? AND reserved_at = ? AND status = 'booked' LIMIT 1",
        array($reservation['phone'], $reservation['reservedAt'])
    );
    if ($same) return array(null, 'Số điện thoại này đã đặt bàn vào giờ đó rồi.');
    $pending = db()->fetchOne(
        "SELECT COUNT(*) AS n FROM reservations WHERE phone = ? AND status = 'booked' AND reserved_at >= NOW()",
        array($reservation['phone'])
    );
    if ((int) ($pending['n'] ?? 0) >= RESERVATION_PUBLIC_PHONE_LIMIT) {
        return array(null, 'Số điện thoại này đang có nhiều đặt bàn chờ xác nhận. Vui lòng gọi hotline để thay đổi.');
    }

    $result = reservation_insert($reservation, null);
    if (!$result[1]) reservation_throttle_count(true);
    return $result;
}

function reservations_count()
{
    $row = db()->fetchOne('SELECT COUNT(*) AS n FROM reservations');
    return (int) ($row['n'] ?? 0);
}

// Danh sách đặt bàn; truyền $limit/$offset để chỉ lấy một trang (chia trang trong SQL)
function reservations_list($limit = 0, $offset = 0)
{
    $sql =
        "SELECT r.reservation_id, r.customer_name, r.phone, r.party_size, r.reserved_at,
                r.table_id, t.table_name, r.note, r.status, r.created_at
         FROM reservations r LEFT JOIN dining_tables t ON t.table_id = r.table_id
         ORDER BY (r.reserved_at < NOW()), r.reserved_at, r.reservation_id DESC";
    $params = array();
    if ((int) $limit > 0) {
        $sql .= ' LIMIT ? OFFSET ?';
        $params = array((int) $limit, max(0, (int) $offset));
    }
    $rows = db()->fetchAll($sql, $params);
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
