// Trang Kho: Quản lý tồn kho món theo số ly / phần, kết nối MySQL
(function () {
    'use strict';
    const API = window.InventoryAPI;
    const $ = id => document.getElementById(id);
    const esc = str => String(str ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
    const units = n => Number(n || 0).toLocaleString('vi-VN');

    let currentItems = [];

    function renderStats(stats, items) {
        if (stats) {
            $('totalIngredients').textContent = units(stats.totalIngredients);
            $('underMinLevel').textContent = units(stats.underMinLevel);
            $('outOfStock').textContent = units(stats.outOfStock);
            $('totalStock').textContent = units(stats.totalStock);
        } else if (items) {
            const low = items.filter(i => i.stock > 0 && i.stock <= i.minLevel);
            const out = items.filter(i => !i.stock);
            $('totalIngredients').textContent = units(items.length);
            $('underMinLevel').textContent = units(low.length);
            $('outOfStock').textContent = units(out.length);
            $('totalStock').textContent = units(items.reduce((sum, x) => sum + x.stock, 0));
        }
    }

    function renderTable(items) {
        currentItems = items || [];
        const tbody = $('tableBody');
        if (!tbody) return;

        if (!currentItems.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-table-state">Chưa có món trong Thực đơn. Hãy thêm món ở trang Thực đơn.</td></tr>';
            return;
        }

        tbody.innerHTML = currentItems.map(item => {
            const available = item.available != null ? item.available : API.available(item.id);
            const isOut = item.stock <= 0;
            const isLow = !isOut && item.stock <= item.minLevel;
            const badgeClass = isOut ? 'badge-cancelled' : (isLow ? 'badge-cancelled' : 'badge-active');
            const statusLabel = isOut ? 'Hết hàng' : (isLow ? 'Sắp hết' : 'Còn hàng');

            return `<tr>
                <td><b>${esc(item.name)}</b><br><small>${esc(item.category)}</small></td>
                <td>${esc(item.unit)}</td>
                <td><strong>${units(item.stock)}</strong></td>
                <td>${units(available)}</td>
                <td>${units(item.minLevel)}</td>
                <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
                <td>
                    <div class="table-actions">
                        <button type="button" class="btn-outline" data-edit="${esc(item.id)}">Nhập / điều chỉnh</button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        // Cập nhật dropdown chọn món ở tab nhập kho
        const select = $('stockItem');
        if (select) {
            const currentVal = select.value;
            select.innerHTML = currentItems.map(i => `<option value="${esc(i.id)}">${esc(i.name)} (${esc(i.unit)})</option>`).join('');
            if (currentItems.some(i => String(i.id) === String(currentVal))) {
                select.value = currentVal;
            }
        }

        // Cập nhật dropdown lọc món ở tab lịch sử
        const filterSelect = $('historyFilterItem');
        if (filterSelect) {
            const currentFilter = filterSelect.value;
            filterSelect.innerHTML = '<option value="">-- Tất cả món --</option>' +
                currentItems.map(i => `<option value="${esc(i.id)}">${esc(i.name)}</option>`).join('');
            if (currentFilter) filterSelect.value = currentFilter;
        }
    }

    async function loadData() {
        if (window.Api) {
            try {
                const items = await API.sync();
                const res = { items };
                if (res && res.items) {
                    renderStats(res.stats, res.items);
                    renderTable(res.items);
                    return;
                }
            } catch (err) {
                $('tableBody').innerHTML = `<tr><td colspan="7">Không tải được kho: ${esc(err.message)}. Hãy tải lại trang trước khi thao tác.</td></tr>`;
                return;
            }
        }
        $('tableBody').innerHTML = '<tr><td colspan="7">Không tải được API kho. Chưa thể cập nhật dữ liệu.</td></tr>';
    }

    async function loadHistory(filters = {}) {
        const tbody = $('stockLogBody');
        if (!tbody) return;

        try {
            const logs = await API.getHistory(filters);
            if (!logs || !logs.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-table-state">Không có lịch sử biến động phù hợp với bộ lọc.</td></tr>';
                return;
            }

            tbody.innerHTML = logs.map(l => {
                const delta = Number(l.delta || 0);
                const sign = delta > 0 ? '+' : '';
                const deltaClass = delta > 0 ? 'delta-positive' : 'delta-negative';
                const typeLabel = l.typeName || (({ import: 'Nhập kho', adjust: 'Điều chỉnh', adjustment: 'Điều chỉnh', sale: 'Bán hàng', rollback: 'Hoàn tác', sale_rollback: 'Hoàn tác' })[l.type] || l.type);
                const dateStr = l.ts ? (new Date(l.ts)).toLocaleString('vi-VN') : '—';
                const noteStr = l.note || l.ref || '—';

                return `<tr>
                    <td>${esc(dateStr)}</td>
                    <td><b>${esc(l.name)}</b></td>
                    <td class="${deltaClass}"><strong>${sign}${units(delta)} ${esc(l.unit || 'ly')}</strong></td>
                    <td><span class="badge badge-active">${esc(typeLabel)}</span></td>
                    <td>${esc(noteStr)}</td>
                    <td>${esc(l.actor || 'Hệ thống')}</td>
                </tr>`;
            }).join('');
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" class="empty-table-state">Lỗi khi tải lịch sử: ${esc(err.message)}</td></tr>`;
        }
    }

    function switchTab(tab) {
        document.querySelectorAll('.topnav-item').forEach(b => b.classList.toggle('active', b.dataset.action === tab));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === 'panel-' + tab));
        if (tab === 'history') {
            loadHistory(getCurrentHistoryFilters());
        }
    }

    function edit(id) {
        const item = currentItems.find(x => String(x.id) === String(id)) || API.get(id);
        if (!item) return;

        const select = $('stockItem');
        if (select) select.value = item.id;
        $('stockUnit').textContent = item.unit;
        $('stockCurrent').textContent = `${units(item.stock)} ${item.unit}`;
        $('stockAmount').value = 1;
        $('minLevel').value = item.minLevel;
        $('stockReason').value = '';
        $('stockMode').value = 'import';

        switchTab('add');
        setTimeout(() => $('stockAmount').focus(), 100);
    }

    function getCurrentHistoryFilters() {
        return {
            itemId: $('historyFilterItem') ? $('historyFilterItem').value : '',
            type: $('historyFilterType') ? $('historyFilterType').value : '',
            dateFrom: $('historyFilterDateFrom') ? $('historyFilterDateFrom').value : '',
            dateTo: $('historyFilterDateTo') ? $('historyFilterDateTo').value : ''
        };
    }

    document.addEventListener('DOMContentLoaded', async () => {
        // Nếu có sẵn dữ liệu do PHP in ra trên trang
        if (window.INITIAL_WAREHOUSE_DATA && Array.isArray(window.INITIAL_WAREHOUSE_DATA.items)) {
            renderStats(window.INITIAL_WAREHOUSE_DATA.stats, window.INITIAL_WAREHOUSE_DATA.items);
            renderTable(window.INITIAL_WAREHOUSE_DATA.items);
            if (window.INITIAL_WAREHOUSE_DATA.items.length) {
                edit(window.INITIAL_WAREHOUSE_DATA.items[0].id);
            }
        } else {
            await loadData();
            const items = API.getAll();
            if (items.length) edit(items[0].id);
        }

        switchTab('list');

        // Bắt sự kiện chuyển tab
        document.querySelectorAll('.topnav-item').forEach(b => {
            b.addEventListener('click', () => switchTab(b.dataset.action));
        });

        // Bắt sự kiện đổi món trên form nhập
        const selectItem = $('stockItem');
        if (selectItem) {
            selectItem.addEventListener('change', () => {
                const item = currentItems.find(x => String(x.id) === String(selectItem.value)) || API.get(selectItem.value);
                if (!item) return;
                $('stockUnit').textContent = item.unit;
                $('stockCurrent').textContent = `${units(item.stock)} ${item.unit}`;
                $('minLevel').value = item.minLevel;
            });
        }

        // Bắt sự kiện bấm nút Nhập / điều chỉnh trên bảng
        $('tableBody').addEventListener('click', e => {
            const b = e.target.closest('[data-edit]');
            if (b) edit(b.dataset.edit);
        });

        // Bắt sự kiện gửi form nhập / điều chỉnh kho
        $('inventoryForm').addEventListener('submit', async e => {
            e.preventDefault();
            const id = $('stockItem').value;
            const count = Number($('stockAmount').value);
            const mode = $('stockMode').value;
            const min = Number($('minLevel').value);
            const item = currentItems.find(x => String(x.id) === String(id)) || API.get(id);

            if (!item || !Number.isSafeInteger(count) || count <= 0) {
                return alert('Số lượng phải là số nguyên dương lớn hơn 0.');
            }
            if (!Number.isSafeInteger(min) || min < 0) {
                return alert('Mức cảnh báo sắp hết phải là số nguyên không âm.');
            }

            const btnSubmit = $('btnSubmitStock');
            if (btnSubmit) btnSubmit.disabled = true;

            const delta = (mode === 'import') ? count : -count;
            const reason = $('stockReason').value.trim() || (delta > 0 ? 'Nhập thêm tồn kho' : 'Điều chỉnh giảm tồn kho');

            try {
                const result = await API.adjust(id, delta, reason, min);
                if (!result.ok) {
                    alert('Không thể lưu: ' + result.error);
                    return;
                }

                alert(result.warning || 'Cập nhật kho thành công!');
                await loadData();
                switchTab('list');
            } catch (err) {
                alert('Có lỗi xảy ra: ' + err.message);
            } finally {
                if (btnSubmit) btnSubmit.disabled = false;
            }
        });

        // Nút hủy form quay về danh sách
        $('btnCancelEdit').addEventListener('click', () => switchTab('list'));

        // Nút lọc lịch sử
        const btnFilter = $('btnApplyHistoryFilter');
        if (btnFilter) {
            btnFilter.addEventListener('click', () => {
                loadHistory(getCurrentHistoryFilters());
            });
        }

        // Nút đặt lại bộ lọc lịch sử
        const btnResetFilter = $('btnResetHistoryFilter');
        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', () => {
                if ($('historyFilterItem')) $('historyFilterItem').value = '';
                if ($('historyFilterType')) $('historyFilterType').value = '';
                if ($('historyFilterDateFrom')) $('historyFilterDateFrom').value = '';
                if ($('historyFilterDateTo')) $('historyFilterDateTo').value = '';
                loadHistory({});
            });
        }

        // Lắng nghe sự kiện đồng bộ từ storage hoặc custom event
        window.addEventListener('storage', e => {
            if ([API.KEY, API.LOG_KEY, 'coffee_tables_v1', 'coffee_menu_data_v2'].includes(e.key)) {
                loadData();
            }
        });
        window.addEventListener('inventory-updated', e => {
            if (e.detail && Array.isArray(e.detail.items)) {
                renderStats(e.detail.stats, e.detail.items);
                renderTable(e.detail.items);
            }
        });
    });
})();
