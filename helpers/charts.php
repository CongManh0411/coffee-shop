<?php
// helpers/charts.php — vẽ biểu đồ SVG ngay phía PHP cho trang Doanh thu (thay cho dashboard.js).
// Mọi hàm trả về chuỗi SVG/HTML; chữ lấy từ CSDL luôn đi qua e().

function chart_colors()
{
    return array('#6b4f3a', '#c4a27c', '#3f8a5a', '#4068b8', '#b0412e');
}

// Cột doanh thu theo giờ. $values/$labels cùng độ dài; $highlight = chỉ số cột cao nhất.
function chart_hour_bars($values, $labels, $highlight)
{
    $w = 520; $h = 190;
    $max = max(1, max($values));
    $bw = $w / count($values);
    $out = '<svg viewBox="0 0 ' . $w . ' ' . ($h + 24) . '">';
    foreach ($values as $k => $v) {
        $bar = $v / $max * $h;
        $fill = ($k === $highlight && $v > 0) ? '#b0412e' : '#c4a27c';
        $out .= '<rect x="' . round($k * $bw + 3, 2) . '" y="' . round($h - $bar, 2) . '" width="' . round($bw - 6, 2)
              . '" height="' . round($bar, 2) . '" rx="3" fill="' . $fill . '"><title>' . e($labels[$k]) . 'h: ' . e(money($v)) . '</title></rect>';
        $out .= '<text x="' . round($k * $bw + $bw / 2, 2) . '" y="' . ($h + 14) . '" font-size="10" text-anchor="middle" fill="#6b4f3a">' . e($labels[$k]) . '</text>';
    }
    return $out . '</svg>';
}

// Thanh ngang: top món bán chạy. $rows = array(array('item_name' => ..., 'qty' => ...)).
function chart_top_items($rows)
{
    if (!$rows) return '<p class="ops-empty">Chưa có dữ liệu.</p>';
    $max = max(1, (int) $rows[0]['qty']);
    $out = '<svg viewBox="0 0 320 ' . (count($rows) * 30) . '">';
    foreach ($rows as $k => $row) {
        $qty = (int) $row['qty'];
        $len = $qty / $max * 170;
        $name = function_exists('mb_substr') ? mb_substr($row['item_name'], 0, 18, 'UTF-8') : substr($row['item_name'], 0, 18);
        $out .= '<text x="0" y="' . ($k * 30 + 18) . '" font-size="11" fill="#4a3626">' . e($name) . '</text>'
              . '<rect x="110" y="' . ($k * 30 + 6) . '" width="' . round($len, 2) . '" height="16" rx="3" fill="#6b4f3a"/>'
              . '<text x="' . round(114 + $len, 2) . '" y="' . ($k * 30 + 18) . '" font-size="11" fill="#4a3626">' . $qty . '</text>';
    }
    return $out . '</svg>';
}

// Đường: 7 ngày gần nhất ($current) so với 7 ngày trước đó ($previous).
function chart_week_compare($current, $previous)
{
    $w = 320; $h = 150;
    $max = max(1, max($current), max($previous));
    $pt = function ($v, $k) use ($w, $h, $max) {
        return array(round(20 + $k * ($w - 40) / 6, 2), round($h - $v / $max * ($h - 10), 2));
    };
    $path = function ($arr, $color, $dashed) use ($pt) {
        $points = array();
        $dots = '';
        foreach ($arr as $k => $v) {
            $p = $pt($v, $k);
            $points[] = $p[0] . ',' . $p[1];
            $dots .= '<circle cx="' . $p[0] . '" cy="' . $p[1] . '" r="3.5" fill="' . $color . '"><title>' . e(money($v)) . '</title></circle>';
        }
        return '<polyline fill="none" stroke="' . $color . '" stroke-width="2.5"' . ($dashed ? ' stroke-dasharray="6 4"' : '')
             . ' points="' . implode(' ', $points) . '"/>' . $dots;
    };
    $out = '<svg viewBox="0 0 ' . $w . ' ' . ($h + 20) . '">' . $path($previous, '#c4a27c', true) . $path($current, '#6b4f3a', false);
    for ($k = 0; $k < 7; $k++) {
        $out .= '<text x="' . round(20 + $k * ($w - 40) / 6, 2) . '" y="' . ($h + 16) . '" font-size="10" text-anchor="middle" fill="#6b4f3a">N' . ($k + 1) . '</text>';
    }
    return $out . '</svg><div class="dash-legend"><span><i style="background:#6b4f3a"></i>7 ngày gần nhất</span>'
         . '<span><i style="background:#c4a27c"></i>7 ngày trước đó</span></div>';
}

// Biểu đồ tròn: $map = array('Tại bàn' => 120000, ...).
function chart_pie($map)
{
    $total = array_sum($map);
    if ($total <= 0) return '<p class="ops-empty">Chưa có dữ liệu.</p>';
    $colors = chart_colors();
    $angle = -M_PI / 2;
    $out = '';
    $legend = '';
    $k = 0;
    foreach ($map as $name => $value) {
        $color = $colors[$k % count($colors)];
        $next = $angle + $value / $total * 2 * M_PI;
        if ($value == $total) {
            $out .= '<circle cx="80" cy="80" r="70" fill="' . $color . '"><title>' . e($name) . '</title></circle>';
        } else {
            $big = ($next - $angle) > M_PI ? 1 : 0;
            $out .= '<path d="M80,80 L' . round(80 + 70 * cos($angle), 2) . ',' . round(80 + 70 * sin($angle), 2)
                  . ' A70,70 0 ' . $big . ' 1 ' . round(80 + 70 * cos($next), 2) . ',' . round(80 + 70 * sin($next), 2)
                  . ' Z" fill="' . $color . '"><title>' . e($name) . ': ' . e(money($value)) . '</title></path>';
        }
        $legend .= '<span><i style="background:' . $color . '"></i>' . e($name) . ' ' . round($value / $total * 100) . '%</span>';
        $angle = $next;
        $k++;
    }
    return '<svg viewBox="0 0 160 160" style="max-width:170px;margin:auto">' . $out . '</svg><div class="dash-legend">' . $legend . '</div>';
}
