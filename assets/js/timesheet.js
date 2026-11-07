// ============================================================
// timesheet.js — CHẤM CÔNG & GIAO CA CỦA NHÂN VIÊN PHỤC VỤ
// ------------------------------------------------------------
// Luồng: đăng nhập (tự ghi GIỜ VÀO) -> làm việc -> bấm "Giao ca" ở sidebar -> hiện NGÀY CÔNG HÔM NAY
//        (giờ vào, giờ ra, tổng giờ, tiền) + nút "Xác nhận" -> bấm Xác nhận là hết ca, quay lại màn hình
//        nhân viên -> cuối cùng nhân viên tự bấm "Đăng xuất".
//
// - Giờ vào: ghi ở lần đầu nhân viên mở trang sau khi đăng nhập trong ngày (API 'attendance/clock-in', giờ do máy chủ ghi).
// - Giờ ra: đúng lúc bấm "Xác nhận" (API 'attendance/clock-out'). Mỗi ngày chỉ có 1 lần giao ca; sau khi Xác nhận là hết.
// - Tiền = số phút làm x lương/giờ (đổi HOURLY_RATE bên dưới). Chỉ hiện tiền của chính người đang đăng nhập.
//
// DỮ LIỆU — bảng `timesheets` trên máy chủ (mỗi nhân viên một dòng mỗi ngày), API trả về:
//   { staffId: 3, date: '2026-10-05', clockIn: 1790000000000, clockOut: 1790018000000 | null }  (mili-giây)
// Ca đã xếp lấy từ API 'shifts'. Trình duyệt chỉ giữ bản sao trong bộ nhớ trang, không dùng localStorage.
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

    // ---------- Dữ liệu (từ máy chủ, giữ trong bộ nhớ trang) ----------
    let record = null;      // bản ghi chấm công hôm nay của người đang đăng nhập
    let shiftsCache = [];   // ca đã xếp (API 'shifts')

    function currentStaff() {
        const session = AuthAPI.getSession();
        if (!session) return null;
        return { id: session.staffId != null ? session.staffId : session.name, name: session.name };
    }

    function todayRecord() {
        return record; // máy chủ đã chọn đúng bản ghi của hôm nay
    }

    async function loadShifts() {
        try { shiftsCache = await Api.get('shifts'); } catch (e) { /* giữ bản đang có */ }
    }

    // Ghi GIỜ VÀO nếu hôm nay chưa có bản ghi (đã có — kể cả đã giao ca — thì máy chủ giữ nguyên, mỗi ngày 1 lần)
    async function recordClockIn() {
        if (!currentStaff()) return null;
        try { record = await Api.post('attendance/clock-in'); }
        catch (e) { return null; }
        refreshButton();
        return record;
    }

    // Ca đã xếp của 1 người trong 1 ngày (Quản lý xếp ở trang Xếp ca)
    function shiftsOf(staffId, date) {
        return shiftsCache.filter(s => String(s.staffId) === String(staffId) && s.date === date)
            .sort((a, b) => toMin(a.start) - toMin(b.start));
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

        // Xác nhận = giao ca: chốt giờ ra (máy chủ ghi), hết ca, thoát popup về lại màn hình nhân viên
        $('hoConfirm').addEventListener('click', async () => {
            const btn = $('hoConfirm');
            btn.disabled = true;
            try {
                let busy = 0;
                try { await TablesStore.refresh(); busy = TablesStore.busyCount(); } catch (err) {}
                if (busy && !window.confirm(`Còn ${busy} bàn đang có khách hoặc chưa thanh toán. Vẫn giao ca?`)) return;
                const rec = todayRecord();
                if (rec && !rec.clockOut) {
                    try { record = await Api.post('attendance/clock-out'); }
                    catch (err) { return alert('Không ghi được giờ ra ca: ' + err.message); }
                }
                closeModal();
                refreshButton();
            } finally { btn.disabled = false; }
        });
    }

    async function openHandover() {
        await Promise.all([recordClockIn(), loadShifts()]); // phòng khi chưa có bản ghi hôm nay; lấy ca đã xếp để tính lương
        if (!todayRecord()) return alert('Không tải được dữ liệu chấm công từ máy chủ. Hãy thử lại.');
        buildModal();
        renderModal();
        $('hoBackdrop').classList.add('active');
        const nb = $('navHandover'); if (nb) nb.classList.add('active');
        if (timer) clearInterval(timer);
        timer = setInterval(renderModal, 1000); // giờ ra "bây giờ", tổng giờ và tiền chạy theo thời gian thực
    }

    window.WaiterTimesheet = { recordClockIn, openHandover, refreshButton, todayRecord, HOURLY_RATE };
})();