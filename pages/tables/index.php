<!-- pages/tables/index.php — Bàn / Gọi món / Thanh toán. PHP in toàn bộ; mọi nút là link hoặc form POST (có CSRF). -->
<?php
$csrf = function () { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; };
$curUrl = $cur ? pos_url(array('order' => $cur['id'])) : ($curTable ? pos_url(array('table' => $curTable['table_id'])) : pos_url());
$freeTables = array();
foreach ($tablesStatus as $t) if (!isset($ordersByTable[(int) $t['table_id']])) $freeTables[] = $t;
?>
    <main class="main">
<div id="order-app">
<?php if ($flash['error']): ?><div class="menu-empty-state" role="alert" style="margin:10px 16px"><?= e($flash['error']) ?></div><?php endif; ?>
<?php if ($flash['ok']): ?><div class="pos-ok" role="status"><?= e($flash['ok']) ?></div><?php endif; ?>

<?php if ($mode === 'grid'): ?>
    <?php
    $togoOrders = array_values(array_filter($openOrders, function ($o) { return $o['tableId'] === null; }));
    $occupied = count(array_filter($tablesStatus, function ($t) { return $t['status'] === 'occupied'; }));
    ?>
    <div class="topnav">
        <?php foreach (array('all' => 'Tất cả bàn', 'empty' => 'Bàn trống', 'occupied' => 'Đang phục vụ', 'togo' => 'Mang đi / Giao hàng') as $k => $v): ?>
            <a class="topnav-item<?= $filter === $k ? ' active' : '' ?>" href="<?= e(pos_url($k === 'all' ? array() : array('f' => $k))) ?>"><?= e($v) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="main-content">
        <h2 class="tables-title">Sơ đồ bàn</h2>
        <div class="tables-summary">
            <span>Tổng: <b><?= count($tablesStatus) ?></b> bàn</span>
            <span>Đang phục vụ: <b><?= (int) $occupied ?></b></span>
            <span>Bàn trống: <b><?= count($tablesStatus) - $occupied ?></b></span>
            <span>Mang đi / giao: <b><?= count($togoOrders) ?></b></span>
        </div>
        <?php if ($isCashier): ?>
        <div class="pos-togo">
            <form method="post" action="<?= e(pos_url()) ?>" class="pos-inline"><?= $csrf() ?><input type="hidden" name="pos" value="create_togo"><input type="hidden" name="type" value="takeaway"><button type="submit" class="btn-togo"><i class="fa-solid fa-bag-shopping"></i> + Đơn mang đi</button></form>
            <form method="post" action="<?= e(pos_url()) ?>" class="pos-inline pos-delivery"><?= $csrf() ?><input type="hidden" name="pos" value="create_togo"><input type="hidden" name="type" value="delivery">
                <input name="customer" maxlength="80" placeholder="Tên khách" required><input name="address" maxlength="160" placeholder="Địa chỉ giao" required>
                <button type="submit" class="btn-togo"><i class="fa-solid fa-motorcycle"></i> + Đơn giao hàng</button></form>
        </div>
        <?php endif; ?>
        <div class="tables-grid">
            <?php if ($filter !== 'togo'): foreach ($tablesStatus as $t):
                $isOcc = $t['status'] === 'occupied';
                if (($filter === 'empty' && $isOcc) || ($filter === 'occupied' && !$isOcc)) continue;
                $o = $ordersByTable[(int) $t['table_id']] ?? null; ?>
                <a class="table-card <?= e($t['status']) ?>" href="<?= e($o ? pos_url(array('order' => $o['id'])) : pos_url(array('table' => $t['table_id']))) ?>" style="text-decoration:none;color:inherit">
                    <div class="table-card-name"><?= e($t['table_name']) ?></div>
                    <div class="table-card-status"><?= $isOcc ? 'Đang phục vụ' : 'Bàn trống' ?></div>
                    <?php if ($isOcc && $t['created_at']): ?><div class="table-card-time"><i class="fa-regular fa-clock"></i> Vào lúc: <?= e(pos_fmt_time($t['created_at'])) ?></div><?php endif; ?>
                </a>
            <?php endforeach; endif; ?>
            <?php if ($filter === 'all' || $filter === 'togo'): foreach ($togoOrders as $o): ?>
                <a class="table-card occupied togo" href="<?= e(pos_url(array('order' => $o['id']))) ?>" style="text-decoration:none;color:inherit">
                    <div class="table-card-name"><?= e($o['tableName']) ?></div>
                    <div class="table-card-status"><?= e(POS_ORDER_TYPES[$o['orderType']] ?? '') ?></div>
                    <?php if ($o['customer']): ?><div class="table-card-time"><?= e($o['customer']) ?><?= $o['address'] ? ' — ' . e($o['address']) : '' ?></div><?php endif; ?>
                    <div class="table-card-time"><i class="fa-regular fa-clock"></i> <?= e(pos_fmt_time($o['created_at'])) ?></div>
                </a>
            <?php endforeach; endif; ?>
            <?php if (!$tablesStatus && !$togoOrders): ?><div class="tables-empty">Chưa có bàn nào. Vui lòng liên hệ Quản lý để thêm bàn.</div><?php endif; ?>
        </div>
        <p class="pos-hint">Trang tự tải lại mỗi 15 giây. Bấm một bàn để gọi món hoặc thanh toán.</p>
    </div>

