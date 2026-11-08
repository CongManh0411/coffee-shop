<!-- pages/ops/index.php — trang "Vận hành": PHP in dữ liệu từ MySQL; mọi thao tác là form POST. -->
    <?php
    $act = 'index.php?page=ops&tab=' . $opsTab;
    $moneyV = function ($n) { return money($n); };
    $tierOf = function ($p) { return $p >= 300 ? 'Vàng' : ($p >= 100 ? 'Bạc' : 'Đồng'); };
    ?>
    <main class="main">
        <nav class="topnav" id="opsTabs">
            <?php foreach ($opsTabs as $k => $label): ?>
                <a class="topnav-item<?= $opsTab === $k ? ' active' : '' ?>" style="text-decoration:none;display:inline-block" href="<?= e('index.php?page=ops&tab=' . $k) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="main-content" id="opsBody">
            <?php if ($opsSaved): ?><p class="ops-tag" role="status"><?= e($opsSaved) ?></p><?php endif; ?>
            <?php if ($opsError): ?><p class="ops-warn" role="alert"><?= e($opsError) ?></p><?php endif; ?>

<?php if ($opsTab === 'res'): ?>
            <div class="ops-card"><h3>Đặt bàn</h3><p>Yêu cầu khách gửi trên trang chủ và yêu cầu tạo tại quầy đều được lưu trong cơ sở dữ liệu.</p>
                <form method="post" action="<?= e($act) ?>" class="ops-form"><?= ops_hidden('reservation_create') ?>
                    <input name="name" placeholder="Tên khách" required maxlength="150">
                    <input name="phone" placeholder="SĐT" maxlength="30">
                    <input name="partySize" type="number" placeholder="Số người" min="1" max="20" required>
                    <input name="reservedAt" type="datetime-local" required>
                    <select name="tableId"><option value="">-- Chưa xếp bàn --</option><?php foreach ($opsTables as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select>
                    <input name="note" placeholder="Ghi chú" maxlength="500">
                    <button type="submit">Tạo đặt bàn</button>
                </form>
                <?php if (!$opsReservations): ?><div class="ops-empty">Chưa có yêu cầu đặt bàn.</div><?php else: ?>
                <div class="ops-scroll"><table class="ops-table"><tr><th>Khách</th><th>SĐT</th><th>Người</th><th>Giờ đặt</th><th>Bàn</th><th>Trạng thái</th><th>Ghi chú</th><th></th></tr>
                <?php $resStatus = array('booked' => 'Đã đặt', 'arrived' => 'Đã đến', 'cancelled' => 'Đã hủy', 'no_show' => 'Không đến'); ?>
                <?php foreach ($opsReservations as $r): ?>
                    <tr><td><?= e($r['name']) ?></td><td><?= e($r['phone'] ?: '—') ?></td><td><?= (int) $r['partySize'] ?></td><td><?= e(substr(str_replace('T', ' ', $r['reservedAt']), 0, 16)) ?></td><td><?= e($r['tableName'] ?: 'Chưa xếp') ?></td>
                        <td><span class="ops-tag"><?= e($resStatus[$r['status']] ?? $r['status']) ?></span></td><td><?= e($r['note'] ?: '—') ?></td>
                        <td><?php if ($r['status'] === 'booked'): ?>
                            <?= ops_row_form('reservation_status', array('id' => $r['id'], 'status' => 'arrived'), 'Đã đến', 'green') ?>
                            <?= ops_row_form('reservation_status', array('id' => $r['id'], 'status' => 'cancelled'), 'Hủy', 'red') ?>
                            <?= ops_row_form('reservation_status', array('id' => $r['id'], 'status' => 'no_show'), 'Không đến') ?>
                        <?php endif; ?></td></tr>
                <?php endforeach; ?></table></div><?php endif; ?>
            </div>
            <div class="ops-card"><h3>Hàng chờ (khách vãng lai)</h3>
                <form method="post" action="<?= e($act) ?>" class="ops-form"><?= ops_hidden('generic', array('op_method' => 'POST', 'action' => 'waitlist')) ?>
                    <input name="name" placeholder="Tên khách" required maxlength="150"><input name="partySize" type="number" placeholder="Số người" min="1" max="20" required><input name="phone" placeholder="SĐT"><button type="submit">Thêm vào hàng chờ</button>
                </form>
                <?php if (!$opsData['waitlist']): ?><div class="ops-empty">Chưa có dữ liệu.</div><?php else: ?>
                <div class="ops-scroll"><table class="ops-table"><tr><th>#</th><th>Khách</th><th>Người</th><th>Chờ</th><th></th></tr>
                <?php foreach ($opsData['waitlist'] as $i => $w): ?>
                    <tr><td><?= $i + 1 ?></td><td><?= e($w['name']) ?></td><td><?= (int) $w['pax'] ?></td><td><?= max(0, (int) round((time() * 1000 - $w['at']) / 60000)) ?> phút</td>
                        <td><?= ops_row_form('generic', array('op_method' => 'PUT', 'action' => 'waitlist', 'id' => $w['id'], 'status' => 'seated'), 'Gọi / xếp bàn', 'green') ?>
                            <?= ops_row_form('generic', array('op_method' => 'DELETE', 'action' => 'waitlist', 'id' => $w['id']), 'Bỏ', 'red') ?></td></tr>
                <?php endforeach; ?></table></div><?php endif; ?>
            </div>

<?php elseif ($opsTab === 'crm'): ?>
            <div class="ops-card"><h3>Khách thân thiết</h3><p style="font-size:13px;color:#7a6652">Quy ước: 10.000đ = 1 điểm. Hạng: Đồng &lt;100, Bạc ≥100, Vàng ≥300 điểm.</p>
                <form method="post" action="<?= e($act) ?>" class="ops-form"><?= ops_hidden('generic', array('op_method' => 'POST', 'action' => 'customer')) ?>
                    <input name="name" placeholder="Tên khách" required maxlength="150"><input name="phone" placeholder="SĐT" required><input name="birth" type="date" title="Sinh nhật"><button type="submit">Thêm khách</button>
                </form>
                <?php if (!$opsData['customers']): ?><div class="ops-empty">Chưa có dữ liệu.</div><?php else: ?>
                <div class="ops-scroll"><table class="ops-table"><tr><th>Khách</th><th>SĐT</th><th>Sinh nhật</th><th>Điểm</th><th>Hạng</th><th>Chi tiêu</th><th></th></tr>
                <?php $curMonth = (int) date('n'); foreach ($opsData['customers'] as $c): ?>
                    <tr><td><?= e($c['name']) ?></td><td><?= e($c['phone']) ?></td>
                        <td><?php if ($c['birth']): ?><?= (int) substr($c['birth'], 5, 2) === $curMonth ? '<span class="ops-warn">' . e(substr($c['birth'], 5)) . ' 🎂 tháng này – gửi ưu đãi</span>' : e(substr($c['birth'], 5)) ?><?php else: ?>—<?php endif; ?></td>
                        <td><?= (int) $c['points'] ?></td><td><span class="ops-tag"><?= e($tierOf($c['points'])) ?></span></td><td><?= e(money($c['spent'])) ?></td>
                        <td><form method="post" action="<?= e($act) ?>" style="display:inline"><?= ops_hidden('generic', array('op_method' => 'PUT', 'action' => 'customer-points', 'id' => $c['id'])) ?>
                                <input name="amount" type="number" min="1" placeholder="Hóa đơn (đ)" required style="width:110px"><button class="ops-btn sm green" type="submit">+ Điểm</button></form>
                            <?= ops_row_form('generic', array('op_method' => 'DELETE', 'action' => 'customer', 'id' => $c['id']), 'Xóa', 'red', 'Xóa khách này?') ?></td></tr>
                <?php endforeach; ?></table></div><?php endif; ?>
            </div>

<?php elseif ($opsTab === 'promo'): ?>
            <div class="ops-card"><h3>Mã khuyến mãi</h3><p style="font-size:13px;color:#7a6652">Mã được lưu trong database, kiểm tra hạn dùng và giới hạn lượt khi thanh toán. Các mã mẫu GIAM10/GIAM20/GIAM5K/GIAM20K cũng được giữ sẵn.</p>
                <form method="post" action="<?= e($act) ?>" class="ops-form"><?= ops_hidden('generic', array('op_method' => 'POST', 'action' => 'promotion')) ?>
                    <input name="code" placeholder="MÃ (vd HAPPY15)" required><input name="label" placeholder="Tên chương trình">
                    <select name="type"><option value="pct">Giảm %</option><option value="vnd">Giảm đ</option></select><input name="value" type="number" min="1" placeholder="Giá trị" required>
                    <label>Từ <input name="from" type="date"></label><label>Đến <input name="to" type="date"></label>
                    <input name="maxUses" type="number" min="0" placeholder="Giới hạn lượt"><input name="hourFrom" type="number" min="0" max="23" placeholder="Từ giờ (0-23)"><input name="hourTo" type="number" min="0" max="23" placeholder="Đến giờ">
                    <button type="submit">Thêm mã</button>
                </form>
                <?php if (!$opsData['promotions']): ?><div class="ops-empty">Chưa có dữ liệu.</div><?php else: ?>
                <div class="ops-scroll"><table class="ops-table"><tr><th>Mã</th><th>Chương trình</th><th>Giảm</th><th>Hạn</th><th>Lượt</th><th>Giờ</th><th></th></tr>
                <?php foreach ($opsData['promotions'] as $p): ?>
                    <tr><td><b><?= e($p['code']) ?></b></td><td><?= e($p['label']) ?></td><td><?= $p['type'] === 'pct' ? (int) $p['value'] . '%' : e(money($p['value'])) ?></td>
                        <td><?= e(($p['from'] ?: '…') . ' → ' . ($p['to'] ?: '…')) ?></td><td><?= (int) $p['used'] ?>/<?= $p['maxUses'] ? (int) $p['maxUses'] : '∞' ?></td>
                        <td><?= $p['hourFrom'] === '' ? 'Cả ngày' : (int) $p['hourFrom'] . 'h–' . (int) $p['hourTo'] . 'h' ?></td>
                        <td><?= ops_row_form('generic', array('op_method' => 'PUT', 'action' => 'promotion', 'id' => $p['id']), $p['active'] ? 'Tắt' : 'Bật') ?>
                            <?= ops_row_form('generic', array('op_method' => 'DELETE', 'action' => 'promotion', 'id' => $p['id']), 'Xóa', 'red', 'Xóa mã này?') ?></td></tr>
                <?php endforeach; ?></table></div><?php endif; ?>
            </div>

<?php elseif ($opsTab === 'stock'): ?>
            <?php
            $movLabel = array('import' => 'Nhập', 'export' => 'Xuất', 'waste' => 'Hao hụt');
            $costAcc = array();
            foreach ($opsData['movements'] as $m) if ($m['type'] === 'import') { $costAcc[$m['item']]['q'] = ($costAcc[$m['item']]['q'] ?? 0) + $m['qty']; $costAcc[$m['item']]['v'] = ($costAcc[$m['item']]['v'] ?? 0) + $m['qty'] * $m['price']; }
            $today = date('Y-m-d'); $soon = date('Y-m-d', strtotime('+7 days'));
            ?>
            <div class="ops-card"><h3>Nhà cung cấp</h3>
                <form method="post" action="<?= e($act) ?>" class="ops-form"><?= ops_hidden('generic', array('op_method' => 'POST', 'action' => 'supplier')) ?>
                    <input name="name" placeholder="Tên NCC" required><input name="phone" placeholder="SĐT"><button type="submit">Thêm NCC</button></form>
                <?php if (!$opsData['suppliers']): ?><div class="ops-empty">Chưa có dữ liệu.</div><?php else: ?>
                <div class="ops-scroll"><table class="ops-table"><tr><th>NCC</th><th>SĐT</th><th></th></tr>
                <?php foreach ($opsData['suppliers'] as $s): ?><tr><td><?= e($s['name']) ?></td><td><?= e($s['phone']) ?></td><td><?= ops_row_form('generic', array('op_method' => 'DELETE', 'action' => 'supplier', 'id' => $s['id']), 'Xóa', 'red', 'Ngừng sử dụng nhà cung cấp này?') ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
            </div>
            <div class="ops-card"><h3>Mặt hàng kho</h3><p>Kho vật tư được lưu trong database, độc lập với tồn kho thành phẩm trên màn bán hàng.</p>
                <form method="post" action="<?= e($act) ?>" class="ops-form"><?= ops_hidden('generic', array('op_method' => 'POST', 'action' => 'stock-item')) ?>
                    <input name="name" placeholder="Tên mặt hàng" required><input name="unit" placeholder="Đơn vị (kg, hộp…)" required><input name="minLevel" type="number" min="0" step="1" placeholder="Mức tồn tối thiểu"><button type="submit">Thêm mặt hàng</button></form>
                <?php if (!$opsData['stockItems']): ?><div class="ops-empty">Chưa có dữ liệu.</div><?php else: ?>
                <div class="ops-scroll"><table class="ops-table"><tr><th>Mặt hàng</th><th>Đơn vị</th><th>Tồn hiện tại</th><th>Mức tối thiểu</th></tr>
                <?php foreach ($opsData['stockItems'] as $i): ?><tr><td><?= e($i['name']) ?></td><td><?= e($i['unit']) ?></td><td><?= e(rtrim(rtrim(number_format($i['stock'], 3, '.', ''), '0'), '.')) ?></td><td><?= e(rtrim(rtrim(number_format($i['minLevel'], 3, '.', ''), '0'), '.')) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
            </div>
            <div class="ops-card"><h3>Phiếu nhập / xuất / hao hụt</h3>
                <form method="post" action="<?= e($act) ?>" class="ops-form"><?= ops_hidden('generic', array('op_method' => 'POST', 'action' => 'stock-movement')) ?>
                    <select name="type"><option value="import">Nhập</option><option value="export">Xuất</option><option value="waste">Hao hụt</option></select>
                    <select name="stockId" required><?php foreach ($opsData['stockItems'] as $i): ?><option value="<?= (int) $i['id'] ?>"><?= e($i['name']) ?> (<?= e($i['unit']) ?>)</option><?php endforeach; ?></select>
                    <input name="quantity" type="number" min="0.001" step="0.001" placeholder="Số lượng" required><input name="unitCost" type="number" min="0" placeholder="Đơn giá nhập">
                    <select name="supplierId"><option value="">-- NCC --</option><?php foreach ($opsData['suppliers'] as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select>
                    <label>Hạn dùng <input name="expiresOn" type="date"></label><input name="note" placeholder="Ghi chú / lý do"><button type="submit">Ghi phiếu</button></form>
                <p style="font-size:13px">Giá nhập bình quân:
                    <?php if ($costAcc): $parts = array(); foreach ($costAcc as $name => $c) $parts[] = '<b>' . e($name) . '</b> ' . e(money($c['v'] / $c['q'])); echo implode(' · ', $parts); else: ?>chưa có phiếu nhập<?php endif; ?></p>
                <?php if (!$opsData['movements']): ?><div class="ops-empty">Chưa có dữ liệu.</div><?php else: ?>
                <div class="ops-scroll"><table class="ops-table"><tr><th>Ngày</th><th>Loại</th><th>Mặt hàng</th><th>SL</th><th>Đơn giá</th><th>NCC</th><th>Hạn dùng</th><th>Ghi chú</th></tr>
                <?php foreach ($opsData['movements'] as $m): ?>
                    <tr><td><?= e(substr($m['date'], 0, 10)) ?></td><td><?= e($movLabel[$m['type']] ?? $m['type']) ?></td><td><?= e($m['item']) ?></td><td><?= e(rtrim(rtrim(number_format($m['qty'], 3, '.', ''), '0'), '.')) ?></td><td><?= $m['price'] ? e(money($m['price'])) : '—' ?></td><td><?= e($m['sup'] ?: '—') ?></td>
                        <td><?php if ($m['exp']): ?><?= $m['exp'] < $today ? '<span class="ops-warn">' . e($m['exp']) . ' (hết hạn)</span>' : ($m['exp'] <= $soon ? '<span class="ops-warn">' . e($m['exp']) . ' (sắp hết)</span>' : e($m['exp'])) ?><?php else: ?>—<?php endif; ?></td><td><?= e($m['note'] ?: '—') ?></td></tr>
                <?php endforeach; ?></table></div><?php endif; ?>
            </div>

<?php else: ?>
            <?php
            $platforms = array('GrabFood' => 20, 'ShopeeFood' => 20, 'BeFood' => 18);
            $delStatus = array('new' => 'Mới', 'making' => 'Đang làm', 'delivering' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Hủy');
            $delNext = array('new' => 'making', 'making' => 'delivering', 'delivering' => 'completed');
            ?>
            <div class="ops-card"><h3>Đơn từ ứng dụng giao hàng</h3><p style="font-size:13px;color:#7a6652">Đơn được nhập tay và lưu vào database. Webhook tự động và đồng bộ doanh thu chưa được kết nối.</p>
                <form method="post" action="<?= e($act) ?>" class="ops-form"><?= ops_hidden('generic', array('op_method' => 'POST', 'action' => 'delivery')) ?>
                    <select name="platform"><?php foreach (array_keys($platforms) as $pl): ?><option><?= e($pl) ?></option><?php endforeach; ?></select>
                    <input name="code" placeholder="Mã đơn / vận đơn" required><input name="name" placeholder="Khách"><input name="items" placeholder="Món (vd: 2 Cà phê sữa đá)"><input name="total" type="number" min="1" placeholder="Tổng tiền" required><button type="submit">Nhận đơn</button></form>
                <?php if (!$opsData['deliveries']): ?><div class="ops-empty">Chưa có dữ liệu.</div><?php else: ?>
                <div class="ops-scroll"><table class="ops-table"><tr><th>Nền tảng</th><th>Mã đơn</th><th>Khách</th><th>Món</th><th>Tổng</th><th>Hoa hồng ước tính</th><th>Trạng thái</th><th></th></tr>
                <?php foreach ($opsData['deliveries'] as $d): $next = $delNext[$d['status']] ?? null; ?>
                    <tr><td><?= e($d['plat']) ?></td><td><?= e($d['code']) ?></td><td><?= e($d['name']) ?></td><td><?= e($d['items']) ?></td><td><?= e(money($d['total'])) ?></td><td><?= e(money($d['total'] * ($platforms[$d['plat']] ?? 0) / 100)) ?></td>
                        <td><span class="ops-tag"><?= e($delStatus[$d['status']] ?? $d['status']) ?></span></td>
                        <td><?php if ($next): ?><?= ops_row_form('generic', array('op_method' => 'PUT', 'action' => 'delivery', 'id' => $d['id'], 'status' => $next), '→ ' . $delStatus[$next], 'green') ?><?= ops_row_form('generic', array('op_method' => 'PUT', 'action' => 'delivery', 'id' => $d['id'], 'status' => 'cancelled'), 'Hủy', 'red') ?><?php endif; ?></td></tr>
                <?php endforeach; ?></table></div><?php endif; ?>
            </div>
<?php endif; ?>
        </div>
    </main>
