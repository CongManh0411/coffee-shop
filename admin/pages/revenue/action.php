<?php
// admin/pages/revenue/action.php — trang "Doanh thu" (Admin)
// Chạy TRƯỚC khi Router in header: đọc MySQL bằng hàm trong functions.php, trang index.php chỉ in bằng foreach.
require_once __DIR__ . '/../../../functions.php';
require_once __DIR__ . '/../../../helpers/charts.php';

$title = 'Doanh thu';
$css = array('revenue.css', 'dashboard.css');
$scripts = array(); // không còn revenue.js / dashboard.js: số liệu và biểu đồ do PHP in ra

// Kỳ xem: ?period=today|week|month|quarter|year|custom (&from=&to= khi chọn "Tùy chọn")
$period = isset($_GET['period']) && is_string($_GET['period']) ? $_GET['period'] : 'today';
$from = isset($_GET['from']) && is_string($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) && is_string($_GET['to']) ? $_GET['to'] : '';
$range = revenue_period_range($period, $from, $to);
$period = $range['period'];

// Giá vốn ước tính (% doanh thu) để tính lợi nhuận gộp: ?cost=37
$costPct = isset($_GET['cost']) && is_numeric($_GET['cost']) ? (int) $_GET['cost'] : 37;
$costPct = max(0, min(100, $costPct));

$valid = admin_revenue_totals('paid', $range['start'], $range['end']);
$cancelled = admin_revenue_totals('cancelled', $range['start'], $range['end']);
$avgInvoice = $valid['count'] > 0 ? (int) round($valid['total'] / $valid['count']) : 0;
$grossProfit = (int) round($valid['total'] * (1 - $costPct / 100));

// Bảng theo ngày + tổng theo phương thức thanh toán (cộng từ các dòng của bảng)
$dailyRows = admin_revenue_daily($range['start'], $range['end']);
$methodTotals = array('cash' => 0, 'transfer' => 0, 'card' => 0);
foreach ($dailyRows as $row) {
    $methodTotals['cash'] += (int) $row['cash'];
    $methodTotals['transfer'] += (int) $row['transfer'];
    $methodTotals['card'] += (int) $row['card'];
}
$methodSum = array_sum($methodTotals);
$methodPct = array();
foreach ($methodTotals as $key => $amount) {
    $methodPct[$key] = $methodSum > 0 ? (int) round($amount / $methodSum * 100) : 0;
}

// Báo cáo chuyên sâu: doanh thu theo giờ (06h–22h), top món, kênh bán, so sánh 7 ngày
$hours = array_fill(0, 17, 0);
foreach (admin_revenue_by_hour($range['start'], $range['end']) as $row) {
    $h = (int) $row['hour_of_day'];
    if ($h >= 6 && $h <= 22) $hours[$h - 6] += (int) $row['total'];
}
$hourLabels = range(6, 22);
$peakIndex = array_search(max($hours), $hours, true);
$topItems = admin_revenue_top_items($range['start'], $range['end'], 5);
$channels = array();
foreach (admin_revenue_by_channel($range['start'], $range['end']) as $row) {
    $name = order_channel_label($row['order_type']);
    $channels[$name] = (isset($channels[$name]) ? $channels[$name] : 0) + (int) $row['total'];
}
$thisWeek = array_fill(0, 7, 0);
$prevWeek = array_fill(0, 7, 0);
$todayTs = strtotime(date('Y-m-d'));
foreach (admin_revenue_per_day_since(date('Y-m-d', $todayTs - 13 * 86400)) as $row) {
    $diff = (int) round(($todayTs - strtotime($row['day'])) / 86400);
    if ($diff >= 0 && $diff < 7) $thisWeek[6 - $diff] += (int) $row['total'];
    elseif ($diff >= 7 && $diff < 14) $prevWeek[13 - $diff] += (int) $row['total'];
}

// Xuất Excel (CSV): ?export=csv — thay cho nút xuất bằng JavaScript
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $invoices = admin_invoices('active', '', $range['start'], $range['end'], 1000);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bao-cao-doanh-thu.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('Mã HĐ', 'Thời gian', 'Kênh', 'Thanh toán', 'Giảm giá', 'Tổng tiền'));
    foreach ($invoices as $inv) {
        fputcsv($out, array($inv['order_code'], date('d/m/Y H:i', strtotime($inv['paid_at'])), order_channel_label($inv['order_type']),
            payment_method_label($inv['payment_method']), (int) $inv['discount'], (int) $inv['total']));
    }
    fputcsv($out, array());
    fputcsv($out, array('Doanh thu', $valid['total']));
    fputcsv($out, array('Lợi nhuận gộp ước tính', $grossProfit));
    fputcsv($out, array('Số hóa đơn', $valid['count']));
    fputcsv($out, array());
    fputcsv($out, array('Món', 'Số lượng'));
    foreach ($topItems as $item) fputcsv($out, array($item['item_name'], (int) $item['qty']));
    fclose($out);
    exit;
}
