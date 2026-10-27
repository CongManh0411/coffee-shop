<?php
// admin/pages/tablemanage/action.php — trang "Quản lý bàn" (Admin)
// Chạy TRƯỚC khi in HTML nên redirect() được: nhận form POST, kiểm tra, INSERT/UPDATE/DELETE rồi Post/Redirect/Get.
require_once __DIR__ . '/../../../functions.php';

$title = 'Bàn';
$css = array('table-manage.css');
$scripts = array(); // trang này dùng form POST của PHP, không cần JavaScript

$tableFormError = '';
$formTableId = 0;      // 0 = đang thêm bàn mới, > 0 = đang sửa bàn có id này
$formTableName = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postAction = (string) ($_POST['action'] ?? '');
    $postId = (int) ($_POST['table_id'] ?? 0);

    if ($postAction === 'save') {
        $formTableId = $postId;
        $formTableName = trim((string) ($_POST['name'] ?? ''));
        $data = array('name' => $formTableName);
        if ($postId > 0) {
            $tableFormError = tables_update($postId, $data);          // UPDATE (có kiểm tra tên rỗng/trùng/quá dài)
            $savedMessage = 'Đã cập nhật tên bàn.';
        } else {
            list($newId, $tableFormError) = tables_create($data);     // INSERT (có kiểm tra tên rỗng/trùng/quá dài)
            $savedMessage = 'Đã thêm bàn mới.';
        }
        if ($tableFormError === '') {
            $_SESSION['table_flash'] = array('type' => 'ok', 'text' => $savedMessage);
            redirect(admin_url('tablemanage'));                       // Post/Redirect/Get
        }
        // Sai dữ liệu: không ghi vào CSDL, giữ lại giá trị đã nhập và hiện lỗi bên dưới.
    } elseif ($postAction === 'delete') {
        $deleteError = tables_delete($postId);
        $_SESSION['table_flash'] = $deleteError === ''
            ? array('type' => 'ok', 'text' => 'Đã xóa bàn.')
            : array('type' => 'error', 'text' => $deleteError);
        redirect(admin_url('tablemanage'));
    }
} else {
    // Bấm "Sửa" ở một dòng: ?page=tablemanage&edit=ID
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId && $editId > 0) {
        $editTable = get_dining_table($editId);
        if ($editTable) {
            $formTableId = (int) $editTable['table_id'];
            $formTableName = $editTable['table_name'];
        }
    }
}

$adminTables = admin_tables_with_status();
$tablesOccupied = 0;
foreach ($adminTables as $tableRow) {
    if ($tableRow['status'] === 'occupied') {
        $tablesOccupied++;
    }
}

$tableFlash = $_SESSION['table_flash'] ?? null;
unset($_SESSION['table_flash']);
