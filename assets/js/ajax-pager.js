// ajax-pager.js — phân trang bằng AJAX cho mọi vùng có thuộc tính data-ajax-region="tên".
// PHP vẫn in HTML như cũ (kể cả link ?p=2). Khi bấm số trang, file này tải lại cùng URL bằng fetch(),
// lấy đúng vùng cùng tên trong HTML trả về rồi thay vào trang, không tải lại cả trang.
// Không có JavaScript hoặc fetch lỗi thì link vẫn mở trang bình thường.
//   data-ajax-region="tên"        vùng được thay (danh sách + thanh phân trang nằm trong vùng)
//   data-ajax-sync="id1,id2"      (tùy chọn) id các thẻ ngoài vùng cần cập nhật nội dung, vd. <script type="application/json">
// Sau khi thay xong có sự kiện document 'ajax-pager:updated' để trang gắn lại sự kiện riêng nếu cần.
(function () {
    'use strict';
    if (!window.fetch || !window.DOMParser || !window.history || !history.pushState) return;

    const REGION = '[data-ajax-region]';
    let requestId = 0;

    const style = document.createElement('style');
    style.textContent = '[data-ajax-region].is-loading{opacity:.5;pointer-events:none;transition:opacity .15s}';
    document.head.appendChild(style);

    // Tải url, trả về Document đã parse; ném lỗi nếu không phải trang HTML bình thường
    async function fetchDocument(url) {
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const html = await res.text();
        return new DOMParser().parseFromString(html, 'text/html');
    }

    // Thay nội dung các vùng hiện có bằng vùng cùng tên trong doc; trả về false nếu thiếu vùng
    function swapRegions(doc, names) {
        const pairs = [];
        for (const name of names) {
            const current = document.querySelector('[data-ajax-region="' + name + '"]');
            const fresh = doc.querySelector('[data-ajax-region="' + name + '"]');
            if (!current || !fresh) return false;
            pairs.push([current, fresh]);
        }
        for (const [current, fresh] of pairs) {
            current.innerHTML = fresh.innerHTML;
            const ids = (current.dataset.ajaxSync || '').split(',').map(s => s.trim()).filter(Boolean);
            for (const id of ids) {
                const target = document.getElementById(id);
                const source = doc.getElementById(id);
                if (target && source) target.textContent = source.textContent;
            }
            document.dispatchEvent(new CustomEvent('ajax-pager:updated', { detail: { region: current } }));
        }
        return true;
    }

    function regionNames() {
        return Array.from(document.querySelectorAll(REGION)).map(el => el.dataset.ajaxRegion);
    }

    async function go(url, region, push) {
        const id = ++requestId;
        const names = push ? [region.dataset.ajaxRegion] : regionNames();
        document.querySelectorAll(REGION).forEach(el => { if (names.indexOf(el.dataset.ajaxRegion) !== -1) { el.classList.add('is-loading'); el.setAttribute('aria-busy', 'true'); } });
        try {
            const doc = await fetchDocument(url);
            if (id !== requestId) return;                       // đã có yêu cầu mới hơn, bỏ kết quả cũ
            if (!swapRegions(doc, names)) throw new Error('Thiếu vùng');
            if (push) {
                history.pushState({ ajaxPager: true }, '', url);
                region.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (err) {
            if (id === requestId) location.href = url;          // lỗi mạng/phiên hết hạn: mở trang đầy đủ
        } finally {
            if (id === requestId) document.querySelectorAll(REGION).forEach(el => { el.classList.remove('is-loading'); el.removeAttribute('aria-busy'); });
        }
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const link = e.target.closest('.pagination a[href]');
        if (!link) return;
        const region = link.closest(REGION);
        if (!region) return;
        const url = new URL(link.getAttribute('href'), location.href);
        if (url.origin !== location.origin) return;
        e.preventDefault();
        go(url.href, region, true);
    });

    // Nút Back/Forward của trình duyệt: tải lại các vùng theo URL hiện tại
    window.addEventListener('popstate', function () {
        const first = document.querySelector(REGION);
        if (first) go(location.href, first, false);
    });
})();
