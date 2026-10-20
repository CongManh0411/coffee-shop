// ops.js — Vận hành: đặt bàn/hàng chờ, khách thân thiết (CRM), khuyến mãi, kho nâng cao, giao hàng.
// Dữ liệu các mục Vận hành được tải và lưu qua API/database.
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const money = n => Math.round(n || 0).toLocaleString('vi-VN') + ' đ';
const val = id => (document.getElementById(id) || {}).value || '';
const body = () => document.getElementById('opsBody');
let tab = 'res';
let opsData = { waitlist: [], customers: [], promotions: [], suppliers: [], stockItems: [], movements: [], deliveries: [] };

function field(id, ph, type = 'text', extra = '') { return `<input id="${id}" type="${type}" placeholder="${ph}" ${extra}>`; }
function table(heads, rows) {
    if (!rows.length) return '<div class="ops-empty">Chưa có dữ liệu.</div>';
    return `<div class="ops-scroll"><table class="ops-table"><tr>${heads.map(h => `<th>${h}</th>`).join('')}</tr>${rows.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('')}</table></div>`;
}
const btn = (fn, arg, label, cls = '') => `<button class="ops-btn sm ${cls}" onclick="${fn}('${arg}')">${label}</button>`;

/* ---------- ĐẶT BÀN & HÀNG CHỜ ---------- */
function viewRes() {
    const wait = opsData.waitlist || [];
    const html = `<div class="ops-card"><h3>Đặt bàn</h3><p>Yêu cầu khách gửi trên trang chủ và yêu cầu tạo tại quầy đều được lưu trong cơ sở dữ liệu.</p><div class="ops-form">${field('rName', 'Tên khách')}${field('rPhone', 'SĐT')}${field('rPax', 'Số người', 'number', 'min=1 max=20')}${field('rTime', '', 'datetime-local')}
        <select id="rTable"><option value="">-- Chưa xếp bàn --</option></select>${field('rNote', 'Ghi chú')}<button onclick="addRes()">Tạo đặt bàn</button></div><p id="reservationError" class="ops-warn" role="status"></p><div id="reservationRows"><p>Đang tải yêu cầu đặt bàn…</p></div></div>
        <div class="ops-card"><h3>Hàng chờ (khách vãng lai)</h3><div class="ops-form">${field('wName', 'Tên khách')}${field('wPax', 'Số người', 'number', 'min=1')}${field('wPhone', 'SĐT')}<button onclick="addWait()">Thêm vào hàng chờ</button></div>
        ${table(['#', 'Khách', 'Người', 'Chờ', ''], wait.map((w, i) => [i + 1, esc(w.name), w.pax, Math.max(0, Math.round((Date.now() - w.at) / 60000)) + ' phút', btn('waitCall', w.id, 'Gọi / xếp bàn', 'green') + btn('waitDel', w.id, 'Bỏ', 'red')]))}</div>`;
    refreshReservations();
    return html;
}
const RES_STATUS = { booked: 'Đã đặt', arrived: 'Đã đến', cancelled: 'Đã hủy', no_show: 'Không đến' };
function renderReservations(reservations) {
    const host = document.getElementById('reservationRows');
    if (!host) return;
    const rows = (reservations || []).map(r => {
        const canUpdate = r.status === 'booked';
        const actions = canUpdate
            ? `<button class="ops-btn sm green" onclick="resSet(${r.id},'arrived')">Đã đến</button> <button class="ops-btn sm red" onclick="resSet(${r.id},'cancelled')">Hủy</button> <button class="ops-btn sm" onclick="resSet(${r.id},'no_show')">Không đến</button>`
            : '';
        return [esc(r.name), esc(r.phone || '—'), r.partySize, esc(r.reservedAt.replace('T', ' ').slice(0, 16)), esc(r.tableName || 'Chưa xếp'), `<span class="ops-tag">${RES_STATUS[r.status] || esc(r.status)}</span>`, esc(r.note || '—'), actions];
    });
    host.innerHTML = rows.length ? table(['Khách', 'SĐT', 'Người', 'Giờ đặt', 'Bàn', 'Trạng thái', 'Ghi chú', ''], rows) : '<div class="ops-empty">Chưa có yêu cầu đặt bàn.</div>';
}
function refreshReservations() {
    Api.get('reservations').then(renderReservations).catch(error => {
        const host = document.getElementById('reservationRows');
        if (host) host.innerHTML = `<p class="ops-warn">${esc(error.message || 'Không tải được yêu cầu đặt bàn.')}</p>`;
    });
    Api.get('tables').then(tables => {
        const select = document.getElementById('rTable');
        if (select) select.innerHTML = '<option value="">-- Chưa xếp bàn --</option>' + tables.map(t => `<option value="${t.id}">${esc(t.name)}</option>`).join('');
    }).catch(() => {});
}
async function addRes() {
    const error = document.getElementById('reservationError');
    if (!val('rName') || !val('rTime') || !val('rPax')) { if (error) error.textContent = 'Nhập tên, số khách và thời gian đặt.'; return; }
    try {
        await Api.post('reservations', { name: val('rName'), phone: val('rPhone'), partySize: val('rPax'), reservedAt: val('rTime'), tableId: val('rTable'), note: val('rNote') });
        go();
    } catch (e) { if (error) error.textContent = e.message || 'Không tạo được đặt bàn.'; }
}
async function resSet(id, status) {
    try { await Api.put('reservations&id=' + encodeURIComponent(id), { status }); refreshReservations(); }
    catch (e) { alert(e.message || 'Không cập nhật được trạng thái.'); }
}
async function addWait() { if (!val('wName') || !val('wPax')) return alert('Nhập tên khách và số người.'); await opsMutate('POST', { action: 'waitlist', name: val('wName'), phone: val('wPhone'), partySize: val('wPax') }); }
async function waitDel(id) { await opsMutate('DELETE', { action: 'waitlist', id }); }
async function waitCall(id) { const w = opsData.waitlist.find(x => String(x.id) === String(id)); if (w) alert(`Mời khách ${w.name}${w.phone ? ' (' + w.phone + ')' : ''} vào bàn.`); await opsMutate('PUT', { action: 'waitlist', id, status: 'seated' }); }

