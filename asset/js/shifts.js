// shifts.js — XẾP LỊCH CA (Shifts.html, vai trò Quản lý). Dữ liệu lưu trong DB (API 'shifts'); đồng thời chép sang 'coffee_shifts_v1' cho trang Lịch ca làm của nhân viên đọc.
// Ca: { id, staffId, date: 'YYYY-MM-DD', start: 'HH:MM', end: 'HH:MM' }. Kiểm tra: giờ trong 6h30–22h, kết thúc sau bắt đầu, không trùng ca khác của cùng người.
(function () {
    'use strict';
    const SHIFTS_KEY = 'coffee_shifts_v1';
    const OPEN = 6 * 60 + 30, CLOSE = 22 * 60;
    const DAYS = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN'];
    const $ = id => document.getElementById(id);
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const pad = n => String(n).padStart(2, '0');
    const toMin = h => { const [a, b] = String(h).split(':').map(Number); return (a || 0) * 60 + (b || 0); };
    const dateKey = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const fmtDate = d => `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
    const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
    const mondayOf = d => { const x = new Date(d.getFullYear(), d.getMonth(), d.getDate()); x.setDate(x.getDate() - ((x.getDay() + 6) % 7)); return x; };
    const clock = m => { const h = Math.floor(m / 60), r = m % 60; return r ? `${h}h${pad(r)}` : `${h}h`; };
    const hours = m => (m / 60).toFixed(m % 60 ? 1 : 0) + 'h';

    // Nhân viên lấy từ API (chỉ Quản lý gọi được). Chấp nhận vài tên trường khác nhau của staff_list().
    const isManager = x => x.roleKey === 'manager' || x.role === 'Quản lý' || x.roleLabel === 'Quản lý';
    async function fetchStaff() {
        const list = await Api.get('staff');
        return list.filter(x => !isManager(x) && (x.status === undefined || Number(x.status) !== 0))
            .map(x => ({ id: x.id ?? x.staffId, fullName: x.fullName ?? x.name ?? x.full_name, role: x.role ?? x.roleLabel ?? '' }));
    }
    async function fetchShifts() {
        const list = await Api.get('shifts');
        try { localStorage.setItem(SHIFTS_KEY, JSON.stringify(list)); } catch (e) {}
        return list;
    }
    async function reload(silent) {
        try { [staff, shifts] = await Promise.all([fetchStaff(), fetchShifts()]); }
        catch (e) { if (silent !== true) alert('Không tải được lịch ca: ' + e.message); }
        render();
    }
    const overlap = (a, b) => toMin(a.start) < toMin(b.end) && toMin(b.start) < toMin(a.end);

    let weekStart = mondayOf(new Date()), staff = [], shifts = [], ed = null;
    const weekKey = () => dateKey(weekStart);

    function render() {
        const days = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));
        const today = dateKey(new Date());
        $('shMonth').textContent = `Tháng ${addDays(weekStart, 3).getMonth() + 1}/${addDays(weekStart, 3).getFullYear()}`;
        $('shRange').textContent = `${fmtDate(weekStart)} – ${fmtDate(addDays(weekStart, 6))}`;

        const head = `<thead><tr><th class="sch-staffcol">Nhân viên</th>${days.map((d, i) => `<th class="${dateKey(d) === today ? 'today' : ''}">${DAYS[i]}<small>${fmtDate(d)}</small></th>`).join('')}<th class="shf-total">Tổng giờ</th></tr></thead>`;
        const rows = staff.map(s => {
            let total = 0;
            const cells = days.map(d => {
                const key = dateKey(d);
                const mine = shifts.filter(x => String(x.staffId) === String(s.id) && x.date === key).sort((a, b) => toMin(a.start) - toMin(b.start));
                mine.forEach(x => total += toMin(x.end) - toMin(x.start));
                return `<td class="${key === today ? 'today' : ''}">${mine.map(x => `<button type="button" class="sch-shift shf-shift" data-edit="${x.id}"><span class="sch-time">${clock(toMin(x.start))} – ${clock(toMin(x.end))} <small>(${hours(toMin(x.end) - toMin(x.start))})</small></span></button>`).join('')}<button type="button" class="shf-add" data-staff="${s.id}" data-date="${key}">+ Thêm ca</button></td>`;
            }).join('');
            return `<tr><th class="sch-staffcol"><b>${esc(s.fullName)}</b><small>${esc(s.role || '')}</small></th>${cells}<td class="shf-total">${total ? hours(total) : '—'}</td></tr>`;
        }).join('');
        const foot = `<tfoot><tr><td>Số người / ngày</td>${days.map(d => {
            const key = dateKey(d), ids = new Set(shifts.filter(x => x.date === key).map(x => String(x.staffId)));
            return ids.size ? `<td>${ids.size} người</td>` : '<td class="warn">Chưa có ai</td>';
        }).join('')}<td></td></tr></tfoot>`;
        $('shTable').innerHTML = head + `<tbody>${rows || '<tr><td colspan="9" class="sch-none">Chưa có nhân viên. Thêm ở trang Nhân viên.</td></tr>'}</tbody>` + (staff.length ? foot : '');
    }

    function err(m) { $('shError').textContent = m; $('shError').hidden = !m; }
    function openModal(o) {
        const s = staff.find(x => String(x.id) === String(o.staffId));
        if (!s) return;
        ed = o;
        const d = new Date(o.date + 'T00:00:00');
        $('shTitle').textContent = o.id ? 'Sửa ca' : 'Thêm ca';
        $('shSub').textContent = `${s.fullName} · ${DAYS[(d.getDay() + 6) % 7]} ${fmtDate(d)}`;
        $('shStart').value = o.start || '06:30'; $('shEnd').value = o.end || '14:00';
        $('shDelete').hidden = !o.id; err('');
        $('shModal').classList.add('active'); $('shStart').focus();
    }
    const closeModal = () => { $('shModal').classList.remove('active'); ed = null; };

    async function submit(e) {
        e.preventDefault();
        const start = $('shStart').value, end = $('shEnd').value;
        if (!start || !end) return err('Vui lòng nhập giờ bắt đầu và kết thúc.');
        if (toMin(end) <= toMin(start)) return err('Giờ kết thúc phải sau giờ bắt đầu.');
        if (toMin(start) < OPEN || toMin(end) > CLOSE) return err('Ca phải nằm trong giờ mở cửa 6h30 – 22h.');
        const cand = { start, end };
        const clash = shifts.find(x => x.id !== ed.id && String(x.staffId) === String(ed.staffId) && x.date === ed.date && overlap(x, cand));
        if (clash) return err(`Trùng với ca ${clock(toMin(clash.start))} – ${clock(toMin(clash.end))} của người này.`);
        try {
            if (ed.id) await Api.put('shifts&id=' + ed.id, { start, end });
            else await Api.post('shifts', { staffId: Number(ed.staffId), date: ed.date, start, end });
        } catch (ex) { return err(ex.message); }   // máy chủ cũng kiểm tra lại các luật trên
        closeModal();
        await reload();
    }

    async function copyPrev() {
        try {
            const r = await Api.post('shifts/copy', { weekStart: weekKey() });
            await reload();
            alert(r.added ? `Đã sao chép ${r.added} ca.` : 'Các ca tuần này đã có sẵn, không sao chép thêm.');
        } catch (ex) { alert(ex.message); }
    }
    async function clearWeek() {
        const keys = Array.from({ length: 7 }, (_, i) => dateKey(addDays(weekStart, i)));
        const n = shifts.filter(x => keys.includes(x.date)).length;
        if (!n) return alert('Tuần này chưa có ca nào.');
        if (!confirm(`Xóa ${n} ca của tuần này?`)) return;
        try { await Api.post('shifts/clear', { weekStart: weekKey() }); await reload(); }
        catch (ex) { alert(ex.message); }
    }

    document.addEventListener('DOMContentLoaded', () => {
        reload();
        $('shPrev').addEventListener('click', () => { weekStart = addDays(weekStart, -7); render(); });
        $('shNext').addEventListener('click', () => { weekStart = addDays(weekStart, 7); render(); });
        $('shThis').addEventListener('click', () => { weekStart = mondayOf(new Date()); render(); });
        $('shCopy').addEventListener('click', copyPrev);
        $('shClear').addEventListener('click', clearWeek);
        $('shTable').addEventListener('click', e => {
            const a = e.target.closest('[data-staff]'), s = e.target.closest('[data-edit]');
            if (a) openModal({ id: null, staffId: a.dataset.staff, date: a.dataset.date });
            else if (s) { const x = shifts.find(y => String(y.id) === s.dataset.edit); if (x) openModal({ ...x }); }
        });
        $('shForm').addEventListener('submit', submit);
        $('shCancel').addEventListener('click', closeModal);
        $('shDelete').addEventListener('click', async () => {
            if (!ed || !ed.id) return;
            try { await Api.del('shifts&id=' + ed.id); closeModal(); await reload(); }
            catch (ex) { err(ex.message); }
        });
        $('shModal').addEventListener('click', e => { if (e.target === $('shModal')) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
        setInterval(() => { if (!ed) reload(true); }, 15000); // quản lý khác sửa lịch -> tự cập nhật (không làm gián đoạn lúc đang mở hộp thoại)
    });
})();