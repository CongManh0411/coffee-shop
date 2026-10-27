<!-- pages/schedule/index.php — giao diện trang "Lịch ca" (header/footer do index.php gốc in) -->
    <main class="main">
        <div id="schedule-app">
            <h2 class="sch-title">Lịch ca</h2>
            <div class="sch-demo" id="schDemo" hidden>
                <i class="fa-solid fa-circle-info"></i>
                Đang hiển thị lịch mẫu để xem thử. Khi quản lý lưu lịch thật, lịch mẫu sẽ tự biến mất.
            </div>

            <!-- Ca của tôi -->
            <section class="sch-me" id="schMe" hidden>
                <div class="sch-me-card"><span class="sch-me-label">Ca tiếp theo của bạn</span><b id="schMeNext">--</b><small id="schMeNextSub"></small></div>
                <div class="sch-me-card"><span class="sch-me-label">Giờ làm tuần này</span><b id="schMeHours">--</b><small id="schMeHoursSub"></small></div>
                <div class="sch-me-card"><span class="sch-me-label">Lương dự kiến tuần này</span><b id="schMePay">--</b><small>Theo ca đã xếp, chỉ tính giờ trong ca</small></div>
            </section>

            <!-- Đầu trang: ai đang trong ca / ai chưa tới ca (hôm nay) -->
            <div class="sch-now">
                <section class="sch-box in-shift">
                    <h3><i class="fa-solid fa-circle-play"></i> Đang trong ca</h3>
                    <div id="schInShift"></div>
                </section>
                <section class="sch-box upcoming">
                    <h3><i class="fa-regular fa-clock"></i> Chưa vào ca</h3>
                    <div id="schUpcoming"></div>
                </section>
            </div>

            <!-- Chọn tuần -->
            <div class="sch-weekbar">
                <button type="button" id="schPrev"><i class="fa-solid fa-chevron-left"></i> Tuần trước</button>
                <div class="sch-week-label">
                    <b id="schMonthLabel"></b>
                    <span id="schRangeLabel"></span>
                </div>
                <button type="button" id="schThisWeek">Tuần này</button>
                <input type="date" id="schPick" aria-label="Chọn tuần theo ngày">
                <button type="button" id="schNext">Tuần sau <i class="fa-solid fa-chevron-right"></i></button>
            </div>

            <!-- Bảng lịch tuần: hàng = nhân viên, cột = Thứ 2 → CN -->
            <div class="sch-table-wrap">
                <table class="sch-table" id="schTable"></table>
            </div>
            <div class="sch-day-tabs" id="schDayTabs" role="group" aria-label="Chọn ngày trong tuần"></div>
            <div class="sch-list" id="schList"></div>
            <p class="sch-note">Giờ làm việc của quán: 6h30 – 22h. Lịch do quản lý sắp xếp, nhân viên chỉ xem. Ô để trống là không có ca. Giờ làm thật lấy từ chấm công của Phục vụ.</p>
        </div>
    </main>