<?php elseif ($mode === 'receipt'): ?>
    <?php $given = (float) ($paid['given'] ?? 0); $change = $invoice['paymentMethod'] === 'cash' && $given > 0 ? max(0, $given - $invoice['total']) : 0; ?>
    <div class="main-content">
        <div class="pos-receipt">
            <h2>HÓA ĐƠN <?= e($invoice['id']) ?></h2>
            <p class="pos-muted"><?= e($invoice['tableName']) ?> · <?= e(POS_ORDER_TYPES[$invoice['orderType']] ?? '') ?> · <?= e(date('d/m/Y H:i', strtotime($invoice['createdAt']))) ?> · Thu ngân: <?= e($invoice['createdBy'] ?? '') ?></p>
            <table class="pos-table">
                <?php foreach ($invoice['items'] as $it): ?>
                    <tr><td><?= e($it['name']) ?> × <?= (int) $it['qty'] ?></td><td class="r"><?= e(money($it['price'] * $it['qty'])) ?></td></tr>
                <?php endforeach; ?>
                <tr><td>Tạm tính</td><td class="r"><?= e(money($invoice['subtotal'])) ?></td></tr>
                <?php if ($invoice['discount']): ?><tr><td>Giảm giá<?= $invoice['voucher'] ? ' (' . e($invoice['voucher']) . ')' : '' ?></td><td class="r">-<?= e(money($invoice['discount'])) ?></td></tr><?php endif; ?>
                <tr class="tot"><td>Tổng thanh toán</td><td class="r"><?= e(money($invoice['total'])) ?></td></tr>
                <tr><td>Hình thức</td><td class="r"><?= e(POS_METHODS[$invoice['payment_method']] ?? $invoice['payment_method']) ?></td></tr>
                <?php if ($change > 0 || ($invoice['paymentMethod'] === 'cash' && $given > 0)): ?>
                    <tr><td>Khách đưa</td><td class="r"><?= e(money($given)) ?></td></tr><tr><td>Tiền thối</td><td class="r"><?= e(money($change)) ?></td></tr>
                <?php endif; ?>
            </table>
            <p class="pos-actions">
                <a class="btn-submit pos-btn" href="#" onclick="window.print();return false;">In hóa đơn</a>
                <a class="btn-outline pos-btn" href="<?= e(pos_url()) ?>">Về sơ đồ bàn</a>
            </p>
        </div>
    </div>

