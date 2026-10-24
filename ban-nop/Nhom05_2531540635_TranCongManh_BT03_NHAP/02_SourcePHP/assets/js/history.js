let currentStatusFilter = 'all';
let searchTerm = '';
let invoiceIdForCancel = null;
let invoicesCache = [];

const methodLabel = { cash: 'Tiền mặt', transfer: 'Chuyển khoản', card: 'Thẻ ATM' };
const methodBadgeClass = { cash: 'badge-cash', transfer: 'badge-transfer', card: 'badge-card' };
function fmtMoney(n) { return `${Math.round(Number(n) || 0).toLocaleString('vi-VN')} đ`; }
function fmtDateTime(value) {
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

async function render() {
    const tbody = document.getElementById('invoiceTableBody');
    try { invoicesCache = await Api.get('orders/invoices?status=all'); }
    catch (err) { tbody.innerHTML = `<tr><td colspan="8"><div class="empty-table-state">${escapeHtml(err.message)}</div></td></tr>`; return; }
    let list = invoicesCache.slice().sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    if (currentStatusFilter !== 'all') list = list.filter(i => i.status === currentStatusFilter);
    if (searchTerm.trim()) {
        const kw = searchTerm.trim().toLowerCase();
        list = list.filter(i => String(i.id).toLowerCase().includes(kw) || String(i.tableName || '').toLowerCase().includes(kw));
    }
    document.getElementById('invoiceCountLabel').innerText = `Tổng cộng ${list.length} hóa đơn`;
    if (!list.length) { tbody.innerHTML = '<tr><td colspan="8"><div class="empty-table-state">Không có hóa đơn phù hợp.</div></td></tr>'; return; }
    tbody.innerHTML = list.map(inv => {
        const summary = (inv.items || []).map(it => `${it.name} x${it.qty}`).join(', ');
        const status = inv.status === 'active'
            ? '<span class="badge badge-active">Hiệu lực</span>'
            : `<span class="badge badge-cancelled">Đã hủy</span><div class="cancel-reason-note">Lý do: ${escapeHtml(inv.cancelReason || '')}${inv.cancelledBy ? ' (' + escapeHtml(inv.cancelledBy) + ')' : ''}</div>`;
        return `<tr><td><b>${escapeHtml(inv.id)}</b></td><td>${escapeHtml(inv.tableName)}</td><td>${fmtDateTime(inv.createdAt)}</td>
            <td style="max-width:220px"><span style="cursor:pointer;color:#7a5c3e;text-decoration:underline" onclick="openDetailModal('${escapeHtml(inv.id)}')">${escapeHtml(summary)}</span></td>
            <td><span class="badge ${methodBadgeClass[inv.paymentMethod] || ''}">${methodLabel[inv.paymentMethod] || escapeHtml(inv.paymentMethod || '—')}</span></td>
            <td><b>${fmtMoney(inv.total)}</b></td><td>${status}</td><td><div style="display:flex;gap:6px">
            <button class="btn-outline" style="padding:6px 10px;font-size:12px" onclick="openDetailModal('${escapeHtml(inv.id)}')">Xem</button>
            ${inv.status === 'active' ? `<button class="btn-danger" style="padding:6px 10px;font-size:12px" onclick="openCancelModal('${escapeHtml(inv.id)}')">Hủy</button>` : ''}</div></td></tr>`;
    }).join('');
}

document.getElementById('statusFilterBar').addEventListener('click', e => {
    const btn = e.target.closest('.topnav-item'); if (!btn) return;
    document.querySelectorAll('#statusFilterBar .topnav-item').forEach(b => b.classList.toggle('active', b === btn));
    currentStatusFilter = btn.dataset.status; render();
});
document.getElementById('searchBox').addEventListener('input', e => { searchTerm = e.target.value; render(); });

function openCancelModal(id) {
    const inv = invoicesCache.find(i => i.id === id); if (!inv) return;
    invoiceIdForCancel = id;
    document.getElementById('cancelModalInvoiceId').innerText = inv.id;
    document.getElementById('cancelModalTotal').innerText = fmtMoney(inv.total);
    document.getElementById('cancelReasonInput').value = '';
    document.getElementById('btnConfirmCancel').disabled = true;
    document.getElementById('cancelModal').classList.add('active');
}
function closeCancelModal() { document.getElementById('cancelModal').classList.remove('active'); invoiceIdForCancel = null; }
document.getElementById('cancelReasonInput').addEventListener('input', e => { document.getElementById('btnConfirmCancel').disabled = !e.target.value.trim(); });
async function confirmCancelInvoice() {
    const inv = invoicesCache.find(i => i.id === invoiceIdForCancel);
    const reason = document.getElementById('cancelReasonInput').value.trim();
    if (!inv || !reason) return;
    const button = document.getElementById('btnConfirmCancel'); button.disabled = true;
    try { await Api.post('orders/invoices/cancel', { orderId: inv.orderId, reason }); }
    catch (err) { alert(err.message); button.disabled = false; return; }
    closeCancelModal(); await render();
}

function openDetailModal(id) {
    const inv = invoicesCache.find(i => i.id === id); if (!inv) return;
    document.getElementById('detailModalTitle').innerText = `Hóa đơn ${inv.id}`;
    document.getElementById('detailModalMeta').innerText = `${inv.tableName} • ${fmtDateTime(inv.createdAt)} • ${methodLabel[inv.paymentMethod] || inv.paymentMethod || '—'}`;
    document.getElementById('detailModalItems').innerHTML = (inv.items || []).map(it => `<tr><td>${escapeHtml(it.name)}</td><td>${it.qty}</td><td>${fmtMoney(it.price * it.qty)}</td></tr>`).join('') +
        `<tr><td colspan="2" style="text-align:right;font-weight:700">Tổng cộng</td><td style="font-weight:700">${fmtMoney(inv.total)}</td></tr>`;
    document.getElementById('detailModalCancelNote').innerHTML = inv.status === 'cancelled'
        ? `<div class="cancel-reason-note" style="margin-bottom:16px">Đã hủy lúc ${fmtDateTime(inv.cancelledAt)}${inv.cancelledBy ? ' bởi ' + escapeHtml(inv.cancelledBy) : ''} — Lý do: ${escapeHtml(inv.cancelReason || '')}</div>` : '';
    document.getElementById('detailModal').classList.add('active');
}
function closeDetailModal() { document.getElementById('detailModal').classList.remove('active'); }
render();
