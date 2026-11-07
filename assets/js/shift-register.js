// ============================================================
// shift-register.js — ĐĂNG KÝ CA LÀM (Thu ngân) — chạy trên Schedule.html
// ------------------------------------------------------------
// Thu ngân tick các KHUNG GIỜ TRỐNG mình rảnh trong 14 ngày tới; bấm "Gửi đăng ký" thì hệ thống TỰ XẾP
// vào lịch chung trong MySQL (bảng shifts, cùng nơi Quản lý xếp ở trang Lịch ca) qua API 'shifts/register', theo quy tắc:
//   - Mỗi người tối đa 1 ca / ngày.
//   - Mỗi khung giờ chỉ nhận tối đa MAX_CASHIER_PER_SLOT thu ngân (đã đủ thì báo "đã đủ người").
//   - Không đăng ký được ca đã bắt đầu / đã qua. Ca do Quản lý xếp thì khóa, không tự sửa.
//   - Bỏ tick ca mình đã đăng ký (chưa bắt đầu) = hủy đăng ký.
// Ca đăng ký có cờ registered:true. Máy chủ kiểm tra lại toàn bộ quy tắc ở trên; trình duyệt chỉ hiển thị.
// ============================================================
(function () {
    'use strict';
    const C = window.Cashier;
    const session = AuthAPI.getSession();
    if (!session || session.roleKey !== 'cashier') return;

    const SLOTS = [
        { key: 'sang', label: 'Ca sáng', start: '06:30', end: '14:00' },
        { key: 'chieu', label: 'Ca chiều', start: '11:00', end: '19:00' },
        { key: 'toi', label: 'Ca tối', start: '17:00', end: '22:00' }
    ];
    const MAX_CASHIER_PER_SLOT = 1;
    const DAYS_AHEAD = 14;
    const DOW = ['CN', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];

    const pad = n => String(n).padStart(2, '0');
    const dateKey = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const toMin = h => { const [a, b] = String(h).split(':').map(Number); return a * 60 + (b || 0); };
    const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
    const mondayOf = d => { const x = new Date(d.getFullYear(), d.getMonth(), d.getDate()); x.setDate(x.getDate() - ((x.getDay() + 6) % 7)); return x; };

    // Dữ liệu lấy từ MySQL qua API (không dùng localStorage)
    let staffList = [], shiftList = [];
    async function refresh() {
        [staffList, shiftList] = await Promise.all([Api.get('staff/roster'), Api.get('shifts')]);
    }

    const myId = () => {
        if (session.staffId != null && staffList.some(s => String(s.id) === String(session.staffId))) return session.staffId;
        const s = staffList.find(x => x.fullName === session.name); return s ? s.id : null;
    };
    const slotOf = sh => SLOTS.find(s => s.start === sh.start && s.end === sh.end);
    const started = (key, slot) => new Date(`${key}T${slot.start}:00`).getTime() <= Date.now();

    function build() {
        const app = document.getElementById('schedule-app'); if (!app || document.getElementById('regPanel')) return;
        const sec = document.createElement('section');
        sec.className = 'cs-panel reg-panel'; sec.id = 'regPanel';
        const anchor = document.getElementById('schMe');
        anchor ? anchor.insertAdjacentElement('afterend', sec) : app.appendChild(sec);
        sec.addEventListener('click', e => {
            if (e.target.id === 'regSubmit') submit();
            else if (e.target.id === 'regToggle') { sec.classList.toggle('collapsed'); renderRegBody(); }
        });
        render();
    }

    let collapsed = false;
    function renderRegBody() { collapsed = document.getElementById('regPanel').classList.contains('collapsed'); render(); }

    function render() {
        const sec = document.getElementById('regPanel'); if (!sec) return;
        const id = myId(), shifts = shiftList, today = new Date();
        const head = `<h3 style="display:flex;justify-content:space-between;align-items:center;gap:8px">Đăng ký ca <button type="button" class="cs-btn line" id="regToggle" style="padding:6px 12px;font-size:12px">${collapsed ? 'Mở' : 'Thu gọn'}</button></h3>`;
        if (collapsed) { sec.innerHTML = head; return; }
        if (id == null) { sec.innerHTML = head + '<p class="cs-note">Không tìm thấy tài khoản của bạn trong danh sách nhân viên nên chưa đăng ký ca được.</p>'; return; }
        let html = head + `<p class="cs-note" style="margin:0 0 12px">Tick những khung giờ bạn rảnh rồi bấm <b>Gửi đăng ký</b> — hệ thống tự xếp vào lịch chung (tối đa 1 ca/ngày, mỗi khung giờ ${MAX_CASHIER_PER_SLOT} thu ngân). Bỏ tick ca đã đăng ký để hủy.</p><div class="reg-days">`;
        for (let i = 0; i < DAYS_AHEAD; i++) {
            const d = addDays(today, i), key = dateKey(d);
            const mine = shifts.find(s => String(s.staffId) === String(id) && s.date === key);
            const mineSlot = mine && slotOf(mine);
            html += `<div class="reg-day ${i === 0 ? 'today' : ''}"><div class="reg-day-h">${DOW[d.getDay()]} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}${i === 0 ? ' (hôm nay)' : ''}</div>`;
            SLOTS.forEach(sl => {
                const isMine = mineSlot && mineSlot.key === sl.key;
                const lockedByManager = isMine && !mine.registered;
                const otherMine = mine && !isMine;               // đã có ca khác trong ngày
                const taken = shifts.filter(s => s.date === key && s.start === sl.start && s.end === sl.end && String(s.staffId) !== String(id) && staffRole(s.staffId) === 'Thu ngân').length >= MAX_CASHIER_PER_SLOT;
                const past = started(key, sl);
                const disabled = past || lockedByManager || (!isMine && (otherMine || taken));
                const note = lockedByManager ? 'Quản lý xếp' : past ? 'đã qua' : otherMine ? 'đã có ca khác' : (!isMine && taken) ? 'đã đủ người' : '';
                html += `<label class="reg-slot ${disabled ? 'locked' : ''}"><input type="checkbox" data-date="${key}" data-slot="${sl.key}" ${isMine ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
                    <span>${sl.label} <small>${sl.start}–${sl.end}${note ? ' • ' + note : ''}</small></span></label>`;
            });
            html += '</div>';
        }
        html += '</div><div style="margin-top:12px"><button type="button" class="cs-btn" id="regSubmit">Gửi đăng ký</button> <span class="cs-msg ok" id="regMsg" hidden></span></div>';
        sec.innerHTML = html;
    }
    const staffRole = sid => { const s = staffList.find(x => String(x.id) === String(sid)); return s ? s.role : ''; };

    async function submit() {
        const id = myId(); if (id == null) return;
        const btn = document.getElementById('regSubmit');
        const shifts = shiftList, ops = [], skipped = [];
        document.querySelectorAll('#regPanel input[type=checkbox]:not(:disabled)').forEach(cb => {
            const key = cb.dataset.date, sl = SLOTS.find(s => s.key === cb.dataset.slot);
            const mine = shifts.find(s => String(s.staffId) === String(id) && s.date === key);
            const isMineSlot = mine && mine.start === sl.start && mine.end === sl.end;
            if (cb.checked && !isMineSlot) {
                if (started(key, sl)) return;
                if (mine) { skipped.push(`${key}: đã có ca khác`); return; }
                const full = shifts.filter(s => s.date === key && s.start === sl.start && s.end === sl.end && staffRole(s.staffId) === 'Thu ngân').length >= MAX_CASHIER_PER_SLOT;
                if (full) { skipped.push(`${key} ${sl.label}: đã đủ người`); return; }
                ops.push({ register: true, date: key, start: sl.start, end: sl.end });
            } else if (!cb.checked && isMineSlot && mine.registered && !started(key, sl)) {
                ops.push({ register: false, date: key, start: sl.start, end: sl.end });
            }
        });
        if (btn) btn.disabled = true;
        let added = 0, removed = 0;
        for (const op of ops) {
            try { await Api.post('shifts/register', op); if (op.register) added++; else removed++; }
            catch (e) { skipped.push(`${op.date}: ${e.message}`); }
        }
        try { await refresh(); } catch (e) { skipped.push('không tải lại được lịch: ' + e.message); }
        render();
        const m = document.getElementById('regMsg');
        if (m) {
            m.textContent = `Đã xếp ${added} ca, hủy ${removed} ca${skipped.length ? ` • bỏ qua ${skipped.length} (${skipped.slice(0, 3).join('; ')}${skipped.length > 3 ? '…' : ''})` : ''}.`;
            m.hidden = false;
        }
    }

    async function init() {
        try { await refresh(); } catch (e) { /* hiện thông báo trong panel bên dưới */ }
        build();
        if (!staffList.length) {
            const sec = document.getElementById('regPanel');
            if (sec) sec.insertAdjacentHTML('beforeend', '<p class="cs-note">Không tải được dữ liệu ca từ máy chủ. Hãy tải lại trang.</p>');
        }
    }
    // Chạy ngay nếu trang đã tải xong (file này được nạp sau khi trang dựng xong)
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
