<?php
const STAFF_ROLES = array(
    'Quản lý'  => 'manager',
    'Phục vụ'  => 'waiter',
    'Bếp'      => 'kitchen',
    'Thu ngân' => 'cashier',
);

// Đổi dòng DB -> dạng frontend đang dùng (fullName, role tiếng Việt...)
function staff_format($r)
{
    return array(
        'id'        => (int) $r['staff_id'],
        'fullName'  => $r['full_name'],
        'username'  => $r['username'],
        'role'      => array_search($r['role'], STAFF_ROLES, true),
        'phone'     => $r['phone'] ?? '',
        'email'     => $r['email'] ?? '',
        'gender'    => $r['gender'] ?? '',
        'birthDate' => $r['birth_date'] ?? '',
    );
}

function staff_list()
{
    $rows = db()->fetchAll("SELECT * FROM staff WHERE status = 1 ORDER BY staff_id");
    return array_map('staff_format', $rows);
}

// Kiểm tra dữ liệu; trả về chuỗi lỗi hoặc '' nếu hợp lệ
function staff_validate($d, $id = 0, $needPassword = true)
{
    if (trim($d['fullName'] ?? '') === '') return 'Vui lòng nhập họ tên.';
    if (!isset(STAFF_ROLES[$d['role'] ?? ''])) return 'Chức vụ không hợp lệ.';
    $u = trim($d['username'] ?? '');
    if ($u === '') return 'Vui lòng nhập tài khoản.';
    if ($needPassword && trim($d['password'] ?? '') === '') return 'Vui lòng nhập mật khẩu.';
    if (!preg_match('/^0\d{9}$/', $d['phone'] ?? '')) return 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.';
    if (($d['email'] ?? '') !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) return 'Email chưa hợp lệ.';

    $dup = db()->fetchOne("SELECT staff_id FROM staff WHERE username = ? AND staff_id <> ?", array($u, (int) $id));
    if ($dup) return 'Tài khoản "' . $u . '" đã tồn tại, hãy chọn tên khác.';
    return '';
}

function staff_create($d)
{
    $err = staff_validate($d, 0, true);
    if ($err) return array(null, $err);

    db()->execute(
        "INSERT INTO staff (full_name, username, password, role, phone, email, gender, birth_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        array(
            trim($d['fullName']), trim($d['username']),
            password_hash($d['password'], PASSWORD_DEFAULT),
            STAFF_ROLES[$d['role']], $d['phone'], ($d['email'] ?? ''),
            ($d['gender'] ?? '') ?: null, ($d['birthDate'] ?? '') ?: null,
        )
    );
    return array(db()->insertId(), '');
}

function staff_update($id, $d)
{
    $old = db()->fetchOne("SELECT staff_id FROM staff WHERE staff_id = ? AND status = 1", array((int) $id));
    if (!$old) return 'Không tìm thấy nhân viên.';

    $changePass = trim($d['password'] ?? '') !== '';
    $err = staff_validate($d, $id, false);
    if ($err) return $err;

    db()->execute(
        "UPDATE staff SET full_name = ?, username = ?, role = ?, phone = ?, email = ?, gender = ?, birth_date = ?
         WHERE staff_id = ?",
        array(
            trim($d['fullName']), trim($d['username']), STAFF_ROLES[$d['role']],
            $d['phone'], ($d['email'] ?? ''),
            ($d['gender'] ?? '') ?: null, ($d['birthDate'] ?? '') ?: null, (int) $id,
        )
    );
    if ($changePass) {
        db()->execute("UPDATE staff SET password = ? WHERE staff_id = ?",
            array(password_hash($d['password'], PASSWORD_DEFAULT), (int) $id));
    }
    return '';
}

// Xóa mềm: giữ lại dữ liệu để hóa đơn/chấm công cũ không bị mất tên người
function staff_delete($id)
{
    db()->execute("UPDATE staff SET status = 0 WHERE staff_id = ?", array((int) $id));
}