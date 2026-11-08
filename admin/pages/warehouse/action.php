<?php
// admin/pages/warehouse/action.php — trang "Kho" (Admin)
// Nhập / điều chỉnh kho là form POST (có CSRF) xử lý bằng PHP rồi redirect; chuyển tab và lọc lịch sử dùng link / form GET. Không còn JavaScript gọi API.
require_once __DIR__ . '/../../../functions.php';

$title = 'Kho';
$css = array('warehouse.css');
$scripts = array();

$tab = (string) ($_GET['tab'] ?? $_POST['tab'] ?? 'list');
if (!in_array($tab, array('list', 'add', 'history'), true)) $tab = 'list';

$warehouseError = '';
$formItemId = (int) ($_GET['item'] ?? 0);
$formMode = 'import';
$formAmount = 1;
$formMin = null;
$formReason = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['warehouse_action'])) {
    $formItemId = (int) ($_POST['item_id'] ?? 0);
    $formMode = (string) ($_POST['mode'] ?? 'import');
    $formAmount = (string) ($_POST['amount'] ?? '');
    $formMin = (string) ($_POST['min_level'] ?? '');
    $formReason = trim((string) ($_POST['reason'] ?? ''));
    $tab = 'add';
    if (!post_csrf_ok()) {
        $warehouseError = 'Phiên bảo mật hết hạn. Hãy tải lại trang rồi thử lại.';
    } else {
        $warehouseError = warehouse_adjust(array(
            'itemId'   => $formItemId,
            'mode'     => $formMode,
            'amount'   => $formAmount,
            'minLevel' => $formMin,
            'note'     => $formReason,
        ), $_SESSION['user'] ?? array());
    }
    if ($warehouseError === '') {
        $_SESSION['warehouse_saved'] = 'Cập nhật kho thành công!';
        redirect(admin_url('warehouse'));
    }
}
$warehouseSaved = $_SESSION['warehouse_saved'] ?? '';
unset($_SESSION['warehouse_saved']);

// Dữ liệu kho từ MySQL
$warehouseData = warehouse_list();
$warehouseItems = $warehouseData['items'] ?? array();

// Món đang chọn ở form nhập / điều chỉnh (mặc định món đầu tiên)
$formItem = null;
foreach ($warehouseItems as $it) {
    if ((int) $it['id'] === $formItemId) $formItem = $it;
}
if (!$formItem && $warehouseItems) $formItem = $warehouseItems[0];
if ($formMin === null && $formItem) $formMin = (string) (int) $formItem['minLevel'];

// Bộ lọc lịch sử (GET)
$historyFilters = array(
    'itemId'   => (string) ($_GET['f_item'] ?? ''),
    'type'     => (string) ($_GET['f_type'] ?? ''),
    'dateFrom' => (string) ($_GET['f_from'] ?? ''),
    'dateTo'   => (string) ($_GET['f_to'] ?? ''),
);
$stockMovements = $tab === 'history' ? warehouse_movements($historyFilters) : array();
