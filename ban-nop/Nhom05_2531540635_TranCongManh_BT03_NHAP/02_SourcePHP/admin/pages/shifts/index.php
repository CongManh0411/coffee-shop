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

            <details class="shf-db-section" open>
                <summary>Danh sách ca đã xếp</summary>
                <p class="sch-note" id="shDbSummary">Tuần <?= e(date('d/m/Y', strtotime($shiftWeekStart))) ?> – <?= e(date('d/m/Y', strtotime($shiftWeekEnd))) ?> · <?= count($weekShifts) ?> ca</p>
                <div class="shf-db-scroll">
                    <table class="shf-db-table">
                        <thead><tr><th scope="col">Mã ca</th><th scope="col">Ngày</th><th scope="col">Giờ bắt đầu</th><th scope="col">Giờ kết thúc</th><th scope="col">Nhân viên</th><th scope="col">Chức vụ</th></tr></thead>
                        <tbody id="shDbBody">
                            <?php foreach ($weekShifts as $shift): ?>
                                <tr>
                                    <td><?= (int) $shift['shift_id'] ?></td>
                                    <td><?= e(date('d/m/Y', strtotime($shift['shift_date']))) ?></td>
                                    <td><?= e(substr($shift['start_time'], 0, 5)) ?></td>
                                    <td><?= e(substr($shift['end_time'], 0, 5)) ?></td>
                                    <td><?= e($shift['full_name']) ?></td>
                                    <td><?= e($shiftRoleLabels[$shift['role']] ?? $shift['role']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$weekShifts): ?>
                                <tr><td colspan="6">Chưa có ca nào trong tuần này.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </details>
            <script type="application/json" id="shDbData"><?= json_encode($adminShifts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>

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