<?php elseif ($mode === 'pay'): ?>
    <?php
    $sent = array_values(array_filter($cur['items'], function ($i) { return $i['status'] !== 'draft'; }));
    $sub = (int) $cur['sent_total'];
    $disc = (int) $payPreview['discount'];
    $total = max(0, $sub - $disc);
    $payUrl = pos_url(array('order' => $cur['id'], 'pay' => 1));
    ?>
    <div class="topnav"><a class="topnav-back" href="<?= e(pos_url(array('order' => $cur['id']))) ?>"><i class="fa-solid fa-arrow-left"></i> Quay lại gọi món</a></div>
    <div class="main-content">
        <div class="pos-pay">
            <h2>Thanh toán — <?= e($cur['tableName']) ?></h2>
            <table class="pos-table">
                <?php foreach ($sent as $i): ?>
                    <tr><td><?= e($i['name']) ?> × <?= (int) $i['quantity'] ?><?= $i['note'] ? ' <small>(' . e($i['note']) . ')</small>' : '' ?> <span class="cs-status <?= e($i['status']) ?>"><?= e(POS_LINE_TEXT[$i['status']] ?? '') ?></span></td><td class="r"><?= e(money($i['price'] * $i['quantity'])) ?></td></tr>
                <?php endforeach; ?>
                <tr><td>Tạm tính (món đã gửi bếp)</td><td class="r"><?= e(money($sub)) ?></td></tr>
                <?php if ($disc): ?><tr><td><?= e($payPreview['label']) ?></td><td class="r">-<?= e(money($disc)) ?></td></tr><?php endif; ?>
                <tr class="tot"><td>Khách cần trả</td><td class="r"><?= e(money($total)) ?></td></tr>
            </table>
            <?php if ($cur['all_total'] > $sub): ?><p class="pos-muted">Còn món nháp chưa gửi bếp — không tính vào hóa đơn này.</p><?php endif; ?>

            <?php if ($isCashier): ?>
            <form method="get" action="index.php" class="pos-box">
                <input type="hidden" name="page" value="tables"><input type="hidden" name="order" value="<?= (int) $cur['id'] ?>"><input type="hidden" name="pay" value="1">
                <b>Giảm giá</b> (Thu ngân)
                <div class="pos-row">
                    <input name="v" value="<?= e($payPreview['voucher']) ?>" placeholder="Mã khuyến mãi" maxlength="30">
                    <span>hoặc</span>
                    <input name="dv" type="number" min="0" step="1" value="<?= e($payPreview['discVal']) ?>" placeholder="Giảm thủ công">
                    <select name="dt"><option value="vnd"<?= $payPreview['discType'] === 'vnd' ? ' selected' : '' ?>>đ</option><option value="pct"<?= $payPreview['discType'] === 'pct' ? ' selected' : '' ?>>%</option></select>
                    <button type="submit" class="btn-outline">Áp dụng / xem tổng</button>
                </div>
                <?php if ($payPreview['error']): ?><p class="pos-err"><?= e($payPreview['error']) ?></p><?php endif; ?>
            </form>
            <?php endif; ?>

            <form method="post" action="<?= e($payUrl) ?>" class="pos-box">
                <?= $csrf() ?>
                <input type="hidden" name="pos" value="pay"><input type="hidden" name="order_id" value="<?= (int) $cur['id'] ?>">
                <input type="hidden" name="voucher" value="<?= e($payPreview['voucher']) ?>">
                <input type="hidden" name="disc_type" value="<?= e($payPreview['discType']) ?>">
                <input type="hidden" name="disc_val" value="<?= e($payPreview['discVal']) ?>">
                <b>Hình thức thanh toán</b>
                <div class="pos-row">
                    <?php foreach (POS_METHODS as $k => $v): ?>
                        <label class="opt-chip"><input type="radio" name="method" value="<?= e($k) ?>"<?= $k === 'cash' ? ' checked' : '' ?>> <?= e($v) ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="pos-row"><label>Khách đưa (tiền mặt) <input name="given" type="number" min="0" step="1000" value="<?= (int) $total ?>"></label>
                    <label>Mã giao dịch (QR / thẻ / ví) <input name="ref" maxlength="60"></label></div>
                <?php if ($cur['not_ready'] > 0): ?><label class="pos-check"><input type="checkbox" name="confirm_notready" value="1"> Còn <?= (int) $cur['not_ready'] ?> món chưa xong — vẫn thanh toán</label><?php endif; ?>
                <?php if (pos_large_discount($disc, $sub)): ?><label class="pos-check"><input type="checkbox" name="confirm_large" value="1"> Xác nhận giảm giá lớn (<?= e(money($disc)) ?>) — sẽ ghi Nhật ký cho Quản lý</label><?php endif; ?>
                <p><button type="submit" class="btn-submit pos-btn">Xác nhận thanh toán <?= e(money($total)) ?></button></p>
            </form>
        </div>
    </div>

