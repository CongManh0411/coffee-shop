<!-- pages/cashshift/index.php — trang "Ca & Quỹ": PHP in số liệu từ MySQL, thao tác bằng form POST. -->
    <?php
    $card = function ($label, $val, $sub = '', $cls = '') {
        return '<div class="cs-card ' . e($cls) . '"><span>' . $label . '</span><b>' . $val . '</b>' . ($sub !== '' ? '<small>' . $sub . '</small>' : '') . '</div>';
    };
    $formUrl = 'index.php?page=cashshift';
    ?>
    <main class="main">
        <div class="main-content cs-wrap">
            <div class="menu-page-header">
                <h2>Ca & Quỹ tiền mặt</h2>
                <p><?= $csCurrent ? 'Đang mở ca của ' . e($csCurrent['cashierName']) . ' từ ' . e(date('H:i', strtotime($csCurrent['openedAt']))) : 'Chưa mở ca' ?></p>
            </div>
            <?php if ($csSavedMessage): ?><p class="cs-msg ok" role="status"><?= e($csSavedMessage) ?></p><?php endif; ?>

            <?php if ($csReport): ?>
                <div class="cs-panel"><div class="cs-report"><?= shift_report_html($csReport) ?></div>
                    <p style="margin-top:12px"><a class="btn-outline" href="<?= e($formUrl) ?>">Đóng</a> <a class="btn-outline" href="#" onclick="window.print();return false;"><i class="fa-solid fa-print"></i> In báo cáo</a></p>
                </div>
            <?php endif; ?>

            <?php if (!$csCurrent): ?>
                <div class="cs-panel"><h3>Mở ca</h3>
                    <p class="cs-note" style="margin:0 0 12px">Chưa có ca nào đang mở. Đếm tiền trong két rồi nhập số tiền đầu ca để bắt đầu bán hàng.</p>
                    <form method="post" action="<?= e($formUrl) ?>" class="cs-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="cs_action" value="open">
                        <label>Tiền đầu ca (đ)<input type="number" name="openingCash" min="0" inputmode="numeric" placeholder="vd: 500000" required></label>
                        <button class="cs-btn" type="submit">Mở ca</button>
                    </form>
                    <?php if ($csError && $csErrorBox === 'open'): ?><p class="cs-msg" role="alert"><?= e($csError) ?></p><?php endif; ?>
                </div>
            <?php else: ?>
                <?php $all = $csCurrent['summary']; $mine = $csCurrent['mine'] ?? $all; ?>
                <div class="cs-grid">
                    <?= $card('Ca đang mở', e($csCurrent['cashierName']), 'Từ ' . e(date('H:i', strtotime($csCurrent['openedAt']))) . ' • đầu ca ' . e(money($csCurrent['openingCash']))) ?>
                    <?= $card('Doanh thu của bạn', e(money($mine['revenue'])), (int) $mine['orders'] . ' đơn • giảm giá ' . e(money($mine['discount'])), 'ok') ?>
                    <?= $card('Doanh thu cả ca', e(money($all['revenue'])), (int) $all['orders'] . ' đơn (tại quán ' . (int) ($all['byType']['dinein'] ?? 0) . ', mang đi ' . (int) ($all['byType']['takeaway'] ?? 0) . ', giao ' . (int) ($all['byType']['delivery'] ?? 0) . ')') ?>
                    <?= $card('Tiền mặt trong két (theo sổ)', e(money($all['expectedCash'])), 'Đầu ca ' . e(money($csCurrent['openingCash'])) . ' + bán ' . e(money($all['cashSales'])) . ' + thu ' . e(money($all['cashIn'])) . ' − chi ' . e(money($all['cashOut'])), 'ok') ?>
                </div>
                <div class="cs-grid">
                    <?= $card('Tiền mặt', e(money($all['by']['cash']))) ?><?= $card('Chuyển khoản / QR', e(money($all['by']['qr']))) ?><?= $card('Thẻ', e(money($all['by']['card']))) ?><?= $card('Ví điện tử', e(money($all['by']['ewallet']))) ?>
                    <?= $card('Hóa đơn hủy / hoàn', (int) $all['cancelledCount'], e(money($all['cancelledTotal'])), $all['cancelledCount'] ? 'warn' : '') ?>
                </div>

                <div class="cs-panel"><h3>Thu / chi trong ca</h3>
                    <form method="post" action="<?= e($formUrl) ?>" class="cs-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="cs_action" value="movement">
                        <label>Loại / danh mục
                            <select name="mv">
                                <optgroup label="Chi (lấy tiền từ két)"><?php foreach (array('Mua đá', 'Đồ lặt vặt', 'Chi khác') as $c): ?><option value="out|<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?></optgroup>
                                <optgroup label="Thu (bỏ tiền vào két)"><?php foreach (array('Tiền tip', 'Thu khác') as $c): ?><option value="in|<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?></optgroup>
                            </select>
                        </label>
                        <label>Số tiền (đ)<input type="number" name="amount" min="1" inputmode="numeric" required></label>
                        <label style="flex:2 1 220px">Ghi chú (bắt buộc)<input type="text" name="note" maxlength="80" placeholder="vd: mua 3 bao đá" required></label>
                        <button class="cs-btn" type="submit">Ghi sổ</button>
                    </form>
                    <?php if ($csError && $csErrorBox === 'movement'): ?><p class="cs-msg" role="alert"><?= e($csError) ?></p><?php endif; ?>
                    <?php $movs = array_reverse($csCurrent['movements'] ?? array()); ?>
                    <?php if ($movs): ?>
                        <div class="data-table-wrapper" style="margin-top:12px"><table class="data-table"><thead><tr><th>Giờ</th><th>Loại</th><th>Nội dung</th><th>Số tiền</th><th>Người ghi</th></tr></thead><tbody>
                            <?php foreach ($movs as $m): ?>
                                <tr><td><?= e(date('H:i', strtotime($m['time']))) ?></td><td><?= $m['kind'] === 'in' ? 'Thu' : 'Chi' ?> — <?= e($m['category']) ?></td><td><?= e($m['note']) ?></td><td style="color:<?= $m['kind'] === 'in' ? '#2c7046' : '#b5573c' ?>;font-weight:600"><?= $m['kind'] === 'in' ? '+' : '−' ?><?= e(money($m['amount'])) ?></td><td><?= e($m['by']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?><p class="cs-note">Chưa có khoản thu / chi nào trong ca.</p><?php endif; ?>
                    <p class="cs-note">Thu/chi lớn (từ 200.000đ) được đánh dấu trong Nhật ký của Quản lý.</p>
                </div>

                <div class="cs-panel"><h3>Kết ca</h3>
                    <p class="cs-note" style="margin:0 0 10px">Đếm tiền mặt thực tế trong két rồi nhập vào. Hệ thống đang ghi: <b><?= e(money($all['expectedCash'])) ?></b>.</p>
                    <form method="post" action="<?= e($formUrl) ?>" onsubmit="return confirm('Kết ca? Sau khi kết ca sẽ không thu tiền được cho đến khi mở ca mới.');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="cs_action" value="close">
                        <div class="cs-form">
                            <label>Tiền đếm thực tế (đ)<input type="number" name="countedCash" min="0" inputmode="numeric" required></label>
                            <label style="flex:1 1 280px">Lý do chênh lệch (bắt buộc nếu tiền đếm lệch)<textarea name="reason" rows="2" maxlength="200" placeholder="vd: trả nhầm tiền thối cho khách bàn 3"></textarea></label>
                        </div>
                        <div style="margin-top:12px"><button class="cs-btn danger" type="submit">Kết ca & xem báo cáo</button></div>
                    </form>
                    <?php if ($csError && $csErrorBox === 'close'): ?><p class="cs-msg" role="alert"><?= e($csError) ?></p><?php endif; ?>
                    <p class="cs-note">Nếu tiền lệch vẫn kết ca được, nhưng phải ghi lý do; chênh lệch sẽ hiện trong Nhật ký của Quản lý.</p>
                </div>
            <?php endif; ?>

            <?php if ($csError && $csErrorBox === ''): ?><p class="cs-msg" role="alert"><?= e($csError) ?></p><?php endif; ?>

            <div class="cs-panel"><h3>Báo cáo các ca gần đây</h3>
                <?php if ($csMine): ?>
                    <div class="data-table-wrapper"><table class="data-table"><thead><tr><th>Ca</th><th>Thời gian</th><th>Số đơn</th><th>Doanh thu</th><th>Chênh lệch</th><th></th></tr></thead><tbody>
                        <?php foreach ($csMine as $s): ?>
                            <tr><td><?= e($s['cashierName']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($s['openedAt']))) ?> → <?= e(date('H:i', strtotime($s['closedAt']))) ?></td><td><?= (int) $s['summary']['orders'] ?></td><td><?= e(money($s['summary']['revenue'])) ?></td>
                                <td style="color:<?= $s['diff'] ? '#b5573c' : '#2c7046' ?>;font-weight:600"><?= $s['diff'] > 0 ? '+' : '' ?><?= e(money($s['diff'])) ?></td>
                                <td><a class="btn-outline" href="<?= e($formUrl . '&report=' . rawurlencode($s['id'])) ?>">Xem / In</a></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                <?php else: ?><p class="cs-note">Chưa có ca nào đã kết.</p><?php endif; ?>
            </div>
        </div>
    </main>
