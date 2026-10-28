<?php
// admin/pages/history/index.php — giao diện "Lịch sử hóa đơn" (header/sidebar/footer do router in).
$statusTabs = array('all' => 'Tất cả', 'active' => 'Đang hiệu lực', 'cancelled' => 'Đã hủy');
$baseQuery = array('status' => $statusFilter, 'q' => $keyword);
?>
    <main class="main">
        <nav class="topnav period-filter-bar" style="padding:16px 24px 0; border-bottom:none;">
<?php foreach ($statusTabs as $key => $text): ?>
            <a class="topnav-item<?= $statusFilter === $key ? ' active' : '' ?>" href="<?= e(admin_url('history', array('status' => $key, 'q' => $keyword))) ?>"><?= e($text) ?></a>
<?php endforeach; ?>
            <form method="get" action="index.php" class="date-range-inputs active" style="margin-left:auto;">
                <input type="hidden" name="page" value="history">
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                <input type="text" name="q" value="<?= e($keyword) ?>" placeholder="Tìm mã HD hoặc tên bàn..." style="padding:8px 12px; border:1px solid #e0d3bd; border-radius:8px; font-family:'Poppins',sans-serif; font-size:13px; min-width:220px;">
                <button type="submit" class="btn-outline">Tìm</button>
            </form>
        </nav>

        <div class="main-content">
            <div class="menu-page-header">
                <h2>Hóa đơn</h2>
                <p>Tổng cộng <?= count($invoices) ?> hóa đơn</p>
<?php if ($savedMessage !== ''): ?>
                <p class="badge badge-active" role="status"><?= e($savedMessage) ?></p>
<?php endif; ?>
<?php if ($modalNotFound): ?>
                <p class="cancel-reason-note" role="alert">Không tìm thấy hóa đơn (hoặc hóa đơn không còn hiệu lực).</p>
<?php endif; ?>
            </div>

            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã HD</th><th>Bàn</th><th>Ngày giờ</th><th>Chi tiết món</th>
                            <th>PTTT</th><th>Tổng tiền</th><th>Trạng thái</th><th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if (!$invoices): ?>
                        <tr><td colspan="8"><div class="empty-table-state">Không có hóa đơn phù hợp.</div></td></tr>
<?php endif; ?>
<?php foreach ($invoices as $inv):
    $method = payment_method_label($inv['payment_method']);
    $methodClass = $method === 'Thẻ ATM' ? 'badge-card' : ($method === 'Chuyển khoản' ? 'badge-transfer' : 'badge-cash');
    $reason = $inv['cancel_reason'] !== null ? $inv['cancel_reason'] : ($inv['refund_reason'] !== null ? 'Hoàn tiền: ' . $inv['refund_reason'] : '');
    $by = $inv['cancel_by'] ?: $inv['refund_by'];
?>
                        <tr>
                            <td><b><?= e($inv['order_code']) ?></b></td>
                            <td><?= e($inv['table_name']) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($inv['paid_at']))) ?></td>
                            <td style="max-width:220px"><a href="<?= e(admin_url('history', $baseQuery + array('view' => $inv['order_id']))) ?>" style="color:#7a5c3e;text-decoration:underline"><?= e($inv['items_summary']) ?></a></td>
                            <td><span class="badge <?= e($methodClass) ?>"><?= e($method) ?></span></td>
                            <td><b><?= e(money($inv['total'])) ?></b></td>
                            <td>
<?php if ($inv['status'] === 'paid'): ?>
                                <span class="badge badge-active">Hiệu lực</span>
<?php else: ?>
                                <span class="badge badge-cancelled">Đã hủy</span>
                                <div class="cancel-reason-note">Lý do: <?= e($reason) ?><?= $by ? ' (' . e($by) . ')' : '' ?></div>
<?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a class="btn-outline" style="padding:6px 10px;font-size:12px;text-decoration:none" href="<?= e(admin_url('history', $baseQuery + array('view' => $inv['order_id']))) ?>">Xem</a>
<?php if ($inv['status'] === 'paid'): ?>
                                    <a class="btn-danger" style="padding:6px 10px;font-size:12px;text-decoration:none" href="<?= e(admin_url('history', $baseQuery + array('cancel' => $inv['order_id']))) ?>">Hủy / Hoàn</a>
<?php endif; ?>
                                </div>
                            </td>
                        </tr>
