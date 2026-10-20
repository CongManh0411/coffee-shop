// ============================================================
// kitchen.js — TRANG BẾP (Kitchen.html)
// ------------------------------------------------------------
// Bếp thấy mọi món Phục vụ đã "Xác nhận" (coffee_tables_v1 -> bàn -> items có confirmed = true).
// Các món của CÙNG MỘT BÀN gửi cùng lúc (cùng confirmedAt) được gom thành 1 phiếu.
// Mỗi món có trường kStatus do bếp đặt:
//   'waiting' (chờ làm)  --Nhận làm-->  'making' (đang làm)  --Xong-->  'done' (đã xong, ghi doneAt)
// Phục vụ nhận thông báo (chấm đỏ + toast) ở notifications.js ngay khi món chuyển 'done'.
// Khi Phục vụ thanh toán xong, bàn về trống nên món tự biến mất khỏi Bếp.
//
// Tính năng: gom phiếu theo bàn, nút "tất cả" cho cả phiếu, nhận/xong từng món,
// phiếu đổi vàng (>3 phút) / đỏ (>5 phút), đồng hồ mm:ss cho món đang làm,
// giữ màn hình luôn sáng.
// ============================================================
(function () {
    'use strict';

    const DONE_SHOW_MS = 30 * 60 * 1000; // cột "Đã xong" chỉ giữ 30 phút gần nhất
    const WARN_MS = 3 * 60 * 1000;       // chờ quá 3 phút -> vàng
    const LATE_MS = 5 * 60 * 1000;       // chờ quá 5 phút -> đỏ

    const $ = id => document.getElementById(id);
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    let activeOrders = [];

    async function refreshOrders() {
        try {
            activeOrders = await Api.get('orders');
            syncLocalCache();
            render();
        } catch (err) {
            $('kSummary').textContent = 'Không tải được đơn từ máy chủ: ' + err.message;
        }
    }

    function syncLocalCache() {
        const key = 'coffee_tables_v1';
        let tables;
        try { tables = JSON.parse(localStorage.getItem(key)); } catch (e) { return; }
        if (!Array.isArray(tables)) return;
        const byId = new Map();
        activeOrders.forEach(order => (order.items || []).forEach(item => byId.set(String(item.id), item)));
        let changed = false;
        tables.forEach(table => (table.items || []).forEach(item => {
            if (!item.confirmed) return;
            const server = byId.get(String(item.lineId));
            if (!server) return;
            const startedAt = server.startedAt ? new Date(server.startedAt).getTime() : 0;
            const doneAt = server.doneAt ? new Date(server.doneAt).getTime() : 0;
            if (item.kStatus !== server.status || (Number(item.startedAt) || 0) !== startedAt || (Number(item.doneAt) || 0) !== doneAt) {
                item.kStatus = server.status;
                item.startedAt = startedAt || undefined;
                item.doneAt = doneAt || undefined;
                changed = true;
            }
        }));
        if (changed) {
            try { localStorage.setItem(key, JSON.stringify(tables)); } catch (e) {}
        }
    }

    // Gom toàn bộ món đã gửi bếp thành danh sách phẳng
    function collect(now) {
        const out = [];
        activeOrders.forEach(order => (order.items || []).forEach(i => {
            if (i.status === 'draft') return;
            const doneAt = i.doneAt ? new Date(i.doneAt).getTime() : 0;
            if (i.status === 'done' && doneAt && now - doneAt > DONE_SHOW_MS) return;
            out.push({ orderId: order.id, tableId: order.tableId || -order.id, tableName: order.tableName,
                       lineId: String(i.id), name: i.name, qty: i.quantity, note: i.note || '', status: i.status,
                       since: i.confirmedAt ? new Date(i.confirmedAt).getTime() : now,
                       startedAt: i.startedAt ? new Date(i.startedAt).getTime() : 0, doneAt });
        }));
        return out;
    }

    // Gom các món cùng bàn + cùng lúc gửi thành 1 phiếu
    function group(items) {
        const map = new Map();
        items.forEach(x => {
            const key = x.tableId + ':' + x.since;
            if (!map.has(key)) map.set(key, { key, tableId: x.tableId, tableName: x.tableName, since: x.since, doneAt: 0, items: [] });
            const g = map.get(key);
            g.items.push(x);
            g.doneAt = Math.max(g.doneAt, x.doneAt);
        });
        return [...map.values()];
    }

    // Đổi trạng thái nhiều món cùng lúc. Luôn đọc mới từ localStorage trước khi ghi để không đè thay đổi của Phục vụ.
    async function setStatus(tableId, lineIds, status) {
        const ids = lineIds.map(Number).filter(Number.isFinite);
        if (!ids.length) return;
        try {
            await Promise.all(ids.map(itemId => Api.put('orders/kitchen', { itemId, status })));
            await refreshOrders();
        } catch (err) { alert('Không cập nhật được trạng thái món: ' + err.message); }
    }

    // ---------- Hiển thị ----------
    const seenBatches = new Set();   // phiếu đã từng hiện, để biết phiếu nào MỚI
    let firstRender = true;
    let lastSig = '';

    function fmtElapsed(ms) {
        const m = Math.max(0, Math.floor(ms / 60000));
        if (m < 1) return 'vừa gửi';
        if (m < 60) return `${m} phút`;
        return `${Math.floor(m / 60)}h${String(m % 60).padStart(2, '0')}`;
    }
    function fmtMMSS(ms) {
        const s = Math.max(0, Math.floor(ms / 1000));
        return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    }
    const urgency = ms => ms > LATE_MS ? 'late' : ms > WARN_MS ? 'warn' : '';

    function lineHtml(x, status, multi, now) {
        const timer = status === 'making' && x.startedAt ? `<span class="k-timer" data-start="${x.startedAt}">${fmtMMSS(now - x.startedAt)}</span>` : '';
        let btn = '';
        if (multi && status === 'waiting') btn = `<button type="button" class="k-btn mini" data-table="${x.tableId}" data-lines="${esc(x.lineId)}" data-to="making">Nhận</button>`;
        else if (multi && status === 'making') btn = `<button type="button" class="k-btn mini go" data-table="${x.tableId}" data-lines="${esc(x.lineId)}" data-to="done">Xong</button>`;
        return `<li class="k-line">
            <span class="k-qty">${x.qty}</span>
            <span class="k-name">${esc(x.name)}${x.note ? `<span class="k-note">${esc(x.note)}</span>` : ''}</span>
            ${timer}${btn ? `<span class="k-line-btns">${btn}</span>` : ''}
        </li>`;
    }

    function orderHtml(g, status, now) {
        const isDone = status === 'done';
        const ref = isDone ? g.doneAt : g.since;
        const elapsed = now - ref;
        const urg = isDone ? '' : urgency(elapsed);
        const isNew = !firstRender && !seenBatches.has(g.key) && status === 'waiting';
        const multi = g.items.length > 1;
        const lines = g.items.map(x => x.lineId).join(',');
        const base = `data-table="${g.tableId}" data-lines="${esc(lines)}"`;
        let actions;
        if (status === 'waiting') {
            actions = `<button type="button" class="k-btn primary" ${base} data-to="making">${multi ? `Nhận làm tất cả (${g.items.length})` : 'Nhận làm'}</button>`;
        } else if (status === 'making') {
            actions = `<button type="button" class="k-btn go" ${base} data-to="done">${multi ? `Xong tất cả (${g.items.length})` : 'Xong'}</button>
                       <button type="button" class="k-btn ghost" ${base} data-to="waiting" title="Trả về cột Chờ làm">Trả về chờ</button>`;
        } else {
            actions = `<button type="button" class="k-btn ghost" ${base} data-to="making">Hoàn tác</button>`;
        }
        return `<article class="k-order ${urg}" data-key="${esc(g.key)}">
            <div class="k-order-top">
                <span class="k-table">${esc(g.tableName)}${isNew ? '<span class="k-new">Mới</span>' : ''}</span>
                <span class="k-elapsed" data-ref="${ref}" data-live="${isDone ? 0 : 1}">${isDone ? 'xong ' : ''}${fmtElapsed(elapsed)}${isDone ? ' trước' : ''}</span>
            </div>
            <ul class="k-lines">${g.items.map(x => lineHtml(x, status, multi, now)).join('')}</ul>
            <div class="k-actions">${actions}</div>
        </article>`;
    }

    function render() {
        const now = Date.now();
        const all = collect(now);
        const by = s => all.filter(x => x.status === s);
        const groups = s => group(by(s));
        const waiting = groups('waiting').sort((a, b) => a.since - b.since);
        const making = groups('making').sort((a, b) => a.since - b.since);
        const done = groups('done').sort((a, b) => b.doneAt - a.doneAt);

        const fill = (listId, countId, arr, status, emptyText) => {
            $(listId).innerHTML = arr.length ? arr.map(g => orderHtml(g, status, now)).join('') : `<div class="k-empty">${emptyText}</div>`;
            $(countId).textContent = arr.reduce((n, g) => n + g.items.length, 0);
        };
        fill('kListWaiting', 'kCountWaiting', waiting, 'waiting', 'Không có món nào đang chờ.');
        fill('kListMaking', 'kCountMaking', making, 'making', 'Chưa nhận làm món nào.');
        fill('kListDone', 'kCountDone', done, 'done', 'Chưa có món nào xong.');

        const nW = by('waiting').length, nM = by('making').length;
        $('kSummary').textContent = (nW + nM)
            ? `${nW} món chờ · ${nM} món đang làm · ${waiting.length + making.length} phiếu`
            : 'Hiện không còn món nào cần làm.';

        [...waiting, ...making, ...done].forEach(g => seenBatches.add(g.key));
        firstRender = false;
        lastSig = signature();
    }

    function signature() { return JSON.stringify(activeOrders.map(o => [o.id, (o.items || []).map(i => [i.id, i.status, i.quantity, i.note])])); }

    // Mỗi giây: cập nhật đồng hồ và màu phiếu mà không vẽ lại cả bảng
    function tick() {
        const now = Date.now();
        document.querySelectorAll('.k-elapsed').forEach(el => {
            const ref = Number(el.dataset.ref), live = el.dataset.live === '1';
            el.textContent = live ? fmtElapsed(now - ref) : 'xong ' + fmtElapsed(now - ref) + ' trước';
            if (live) {
                const card = el.closest('.k-order'), u = urgency(now - ref);
                card.classList.toggle('warn', u === 'warn');
                card.classList.toggle('late', u === 'late');
            }
        });
        document.querySelectorAll('.k-timer').forEach(el => { el.textContent = fmtMMSS(now - Number(el.dataset.start)); });
    }

    let wakeLock = null;
    async function keepAwake() {
        try { if ('wakeLock' in navigator && document.visibilityState === 'visible') wakeLock = await navigator.wakeLock.request('screen'); } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', () => {
        refreshOrders();

        document.querySelector('.k-board').addEventListener('click', e => {
            const btn = e.target.closest('button[data-to]');
            if (!btn) return;
            setStatus(Number(btn.dataset.table), btn.dataset.lines.split(','), btn.dataset.to);
        });
        keepAwake();
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') { keepAwake(); render(); } });

        // Phục vụ gửi món mới / sửa món ở tab khác -> cập nhật ngay
        // Lấy trạng thái mới từ server định kỳ để các màn hình bếp khác nhau đồng bộ.
        setInterval(refreshOrders, 3000);
        setInterval(tick, 1000);
        // Cột "Đã xong" tự bỏ món quá 30 phút
        setInterval(render, 60000);
    });
})();
