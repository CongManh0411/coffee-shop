// ============================================================
// shift-register.js — ĐĂNG KÝ CA LÀM (Thu ngân) — chạy trên trang Lịch ca
// ------------------------------------------------------------
// Thu ngân tick các KHUNG GIỜ TRỐNG mình rảnh trong 14 ngày tới; bấm "Gửi đăng ký" thì MÁY CHỦ TỰ XẾP
// vào lịch chung (bảng shifts, cùng nơi Quản lý xếp ở trang Xếp ca) — API 'shifts/register', theo quy tắc:
//   - Mỗi người tối đa 1 ca / ngày.
//   - Mỗi khung giờ chỉ nhận tối đa `maxPerSlot` thu ngân (đã đủ thì báo "đã đủ người").
//   - Không đăng ký được ca đã bắt đầu / đã qua. Ca do Quản lý xếp thì khóa, không tự sửa.
//   - Bỏ tick ca mình đã đăng ký (chưa bắt đầu) = hủy đăng ký.
// Máy chủ kiểm tra lại toàn bộ quy tắc; trình duyệt không lưu gì (không dùng localStorage / sessionStorage).
// ============================================================
(function () {
    'use strict';
    const session = AuthAPI.getSession();
    if (!session || session.roleKey !== 'cashier') return;

    const DOW = ['CN', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const pad = n => String(n).padStart(2, '0');
    const dateKey = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };

    let state = null;          // { days, slots, mine, full, maxPerSlot } từ API 'shifts/register'
    let loadError = '';
    let message = '';          // kết quả lần gửi gần nhất
    let collapsed = false;
    let busy = false;

    const started = (key, slot) => new Date(`${key}T${slot.start}:00`).getTime() <= Date.now();
    const slotOf = sh => state.slots.find(s => s.start === sh.start && s.end === sh.end);

    async function load() {
        try { state = await Api.get('shifts/register'); loadError = ''; }
        catch (err) { loadError = err.message || 'Không tải được lịch đăng ký.'; }
    }

    function build() {
        const app = document.getElementById('schedule-app'); if (!app || document.getElementById('regPanel')) return;
        const sec = document.createElement('section');
        sec.className = 'cs-panel reg-panel'; sec.id = 'regPanel';
        const anchor = document.getElementById('schMe');
        anchor ? anchor.insertAdjacentElement('afterend', sec) : app.appendChild(sec);
        sec.addEventListener('click', e => {
            if (e.target.id === 'regSubmit') submit();
            else if (e.target.id === 'regToggle') { collapsed = !collapsed; render(); }
        });
        render();
    }

    function render() {
        const sec = document.getElementById('regPanel'); if (!sec) return;
        const head = `<h3 style="display:flex;justify-content:space-between;align-items:center;gap:8px">Đăng ký ca <button type="button" class="cs-btn line" id="regToggle" style="padding:6px 12px;font-size:12px">${collapsed ? 'Mở' : 'Thu gọn'}</button></h3>`;
        if (collapsed) { sec.innerHTML = head; return; }
        if (!state) { sec.innerHTML = head + `<p class="cs-note">${esc(loadError || 'Đang tải lịch đăng ký…')}</p>`; return; }

        const today = new Date();
        let html = head + `<p class="cs-note" style="margin:0 0 12px">Tick những khung giờ bạn rảnh rồi bấm <b>Gửi đăng ký</b> — hệ thống tự xếp vào lịch chung (tối đa 1 ca/ngày, mỗi khung giờ ${state.maxPerSlot} thu ngân). Bỏ tick ca đã đăng ký để hủy.</p><div class="reg-days">`;
        for (let i = 0; i < state.days; i++) {
            const d = addDays(today, i), key = dateKey(d);
            const mine = state.mine.find(s => s.date === key);
            const mineSlot = mine && slotOf(mine);
            html += `<div class="reg-day ${i === 0 ? 'today' : ''}"><div class="reg-day-h">${DOW[d.getDay()]} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}${i === 0 ? ' (hôm nay)' : ''}</div>`;
            state.slots.forEach(sl => {
                const isMine = mineSlot && mineSlot.key === sl.key;
                const lockedByManager = isMine && !mine.registered;
                const otherMine = mine && !isMine;               // đã có ca khác trong ngày
                const taken = state.full.includes(`${key}|${sl.start}|${sl.end}`);
                const past = started(key, sl);
                const disabled = busy || past || lockedByManager || (!isMine && (otherMine || taken));
                const note = lockedByManager ? 'Quản lý xếp' : past ? 'đã qua' : otherMine ? 'đã có ca khác' : (!isMine && taken) ? 'đã đủ người' : '';
                html += `<label class="reg-slot ${disabled ? 'locked' : ''}"><input type="checkbox" data-date="${key}" data-slot="${esc(sl.key)}" ${isMine ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
                    <span>${esc(sl.label)} <small>${sl.start}–${sl.end}${note ? ' • ' + note : ''}</small></span></label>`;
            });
            html += '</div>';
        }
        html += `</div><div style="margin-top:12px"><button type="button" class="cs-btn" id="regSubmit" ${busy ? 'disabled' : ''}>Gửi đăng ký</button> <span class="cs-msg ok" id="regMsg" ${message ? '' : 'hidden'}>${esc(message)}</span></div>`;
        sec.innerHTML = html;
    }

    async function submit() {
        if (!state || busy) return;
        const changes = [];
        document.querySelectorAll('#regPanel input[type=checkbox]:not(:disabled)').forEach(cb => {
            const key = cb.dataset.date, sl = state.slots.find(s => s.key === cb.dataset.slot);
            if (!sl) return;
            const mine = state.mine.find(s => s.date === key);
            const isMineSlot = !!mine && mine.start === sl.start && mine.end === sl.end;
            if (cb.checked !== isMineSlot) changes.push({ date: key, start: sl.start, end: sl.end, checked: cb.checked });
        });
        if (!changes.length) { message = 'Bạn chưa thay đổi lựa chọn nào.'; render(); return; }

        busy = true; message = ''; render();
        try {
            const r = await Api.post('shifts/register', { changes });
            const skipped = r.skipped || [];
            message = `Đã xếp ${r.added} ca, hủy ${r.removed} ca${skipped.length ? ` • bỏ qua ${skipped.length} (${skipped.slice(0, 3).join('; ')}${skipped.length > 3 ? '…' : ''})` : ''}.`;
        } catch (err) {
            message = 'Không gửi được đăng ký: ' + err.message;
        }
        await load();
        busy = false;
        render();
    }

    async function init() {
        build();
        await load();
        render();
    }
    // Chạy ngay nếu trang đã tải xong (file này được nạp ở cuối trang Lịch ca)
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
