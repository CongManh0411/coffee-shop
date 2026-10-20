// Trang Kho: mỗi món trong Menu là một mặt hàng tồn kho (ly / phần).
(function () {
    'use strict';
    const API = window.InventoryAPI;
    const $ = id => document.getElementById(id);
    const esc = str => String(str ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
    const units = n => Number(n).toLocaleString('vi-VN');
    function render() {
        const list = API.getAll(), low = list.filter(i => i.stock > 0 && i.stock <= i.minLevel), out = list.filter(i => !i.stock);
        const select = $('stockItem'), current = select.value;
        select.innerHTML = list.map(i => `<option value="${esc(i.id)}">${esc(i.name)} (${esc(i.unit)})</option>`).join('');
        if (list.some(i => i.id === current)) select.value = current;
        $('totalIngredients').textContent = list.length;
        $('underMinLevel').textContent = low.length;
        $('outOfStock').textContent = out.length;
        $('totalStock').textContent = units(list.reduce((sum, x) => sum + x.stock, 0));
        $('tableBody').innerHTML = list.length ? list.map(item => {
            const available = API.available(item.id);
            const label = item.stock === 0 ? 'Hết hàng' : item.stock <= item.minLevel ? 'Sắp hết' : 'Còn hàng';
            return `<tr><td><b>${esc(item.name)}</b><br><small>${esc(item.category)}</small></td>
                <td>${esc(item.unit)}</td><td>${units(item.stock)}</td><td>${units(available)}</td>
                <td>${units(item.minLevel)}</td><td><span class="badge ${item.stock <= item.minLevel ? 'badge-cancelled' : 'badge-active'}">${label}</span></td>
                <td><div class="table-actions"><button type="button" class="btn-outline" data-edit="${esc(item.id)}">Nhập / điều chỉnh</button></div></td></tr>`;
        }).join('') : '<tr><td colspan="7" class="empty-table-state">Chưa có món trong Thực đơn. Hãy thêm món ở trang Thực đơn.</td></tr>';
        const log = API.getLog().slice().reverse().slice(0, 100);
        $('stockLogBody').innerHTML = log.length ? log.map(l => `<tr><td>${new Date(l.ts).toLocaleString('vi-VN')}</td><td>${esc(l.name)}</td>
            <td>${l.delta > 0 ? '+' : ''}${units(l.delta)} ${esc(l.unit)}</td><td>${esc(({import:'Nhập kho',adjust:'Điều chỉnh',sale:'Bán hàng',rollback:'Hoàn tác'})[l.type] || l.type)}</td>
            <td>${esc(l.ref)}</td><td>${esc(l.actor)}</td></tr>`).join('') : '<tr><td colspan="6" class="empty-table-state">Chưa có biến động kho.</td></tr>';
    }
    function switchTab(tab) {
        document.querySelectorAll('.topnav-item').forEach(b => b.classList.toggle('active', b.dataset.action === tab));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === 'panel-' + tab));
    }
    function edit(id) {
        const item = API.get(id); if (!item) return;
        $('stockItem').value = item.id;
        $('stockUnit').textContent = item.unit;
        $('stockCurrent').textContent = `${units(item.stock)} ${item.unit}`;
        $('stockAmount').value = 1;
        $('minLevel').value = item.minLevel;
        $('stockReason').value = '';
        switchTab('add');
    }
    document.addEventListener('DOMContentLoaded', () => {
        const items = API.getAll();
        render();
        if (items.length) edit(items[0].id);
        switchTab('list');
        document.querySelectorAll('.topnav-item').forEach(b => b.addEventListener('click', () => switchTab(b.dataset.action)));
        $('stockItem').addEventListener('change', () => { const item = API.get($('stockItem').value); if (!item) return;
            $('stockUnit').textContent = item.unit; $('stockCurrent').textContent = `${units(item.stock)} ${item.unit}`; $('minLevel').value = item.minLevel; });
        $('tableBody').addEventListener('click', e => { const b = e.target.closest('[data-edit]'); if (b) edit(b.dataset.edit); });
        $('inventoryForm').addEventListener('submit', e => {
            e.preventDefault();
            const id = $('stockItem').value, count = Number($('stockAmount').value), mode = $('stockMode').value;
            const min = Number($('minLevel').value), item = API.get(id);
            if (!item || !Number.isSafeInteger(count) || count <= 0) return alert('Số lượng phải là số nguyên dương.');
            if (!Number.isSafeInteger(min) || min < 0) return alert('Mức cảnh báo không hợp lệ.');
            const delta = mode === 'import' ? count : -count;
            const result = API.adjust(id, delta, $('stockReason').value.trim() || (delta > 0 ? 'Nhập thêm' : 'Giảm tồn kho'));
            if (!result.ok) return alert(result.error);
            const levelResult = API.setLevel(id, min);
            if (!levelResult.ok) return alert(levelResult.error);
            render(); switchTab('list');
        });
        $('btnCancelEdit').addEventListener('click', () => switchTab('list'));
        window.addEventListener('storage', e => { if ([API.KEY, API.LOG_KEY, 'coffee_tables_v1', 'coffee_menu_data_v2'].includes(e.key)) render(); });
    });
})();
