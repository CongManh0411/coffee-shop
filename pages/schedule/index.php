<!-- pages/schedule/index.php — giao diện trang "Lịch ca" (header/footer do index.php gốc in) -->
<?php
$monthMid = DateTime::createFromFormat('!Y-m-d', schedule_add_days($weekStart, 3));
$rangeFrom = DateTime::createFromFormat('!Y-m-d', $weekStart);
$rangeTo = DateTime::createFromFormat('!Y-m-d', $weekEnd);
?>
    <main class="main">
        <div id="schedule-app">
            <h2 class="sch-title">Lịch ca</h2>

            <!-- Ca của tôi -->
            <?php if ($myStaffId): ?>
            <section class="sch-me" id="schMe">
                <div class="sch-me-card">
                    <span class="sch-me-label">Ca tiếp theo của bạn</span>
                    <?php if ($myNextShift): ?>
                        <b><?= e(date('d/m', strtotime($myNextShift['shift_date']))) ?> · <?= e(schedule_clock($myNextShift['start_time'])) ?> – <?= e(schedule_clock($myNextShift['end_time'])) ?></b>
                        <small><?= e(schedule_duration(schedule_minutes($myNextShift['start_time'], $myNextShift['end_time']))) ?></small>
                    <?php else: ?>
                        <b>--</b><small>Chưa có ca nào được xếp</small>
                    <?php endif; ?>
                </div>
                <div class="sch-me-card">
                    <span class="sch-me-label">Giờ làm tuần đang xem</span>
                    <b><?= $myWeekMinutes ? e(schedule_duration($myWeekMinutes)) : '--' ?></b>
                    <small>Tính theo ca đã xếp</small>
                </div>
            </section>
            <?php endif; ?>

            <!-- Đầu trang: ai đang trong ca / ai chưa tới ca (hôm nay) -->
            <div class="sch-now">
                <section class="sch-box in-shift">
                    <h3><i class="fa-solid fa-circle-play"></i> Đang trong ca</h3>
                    <div id="schInShift">
                        <?php foreach ($inShift as $row): ?>
                            <div class="sch-person">
                                <b><?= e($row['full_name']) ?></b>
                                <span><?= e(schedule_clock($row['start_time'])) ?> – <?= e(schedule_clock($row['end_time'])) ?> · <?= e(role_label($row['role'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$inShift): ?><div class="sch-none">Hiện chưa có ai trong ca.</div><?php endif; ?>
                    </div>
                </section>
                <section class="sch-box upcoming">
                    <h3><i class="fa-regular fa-clock"></i> Chưa vào ca</h3>
                    <div id="schUpcoming">
                        <?php foreach ($waitingShift as $row): ?>
                            <div class="sch-person">
                                <b><?= e($row['full_name']) ?></b>
                                <span class="<?= $row['state'] === 'noshow' ? 'warn' : '' ?>"><?= e(schedule_clock($row['start_time'])) ?> – <?= e(schedule_clock($row['end_time'])) ?> · <?= $row['state'] === 'noshow' ? 'đã trễ, chưa vào ca' : 'chưa tới giờ vào ca' ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$waitingShift): ?><div class="sch-none">Không có ai đang chờ vào ca hôm nay.</div><?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Chọn tuần (liên kết thường, không cần JavaScript) -->
            <div class="sch-weekbar">
                <a class="sch-nav-link" href="<?= e(url('schedule', array('week' => $prevWeek))) ?>"><i class="fa-solid fa-chevron-left"></i> Tuần trước</a>
                <div class="sch-week-label">
                    <b>Tháng <?= e($monthMid->format('n/Y')) ?></b>
                    <span><?= e($rangeFrom->format('d/m')) ?> – <?= e($rangeTo->format('d/m')) ?></span>
                </div>
                <a class="sch-nav-link" href="<?= e(url('schedule', array('week' => $thisWeek))) ?>">Tuần này</a>
                <form method="get" action="index.php" class="sch-pick-form">
                    <input type="hidden" name="page" value="schedule">
                    <input type="date" name="week" value="<?= e($weekStart) ?>" aria-label="Chọn tuần theo ngày" onchange="this.form.submit()">
                </form>
                <a class="sch-nav-link" href="<?= e(url('schedule', array('week' => $nextWeek))) ?>">Tuần sau <i class="fa-solid fa-chevron-right"></i></a>
            </div>

            <!-- Bảng lịch tuần: hàng = nhân viên, cột = Thứ 2 → CN -->
            <div class="sch-table-wrap">
                <table class="sch-table" id="schTable">
                    <thead>
                        <tr>
                            <th class="sch-staffcol">Nhân viên</th>
                            <?php foreach ($weekDays as $i => $day): ?>
                                <th class="<?= $day === $today ? 'today' : '' ?>"><?= e($dayLabels[$i]) ?><small><?= e(date('d/m', strtotime($day))) ?></small></th>
                            <?php endforeach; ?>
                            <th class="sch-total">Tổng giờ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduleStaff as $person): ?>
                            <?php $isMe = $person['staff_id'] === $myStaffId; ?>
                            <tr class="<?= $isMe ? 'me' : '' ?>">
                                <th class="sch-staffcol">
                                    <b><?= e($person['full_name']) ?></b><?= $isMe ? ' <span class="sch-me-tag">Bạn</span>' : '' ?>
                                    <small><?= e(role_label($person['role'])) ?></small>
                                </th>
                                <?php foreach ($weekDays as $day): ?>
                                    <td class="<?= $day === $today ? 'today' : '' ?>">
                                        <?php foreach (($person['shifts'][$day] ?? array()) as $shift): ?>
                                            <?php $state = schedule_shift_state($shift, $day, $nowTs); ?>
                                            <div class="sch-shift st-<?= e($state) ?>">
                                                <div class="sch-time"><?= e(schedule_clock($shift['start_time'])) ?> – <?= e(schedule_clock($shift['end_time'])) ?> <small>(<?= e(schedule_duration(schedule_minutes($shift['start_time'], $shift['end_time']))) ?>)</small></div>
                                                <?php if (schedule_state_text($state, $shift) !== ''): ?>
                                                    <div class="sch-meta<?= in_array($state, array('noshow', 'absent', 'left'), true) ? ' warn' : '' ?>"><?= e(schedule_state_text($state, $shift)) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="sch-total"><?= $person['minutes'] ? e(schedule_duration($person['minutes'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$scheduleStaff): ?>
                            <tr><td colspan="9" class="sch-none">Chưa có nhân viên.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Danh sách theo ngày (hiện trên điện thoại thay cho bảng rộng) -->
            <div class="sch-list" id="schList">
                <?php foreach ($weekDays as $i => $day): ?>
                    <section class="sch-day<?= $day === $today ? ' today' : '' ?>">
                        <h4><?= e($dayLabels[$i]) ?> <small><?= e(date('d/m', strtotime($day))) ?></small></h4>
                        <?php $dayHasShift = false; ?>
                        <?php foreach ($scheduleStaff as $person): ?>
                            <?php foreach (($person['shifts'][$day] ?? array()) as $shift): ?>
                                <?php $dayHasShift = true; $state = schedule_shift_state($shift, $day, $nowTs); ?>
                                <div class="sch-li st-<?= e($state) ?><?= $person['staff_id'] === $myStaffId ? ' me' : '' ?>">
                                    <div><b><?= e($person['full_name']) ?></b><?= $person['staff_id'] === $myStaffId ? ' <span class="sch-me-tag">Bạn</span>' : '' ?></div>
                                    <div class="sch-li-time"><?= e(schedule_clock($shift['start_time'])) ?> – <?= e(schedule_clock($shift['end_time'])) ?> <small>(<?= e(schedule_duration(schedule_minutes($shift['start_time'], $shift['end_time']))) ?>)</small></div>
                                    <?php if (schedule_state_text($state, $shift) !== ''): ?>
                                        <div class="sch-meta"><?= e(schedule_state_text($state, $shift)) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php if (!$dayHasShift): ?><div class="sch-none">Không có ca.</div><?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>

            <p class="sch-note">Giờ làm việc của quán: 6h30 – 22h. Lịch do quản lý sắp xếp, nhân viên chỉ xem. Ô để trống là không có ca. Trạng thái ca lấy từ chấm công của Phục vụ.</p>
            <p class="sch-note"><a href="<?= e(url('tables')) ?>">&larr; Quay lại sơ đồ bàn</a></p>
        </div>
    </main>
