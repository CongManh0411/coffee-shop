<?php
function json_ok($data = null)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'data' => $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message, $code = 400)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => false, 'error' => $message), JSON_UNESCAPED_UNICODE);
    exit;
}

// Đọc body JSON gửi từ fetch()
function json_input()
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        json_error('Nội dung JSON không hợp lệ.', 400);
    }
    return $data;
}
class ApiException extends Exception
{
    public $status;
    public function __construct($message, $status = 400)
    {
        parent::__construct($message);
        $this->status = $status;
    }
}

function api_fail($message, $status = 400)
{
    throw new ApiException($message, $status);
}

set_exception_handler(function ($e) {
    if ($e instanceof ApiException) {
        json_error($e->getMessage(), $e->status);
    }
    if ($e instanceof mysqli_sql_exception && $e->getCode() == 1062) {
        json_error('Dữ liệu bị trùng.', 409);          // duplicate key
    }
    error_log($e);                                      // ghi log nội bộ
    json_error('Lỗi máy chủ.', 500);                    // không lộ chi tiết SQL
});