/* ---------- KHÁCH THÂN THIẾT ---------- */
const tier = p => p >= 300 ? 'Vàng' : p >= 100 ? 'Bạc' : 'Đồng';
function viewCrm() {
    const l = opsData.customers || [], m = new Date().getMonth() + 1;
    return `<div class="ops-card"><h3>Khách thân thiết</h3><p style="font-size:13px;color:#7a6652">Quy ước: 10.000đ = 1 điểm. Hạng: Đồng &lt;100, Bạc ≥100, Vàng ≥300 điểm.</p>
        <div class="ops-form">${field('cName', 'Tên khách')}${field('cPhone', 'SĐT')}${field('cBirth', 'Sinh nhật', 'date')}<button onclick="addCust()">Thêm khách</button></div>
        ${table(['Khách', 'SĐT', 'Sinh nhật', 'Điểm', 'Hạng', 'Chi tiêu', ''], l.map(c => [esc(c.name), esc(c.phone), c.birth ? (+c.birth.slice(5, 7) === m ? '<span class="ops-warn">' + c.birth.slice(5) + ' 🎂 tháng này – gửi ưu đãi</span>' : c.birth.slice(5)) : '—', c.points, `<span class="ops-tag">${tier(c.points)}</span>`, money(c.spent), btn('custPay', c.id, '+ Cộng điểm', 'green') + btn('custDel', c.id, 'Xóa', 'red')]))}</div>`;
}
async function addCust() { if (!val('cName') || !val('cPhone')) return alert('Nhập tên và SĐT.'); await opsMutate('POST', { action: 'customer', name: val('cName'), phone: val('cPhone'), birth: val('cBirth') }); }
async function custPay(id) { const amount = Number(prompt('Số tiền hóa đơn của khách (đ):')); if (!Number.isSafeInteger(amount) || amount <= 0) return; await opsMutate('PUT', { action: 'customer-points', id, amount }); }
async function custDel(id) { if (confirm('Xóa khách này?')) await opsMutate('DELETE', { action: 'customer', id }); }

/* ---------- KHUYẾN MÃI ---------- */
function viewPromo() {
    const l = opsData.promotions || [];
    return `<div class="ops-card"><h3>Mã khuyến mãi</h3><p style="font-size:13px;color:#7a6652">Mã được lưu trong database, kiểm tra hạn dùng và giới hạn lượt khi thanh toán. Các mã mẫu GIAM10/GIAM20/GIAM5K/GIAM20K cũng được giữ lại.</p>
        <div class="ops-form">${field('pCode', 'MÃ (vd HAPPY15)')}${field('pLabel', 'Tên chương trình')}<select id="pType"><option value="pct">Giảm %</option><option value="vnd">Giảm đ</option></select>${field('pVal', 'Giá trị', 'number')}
        <label>Từ ${field('pFrom', '', 'date')}</label><label>Đến ${field('pTo', '', 'date')}</label>${field('pMax', 'Giới hạn lượt', 'number')}${field('pH1', 'Từ giờ (0-23)', 'number')}${field('pH2', 'Đến giờ', 'number')}<button onclick="addPromo()">Tạo mã</button></div>
        ${table(['Mã', 'Chương trình', 'Giảm', 'Hạn', 'Lượt', 'Giờ', ''], l.map(p => [`<b>${esc(p.code)}</b>`, esc(p.label), p.type === 'pct' ? p.value + '%' : money(p.value), (p.from || '…') + ' → ' + (p.to || '…'), `${p.used || 0}/${p.maxUses || '∞'}`, p.hourFrom !== '' && p.hourFrom != null ? `${p.hourFrom}h–${p.hourTo}h` : 'Cả ngày', btn('promoTog', p.id, p.active === false ? 'Bật' : 'Tắt') + btn('promoDel', p.id, 'Xóa', 'red')]))}</div>`;
}
async function addPromo() { const code = val('pCode').trim().toUpperCase(); if (!code || !+val('pVal')) return alert('Nhập mã và giá trị giảm.'); await opsMutate('POST', { action: 'promotion', code, label: val('pLabel') || code, type: val('pType'), value: val('pVal'), from: val('pFrom'), to: val('pTo'), maxUses: val('pMax') || 0, hourFrom: val('pH1'), hourTo: val('pH2') }); }
async function promoTog(id) { await opsMutate('PUT', { action: 'promotion', id }); }
async function promoDel(id) { await opsMutate('DELETE', { action: 'promotion', id }); }

