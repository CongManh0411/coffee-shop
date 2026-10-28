// Kho thành phẩm (theo số ly / phần) — kết nối MySQL qua API, đồng bộ bộ nhớ đệm trình duyệt
(function () {
    'use strict';
    if (window.InventoryAPI) return;

    const KEY = 'coffee_portion_stock_v1';
    const LOG_KEY = 'coffee_portion_stock_log_v1';
    const SALE_KEY = 'coffee_portion_stock_sales_v1';
    const MENU_STORAGE = 'coffee_menu_data_v2';
    const TABLES_KEY = 'coffee_tables_v1';
    const PENDING_KEY = 'coffee_pending_orders_v1';

    let cachedItems = null;

    const read = (key, fallback) => {
        try {
            const v = JSON.parse(localStorage.getItem(key));
            return v == null ? fallback : v;
        } catch (_) {
            return fallback;
        }
    };

    const write = (key, data) => {
        try {
            localStorage.setItem(key, JSON.stringify(data));
            return true;
        } catch (_) {
            return false;
        }
    };

    const unitFor = category => category === 'Bánh' ? 'phần' : 'ly';
    const positiveInt = value => Number.isSafeInteger(Number(value)) && Number(value) >= 0;

    const actor = () => {
        try {
            const u = (window.SERVER_USER || (window.AuthAPI && AuthAPI.getSession()));
            return u ? (u.name || u.fullName || u.username || u.roleKey || 'Nhân viên') : 'Nhân viên';
        } catch (_) {
            return 'Nhân viên';
        }
    };

    // Khởi tạo từ dữ liệu ban đầu nếu được PHP in sẵn trên trang
    if (window.INITIAL_WAREHOUSE_DATA && Array.isArray(window.INITIAL_WAREHOUSE_DATA.items)) {
        const stockCache = {};
        cachedItems = window.INITIAL_WAREHOUSE_DATA.items.map(it => {
            stockCache[String(it.id)] = { stock: Number(it.stock), minLevel: Number(it.minLevel) };
            return {
                ...it,
                id: String(it.id),
                name: it.name,
                category: it.category,
                unit: it.unit || unitFor(it.category),
                stock: Number(it.stock),
                minLevel: Number(it.minLevel)
            };
        });
        write(KEY, stockCache);
    }

    function menuItems() {
        if (cachedItems !== null) return cachedItems;
        const menu = read(MENU_STORAGE, typeof DEFAULT_MENU !== 'undefined' ? DEFAULT_MENU : []);
        return (Array.isArray(menu) ? menu : []).flatMap(cat =>
            (cat.items || []).map(item => ({
                id: String(item.id),
                name: item.name,
                category: cat.category,
                unit: unitFor(cat.category)
            }))
        );
    }

    function getAll() {
        if (cachedItems !== null) return cachedItems.map(item => ({ ...item }));
        const items = menuItems(), raw = read(KEY, {}), stock = raw && typeof raw === 'object' && !Array.isArray(raw) ? raw : {};
        return items.map(m => {
            const old = stock[m.id] || {};
            return {
                ...m,
                stock: positiveInt(old.stock) ? Number(old.stock) : 0,
                minLevel: positiveInt(old.minLevel) ? Number(old.minLevel) : 10
            };
        });
    }

    const get = id => getAll().find(x => x.id === String(id));

    function log(type, item, delta, ref) {
        const logs = read(LOG_KEY, []);
        logs.push({
            ts: Date.now(),
            type,
            itemId: item.id,
            name: item.name,
            unit: item.unit,
            delta,
            ref: ref || '',
            actor: actor()
        });
        write(LOG_KEY, logs.slice(-1500));
    }

    function getLog() {
        const logs = read(LOG_KEY, []);
        return Array.isArray(logs) ? logs : [];
    }

    // Đồng bộ số lượng tồn kho từ MySQL qua API
    async function sync() {
        if (!window.Api) throw new Error('Không tải được API kho. Hãy tải lại trang.');
        try {
            const res = await Api.get('warehouse');
            if (res && Array.isArray(res.items)) {
                const stockCache = {};
                cachedItems = res.items.map(it => {
                    stockCache[String(it.id)] = {
                        stock: Number(it.stock || 0),
                        minLevel: Number(it.minLevel ?? 10)
                    };
                    return {
                        ...it,
                        id: String(it.id),
                        name: it.name,
                        category: it.category,
                        unit: it.unit || unitFor(it.category),
                        stock: Number(it.stock || 0),
                        minLevel: Number(it.minLevel ?? 10)
                    };
                });
                write(KEY, stockCache);
                window.dispatchEvent(new CustomEvent('inventory-updated', { detail: res }));
                return getAll();
            }
        } catch (err) { throw err; }
        throw new Error('Dữ liệu kho máy chủ không hợp lệ.');
    }

    // Điều chỉnh tồn kho: nhập thêm hoặc giảm do hao hụt/kiểm kê
    async function adjust(id, delta, reason, minLevel) {
        const change = Number(delta);
        if (!Number.isSafeInteger(Number(id)) || Number(id) < 1) return { ok: false, error: 'Mã món không hợp lệ.' };
        if (!Number.isSafeInteger(change) || !change) return { ok: false, error: 'Số lượng thay đổi phải là số nguyên khác 0.' };

        if (window.Api) {
            try {
                const mode = change > 0 ? 'import' : 'adjust';
                const amount = Math.abs(change);
                await Api.post('warehouse/adjust', {
                    itemId: Number(id),
                    mode,
                    amount,
                    minLevel: minLevel != null ? Number(minLevel) : undefined,
                    note: reason || (mode === 'import' ? 'Nhập thêm tồn kho' : 'Điều chỉnh giảm tồn kho')
                });
                try { await sync(); } catch (_) { return { ok: true, warning: 'Đã lưu trên máy chủ nhưng chưa tải lại được dữ liệu. Hãy tải lại trang, không nhập lại giao dịch.' }; }
                return { ok: true };
            } catch (err) {
                return { ok: false, error: err.message || 'Lỗi kết nối máy chủ.' };
            }
        }

        return { ok: false, error: 'Không có kết nối API kho. Chưa lưu thay đổi.' };
    }

    // Thiết lập định mức cảnh báo sắp hết
    async function setLevel(id, level) {
        const item = get(id);
        if (!item || !positiveInt(level)) return { ok: false, error: 'Định mức cảnh báo phải là số nguyên không âm.' };

        if (window.Api) {
            try {
                await Api.post('warehouse/min-level', {
                    itemId: Number(id),
                    minLevel: Number(level)
                });
                try { await sync(); } catch (_) { return { ok: true, warning: 'Đã lưu trên máy chủ nhưng chưa tải lại được dữ liệu. Hãy tải lại trang, không nhập lại giao dịch.' }; }
                return { ok: true };
            } catch (err) {
                return { ok: false, error: err.message || 'Lỗi kết nối máy chủ.' };
            }
        }

        return { ok: false, error: 'Không có kết nối API kho. Chưa lưu thay đổi.' };
    }

    // Lấy lịch sử biến động từ MySQL
    async function getHistory(filters = {}) {
        if (window.Api) {
            try {
                const params = new URLSearchParams();
                if (filters.itemId) params.append('itemId', filters.itemId);
                if (filters.type) params.append('type', filters.type);
                if (filters.dateFrom) params.append('dateFrom', filters.dateFrom);
                if (filters.dateTo) params.append('dateTo', filters.dateTo);
                const qs = params.toString() ? '&' + params.toString() : '';
                const logs = await Api.get('warehouse/history' + qs);
                if (!Array.isArray(logs)) throw new Error('Dữ liệu lịch sử không hợp lệ.');
                return logs;
            } catch (err) { throw err; }
        }
        throw new Error('Không tải được API lịch sử kho.');
    }

    function totals(items) {
        const qty = {};
        for (const line of items || []) {
            const id = line.id ?? line.itemId ?? line.menuItemId;
            const n = Number(line.quantity ?? line.qty);
            if (id == null || !Number.isSafeInteger(n) || n <= 0) return null;
            qty[String(id)] = (qty[String(id)] || 0) + n;
        }
        return qty;
    }

    // Tính số ly/phần đang được đặt ở các bàn đang mở
    function reserved(excludeTableId) {
        const sum = {};
        const tables = read(TABLES_KEY, []);
        (Array.isArray(tables) ? tables : []).forEach(table => {
            if (excludeTableId != null && String(table.id) === String(excludeTableId)) return;
            (table.items || []).forEach(i => {
                if (i.id == null) return;
                const n = Number(i.quantity || 0);
                if (n > 0) sum[String(i.id)] = (sum[String(i.id)] || 0) + n;
            });
        });
        const pending = read(PENDING_KEY, []);
        (Array.isArray(pending) ? pending : []).forEach(p => {
            if ((Array.isArray(tables) ? tables : []).some(t => String(t.id) === String(p.tableId))) return;
            (p.items || []).forEach(i => {
                if (i.id == null) return;
                const n = Number(i.quantity || i.qty || 0);
                if (n > 0) sum[String(i.id)] = (sum[String(i.id)] || 0) + n;
            });
        });
        return sum;
    }

    function available(id) {
        const item = get(id);
        return item ? Math.max(0, item.stock - (reserved()[String(id)] || 0)) : 0;
    }

    function canReserve(id, quantity) {
        const item = get(id), n = Number(quantity);
        if (!item) return { ok: false, error: 'Món không có trong thực đơn.' };
        if (!Number.isSafeInteger(n) || n < 1) return { ok: false, error: 'Số lượng món không hợp lệ.' };
        const left = available(id);
        return left >= n ? { ok: true, left } : { ok: false, left, error: `${item.name} chỉ còn ${left} ${item.unit} có thể đặt (tồn kho ${item.stock}).` };
    }

    function validateSale(lines, invoiceId) {
        const sold = read(SALE_KEY, {});
        if (invoiceId && sold[invoiceId]) return { ok: false, error: `Hóa đơn ${invoiceId} đã được trừ kho trước đó.` };
        const summary = totals(lines);
        if (!summary || !Object.keys(summary).length) return { ok: false, error: 'Hóa đơn không có số lượng món hợp lệ.' };
        const items = getAll();
        const details = [];
        for (const [id, quantity] of Object.entries(summary)) {
            const item = items.find(x => x.id === id);
            if (!item) return { ok: false, error: `Mã món ${id} không có trong thực đơn. Không thể trừ kho.` };
            if (item.stock < quantity) return { ok: false, error: `${item.name} chỉ còn ${item.stock} ${item.unit}; hóa đơn cần ${quantity}.` };
            details.push({ ...item, quantity });
        }
        return { ok: true, details };
    }

    function drop(id) {
        const data = read(KEY, {});
        delete data[String(id)];
        return write(KEY, data);
    }

    window.InventoryAPI = {
        KEY, LOG_KEY, SALE_KEY, getAll, get, menuItems, adjust, setLevel, sync, getHistory,
        getLog, reserved, available, canReserve, validateSale, drop
    };
})();
