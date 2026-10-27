<!-- pages/shifts/index.php — giao diện trang "Lịch ca" (header/footer do index.php gốc in) -->
    <main class="main">
        <div id="schedule-app">
            <h2 class="sch-title">Xếp lịch ca</h2>
            <p class="sch-note" style="margin:-8px 0 14px">Bấm "+ Thêm ca" trong ô để xếp ca, bấm vào ca có sẵn để sửa hoặc xóa. Giờ mở cửa 6h30 – 22h.</p>

            <div class="sch-weekbar">
                <button type="button" id="shPrev">Tuần trước</button>
                <div class="sch-week-label"><b id="shMonth"></b><span id="shRange"></span></div>
                <button type="button" id="shThis">Tuần này</button>
                <button type="button" id="shNext">Tuần sau</button>
                <button type="button" id="shCopy" class="shf-outline">Sao chép từ tuần trước</button>
                <button type="button" id="shClear" class="shf-danger">Xóa lịch tuần này</button>
            </div>

            <div class="sch-table-wrap"><table class="sch-table" id="shTable"></table></div>
            <section class="shf-mobile-schedule" id="shMobileSchedule" aria-label="Lịch ca theo ngày" hidden>
                <div class="shf-day-tabs" id="shDayTabs" role="group" aria-label="Chọn ngày trong tuần"></div>
                <div class="shf-day-list" id="shDayList" aria-live="polite"></div>
            </section>
            <p class="sch-note">Lịch lưu ngay khi bấm Lưu. Nhân viên (Phục vụ) xem ở mục "Lịch ca làm". Lương chỉ tính cho giờ làm nằm trong ca đã xếp.</p>

            <!-- DANH SÁCH CA SẮP TỚI (PHP + MySQL) -->
            <section class="shf-upcoming">
                <h3>Danh sách ca sắp tới</h3>
                <p class="sch-note">Từ hôm nay trở đi, tối đa 50 ca đọc trực tiếp từ cơ sở dữ liệu. Tải lại trang sau khi lưu ca để thấy thay đổi.</p>
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Nhân viên</th>
                                <th>Vai trò</th>
                                <th>Giờ làm</th>
                                <th>Thời lượng</th>
                                <th>Chấm công</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingShifts as $shift): ?>
                                <tr>
                                    <td><?= e(date('d/m/Y', strtotime($shift['shift_date']))) ?></td>
                                    <td><b><?= e($shift['full_name']) ?></b></td>
                                    <td><?= e(role_label($shift['role'])) ?></td>
                                    <td><?= e(schedule_clock($shift['start_time'])) ?> – <?= e(schedule_clock($shift['end_time'])) ?></td>
                                    <td><?= e(schedule_duration(schedule_minutes($shift['start_time'], $shift['end_time']))) ?></td>
                                    <td><?= (int) $shift['registered'] ? 'Đã vào ca' : 'Chưa vào ca' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$upcomingShifts): ?>
                                <tr><td colspan="6"><div class="empty-table-state">Chưa có ca nào từ hôm nay trở đi.</div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="sch-note"><a href="<?= e(admin_url('tablemanage')) ?>">Quản lý bàn &rarr;</a></p>
            </section>

        </div>
    </main>

    <div id="shModal" class="modal-overlay">
        <form class="modal-box" id="shForm" novalidate>
            <h3 class="modal-title" id="shTitle">Thêm ca</h3>
            <p class="modal-sub" id="shSub"></p>
            <div class="form-row">
                <div class="form-group"><label for="shStart">Giờ bắt đầu</label><input type="time" id="shStart" step="900"></div>
                <div class="form-group"><label for="shEnd">Giờ kết thúc</label><input type="time" id="shEnd" step="900"></div>
            </div>
            <p class="shf-error" id="shError" role="alert" hidden></p>
            <div class="modal-actions">
                <button type="button" class="btn-outline" id="shCancel">Đóng</button>
                <button type="button" class="btn-danger" id="shDelete" hidden>Xóa ca</button>
                <button type="submit" class="btn-submit">Lưu</button>
            </div>
        </form>
    </div>