<?php else: /* mode === 'order' */ ?>
    <?php
    $orderId = $cur ? (int) $cur['id'] : 0;
    $tableId = $curTable ? (int) $curTable['table_id'] : 0;
    $heading = $cur ? $cur['tableName'] : ($curTable['table_name'] ?? '');
    $items = $cur ? $cur['items'] : array();
    $hasDraft = false; foreach ($items as $i) if ($i['status'] === 'draft') $hasDraft = true;
    $keep = function ($extra = array()) use ($orderId, $tableId) { return pos_url(($orderId ? array('order' => $orderId) : array('table' => $tableId)) + $extra); };
    $dineIn = $cur && $cur['orderType'] === 'dinein';
    $mergeTargets = array();
    if ($dineIn) foreach ($openOrders as $o) if ($o['orderType'] === 'dinein' && $o['id'] !== $cur['id']) $mergeTargets[] = $o;
    ?>
    <div class="topnav">
        <a class="topnav-back" href="<?= e(pos_url()) ?>"><i class="fa-solid fa-arrow-left"></i> Quay lại sơ đồ bàn</a>
        <a class="topnav-item<?= $catFilter === '' ? ' active' : '' ?>" href="<?= e($keep($search !== '' ? array('q' => $search) : array())) ?>">Tất cả</a>
        <?php foreach ($menuGroups as $g): if (!$g['all']) continue; ?>
            <a class="topnav-item<?= $catFilter === $g['category'] ? ' active' : '' ?>" href="<?= e($keep(array('cat' => $g['category']) + ($search !== '' ? array('q' => $search) : array()))) ?>"><?= e($g['category']) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="main-content">
        <div class="order-screen" style="display:block">
            <div class="order-layout-container">
                <div class="menu-section">
                    <form method="get" action="index.php" class="menu-search-bar">
                        <input type="hidden" name="page" value="tables">
                        <?php if ($orderId): ?><input type="hidden" name="order" value="<?= $orderId ?>"><?php else: ?><input type="hidden" name="table" value="<?= $tableId ?>"><?php endif; ?>
                        <?php if ($catFilter !== ''): ?><input type="hidden" name="cat" value="<?= e($catFilter) ?>"><?php endif; ?>
                        <i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Tìm món theo tên, danh mục..." autocomplete="off">
                    </form>
                    <div class="menu-grid">
                    <?php $shown = 0; foreach ($menuGroups as $g):
                        if ($catFilter !== '' && $g['category'] !== $catFilter) continue;
                        foreach ($g['items'] as $m): $shown++;
                            $av = $availability[$m['id']]['available'] ?? 0;
                            $unit = $availability[$m['id']]['unit'] ?? 'ly';
                            $opts = !empty($catOptions[$g['category']]);
                            $img = menu_item_image(array('image' => $m['img'], 'item_name' => $m['name'])); ?>
                        <div class="menu-item">
                            <img class="menu-item-img" src="<?= e($img) ?>" alt="<?= e($m['name']) ?>">
                            <div class="menu-item-info">
                                <div class="menu-item-name"><?= e($m['name']) ?></div>
                                <div class="menu-item-desc"><?= e($g['category']) ?><?= $m['desc'] ? ' · ' . e($m['desc']) : '' ?></div>
                            </div>
                            <div class="menu-item-action">
                                <span class="menu-item-price"><?= e(money($m['price'])) ?></span>
                                <?php if ($m['soldOut'] || $av < 1): ?>
                                    <span class="soldout-tag">Hết hàng</span>
                                <?php else: ?>
                                    <form method="post" action="<?= e($keep()) ?>" class="pos-add">
                                        <?= $csrf() ?><input type="hidden" name="pos" value="add_item"><input type="hidden" name="order_id" value="<?= $orderId ?>"><input type="hidden" name="table_id" value="<?= $tableId ?>">
                                        <input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="cat" value="<?= e($catFilter) ?>"><input type="hidden" name="q" value="<?= e($search) ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="<?= (int) min(50, $av) ?>" class="pos-qty" aria-label="Số lượng">
                                        <button type="submit" class="btn-add-item">+ Thêm</button>
                                        <details class="pos-opts"><summary>Tùy chọn / ghi chú (còn <?= (int) $av ?> <?= e($unit) ?>)</summary>
                                            <?php if ($opts): ?>
                                                <div class="opt-label">Size</div><div class="opt-row"><?php foreach (POS_SIZES as $sz => $add): ?><label class="opt-chip"><input type="radio" name="size" value="<?= e($sz) ?>"<?= $sz === 'M' ? ' checked' : '' ?>> <?= e($sz) ?><?= $add ? ' (+' . ($add / 1000) . 'k)' : '' ?></label><?php endforeach; ?></div>
                                                <div class="opt-label">Đường</div><div class="opt-row"><?php foreach (POS_SUGAR as $s): ?><label class="opt-chip"><input type="radio" name="sugar" value="<?= e($s) ?>"<?= $s === '50%' ? ' checked' : '' ?>> <?= e($s) ?></label><?php endforeach; ?></div>
                                                <div class="opt-label">Đá</div><div class="opt-row"><?php foreach (POS_ICE as $s): ?><label class="opt-chip"><input type="radio" name="ice" value="<?= e($s) ?>"<?= $s === 'Ít đá' ? ' checked' : '' ?>> <?= e($s) ?></label><?php endforeach; ?></div>
                                                <div class="opt-label">Topping</div><div class="opt-row"><?php foreach (POS_TOPPINGS as $tp => $add): ?><label class="opt-chip"><input type="checkbox" name="toppings[]" value="<?= e($tp) ?>"> <?= e($tp) ?> +<?= ($add / 1000) ?>k</label><?php endforeach; ?></div>
                                            <?php endif; ?>
                                            <input name="note" class="opt-note" maxlength="60" placeholder="Ghi chú (vd: ít ngọt, mang đi)">
                                        </details>
                                    </form>
                                <?php endif; ?>
                                <?php if ($isCashier): ?>
                                    <form method="post" action="<?= e($keep()) ?>" class="pos-inline"><?= $csrf() ?><input type="hidden" name="pos" value="soldout"><input type="hidden" name="order_id" value="<?= $orderId ?>"><input type="hidden" name="table_id" value="<?= $tableId ?>"><input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="sold_out" value="<?= $m['soldOut'] ? '0' : '1' ?>">
                                        <button type="submit" class="btn-soldout"><?= $m['soldOut'] ? 'Bán lại' : 'Báo hết' ?></button></form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endforeach; ?>
                    <?php if (!$shown): ?><div class="menu-empty-state"><?= $search !== '' ? 'Không tìm thấy món phù hợp.' : 'Chưa có món nào trong danh mục này. Liên hệ Quản lý để thêm món.' ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="bill-section">
                    <div class="bill-header">
                        <h3 class="current-table-title"><?= e($heading) ?></h3>
                        <span class="current-invoice-id"><?= $cur ? 'Mã HD: ' . e($cur['order_code'] ?? '--') : 'Chưa có đơn' ?></span>
                    </div>
                    <hr class="bill-divider-dashed">
                    <div class="selected-items-title">Món đã chọn:</div>
                    <div class="bill-items-container">
                    <?php if (!$items): ?><div class="bill-empty">Chưa chọn món nào</div><?php endif; ?>
                    <?php foreach ($items as $i): $draft = $i['status'] === 'draft'; ?>
                        <div class="bill-item-row">
                            <div class="bill-item-details">
                                <div class="bill-item-name"><?= e($i['name']) ?> <span class="cs-status <?= e($i['status'] === 'draft' ? 'waiting' : $i['status']) ?>"><?= e(POS_LINE_TEXT[$i['status']] ?? '') ?></span></div>
                                <?php if ($i['note'] !== '' && !$draft): ?><div class="bill-item-meta">Ghi chú: <?= e($i['note']) ?></div><?php endif; ?>
                                <div class="bill-item-meta"><?= e(money($i['price'])) ?> × <?= (int) $i['quantity'] ?> = <?= e(money($i['price'] * $i['quantity'])) ?></div>
                                <?php if ($draft): ?>
                                    <form method="post" action="<?= e($keep()) ?>" class="pos-line"><?= $csrf() ?><input type="hidden" name="pos" value="update_item"><input type="hidden" name="order_id" value="<?= $orderId ?>"><input type="hidden" name="item_id" value="<?= (int) $i['id'] ?>">
                                        <input type="number" name="quantity" value="<?= (int) $i['quantity'] ?>" min="1" max="100" class="pos-qty" aria-label="Số lượng">
                                        <input name="note" value="<?= e($i['note']) ?>" maxlength="120" placeholder="Ghi chú" class="pos-note">
                                        <button type="submit" class="btn-outline">Lưu</button></form>
                                    <form method="post" action="<?= e($keep()) ?>" class="pos-inline"><?= $csrf() ?><input type="hidden" name="pos" value="delete_item"><input type="hidden" name="order_id" value="<?= $orderId ?>"><input type="hidden" name="item_id" value="<?= (int) $i['id'] ?>"><button type="submit" class="btn-danger">Xóa món</button></form>
                                <?php else: ?>
                                    <form method="post" action="<?= e($keep()) ?>" class="pos-line" onsubmit="return confirm('Hủy 1 phần món này?');"><?= $csrf() ?><input type="hidden" name="pos" value="cancel_item"><input type="hidden" name="order_id" value="<?= $orderId ?>"><input type="hidden" name="item_id" value="<?= (int) $i['id'] ?>">
                                        <input name="reason" maxlength="120" class="pos-note" placeholder="Lý do hủy<?= $i['status'] === 'done' ? ' (bắt buộc)' : '' ?>"<?= $i['status'] === 'done' ? ' required' : '' ?>>
                                        <button type="submit" class="btn-danger">Hủy 1 phần</button></form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <hr class="bill-divider">
                    <div class="bill-total"><span>Tạm tính:</span><span class="total-amount"><?= e(money($cur['all_total'] ?? 0)) ?></span></div>
                    <div class="bill-actions" style="display:flex;gap:8px;flex-wrap:wrap">
                        <?php if ($hasDraft): ?>
                            <form method="post" action="<?= e($keep()) ?>" class="pos-inline"><?= $csrf() ?><input type="hidden" name="pos" value="confirm"><input type="hidden" name="order_id" value="<?= $orderId ?>"><button type="submit" class="btn-submit">Xác nhận gửi bếp</button></form>
                        <?php endif; ?>
                        <?php if ($cur && $cur['sent_total'] > 0): ?>
                            <a class="btn-submit pos-btn" href="<?= e(pos_url(array('order' => $cur['id'], 'pay' => 1))) ?>">Thanh toán <?= e(money($cur['sent_total'])) ?></a>
                        <?php endif; ?>
                        <?php if ($cur && !$items): ?>
                            <form method="post" action="<?= e($keep()) ?>" class="pos-inline"><?= $csrf() ?><input type="hidden" name="pos" value="cancel_order"><input type="hidden" name="order_id" value="<?= $orderId ?>"><button type="submit" class="btn-outline">Hủy đơn trống</button></form>
                        <?php endif; ?>
                    </div>
                    <?php if ($dineIn && $items): ?>
                    <details class="pos-ops"><summary>Chuyển bàn / Gộp bàn / Tách bill</summary>
                        <form method="post" action="<?= e($keep()) ?>" class="pos-line"><?= $csrf() ?><input type="hidden" name="pos" value="move"><input type="hidden" name="order_id" value="<?= $orderId ?>">
                            <select name="target_table" required><option value="">Chuyển sang bàn trống…</option><?php foreach ($freeTables as $t): ?><option value="<?= (int) $t['table_id'] ?>"><?= e($t['table_name']) ?></option><?php endforeach; ?></select>
                            <button type="submit" class="btn-outline">Chuyển bàn</button></form>
                        <?php if ($mergeTargets): ?>
                        <form method="post" action="<?= e($keep()) ?>" class="pos-line" onsubmit="return confirm('Gộp bàn đã chọn vào bàn này?');"><?= $csrf() ?><input type="hidden" name="pos" value="merge"><input type="hidden" name="order_id" value="<?= $orderId ?>">
                            <select name="source_order" required><option value="">Gộp bàn khác vào đây…</option><?php foreach ($mergeTargets as $o): ?><option value="<?= (int) $o['id'] ?>"><?= e($o['tableName']) ?></option><?php endforeach; ?></select>
                            <button type="submit" class="btn-outline">Gộp bàn</button></form>
                        <?php endif; ?>
                        <?php if (count($items) > 1): ?>
                        <form method="post" action="<?= e($keep()) ?>" class="pos-split"><?= $csrf() ?><input type="hidden" name="pos" value="split"><input type="hidden" name="order_id" value="<?= $orderId ?>">
                            <div class="opt-label">Chọn dòng món để tách sang bàn trống:</div>
                            <?php foreach ($items as $i): ?><label class="pos-check"><input type="checkbox" name="split_items[]" value="<?= (int) $i['id'] ?>"> <?= e($i['name']) ?> × <?= (int) $i['quantity'] ?></label><?php endforeach; ?>
                            <select name="target_table" required><option value="">Tách sang bàn…</option><?php foreach ($freeTables as $t): ?><option value="<?= (int) $t['table_id'] ?>"><?= e($t['table_name']) ?></option><?php endforeach; ?></select>
                            <button type="submit" class="btn-outline">Tách bill</button></form>
                        <?php endif; ?>
                    </details>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
</main>
