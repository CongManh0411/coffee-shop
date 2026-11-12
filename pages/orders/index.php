<!-- pages/orders/index.php — "Đơn hàng": PHP in bảng từ MySQL; tab là link, hoàn tiền là form POST. -->
<?php
$tabUrl = function ($t) { return 'index.php?page=orders' . ($t === 'processing' ? '' : '&tab=' . $t); };
$fmtT = function ($iso) { return $iso ? date('H:i', strtotime($iso)) : '—'; };
?>
    <main class="main">
        <nav class="topnav">
            <a class="topnav-item<?= $ordTab === 'processing' ? ' active' : '' ?>" href="<?= e($tabUrl('processing')) ?>">Đang xử lý</a>
            <a class="topnav-item<?= $ordTab === 'done' ? ' active' : '' ?>" href="<?= e($tabUrl('done')) ?>">Đã hoàn thành</a>
            <a class="topnav-item<?= $ordTab === 'cancelled' ? ' active' : '' ?>" href="<?= e($tabUrl('cancelled')) ?>">Đã hủy / hoàn tiền</a>
        </nav>
        <div class="main-content cs-wrap">
            <?php if ($flash['error']): ?><div class="menu-empty-state" role="alert"><?= e($flash['error']) ?></div><?php endif; ?>
            <?php if ($flash['ok']): ?><div class="pos-ok" role="status"><?= e($flash['ok']) ?></div><?php endif; ?>
            <div class="menu-page-header">
                <h2><?= $ordTab === 'processing' ? 'Đơn đang xử lý' : ($ordTab === 'done' ? 'Đơn đã hoàn thành hôm nay' : 'Đơn đã hủy / hoàn tiền hôm nay') ?></h2>
                <p><?php if ($ordTab === 'processing'): ?><?= count($ordProcessing) ?> đơn đang có món
                   <?php elseif ($ordTab === 'done'): ?><?= $ordTotalCount ?> hóa đơn • <?= e(money($ordTotalMoney)) ?>
                   <?php else: ?><?= $ordTotalCount ?> hóa đơn bị hủy / hoàn<?php endif; ?></p>
            </div>
            <div data-ajax-region="orders-list">
            <div class="data-table-wrapper">
                <table class="data-table">
                <?php if ($ordTab === 'processing'): ?>
                    <thead><tr><th>Bàn / Đơn</th><th>Loại</th><th>Vào lúc</th><th>Món</th><th>Trạng thái</th><th>Tạm tính</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($ordProcessing as $o):
                        $conf = array_filter($o['items'], function ($i) { return $i['status'] !== 'draft'; });
                        if (!$conf) { $ov = 'Chưa gửi bếp'; $ovKey = 'waiting'; }
                        elseif ($o['not_ready'] === 0) { $ov = 'Đã xong — chờ thanh toán'; $ovKey = 'done'; }
                        elseif (array_filter($conf, function ($i) { return $i['status'] === 'making'; })) { $ov = 'Đang làm'; $ovKey = 'making'; }
                        else { $ov = 'Chờ pha chế'; $ovKey = 'waiting'; } ?>
                        <tr>
                            <td><b><?= e($o['tableName']) ?></b><?= $o['customer'] ? '<br><small>' . e($o['customer']) . ($o['address'] ? ' — ' . e($o['address']) : '') . '</small>' : '' ?></td>
                            <td><span class="cs-type"><?= e(POS_ORDER_TYPES[$o['orderType']] ?? '') ?></span></td>
                            <td><?= e($fmtT($o['created_at'])) ?></td>
                            <td style="font-size:12.5px;line-height:1.7"><?php foreach ($o['items'] as $i): ?><?= e($i['name']) ?> ×<?= (int) $i['quantity'] ?><?= $i['note'] ? ' <small>(' . e($i['note']) . ')</small>' : '' ?> <span class="cs-status <?= $i['status'] === 'draft' ? 'waiting' : e($i['status']) ?>"><?= e(POS_LINE_TEXT[$i['status']] ?? '') ?></span><br><?php endforeach; ?></td>
                            <td><span class="cs-status <?= e($ovKey) ?>"><?= e($ov) ?></span></td>
                            <td><?= e(money($o['all_total'])) ?></td>
                            <td><div class="cs-actions"><?= $conf ? '<a class="btn-outline" href="index.php?page=tables&order=' . (int) $o['id'] . '&pay=1">Thanh toán</a>' : '<small>Mở ở "Bán hàng" để gửi bếp</small>' ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ordProcessing): ?><tr><td colspan="7"><div class="empty-table-state">Chưa có đơn nào đang xử lý.</div></td></tr><?php endif; ?>
                    </tbody>
                <?php elseif ($ordTab === 'done'): ?>
                    <thead><tr><th>Mã HD</th><th>Đơn</th><th>Loại</th><th>Giờ</th><th>Thanh toán</th><th>Tổng tiền</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($ordInvoices as $inv): ?>
                        <tr>
                            <td><b><?= e($inv['id']) ?></b></td><td><?= e($inv['tableName']) ?></td>
                            <td><span class="cs-type"><?= e(POS_ORDER_TYPES[$inv['orderType']] ?? '') ?></span></td>
                            <td><?= e($fmtT($inv['createdAt'])) ?></td>
                            <td><?= e(POS_METHODS[$inv['payment_method']] ?? 'Chuyển khoản') ?><?= $inv['discount'] ? '<br><small>Giảm ' . e(money($inv['discount'])) . ($inv['voucher'] ? ' (' . e($inv['voucher']) . ')' : '') . '</small>' : '' ?></td>
                            <td><?= e(money($inv['total'])) ?></td>
                            <td><div class="cs-actions">
                                <a class="btn-outline" href="index.php?page=tables&receipt=<?= (int) $inv['orderId'] ?>">In</a>
                                <form method="post" action="<?= e('index.php?' . http_build_query(array_filter(array('page' => 'orders', 'tab' => 'done', 'p' => $ordPager['page'] > 1 ? $ordPager['page'] : null)))) ?>" class="pos-inline" onsubmit="return confirm('Hoàn tiền hóa đơn này cho khách?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="pos" value="refund"><input type="hidden" name="order_id" value="<?= (int) $inv['orderId'] ?>">
                                    <input name="reason" class="pos-note" maxlength="200" required placeholder="Lý do hoàn tiền (bắt buộc)">
                                    <button type="submit" class="btn-danger">Hoàn tiền</button></form>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ordInvoices): ?><tr><td colspan="7"><div class="empty-table-state">Hôm nay chưa có hóa đơn nào.</div></td></tr><?php endif; ?>
                    </tbody>
                <?php else: ?>
                    <thead><tr><th>Mã HD</th><th>Đơn</th><th>Tổng tiền</th><th>Hủy lúc</th><th>Người thực hiện</th><th>Lý do</th></tr></thead>
                    <tbody>
                    <?php foreach ($ordInvoices as $inv): ?>
                        <tr><td><b><?= e($inv['id']) ?></b></td><td><?= e($inv['tableName']) ?></td><td><?= e(money($inv['total'])) ?></td>
                            <td><?= e($fmtT($inv['cancelledAt'])) ?></td><td><?= e($inv['cancelledBy'] ?: 'Quản lý') ?></td><td><?= e($inv['cancelReason'] ?? '') ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$ordInvoices): ?><tr><td colspan="6"><div class="empty-table-state">Hôm nay chưa có hóa đơn nào bị hủy.</div></td></tr><?php endif; ?>
                    </tbody>
                <?php endif; ?>
                </table>
            </div>
            <?= $ordTab === 'processing' ? '' : render_pagination($ordPager['page'], $ordPager['pages'], array('page' => 'orders', 'tab' => $ordTab)) ?>
            </div>
            <p class="cs-note"><?= $ordTab === 'processing' ? 'Trạng thái pha chế do Bếp cập nhật. Trang tự tải lại mỗi 15 giây. Thêm / sửa món: vào "Bán hàng".' : ($ordTab === 'done' ? 'Hoàn tiền bắt buộc ghi lý do và được ghi Nhật ký cho Quản lý.' : '') ?></p>
        </div>
    </main>
