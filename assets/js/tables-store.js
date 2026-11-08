// ============================================================
// tables-store.js — TRẠNG THÁI BÀN / ĐƠN ĐANG MỞ (window.TablesStore)
// ------------------------------------------------------------
// Nguồn dữ liệu duy nhất là máy chủ: danh sách bàn (API 'tables') + đơn đang mở (API 'orders').
// Trang chỉ giữ bản sao trong BỘ NHỚ của trang; không còn ghi vào localStorage.
// Mỗi phần tử là 1 "bàn" (hoặc 1 đơn mang đi / giao hàng, có trường `type`):
//   { id, orderId?, orderCode?, name, type?, customer, address, status: 'empty'|'occupied', time, total,
//     items: [{ lineId, id (menuId), name, price, quantity, note, confirmed, confirmedAt, kStatus?, startedAt?, doneAt? }] }
// Dùng chung cho: table-order.js, waiter-pay.js, orders.js, notifications.js, inventory.js, timesheet.js.
// ============================================================
(function () {
    'use strict';
    if (window.TablesStore) return;

    let current = [];
    let loadedAt = 0;
    let inflight = null;
    const listeners = [];

    const toMs = v => (v ? new Date(v).getTime() : 0);

    function orderToTable(order, id, name) {
        const items = (order.items || []).map(i => ({
            lineId: String(i.id), id: i.menuId, name: i.name, price: Number(i.price), quantity: Number(i.quantity),
            note: i.note || '', confirmed: i.status !== 'draft',
            confirmedAt: toMs(i.confirmedAt),
            kStatus: i.status === 'draft' ? undefined : i.status,
            startedAt: i.startedAt ? toMs(i.startedAt) : undefined,
            doneAt: i.doneAt ? toMs(i.doneAt) : undefined
        }));
        return {
            id, orderId: Number(order.id), name, orderCode: order.order_code || '',
            type: order.orderType === 'dinein' ? undefined : order.orderType,
            customer: order.customer || '', address: order.address || '',
            status: 'occupied',
            time: order.created_at ? new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '',
            total: Number(order.subtotal) || 0, items
        };
    }

    function build(apiTables, orders) {
        const merged = apiTables.map(a => {
            const order = orders.find(o => o.orderType === 'dinein' && Number(o.tableId) === a.id);
            return order ? orderToTable(order, a.id, a.name)
                         : { id: a.id, name: a.name, status: 'empty', time: '', total: 0, items: [] };
        });
        orders.filter(o => o.orderType !== 'dinein').forEach(o => merged.push(orderToTable(o, -Number(o.id), o.tableName)));
        return merged;
    }

    function notify() { listeners.slice().forEach(fn => { try { fn(current); } catch (e) { /* một người nghe lỗi không chặn người khác */ } }); }

    async function fetchNow() {
        const [apiTables, orders] = await Promise.all([Api.get('tables'), Api.get('orders')]);
        current = build(apiTables, orders);
        loadedAt = Date.now();
        notify();
        return current;
    }

    // refresh()                 : tải lại ngay (nếu đang có lượt tải dở thì đợi xong rồi tải lại để lấy dữ liệu mới nhất).
    // refresh({ maxAge: ms })   : dùng bản đang có nếu mới hơn ms, tránh nhiều nơi cùng gọi API.
    function refresh(opts) {
        const maxAge = (opts && opts.maxAge) || 0;
        if (maxAge && Date.now() - loadedAt < maxAge) return Promise.resolve(current);
        if (inflight && maxAge) return inflight;
        const run = (inflight || Promise.resolve()).catch(() => {}).then(fetchNow);
        const p = run.finally(() => { if (inflight === p) inflight = null; });
        inflight = p;
        return p;
    }

    window.TablesStore = {
        list: () => current,
        // Trang gọi món sửa trực tiếp các phần tử rồi đặt lại mảng của mình làm bản hiện tại.
        set(list) { if (Array.isArray(list)) current = list; },
        refresh,
        subscribe(fn) { listeners.push(fn); return () => { const i = listeners.indexOf(fn); if (i >= 0) listeners.splice(i, 1); }; },
        busyCount: () => current.filter(t => t.status !== 'empty').length
    };
})();