/* ---------- KHO NÂNG CAO ---------- */
function viewStock() {
    const items = opsData.stockItems || [], sup = opsData.suppliers || [], mv = opsData.movements || [];
    const movementLabel = { import: 'Nhập', export: 'Xuất', waste: 'Hao hụt' };
    const cost = {};
    mv.filter(m => m.type === 'import').forEach(m => { const c = cost[m.item] || (cost[m.item] = { q: 0, v: 0 }); c.q += m.qty; c.v += m.qty * m.price; });
    const today = new Date().toISOString().slice(0, 10), soon = new Date(Date.now() + 7 * 864e5).toISOString().slice(0, 10);
    const expLabel = exp => exp ? (exp < today ? `<span class="ops-warn">${esc(exp)} (hết hạn)</span>` : exp <= soon ? `<span class="ops-warn">${esc(exp)} (sắp hết)</span>` : esc(exp)) : '—';
    return `<div class="ops-card"><h3>Nhà cung cấp</h3><div class="ops-form">${field('sName', 'Tên NCC')}${field('sPhone', 'SĐT')}<button onclick="addSup()">Thêm NCC</button></div>
        ${table(['NCC', 'SĐT', ''], sup.map(s => [esc(s.name), esc(s.phone), btn('supDel', s.id, 'Xóa', 'red')]))}</div>
        <div class="ops-card"><h3>Mặt hàng kho</h3><p>Kho vật tư được lưu trong database, độc lập với tồn kho thành phẩm trên màn bán hàng.</p><div class="ops-form">${field('stockName', 'Tên mặt hàng')}${field('stockUnit', 'Đơn vị (kg, hộp…)')}${field('stockMin', 'Mức tồn tối thiểu', 'number', 'min=0 step=1')}<button onclick="addStockItem()">Thêm mặt hàng</button></div>
        ${table(['Mặt hàng', 'Đơn vị', 'Tồn hiện tại', 'Mức tối thiểu'], items.map(i => [esc(i.name), esc(i.unit), i.stock, i.minLevel]))}</div>
        <div class="ops-card"><h3>Phiếu nhập / xuất / hao hụt</h3><div class="ops-form"><select id="mType"><option value="import">Nhập</option><option value="export">Xuất</option><option value="waste">Hao hụt</option></select>
        <select id="mItem">${items.map(i => `<option value="${i.id}">${esc(i.name)} (${esc(i.unit)})</option>`).join('')}</select>${field('mQty', 'Số lượng', 'number', 'min=0.001 step=0.001')}${field('mPrice', 'Đơn giá nhập', 'number', 'min=0')}
        <select id="mSup"><option value="">-- NCC --</option>${sup.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('')}</select><label>Hạn dùng ${field('mExp', '', 'date')}</label>${field('mNote', 'Ghi chú / lý do')}<button onclick="addMove()">Lập phiếu</button></div>
        <p style="font-size:13px">Giá nhập bình quân: ${Object.keys(cost).map(k => `<b>${esc(k)}</b> ${money(cost[k].v / cost[k].q)}`).join(' · ') || 'chưa có phiếu nhập'}</p>
        ${table(['Ngày', 'Loại', 'Mặt hàng', 'SL', 'Đơn giá', 'NCC', 'Hạn dùng', 'Ghi chú'], mv.map(m => [esc(m.date.slice(0, 10)), movementLabel[m.type] || esc(m.type), esc(m.item), m.qty, m.price ? money(m.price) : '—', esc(m.sup || '—'), expLabel(m.exp), esc(m.note)]))}</div>`;
}
async function addSup() { if (!val('sName')) return alert('Nhập tên nhà cung cấp.'); await opsMutate('POST', { action: 'supplier', name: val('sName'), phone: val('sPhone') }); }
async function supDel(id) { if (confirm('Ngừng sử dụng nhà cung cấp này?')) await opsMutate('DELETE', { action: 'supplier', id }); }
async function addStockItem() { if (!val('stockName') || !val('stockUnit')) return alert('Nhập tên mặt hàng và đơn vị.'); await opsMutate('POST', { action: 'stock-item', name: val('stockName'), unit: val('stockUnit'), minLevel: val('stockMin') || 0 }); }
async function addMove() {
    const quantity = Number(val('mQty'));
    if (!Number.isFinite(quantity) || quantity <= 0) return alert('Nhập số lượng lớn hơn 0.');
    await opsMutate('POST', { action: 'stock-movement', stockId: val('mItem'), type: val('mType'), quantity, unitCost: val('mPrice') || 0, supplierId: val('mSup'), expiresOn: val('mExp'), note: val('mNote') });
}

