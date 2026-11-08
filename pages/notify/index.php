<!-- pages/notify/index.php — Thông báo món: bàn nào đủ món (mang ra được) / thiếu mấy món. -->
    <main class="main">
        <div class="main-content cs-wrap">
            <div class="menu-page-header">
                <h2>Thông báo món</h2>
                <p><?= count($notifyOrders) ? count(array_filter($notifyOrders, function ($o) { return $o['complete']; })) . ' bàn đủ món • ' . count(array_filter($notifyOrders, function ($o) { return !$o['complete']; })) . ' bàn còn thiếu' : 'Chưa có bàn nào đang chờ món.' ?></p>
            </div>
            <div class="data-table-wrapper"><table class="data-table">
                <thead><tr><th>Bàn / Đơn</th><th>Tình trạng</th><th>Món</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($notifyOrders as $o): ?>
                    <tr>
                        <td><b><?= e($o['tableName']) ?></b></td>
                        <td><span class="cs-status <?= $o['complete'] ? 'done' : 'making' ?>"><?= $o['complete'] ? 'ĐỦ MÓN — mang ra được' : 'Thiếu ' . (int) $o['missing'] . '/' . count($o['lines']) . ' món' ?></span></td>
                        <td style="font-size:12.5px;line-height:1.7"><?php foreach ($o['lines'] as $i): ?><?= e($i['name']) ?> ×<?= (int) $i['quantity'] ?> <span class="cs-status <?= e($i['status']) ?>"><?= e(POS_LINE_TEXT[$i['status']] ?? '') ?></span><br><?php endforeach; ?></td>
                        <td><a class="btn-outline" href="index.php?page=tables&order=<?= (int) $o['id'] ?>">Mở bàn</a><?= $o['complete'] ? ' <a class="btn-submit pos-btn" href="index.php?page=tables&order=' . (int) $o['id'] . '&pay=1">Thanh toán</a>' : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$notifyOrders): ?><tr><td colspan="4"><div class="empty-table-state">Không có món nào đang chờ.</div></td></tr><?php endif; ?>
                </tbody></table></div>
            <p class="cs-note">Trang tự tải lại mỗi 10 giây. Bàn tự biến mất khỏi danh sách khi đã thanh toán.</p>
        </div>
    </main>
