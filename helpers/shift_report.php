<?php
// helpers/shift_report.php — in "Báo cáo kết ca" bằng PHP (dùng cho trang Ca & Quỹ của thu ngân và trang Nhật ký của quản lý).
// $sh là mảng ca do cashshift_shape() trả về. Hàm trả về chuỗi HTML đã escape.
function shift_report_html($sh)
{
    $s = $sh['summary'];
    $closed = !empty($sh['closedAt']);
    $diff = (int) ($sh['diff'] ?? 0);
    $row = function ($label, $value, $bold = false) {
        return '<tr><td>' . $label . '</td><td style="text-align:right">' . ($bold ? '<b>' . $value . '</b>' : $value) . '</td></tr>';
    };
    $h = '<h2 style="text-align:center;margin:0">BÁO CÁO KẾT CA</h2>';
    $h .= '<p style="text-align:center;font-size:12px;margin:4px 0 10px">' . e($sh['cashierName']) . ' • ' . e(date('d/m/Y H:i', strtotime($sh['openedAt'])))
        . ($closed ? ' → ' . e(date('H:i', strtotime($sh['closedAt']))) : '') . '</p>';
    $h .= '<table style="width:100%;font-size:13px;border-collapse:collapse">'
        . $row('Số đơn', (int) $s['orders'])
        . $row('Doanh thu', e(money($s['revenue'])), true)
        . $row('&nbsp;&nbsp;Tiền mặt', e(money($s['by']['cash'])))
        . $row('&nbsp;&nbsp;Chuyển khoản / QR', e(money($s['by']['qr'])))
        . $row('&nbsp;&nbsp;Thẻ', e(money($s['by']['card'])))
        . $row('&nbsp;&nbsp;Ví điện tử', e(money($s['by']['ewallet'])))
        . $row('Tổng giảm giá', e(money($s['discount'])))
        . $row('Hóa đơn hủy / hoàn', (int) $s['cancelledCount'] . ' (' . e(money($s['cancelledTotal'])) . ')')
        . '</table><hr>';
    $h .= '<table style="width:100%;font-size:13px;border-collapse:collapse">'
        . $row('Tiền đầu ca', e(money($sh['openingCash'])))
        . $row('+ Bán tiền mặt', e(money($s['cashSales'])))
        . $row('+ Thu quỹ', e(money($s['cashIn'])))
        . $row('− Chi quỹ', e(money($s['cashOut'])))
        . $row('<b>Tiền mặt theo hệ thống</b>', e(money($sh['expectedCash'] ?? $s['expectedCash'])), true);
    if ($closed) {
        $h .= $row('Tiền đếm thực tế', e(money($sh['countedCash'])))
            . $row('<b>Chênh lệch</b>', '<span style="color:' . ($diff === 0 ? '#2c7046' : '#c0392b') . '">' . ($diff > 0 ? '+' : '') . e(money($diff)) . '</span>', true);
    }
    $h .= '</table>';
    if (!empty($sh['reason'])) $h .= '<p style="font-size:13px"><b>Lý do chênh lệch:</b> ' . e($sh['reason']) . '</p>';
    if (!empty($sh['movements'])) {
        $h .= '<hr><p style="font-size:13px;margin:4px 0"><b>Thu / chi trong ca</b></p><table style="width:100%;font-size:12px;border-collapse:collapse">';
        foreach ($sh['movements'] as $m) {
            $h .= '<tr><td>' . e(date('H:i', strtotime($m['time']))) . '</td><td>' . ($m['kind'] === 'in' ? 'Thu' : 'Chi') . ' — ' . e($m['category'])
                . (!empty($m['note']) ? ': ' . e($m['note']) : '') . '</td><td style="text-align:right">' . ($m['kind'] === 'in' ? '+' : '-') . e(money($m['amount'])) . '</td></tr>';
        }
        $h .= '</table>';
    }
    return $h;
}
