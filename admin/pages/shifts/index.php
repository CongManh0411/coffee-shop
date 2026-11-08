<!-- pages/shifts/index.php — giao diện trang "Lịch ca" (header/footer do index.php gốc in). Toàn bộ do PHP in ra, thao tác bằng form POST. -->
    <?php
    $fmt = function ($d) { return date('d/m/Y', strtotime($d)); };
    $weekUrl = function ($w) { return admin_url('shifts', array('week' => $w)); };
    $hoursText = function ($m) { return schedule_duration($m); };
    ?>
    <main class="main">
        <div id="schedule-app">
            <h2 class="sch-title">Xếp lịch ca</h2>
            <p class="sch-note" style="margin:-8px 0 14px">Bấm "+ Thêm ca" trong ô để xếp ca, bấm vào ca có sẵn để sửa hoặc xóa. Giờ mở cửa 6h30 – 22h.</p>

            <?php if ($shiftSavedMessage): ?><p class="shf-ok" role="status"><?= e($shiftSavedMessage) ?></p><?php endif; ?>
            <?php if ($shiftError && !$shiftPanel): ?><p class="shf-error" role="alert"><?= e($shiftError) ?></p><?php endif; ?>

            <div class="sch-weekbar">
                <a class="shf-link-btn" href="<?= e($weekUrl(date('Y-m-d', strtotime($weekStart . ' -7 days')))) ?>">Tuần trước</a>
                <div class="sch-week-label"><b>Tháng <?= e(date('n/Y', strtotime($weekStart . ' +3 days'))) ?></b><span><?= e($fmt($weekStart)) ?> – <?= e($fmt($weekEnd)) ?></span></div>
                <a class="shf-link-btn" href="<?= e(admin_url('shifts')) ?>">Tuần này</a>
                <a class="shf-link-btn" href="<?= e($weekUrl(date('Y-m-d', strtotime($weekStart . ' +7 days')))) ?>">Tuần sau</a>
                <form method="post" action="<?= e($weekUrl($weekStart)) ?>" class="shf-inline-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="week" value="<?= e($weekStart) ?>">
                    <input type="hidden" name="shift_action" value="copy">
                    <button type="submit" class="shf-outline">Sao chép từ tuần trước</button>
                </form>
                <form method="post" action="<?= e($weekUrl($weekStart)) ?>" class="shf-inline-form" onsubmit="return confirm('Xóa toàn bộ ca của tuần này?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="week" value="<?= e($weekStart) ?>">
                    <input type="hidden" name="shift_action" value="clear">
                    <button type="submit" class="shf-danger">Xóa lịch tuần này</button>
                </form>
            </div>

            <?php if ($shiftPanel): ?>
                <form method="post" action="<?= e($weekUrl($weekStart)) ?>" class="shf-panel">
                    <h3><?= $shiftPanel['id'] ? 'Sửa ca' : 'Thêm ca' ?></h3>
                    <p class="modal-sub"><?= e($shiftPanelStaff) ?> · <?= e($dayNames[(int) date('N', strtotime($shiftPanel['date'])) - 1]) ?> <?= e($fmt($shiftPanel['date'])) ?></p>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="week" value="<?= e($weekStart) ?>">
                    <input type="hidden" name="shift_action" value="save">
                    <input type="hidden" name="shift_id" value="<?= (int) $shiftPanel['id'] ?>">
                    <input type="hidden" name="staff_id" value="<?= (int) $shiftPanel['staff_id'] ?>">
                    <input type="hidden" name="date" value="<?= e($shiftPanel['date']) ?>">
                    <div class="form-row">
                        <div class="form-group"><label for="shStart">Giờ bắt đầu</label><input type="time" id="shStart" name="start" step="900" value="<?= e($shiftPanel['start']) ?>" required></div>
                        <div class="form-group"><label for="shEnd">Giờ kết thúc</label><input type="time" id="shEnd" name="end" step="900" value="<?= e($shiftPanel['end']) ?>" required></div>
                    </div>
                    <?php if ($shiftError): ?><p class="shf-error" role="alert"><?= e($shiftError) ?></p><?php endif; ?>
                    <div class="shf-panel-actions">
                        <button type="submit" class="btn-submit">Lưu</button>
                        <a class="shf-link-btn" href="<?= e($weekUrl($weekStart)) ?>">Đóng</a>
                        <?php if ($shiftPanel['id']): ?>
                            <button type="submit" class="btn-danger" name="shift_action" value="delete" formnovalidate onclick="return confirm('Xóa ca này?');">Xóa ca</button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>

            <div class="sch-table-wrap">
                <table class="sch-table">
                    <thead>
                        <tr>
                            <th class="sch-staffcol">Nhân viên</th>
                            <?php foreach ($weekDays as $i => $d): ?>
                                <th class="<?= $d === date('Y-m-d') ? 'today' : '' ?>"><?= e($dayNames[$i]) ?><small><?= e(date('d/m/Y', strtotime($d))) ?></small></th>
                            <?php endforeach; ?>
                            <th class="shf-total">Tổng giờ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shiftStaff as $st): $sid = (int) $st['staff_id']; $total = 0; ?>
                            <tr>
                                <th class="sch-staffcol"><b><?= e($st['full_name']) ?></b><small><?= e(role_label($st['role'])) ?></small></th>
                                <?php foreach ($weekDays as $d): ?>
                                    <td class="<?= $d === date('Y-m-d') ? 'today' : '' ?>">
                                        <?php foreach (($shiftGrid[$sid][$d] ?? array()) as $sh):
                                            $mins = schedule_minutes($sh['start_time'], $sh['end_time']); $total += $mins; ?>
                                            <a class="sch-shift shf-shift" href="<?= e(admin_url('shifts', array('week' => $weekStart, 'edit' => $sh['shift_id']))) ?>"><span class="sch-time"><?= e(schedule_clock($sh['start_time'])) ?> – <?= e(schedule_clock($sh['end_time'])) ?> <small>(<?= e($hoursText($mins)) ?>)</small></span></a>
                                        <?php endforeach; ?>
                                        <a class="shf-add" href="<?= e(admin_url('shifts', array('week' => $weekStart, 'add_staff' => $sid, 'add_date' => $d))) ?>">+ Thêm ca</a>
                                    </td>
                                <?php endforeach; ?>
                                <td class="shf-total"><?= $total ? e($hoursText($total)) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$shiftStaff): ?>
                            <tr><td colspan="9" class="sch-none">Chưa có nhân viên. Thêm ở trang Nhân viên.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($shiftStaff): ?>
                    <tfoot>
                        <tr>
                            <td>Số người / ngày</td>
                            <?php foreach ($weekDays as $d): $n = count($dayCount[$d] ?? array()); ?>
                                <?= $n ? '<td>' . $n . ' người</td>' : '<td class="warn">Chưa có ai</td>' ?>
                            <?php endforeach; ?>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            <p class="sch-note">Lịch lưu ngay khi bấm Lưu. Nhân viên (Phục vụ) xem ở mục "Lịch ca làm". Lương chỉ tính cho giờ làm nằm trong ca đã xếp.</p>

            <!-- DANH SÁCH CA SẮP TỚI (PHP + MySQL) -->
            <section class="shf-upcoming" id="upcomingBox">
                <h3>Danh sách ca sắp tới</h3>
                <p class="sch-note">Từ hôm nay trở đi có <span id="upcomingTotal"><?= (int) $upcomingPager['total'] ?></span> ca, hiển thị 10 ca mỗi trang, đọc trực tiếp từ cơ sở dữ liệu. Danh sách tự cập nhật sau mỗi lần lưu ca.</p>
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ngày</th><th>Nhân viên</th><th>Vai trò</th>
                                <th>Giờ làm</th><th>Thời lượng</th><th>Chấm công</th>
                            </tr>
                        </thead>
                        <tbody id="upcomingBody"><?= shifts_upcoming_rows_html($upcomingShifts) ?></tbody>
                    </table>
                </div>
                <div id="upcomingPager"><?= render_pagination($upcomingPager['page'], $upcomingPager['pages'], array_filter(array('page' => 'shifts', 'week' => $weekStart))) ?></div>
                <p class="sch-note"><a href="<?= e(admin_url('tablemanage')) ?>">Quản lý bàn &rarr;</a></p>
            </section>
        </div>
    </main>
