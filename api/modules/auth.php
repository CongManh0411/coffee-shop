<?php
function auth_login($username, $password)
{
    $row = db()->fetchOne(
        "SELECT staff_id, full_name, username, password, role FROM staff WHERE username = ? AND status = 1",
        array(trim($username))
    );

    if (!$row || !password_verify($password, $row['password'])) {
        return null;
    }

    session_regenerate_id(true);
    $user = array(
        'staffId' => (int) $row['staff_id'],
        'name'    => $row['full_name'],
        'roleKey' => $row['role'],
    );
    $_SESSION['user'] = $user;
    return $user;
}

function auth_logout()
{
    $_SESSION = array();
    session_destroy();
}