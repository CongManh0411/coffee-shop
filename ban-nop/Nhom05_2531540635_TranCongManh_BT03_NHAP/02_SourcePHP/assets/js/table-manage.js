// table-manage.js — Trang Quản lý bàn (vai trò Quản lý): chỉ thêm / sửa tên / xóa bàn.
// Danh sách bàn lấy từ API (bảng dining_tables); trạng thái bàn vẫn dùng key 'coffee_tables_v1' với trang Bàn của Phục vụ (table-order.js), Bếp và Thanh toán của Phục vụ,
// nên bàn thêm/sửa/xóa ở đây sẽ hiện ngay trên sơ đồ bàn của Phục vụ.
(function () {
    const TABLES_KEY = 'coffee_tables_v1';

    const $ = id => document.getElementById(id);
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const norm = s => String(s).trim().replace(/\s+/g, ' ').toLowerCase();

    const STATUS = {
        empty: { text: 'Bàn trống', badge: 'badge-active' },
        occupied: { text: 'Đang phục vụ', badge: 'badge-serving' },
        pending: { text: 'Chờ thanh toán', badge: 'badge-pending' }
    };

    // Danh sách bàn (id + tên) nằm trong DB. Trạng thái bàn (đang phục vụ, món...) vẫn ở localStorage
    // cho tới khi chuyển đơn hàng vào DB, nên sau mỗi lần lấy danh sách từ API ta trộn vào localStorage:
    // giữ nguyên trạng thái bàn cũ, thêm bàn mới (trống), bỏ bàn đã bị xóa, cập nhật tên.
    function readLocal() {
        try { const s = JSON.parse(localStorage.getItem(TABLES_KEY)); if (Array.isArray(s)) return s; } catch (e) { }
        return [];
    }
    function writeLocal(list) {
        try { localStorage.setItem(TABLES_KEY, JSON.stringify(list)); } catch (e) { }
    }
    async function pull() {
        const apiList = await Api.get('tables');
        const local = readLocal();
        const extra = local.filter(t => t.type); // đơn mang đi/giao hàng của Thu ngân: giữ nguyên
        const merged = apiList.map(a => {
            const old = local.find(t => !t.type && Number(t.id) === a.id);
            return old ? Object.assign({}, old, { id: a.id, name: a.name })
                : { id: a.id, name: a.name, status: 'empty', time: '', total: 0, items: [] };
        });
        writeLocal(merged.concat(extra));
        return merged;
    }

    let editingId = null;   // null = đang thêm bàn mới
    let deletingId = null;
    let current = [];       // danh sách bàn mới nhất từ máy chủ

    // ---------- Hiển thị danh sách ----------
    async function refresh(silent) {
        try { current = await pull(); }
        catch (err) { if (silent !== true) alert('Không tải được danh sách bàn: ' + err.message); }
        render();
    }
    function render() {
        const list = current;
        const local = readLocal();
        list.forEach(t => { const o = local.find(x => !x.type && Number(x.id) === t.id); t.status = o ? o.status : 'empty'; t.time = o ? o.time : ''; });
        const count = s => list.filter(t => t.status === s).length;
        $('tableSummary').textContent = list.length
            ? `Tổng cộng ${list.length} bàn — ${count('empty')} trống, ${count('occupied')} đang phục vụ, ${count('pending')} chờ thanh toán`
            : 'Tổng cộng 0 bàn';

        const tbody = $('tableManageBody');
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="4"><div class="empty-table-state">Chưa có bàn nào. Bấm "+ Thêm bàn" để bắt đầu.</div></td></tr>';
            return;
        }
        tbody.innerHTML = list.map(t => {
            const st = STATUS[t.status] || STATUS.empty;
            const canDelete = t.status === 'empty';
            return `<tr>
                <td><b>${esc(t.name)}</b></td>
                <td><span class="badge ${st.badge}">${st.text}</span></td>
                <td>${t.time ? esc(t.time) : '—'}</td>
                <td><div class="tm-row-actions">
                    <button type="button" class="btn-outline" data-act="edit" data-id="${t.id}">Sửa</button>
                    <button type="button" class="btn-danger" data-act="delete" data-id="${t.id}" ${canDelete ? '' : 'disabled title="Chỉ xóa được bàn đang trống"'}>Xóa</button>
                </div></td>
            </tr>`;
        }).join('');
    }

    // ---------- Thêm / sửa ----------
    function suggestName(list) {
        let max = 0;
        list.forEach(t => { const m = String(t.name).match(/(\d+)\s*$/); if (m) max = Math.max(max, Number(m[1])); });
        let n = Math.max(max, list.length) + 1, name;
        do { name = 'Bàn ' + String(n++).padStart(2, '0'); } while (list.some(t => norm(t.name) === norm(name)));
        return name;
    }

    function showNameError(msg) {
        $('tableNameError').textContent = msg;
        $('tableNameError').hidden = !msg;
    }

    function openTableModal(id) {
        const list = current;
        editingId = id ?? null;
        const t = editingId === null ? null : list.find(x => x.id === editingId);
        if (editingId !== null && !t) { render(); return; }
        $('tableModalTitle').textContent = t ? 'Sửa bàn' : 'Thêm bàn';
        $('tableModalSub').textContent = t ? `Đổi tên cho ${t.name}.` : 'Nhập tên bàn mới.';
        $('btnTableSave').textContent = t ? 'Lưu thay đổi' : 'Thêm bàn';
        $('tableNameInput').value = t ? t.name : suggestName(list);
        showNameError('');
        $('tableModal').classList.add('active');
        $('tableNameInput').focus();
        $('tableNameInput').select();
    }
    function closeTableModal() {
        $('tableModal').classList.remove('active');
        editingId = null;
    }

    async function submitTable(e) {
        e.preventDefault();
        const name = $('tableNameInput').value.trim().replace(/\s+/g, ' ');
        if (!name) return showNameError('Vui lòng nhập tên bàn.');
        if (current.some(t => t.id !== editingId && norm(t.name) === norm(name))) return showNameError(`Đã có bàn tên "${name}". Hãy chọn tên khác.`);
        try {
            if (editingId === null) await Api.post('tables', { name });
            else await Api.put('tables&id=' + editingId, { name });
        } catch (err) { return showNameError(err.message); }
        closeTableModal();
        await refresh();
    }

    // ---------- Xóa ----------
    function localStatus(id) {
        const t = readLocal().find(x => !x.type && Number(x.id) === id);
        return t ? t.status : 'empty';
    }
    function openDeleteModal(id) {
        const t = current.find(x => x.id === id);
        if (!t) { refresh(); return; }
        if (localStatus(id) !== 'empty') { alert(`${t.name} đang có khách nên chưa thể xóa.`); render(); return; }
        deletingId = id;
        $('deleteModalName').textContent = t.name;
        $('deleteModal').classList.add('active');
    }
    function closeDeleteModal() {
        $('deleteModal').classList.remove('active');
        deletingId = null;
    }
    async function confirmDelete() {
        const t = current.find(x => x.id === deletingId);
        if (t && localStatus(t.id) !== 'empty') { closeDeleteModal(); alert(`${t.name} vừa có khách nên chưa thể xóa.`); render(); return; }
        try { await Api.del('tables&id=' + deletingId); }
        catch (err) { closeDeleteModal(); alert('Không xóa được bàn: ' + err.message); return; }
        closeDeleteModal();
        await refresh();
    }

    // ---------- Gắn sự kiện ----------
    document.addEventListener('DOMContentLoaded', () => {
        refresh();
        $('btnAddTable').addEventListener('click', () => openTableModal(null));
        $('tableManageBody').addEventListener('click', e => {
            const b = e.target.closest('button[data-act]');
            if (!b || b.disabled) return;
            const id = Number(b.dataset.id);
            if (b.dataset.act === 'edit') openTableModal(id);
            else openDeleteModal(id);
        });
        $('tableForm').addEventListener('submit', submitTable);
        $('tableNameInput').addEventListener('input', () => showNameError(''));
        $('btnTableCancel').addEventListener('click', closeTableModal);
        $('btnDeleteCancel').addEventListener('click', closeDeleteModal);
        $('btnDeleteConfirm').addEventListener('click', confirmDelete);
        // bấm ra ngoài hộp thoại hoặc nhấn Esc để đóng
        [['tableModal', closeTableModal], ['deleteModal', closeDeleteModal]].forEach(([id, close]) => {
            $(id).addEventListener('click', e => { if (e.target === $(id)) close(); });
        });
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            closeTableModal(); closeDeleteModal();
        });
        // trạng thái bàn đổi ở tab khác (Phục vụ gọi món, Thu ngân thanh toán) -> cập nhật lại
        // (trạng thái bàn lấy từ localStorage nên chỉ cần vẽ lại, không gọi API)
        window.addEventListener('storage', e => { if (e.key === TABLES_KEY) render(); });
        setInterval(() => refresh(true), 5000); // quản lý khác thêm/xóa bàn -> tự cập nhật
    });
})();