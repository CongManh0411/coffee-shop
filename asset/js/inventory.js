// Kho thành phẩm (theo số ly / phần) — dùng chung cho Menu, Bán hàng, Kho, Thu ngân.
// Không quy đổi nguyên liệu cũ sang ly; dữ liệu nguyên liệu cũ vẫn giữ tại coffee_warehouse_v1.
(function () {
    'use strict';
    if (window.InventoryAPI) return;
    const KEY = 'coffee_portion_stock_v1';
    const LOG_KEY = 'coffee_portion_stock_log_v1';
    const SALE_KEY = 'coffee_portion_stock_sales_v1';
    const MENU_STORAGE = 'coffee_menu_data_v2';
    const TABLES_KEY = 'coffee_tables_v1';
    const PENDING_KEY = 'coffee_pending_orders_v1';
    const read = (key, fallback) => { try { const v = JSON.parse(localStorage.getItem(key)); return v == null ? fallback : v; } catch (_) { return fallback; } };
    const write = (key, data) => { try { localStorage.setItem(key, JSON.stringify(data)); return true; } catch (_) { return false; } };
    const unitFor = category => category === 'Bánh' ? 'phần' : 'ly';
    const positiveInt = value => Number.isSafeInteger(Number(value)) && Number(value) >= 0;
    const actor = () => { try { const u = window.AuthAPI && AuthAPI.getSession(); return u ? (u.name || u.fullName || u.username || u.roleKey || 'Nhân viên') : 'Nhân viên'; } catch (_) { return 'Nhân viên'; } };
    function menuItems() {
        const menu = read(MENU_STORAGE, typeof DEFAULT_MENU !== 'undefined' ? DEFAULT_MENU : []);
        return (Array.isArray(menu) ? menu : []).flatMap(cat => (cat.items || []).map(item => ({ id: String(item.id), name: item.name, category: cat.category, unit: unitFor(cat.category) })));
    }
    function getAll() {
        const items = menuItems(), raw = read(KEY, {}), stock = raw && !Array.isArray(raw) && typeof raw === 'object' ? raw : {};
        return items.map(m => {
            const old = stock[m.id] || {};
            return { ...m, stock: positiveInt(old.stock) ? Number(old.stock) : 0,
                minLevel: positiveInt(old.minLevel) ? Number(old.minLevel) : 10 };
        });
    }
    const get = id => getAll().find(x => x.id === String(id));
    function log(type, item, delta, ref) {
        const logs = read(LOG_KEY, []);
        logs.push({ ts: Date.now(), type, itemId: item.id, name: item.name, unit: item.unit, delta, ref: ref || '', actor: actor() });
        write(LOG_KEY, logs.slice(-1500));
    }
    function getLog() { const logs = read(LOG_KEY, []); return Array.isArray(logs) ? logs : []; }
    function adjust(id, delta, reason) {
        const item = get(id), change = Number(delta);
        if (!item) return { ok: false, error: 'Món không còn trong Menu.' };
        if (!Number.isSafeInteger(change) || !change) return { ok: false, error: 'Số lượng thay đổi phải là số nguyên khác 0.' };
        if (item.stock + change < 0) return { ok: false, error: `Kho ${item.name} chỉ còn ${item.stock} ${item.unit}.` };
        const data = read(KEY, {});
        data[item.id] = { stock: item.stock + change, minLevel: item.minLevel };
        if (!write(KEY, data)) return { ok: false, error: 'Không thể lưu kho vào trình duyệt.' };
        log(change > 0 ? 'import' : 'adjust', item, change, reason || 'Điều chỉnh thủ công');
        return { ok: true };
    }
    function setLevel(id, level) {
        const item = get(id);
        if (!item || !positiveInt(level)) return { ok: false, error: 'Định mức cảnh báo phải là số nguyên không âm.' };
        const data = read(KEY, {});
        data[item.id] = { stock: item.stock, minLevel: Number(level) };
        return write(KEY, data) ? { ok: true } : { ok: false, error: 'Không thể lưu định mức.' };
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
    // Cộng cả món nháp và món đã gửi bếp: món chỉ bị trừ khi thanh toán, không phải khi bếp bấm Xong.
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
        // Các hóa đơn trong Pay.html có thể là đơn độc lập, không có bàn lưu tương ứng.
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
    function available(id) { const item = get(id); return item ? Math.max(0, item.stock - (reserved()[String(id)] || 0)) : 0; }
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
    // Trừ đúng 1 lần theo mã hóa đơn. Nếu không ghi được sổ theo dõi thì hoàn nguyên tồn kho.
    function consume(lines, invoiceId) {
        const check = validateSale(lines, invoiceId);
        if (!check.ok) return check;
        const before = read(KEY, {}), after = JSON.parse(JSON.stringify(before));
        const sold = read(SALE_KEY, {});
        check.details.forEach(x => { after[x.id] = { stock: x.stock - x.quantity, minLevel: x.minLevel }; });
        if (!write(KEY, after)) return { ok: false, error: 'Không lưu được tồn kho.' };
        sold[invoiceId] = { ts: Date.now(), lines: check.details.map(x => ({ id: x.id, quantity: x.quantity })) };
        if (!write(SALE_KEY, sold)) {
            write(KEY, before);
            return { ok: false, error: 'Không lưu được lịch sử trừ kho.' };
        }
        check.details.forEach(x => log('sale', x, -x.quantity, `Hóa đơn ${invoiceId}`));
        return { ok: true };
    }
    // Chỉ sử dụng khi ghi hóa đơn thất bại, không tự động trả kho khi hoàn tiền.
    function rollback(invoiceId) {
        const sales = read(SALE_KEY, {}), record = sales[invoiceId];
        if (!record) return false;
        const before = read(KEY, {});
        const data = JSON.parse(JSON.stringify(before));
        for (const line of record.lines) {
            const item = get(line.id);
            if (!item) continue;
            data[item.id] = { stock: item.stock + line.quantity, minLevel: item.minLevel };
        }
        if (!write(KEY, data)) return false;
        delete sales[invoiceId];
        write(SALE_KEY, sales);
        record.lines.forEach(x => { const it = get(x.id); if (it) log('rollback', it, x.quantity, `Hoàn tác lỗi thanh toán ${invoiceId}`); });
        return true;
    }
    window.InventoryAPI = { KEY, LOG_KEY, SALE_KEY, getAll, get, menuItems, adjust, setLevel,
        getLog, reserved, available, canReserve, validateSale, consume, rollback, drop };
})();