/* ---------- GIAO HÀNG (GrabFood / ShopeeFood / BeFood) ---------- */
const PLATFORMS = { GrabFood: 20, ShopeeFood: 20, BeFood: 18 }; // % hoa hồng mặc định, chỉnh tại đây
const DELIVERY_STATUS = { new: 'Mới', making: 'Đang làm', delivering: 'Đang giao', completed: 'Hoàn tất', cancelled: 'Hủy' };
function viewDeliv() {
    const l = opsData.deliveries || [];
    return `<div class="ops-card"><h3>Đơn từ ứng dụng giao hàng</h3><p style="font-size:13px;color:#7a6652">Đơn được nhập tay và lưu vào database. Webhook tự động và đồng bộ doanh thu chưa được kết nối.</p>
        <div class="ops-form"><select id="dPlat">${Object.keys(PLATFORMS).map(p => `<option>${p}</option>`).join('')}</select>${field('dCode', 'Mã đơn / vận đơn')}${field('dName', 'Khách')}${field('dItems', 'Món (vd: 2 Cà phê sữa đá)')}${field('dTotal', 'Tổng tiền', 'number')}<button onclick="addDeliv()">Nhận đơn</button></div>
        ${table(['Nền tảng', 'Mã đơn', 'Khách', 'Món', 'Tổng', 'Hoa hồng ước tính', 'Trạng thái', ''], l.map(d => {
            const status = DELIVERY_STATUS[d.status] || d.status;
            const next = { new: 'making', making: 'delivering', delivering: 'completed' }[d.status];
            return [esc(d.plat), esc(d.code), esc(d.name), esc(d.items), money(d.total), money(d.total * (PLATFORMS[d.plat] || 0) / 100), `<span class="ops-tag">${esc(status)}</span>`, next ? btn('delNext', d.id + '|' + next, '→ ' + DELIVERY_STATUS[next], 'green') + btn('delCancel', d.id, 'Hủy', 'red') : ''];
        }))}</div>`;
}
async function addDeliv() { if (!val('dCode') || !+val('dTotal')) return alert('Nhập mã đơn và tổng tiền.'); await opsMutate('POST', { action: 'delivery', platform: val('dPlat'), code: val('dCode'), name: val('dName'), items: val('dItems'), total: val('dTotal') }); }
async function delNext(arg) { const [id, status] = arg.split('|'); await opsMutate('PUT', { action: 'delivery', id, status: ({ making: 'making', delivering: 'delivering', completed: 'completed' })[status] }); }
async function delCancel(id) { await opsMutate('PUT', { action: 'delivery', id, status: 'cancelled' }); }

async function reloadOpsData() {
    try { opsData = await Api.get('ops'); go(); }
    catch (error) { body().innerHTML = `<div class="ops-card ops-warn">${esc(error.message || 'Không tải được dữ liệu vận hành.')}</div>`; }
}
async function opsMutate(method, payload) {
    try {
        if (method === 'POST') await Api.post('ops', payload);
        else if (method === 'PUT') await Api.put('ops', payload);
        else await Api.call('ops', 'DELETE', payload);
        await reloadOpsData();
    } catch (error) { alert(error.message || 'Không lưu được thay đổi.'); }
}
function go() { const f = { res: viewRes, crm: viewCrm, promo: viewPromo, stock: viewStock, deliv: viewDeliv }[tab]; body().innerHTML = f(); }
document.getElementById('opsTabs').addEventListener('click', e => { const b = e.target.closest('[data-tab]'); if (!b) return; tab = b.dataset.tab; document.querySelectorAll('#opsTabs .topnav-item').forEach(x => x.classList.toggle('active', x === b)); go(); });
go();
reloadOpsData();
