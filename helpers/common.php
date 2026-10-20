<?php
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value)
{
    return number_format((float) $value, 0, ',', '.') . ' đ';
}

// Đường dẫn tới một trang qua Router: url('menu') => index.php?page=menu
function url($page)
{
    return 'index.php?page=' . rawurlencode($page);
}

// Chuyển trang rồi dừng (dùng trong action.php, trước khi in header)
function redirect($location)
{
    header('Location: ' . $location);
    exit;
}
