<!-- pages/handover/index.php — Giao ca: ngày công hôm nay, tiền công; Xác nhận = chốt giờ ra. -->
    <main class="main">
        <div class="main-content cs-wrap">
            <div class="pos-pay">
                <h2>Giao ca — ngày công hôm nay</h2>
                <?php if ($done): ?><div class="pos-ok">Bạn đã giao ca lúc <?= e(date('H:i', $endTs)) ?>. Hết ca hôm nay.</div><?php endif; ?>
                <?php if ($hoError): ?><div class="menu-empty-state" role="alert"><?= e($hoError) ?></div><?php endif; ?>
                <?php if (!$myShifts): ?><p class="pos-err">Hôm nay bạn không có ca đã xếp nên chưa tính lương. Liên hệ Quản lý nếu có nhầm lẫn.</p><?php endif; ?>
                <table class="pos-table">
                    <tr><td>Nhân viên</td><td class="r"><?= e($user['name']) ?></td></tr>
                    <tr><td>Ngày</td><td class="r"><?= e(date('d/m/Y')) ?></td></tr>
                    <tr><td>Ca đăng ký</td><td class="r"><?= $regText ? e(implode(', ', $regText)) : 'Không có ca đăng ký' ?></td></tr>
                    <tr><td>Giờ vào ca</td><td class="r"><?= e(date('H:i', $clockIn)) ?></td></tr>
                    <tr><td>Giờ ra ca</td><td class="r"><?= $done ? e(date('H:i', $endTs)) : e(date('H:i')) . ' <small>(bây giờ)</small>' ?></td></tr>
                    <tr><td>Có mặt</td><td class="r"><?= e(ho_long($minutes)) ?></td></tr>
                    <?php if ($late > 0): ?><tr><td>Đi muộn</td><td class="r"><?= e(ho_long($late)) ?></td></tr><?php endif; ?>
                    <tr><td>Giờ tính lương (trong ca)</td><td class="r"><?= e(ho_long($paid)) ?></td></tr>
                    <?php if ($outside > 0): ?><tr><td>Ngoài ca (không tính lương)</td><td class="r"><?= e(ho_long($outside)) ?></td></tr><?php endif; ?>
                    <tr><td>Lương mỗi giờ</td><td class="r"><?= e(money(HANDOVER_HOURLY_RATE)) ?></td></tr>
                    <tr class="tot"><td>Tiền công hôm nay</td><td class="r"><?= e(money($pay)) ?></td></tr>
                </table>
                <?php if (!$done): ?>
                <form method="post" action="index.php?page=handover" class="pos-box">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="ho_action" value="confirm">
                    <?php if ($busy > 0): ?><label class="pos-check"><input type="checkbox" name="confirm_busy" value="1"> Còn <?= (int) $busy ?> bàn đang có khách hoặc chưa thanh toán — vẫn giao ca</label><?php endif; ?>
                    <button type="submit" class="btn-submit pos-btn">Xác nhận giao ca</button>
                </form>
                <?php endif; ?>
                <p class="pos-muted">Trang này tính theo giờ hiện tại; tải lại trang để cập nhật số phút và tiền công.</p>
            </div>
        </div>
    </main>
