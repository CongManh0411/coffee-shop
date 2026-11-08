<!-- admin/pages/logs/index.php — trang "Nhật ký": PHP in dữ liệu từ MySQL, bộ lọc là form GET. -->
    <?php
    $tabTitles = array('logs' => 'Nhật ký thao tác', 'shifts' => 'Báo cáo kết ca', 'stock' => 'Nhật ký kho theo ly / phần');
    ?>
    <main class="main">
        <nav class="topnav">
            <a class="topnav-item<?= $tab === 'logs' ? ' active' : '' ?>" href="<?= e(logs_url('logs')) ?>">Nhật ký thao tác</a>
            <a class="topnav-item<?= $tab === 'shifts' ? ' active' : '' ?>" href="<?= e(logs_url('shifts')) ?>">Báo cáo kết ca</a>
            <a class="topnav-item<?= $tab === 'stock' ? ' active' : '' ?>" href="<?= e(logs_url('stock')) ?>">Nhật ký kho</a>
        </nav>
        <div class="main-content cs-wrap">
            <div class="menu-page-header">
                <h2><?= e($tabTitles[$tab]) ?></h2>
                <p>
                    <?php if ($tab === 'logs'): $flagged = count(array_filter($logRows, function ($l) { return !empty($l['flag']); })); ?>
                        <?= count($logRows) ?> thao tác<?= $flagged ? ' • ' . $flagged . ' mục cần chú ý' : '' ?>
                    <?php elseif ($tab === 'shifts'): ?>
                        <?= count($cashHistory) ?> ca đã kết<?= $cashCurrent ? ' • đang mở: ' . e($cashCurrent['cashierName']) . ' (từ ' . e(date('H:i', strtotime($cashCurrent['openedAt']))) . ')' : '' ?>
                    <?php else: ?>
                        <?= count($stockRows) ?> biến động kho
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($tab === 'logs'): ?>
            <form method="get" action="index.php" class="cs-form" style="margin-bottom:14px">
                <input type="hidden" name="page" value="logs">
                <input type="hidden" name="tab" value="logs">
                <label>Loại thao tác
                    <select name="type" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <?php foreach ($logTypes as $k => $v): ?><option value="<?= e($k) ?>"<?= $fType === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Thời gian
                    <select name="range" onchange="this.form.submit()">
                        <?php foreach (array('1' => 'Hôm nay', '7' => '7 ngày', '30' => '30 ngày', '0' => 'Tất cả') as $k => $v): ?><option value="<?= e($k) ?>"<?= $fRange === (string) $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Người thực hiện
                    <select name="who" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <?php foreach ($logWho as $n): ?><option value="<?= e($n) ?>"<?= $fWho === $n ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label style="flex:0 0 auto;flex-direction:row;align-items:center;gap:6px"><input type="checkbox" name="flag" value="1" <?= $fFlag ? 'checked' : '' ?> onchange="this.form.submit()"> Chỉ mục cần chú ý</label>
                <noscript><button type="submit" class="btn-outline">Lọc</button></noscript>
            </form>
            <?php endif; ?>

            <?php if ($reportShift): ?>
            <div class="cs-report" style="margin-bottom:16px">
                <?= shift_report_html($reportShift) ?>
                <p style="margin-top:12px"><a class="btn-outline" href="<?= e(logs_url('shifts')) ?>">Đóng</a> <a class="btn-outline" href="#" onclick="window.print();return false;">In</a></p>
            </div>
            <?php endif; ?>

            <div class="data-table-wrapper">
                <table class="data-table">
                    <?php if ($tab === 'logs'): ?>
                    <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Thao tác</th><th>Chi tiết</th><th>Số tiền</th></tr></thead>
                    <tbody>
                        <?php foreach ($logRows as $l): ?>
                            <tr>
                                <td><?= e(logs_dt($l['time'])) ?></td>
                                <td><b><?= e($l['actorName']) ?></b><br><small><?= e($roleNames[$l['role']] ?? $l['role']) ?></small></td>
                                <td><?= e($logTypes[$l['type']] ?? $l['type']) ?><?= !empty($l['flag']) ? '<span class="cs-flag">Chú ý</span>' : '' ?></td>
                                <td style="max-width:420px"><?= e($l['text']) ?></td>
                                <td><?= !empty($l['amount']) ? e(money($l['amount'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$logRows): ?><tr><td colspan="5"><div class="empty-table-state">Không có thao tác nào trong bộ lọc này.</div></td></tr><?php endif; ?>
                    </tbody>
                    <?php elseif ($tab === 'shifts'): ?>
                    <thead><tr><th>Thu ngân</th><th>Thời gian ca</th><th>Số đơn</th><th>Doanh thu</th><th>Tiền theo sổ</th><th>Tiền đếm</th><th>Chênh lệch</th><th>Lý do</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($cashHistory as $s): ?>
                            <tr>
                                <td><b><?= e($s['cashierName']) ?></b><?= $s['closedBy'] && $s['closedBy'] !== $s['cashierName'] ? '<br><small>kết bởi ' . e($s['closedBy']) . '</small>' : '' ?></td>
                                <td><?= e(logs_dt($s['openedAt'])) ?> → <?= e(date('H:i', strtotime($s['closedAt']))) ?></td>
                                <td><?= (int) $s['summary']['orders'] ?></td>
                                <td><?= e(money($s['summary']['revenue'])) ?></td>
                                <td><?= e(money($s['expectedCash'])) ?></td>
                                <td><?= e(money($s['countedCash'])) ?></td>
                                <td style="color:<?= $s['diff'] ? '#b5573c' : '#2c7046' ?>;font-weight:600"><?= $s['diff'] > 0 ? '+' : '' ?><?= e(money($s['diff'])) ?></td>
                                <td style="max-width:240px"><?= e($s['reason'] ?: '—') ?></td>
                                <td><a class="btn-outline" href="<?= e(logs_url('shifts', array('report' => $s['id']))) ?>">Xem</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$cashHistory): ?><tr><td colspan="9"><div class="empty-table-state">Chưa có ca nào được kết.</div></td></tr><?php endif; ?>
                    </tbody>
                    <?php else: ?>
                    <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Món</th><th>Thao tác</th><th>Biến động</th><th>Hóa đơn / Ghi chú</th></tr></thead>
                    <tbody>
                        <?php foreach ($stockRows as $l): ?>
                            <tr>
                                <td><?= e(date('d/m/Y H:i', strtotime($l['ts']))) ?></td>
                                <td><?= e($l['actor']) ?></td>
                                <td><?= e($l['name']) ?></td>
                                <td><?= e($l['typeName']) ?></td>
                                <td><?= $l['delta'] > 0 ? '+' : '' ?><?= (int) $l['delta'] ?> <?= e($l['unit']) ?></td>
                                <td><?= e($l['ref'] ?: $l['note']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$stockRows): ?><tr><td colspan="6"><div class="empty-table-state">Chưa có biến động kho.</div></td></tr><?php endif; ?>
                    </tbody>
                    <?php endif; ?>
                </table>
            </div>
            <p class="cs-note">
                <?php if ($tab === 'logs'): ?>Thu ngân tự làm các thao tác này (không cần duyệt) — nhật ký là nơi Quản lý kiểm tra lại.
                <?php elseif ($tab === 'stock'): ?>Hủy hay hoàn tiền không tự nhập lại món đã pha; quản lý điều chỉnh ở trang Kho khi cần.<?php endif; ?>
            </p>
        </div>
    </main>