<?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="sync-hint">Hóa đơn được tạo khi Thu ngân xác nhận thanh toán. Hủy hoặc hoàn tiền chỉ đánh dấu "Đã hủy" kèm lý do (ghi vào order_cancellations / order_refunds), không xóa dữ liệu.</p>
        </div>
    </main>

<?php if ($cancelInvoice): ?>
    <!-- Hộp thoại: Hủy hóa đơn / Hoàn tiền (form POST) -->
    <div class="modal-overlay active">
        <form class="modal-box" method="post" action="<?= e(admin_url('history', $baseQuery)) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="order_id" value="<?= (int) $cancelInvoice['order_id'] ?>">
            <h3 class="modal-title">Hủy / hoàn tiền hóa đơn</h3>
            <p class="modal-sub">Hóa đơn <b><?= e($cancelInvoice['order_code']) ?></b> — <b><?= e(money($cancelInvoice['total'])) ?></b>. Chọn thao tác và nhập lý do:</p>
<?php if ($formError !== ''): ?>
            <p class="cancel-reason-note" role="alert"><?= e($formError) ?></p>
<?php endif; ?>
            <p>
                <label><input type="radio" name="mode" value="cancel"<?= $formMode === 'cancel' ? ' checked' : '' ?>> Hủy hóa đơn</label>
                &nbsp;
                <label><input type="radio" name="mode" value="refund"<?= $formMode === 'refund' ? ' checked' : '' ?>> Hoàn tiền</label>
            </p>
            <textarea name="reason" maxlength="500" placeholder="Ví dụ: Khách yêu cầu hủy do gọi nhầm món, order trùng, sai giá..."><?= e($formReason) ?></textarea>
            <div class="modal-actions">
                <a class="btn-outline" style="flex:1;text-align:center;text-decoration:none;" href="<?= e(admin_url('history', $baseQuery)) ?>">Đóng</a>
                <button type="submit" name="invoice_action" value="1" class="btn-danger" style="flex:1; border:1px solid #c96a5b; background:#c96a5b; color:#fff;">Xác nhận</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($viewInvoice): ?>
    <!-- Hộp thoại: Chi tiết hóa đơn -->
    <div class="modal-overlay active">
        <div class="modal-box" style="max-width:460px;">
            <h3 class="modal-title">Hóa đơn <?= e($viewInvoice['order_code']) ?></h3>
            <p class="modal-sub"><?= e($viewInvoice['table_name']) ?> • <?= e(date('d/m/Y H:i', strtotime($viewInvoice['paid_at']))) ?> • <?= e(payment_method_label($viewInvoice['payment_method'])) ?></p>
            <div class="data-table-wrapper" style="margin-bottom:16px;">
                <table class="data-table">
                    <thead><tr><th>Món</th><th>SL</th><th>Thành tiền</th></tr></thead>
                    <tbody>
<?php foreach ($viewItems as $it): ?>
                        <tr><td><?= e($it['item_name']) ?></td><td><?= (int) $it['quantity'] ?></td><td><?= e(money($it['unit_price'] * $it['quantity'])) ?></td></tr>
<?php endforeach; ?>
                        <tr><td colspan="2" style="text-align:right;font-weight:700">Tổng cộng</td><td style="font-weight:700"><?= e(money($viewInvoice['total'])) ?></td></tr>
                    </tbody>
                </table>
            </div>
<?php if ($viewInvoice['status'] === 'cancelled'):
    $vReason = $viewInvoice['cancel_reason'] !== null ? $viewInvoice['cancel_reason'] : 'Hoàn tiền: ' . $viewInvoice['refund_reason'];
    $vAt = $viewInvoice['cancel_at'] ?: $viewInvoice['refund_at'];
    $vBy = $viewInvoice['cancel_by'] ?: $viewInvoice['refund_by'];
?>
            <div class="cancel-reason-note" style="margin-bottom:16px">Đã hủy<?= $vAt ? ' lúc ' . e(date('d/m/Y H:i', strtotime($vAt))) : '' ?><?= $vBy ? ' bởi ' . e($vBy) : '' ?> — Lý do: <?= e($vReason) ?></div>
<?php endif; ?>
            <div class="modal-actions">
                <a class="btn-outline" style="flex:1;text-align:center;text-decoration:none;" href="<?= e(admin_url('history', $baseQuery)) ?>">Đóng</a>
            </div>
        </div>
    </div>
<?php endif; ?>
