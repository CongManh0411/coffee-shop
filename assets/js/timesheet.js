// ============================================================
// timesheet.js — CHẤM CÔNG & GIAO CA CỦA NHÂN VIÊN PHỤC VỤ
// ------------------------------------------------------------
// Luồng: đăng nhập (tự ghi GIỜ VÀO) -> làm việc -> bấm "Giao ca" ở sidebar -> hiện NGÀY CÔNG HÔM NAY
//        (giờ vào, giờ ra, tổng giờ, tiền) + nút "Xác nhận" -> bấm Xác nhận là hết ca, quay lại màn hình
//        nhân viên -> cuối cùng nhân viên tự bấm "Đăng xuất".
//
// - Giờ vào: ghi ở lần đầu nhân viên mở trang sau khi đăng nhập trong ngày (session hiện chỉ lưu {roleKey, name}
//   nên chưa có giờ đăng nhập thật; khi có backend hãy ghi giờ này ngay lúc login).
// - Giờ ra: đúng lúc bấm "Xác nhận". Mỗi ngày chỉ có 1 lần giao ca; sau khi Xác nhận là hết.
// - Tiền = số phút làm x lương/giờ (đổi HOURLY_RATE bên dưới). Chỉ hiện tiền của chính người đang đăng nhập.
// - Giờ ra này cũng được trang Lịch ca làm đọc: giao ca sớm hơn giờ đăng ký thì bảng tự hiện "Nghỉ sớm".
//
// DỮ LIỆU — bảng MySQL `timesheets` qua API: GET 'timesheet', POST 'timesheet/clock-in', POST 'timesheet/clock-out'.
//   { staffId: 3, date: '2026-10-05', clockIn: 1790000000000, clockOut: 1790018000000 | null }  (mili-giây)
// Ca đã xếp lấy từ API 'shifts'. Không còn dùng localStorage cho ca, nhân viên và chấm công.
// ============================================================
(function () {
    'use strict';
    if (window.WaiterTimesheet) return;

    const HOURLY_RATE = 21000; // đồng / giờ — mức chung cho cả quán

    const $ = id => document.getElementById(id);
    const pad = n => String(n).padStart(2, '0');
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const dateKey = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const fmtDate = d => `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
    const fmtTime = ms => { const d = new Date(ms); return `${pad(d.getHours())}:${pad(d.getMinutes())}`; };
    const fmtMoney = n => String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + 'đ';
    const toMin = hhmm => { const [h, m] = String(hhmm).split(':').map(Number); return (h || 0) * 60 + (m || 0); };
    const fmtClockMin = min => { const h = Math.floor(min / 60), m = min % 60; return m ? `${h}h${pad(m)}` : `${h}h`; };
    function fmtLong(min) {
        const h = Math.floor(min / 60), m = min % 60;
        if (h && m) return `${h} giờ ${m} phút`;
        if (h) return `${h} giờ`;
        return `${m} phút`;
    }

    // ---------- Dữ liệu (MySQL qua API, giữ bản nhớ tạm trong biến để vẽ popup mỗi giây) ----------
    let todayRec = null;   // bản ghi chấm công hôm nay của chính mình
    let allShifts = [];    // ca đã xếp (API 'shifts')

    function currentStaff() {
        const session = AuthAPI.getSession();
        if (!session) return null;
        return { id: session.staffId != null ? session.staffId : session.name, name: session.name };
    }

    function todayRecord() { return todayRec; }

    async function loadShifts() {
        try { allShifts = await Api.get('shifts'); } catch (e) { /* giữ bản cũ */ }
    }

    // Ghi GIỜ VÀO nếu hôm nay chưa có bản ghi (máy chủ giữ nguyên nếu đã có, kể cả đã giao ca)
    async function recordClockIn() {
        if (!currentStaff()) return null;
        try { todayRec = await Api.post('timesheet/clock-in'); } catch (e) { /* mất mạng: thử lại lần sau */ }
        refreshButton();
        return todayRec;
    }

    // Ca đã xếp của 1 người trong 1 ngày (quản lý xếp ở trang Lịch ca)
    function shiftsOf(staffId, date) {
        return allShifts.filter(s => String(s.staffId) === String(staffId) && s.date === date).sort((a, b) => toMin(a.start) - toMin(b.start));
    }
    const at = (date, hhmm) => new Date(`${date}T${hhmm}:00`).getTime();

    // CHỈ tính lương phần giờ có mặt NẰM TRONG ca đã xếp. Có mặt ngoài ca (vào sớm, ở lại muộn, không có ca) không tính.
    function calc(rec, nowMs) {
        const end = rec.clockOut || nowMs;
        const minutes = Math.max(0, Math.floor((end - rec.clockIn) / 60000));
        const mine = shiftsOf(rec.staffId, rec.date);
        let paid = 0;
        mine.forEach(s => {
            const a = Math.max(rec.clockIn, at(rec.date, s.start)), b = Math.min(end, at(rec.date, s.end));
            if (b > a) paid += Math.floor((b - a) / 60000);
        });
        const late = mine.length ? Math.max(0, Math.floor((rec.clockIn - at(rec.date, mine[0].start)) / 60000)) : 0;
        return { minutes, paid, late, outside: Math.max(0, minutes - paid), hasShift: mine.length > 0, pay: Math.round(paid * HOURLY_RATE / 60), end };
    }

    function registeredText(me, date) {
        const mine = shiftsOf(me.id, date).map(s => `${fmtClockMin(toMin(s.start))} – ${fmtClockMin(toMin(s.end))}`);
        return mine.length ? mine.join(', ') : 'Không có ca đăng ký';
    }

    // ---------- Nút ở sidebar ----------
    function refreshButton() {
        const btn = $('navHandover');
        if (!btn) return;
        const rec = todayRecord();
        const done = !!(rec && rec.clockOut);
        btn.textContent = done ? 'Đã giao ca' : 'Giao ca';
    }

    // ---------- Popup ngày công ----------
    let timer = null;

    function closeModal() {
        const m = $('hoBackdrop');
        if (m) m.classList.remove('active');
        const nb = $('navHandover'); if (nb) nb.classList.remove('active');
        if (timer) { clearInterval(timer); timer = null; }
    }

    function renderModal() {
        const me = currentStaff();
        const rec = todayRecord();
        const body = $('hoBody');
        if (!me || !rec || !body) return;
        const now = Date.now();
        const c = calc(rec, now);
        const done = !!rec.clockOut;
        const today = new Date();

        body.innerHTML = `
            ${done ? `<div class="ho-done"><i class="ri-checkbox-circle-line"></i> Bạn đã giao ca lúc ${fmtTime(rec.clockOut)}. Hết ca hôm nay.</div>` : ''}
            ${c.hasShift ? '' : '<div class="wp-warn">Hôm nay bạn không có ca đã xếp nên chưa tính lương. Liên hệ Quản lý nếu có nhầm lẫn.</div>'}
            <dl class="ho-rows">
                <div><dt>Nhân viên</dt><dd>${esc(me.name)}</dd></div>
                <div><dt>Ngày</dt><dd>${fmtDate(today)}</dd></div>
                <div><dt>Ca đăng ký</dt><dd>${esc(registeredText(me, rec.date))}</dd></div>
                <div><dt>Giờ vào ca</dt><dd>${fmtTime(rec.clockIn)}</dd></div>
                <div><dt>Giờ ra ca</dt><dd>${done ? fmtTime(rec.clockOut) : fmtTime(now) + ' <small>(bây giờ)</small>'}</dd></div>
                <div><dt>Có mặt</dt><dd>${fmtLong(c.minutes)}</dd></div>
                ${c.late > 0 ? `<div><dt>Đi muộn</dt><dd>${fmtLong(c.late)}</dd></div>` : ''}
                <div><dt>Giờ tính lương (trong ca)</dt><dd>${fmtLong(c.paid)}</dd></div>
                ${c.outside > 0 ? `<div><dt>Ngoài ca (không tính lương)</dt><dd>${fmtLong(c.outside)}</dd></div>` : ''}
                <div><dt>Lương mỗi giờ</dt><dd>${fmtMoney(HOURLY_RATE)}</dd></div>
            </dl>
            <div class="ho-total"><span>Tiền công hôm nay</span><b>${fmtMoney(c.pay)}</b></div>`;
        $('hoConfirm').hidden = done;
        $('hoCloseBtn').hidden = !done;
    }

    function buildModal() {
        if ($('hoBackdrop')) return;
        const wrap = document.createElement('div');
        wrap.className = 'ho-backdrop';
        wrap.id = 'hoBackdrop';
        wrap.innerHTML = `
            <div class="ho-modal" role="dialog" aria-label="Giao ca">
                <div class="ho-head">
                    <h3>Giao ca — ngày công hôm nay</h3>
                    <button type="button" class="ho-x" id="hoX" aria-label="Đóng">&times;</button>
                </div>
                <div class="ho-body" id="hoBody"></div>
                <div class="ho-foot">
                    <button type="button" class="ho-btn primary" id="hoConfirm">Xác nhận</button>
                    <button type="button" class="ho-btn" id="hoCloseBtn" hidden>Đóng</button>
                </div>
            </div>`;
        document.body.appendChild(wrap);

        $('hoX').addEventListener('click', closeModal);
        $('hoCloseBtn').addEventListener('click', closeModal);
        wrap.addEventListener('click', e => { if (e.target === wrap) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        // Xác nhận = giao ca: chốt giờ ra, hết ca, thoát popup về lại màn hình nhân viên
        $('hoConfirm').addEventListener('click', async () => {
            let busy = 0;
            try { busy = (JSON.parse(localStorage.getItem('coffee_tables_v1')) || []).filter(t => t.status !== 'empty').length; } catch (err) {}
            if (busy && !window.confirm(`Còn ${busy} bàn đang có khách hoặc chưa thanh toán. Vẫn giao ca?`)) return;
            try { todayRec = await Api.post('timesheet/clock-out'); }
            catch (err) { alert('Không giao ca được: ' + err.message); return; }
            closeModal();
            refreshButton();
        });
    }

    async function openHandover() {
        await Promise.all([recordClockIn(), loadShifts()]); // phòng khi chưa có bản ghi hôm nay
        if (!todayRec) { alert('Không tải được dữ liệu chấm công. Hãy tải lại trang.'); return; }
        buildModal();
        renderModal();
        $('hoBackdrop').classList.add('active');
        const nb = $('navHandover'); if (nb) nb.classList.add('active');
        if (timer) clearInterval(timer);
        timer = setInterval(renderModal, 1000); // giờ ra "bây giờ", tổng giờ và tiền chạy theo thời gian thực
    }

    window.WaiterTimesheet = { recordClockIn, openHandover, refreshButton, todayRecord, HOURLY_RATE };
})();