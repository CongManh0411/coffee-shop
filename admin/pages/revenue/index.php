<?php
// admin/pages/revenue/index.php — giao diện "Doanh thu" (header/sidebar/footer do router in).
// Dữ liệu đã được action.php lấy từ MySQL; ở đây chỉ in bằng foreach.
$periods = array('today' => 'Hôm nay', 'week' => 'Tuần này', 'month' => 'Tháng này', 'quarter' => 'Quý này', 'year' => 'Năm nay', 'custom' => 'Tùy chọn');
$methodNames = array('cash' => 'Tiền mặt', 'transfer' => 'Chuyển khoản', 'card' => 'Thẻ ATM');
$exportQuery = array('period' => $period, 'from' => $range['from'], 'to' => $range['to'], 'cost' => $costPct);
?>
    <main class="main">
        <nav class="topnav period-filter-bar" style="padding:16px 24px 0; border-bottom:none;">
<?php foreach ($periods as $key => $text): ?>
            <a class="topnav-item<?= $period === $key ? ' active' : '' ?>" href="<?= e(admin_url('revenue', $key === 'custom' ? array('period' => 'custom', 'from' => $range['from'], 'to' => $range['to']) : array('period' => $key))) ?>"><?= e($text) ?></a>
<?php endforeach; ?>
<?php if ($period === 'custom'): ?>
            <form method="get" action="index.php" class="date-range-inputs active">
                <input type="hidden" name="page" value="revenue">
                <input type="hidden" name="period" value="custom">
                <input type="date" name="from" value="<?= e($range['from']) ?>">
                <span>—</span>
                <input type="date" name="to" value="<?= e($range['to']) ?>">
                <button type="submit" class="btn-outline">Áp dụng</button>
            </form>
<?php endif; ?>
        </nav>

        <div class="main-content">
            <div class="menu-page-header">
                <h2>Doanh thu</h2>
                <p><?= e($range['label']) ?></p>
<?php if ($range['error'] !== ''): ?>
                <p class="cancel-reason-note" role="alert"><?= e($range['error']) ?></p>
<?php endif; ?>
            </div>

            <div class="stats-grid">
                <div class="stat-card highlight">
                    <div class="stat-card-label">Tổng doanh thu</div>
                    <div class="stat-card-value"><?= e(money($valid['total'])) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Số hóa đơn hợp lệ</div>
                    <div class="stat-card-value"><?= (int) $valid['count'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Doanh thu TB / hóa đơn</div>
                    <div class="stat-card-value"><?= e(money($avgInvoice)) ?></div>
                </div>
                <div class="stat-card cancelled-note">
                    <div class="stat-card-label">Hóa đơn đã hủy (không tính vào tổng)</div>
                    <div class="stat-card-value"><?= (int) $cancelled['count'] ?> hóa đơn — <?= e(money($cancelled['total'])) ?></div>
                </div>
            </div>

            <!-- Báo cáo chuyên sâu: biểu đồ SVG do PHP vẽ -->
            <div class="dash">
                <div class="dash-head">
                    <h3>Báo cáo chuyên sâu</h3>
                    <div>
                        <form method="get" action="index.php" style="display:inline">
                            <input type="hidden" name="page" value="revenue">
                            <input type="hidden" name="period" value="<?= e($period) ?>">
                            <input type="hidden" name="from" value="<?= e($range['from']) ?>">
                            <input type="hidden" name="to" value="<?= e($range['to']) ?>">
                            <label>Giá vốn ước tính <input type="number" name="cost" value="<?= (int) $costPct ?>" min="0" max="100">%</label>
                            <button type="submit">Áp dụng</button>
                        </form>
                        <a href="<?= e(admin_url('revenue', $exportQuery + array('export' => 'csv'))) ?>"><button type="button">Xuất Excel</button></a>
                        <button type="button" onclick="window.print()">Xuất PDF</button>
                    </div>
                </div>
                <div class="dash-grid">
                    <div class="dash-box">
                        <h4>Doanh thu theo giờ (giờ cao điểm: <?= $valid['total'] > 0 ? ($peakIndex + 6) . 'h' : '—' ?>)</h4>
                        <?= chart_hour_bars($hours, $hourLabels, $valid['total'] > 0 ? $peakIndex : -1) ?>
                    </div>
                    <div>
                        <div class="dash-box dash-kpi"><small>Doanh thu</small><b><?= e(money($valid['total'])) ?></b></div>
                        <div class="dash-box dash-kpi g"><small>Lợi nhuận gộp (ước tính)</small><b><?= e(money($grossProfit)) ?> (<?= 100 - $costPct ?>%)</b></div>
                        <div class="dash-box dash-kpi b"><small>Số hóa đơn</small><b><?= (int) $valid['count'] ?></b></div>
                    </div>
                </div>
                <div class="dash-grid3">
                    <div class="dash-box"><h4>Top món bán chạy</h4><?= chart_top_items($topItems) ?></div>
                    <div class="dash-box"><h4>So sánh kỳ trước (7 ngày)</h4><?= chart_week_compare($thisWeek, $prevWeek) ?></div>
                    <div class="dash-box"><h4>Kênh bán hàng</h4><?= chart_pie($channels) ?></div>
                </div>
            </div>

            <div class="payment-breakdown">
                <h3>Doanh thu theo phương thức thanh toán</h3>
<?php foreach ($methodNames as $key => $text): ?>
                <div class="payment-bar-row">
                    <div class="payment-bar-head"><span><?= e($text) ?></span><span class="amount"><?= e(money($methodTotals[$key])) ?> (<?= (int) $methodPct[$key] ?>%)</span></div>
                    <div class="payment-bar-track"><div class="payment-bar-fill <?= e($key) ?>" style="width:<?= (int) $methodPct[$key] ?>%"></div></div>
                </div>
<?php endforeach; ?>
            </div>

            <div class="menu-page-header" style="margin-bottom:12px;">
                <h2 style="font-size:18px;">Doanh thu theo ngày</h2>
            </div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr><th>Ngày</th><th>Số hóa đơn</th><th>Tiền mặt</th><th>Chuyển khoản</th><th>Thẻ ATM</th><th>Tổng</th></tr>
                    </thead>
                    <tbody>
<?php if (!$dailyRows): ?>
                        <tr><td colspan="6"><div class="empty-table-state">Chưa có dữ liệu trong khoảng thời gian này.</div></td></tr>
<?php endif; ?>
<?php foreach ($dailyRows as $row): ?>
                        <tr>
                            <td><?= e(date('d/m/Y', strtotime($row['day']))) ?></td>
                            <td><?= (int) $row['invoice_count'] ?></td>
                            <td><?= e(money($row['cash'])) ?></td>
                            <td><?= e(money($row['transfer'])) ?></td>
                            <td><?= e(money($row['card'])) ?></td>
                            <td><b><?= e(money($row['total'])) ?></b></td>
                        </tr>
<?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="sync-hint">Số liệu đọc trực tiếp từ MySQL (bảng orders, order_items) theo các hóa đơn đã thanh toán. Hóa đơn đã hủy hoặc hoàn tiền không tính vào tổng.</p>
        </div>
    </main>
