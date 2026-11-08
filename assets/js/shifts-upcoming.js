// Phân trang AJAX cho "Danh sách ca sắp tới" (admin, trang Lịch ca)
(function () {
    'use strict';
    const box = document.getElementById('upcomingBox');
    if (!box) return;
    const body = document.getElementById('upcomingBody');
    const pager = document.getElementById('upcomingPager');
    const total = document.getElementById('upcomingTotal');
    const week = new URLSearchParams(location.search).get('week') || '';
    let loading = false;

    async function load(p, push) {
        if (loading) return;
        loading = true;
        box.classList.add('is-loading');
        try {
            let route = 'shifts/upcoming&p=' + encodeURIComponent(p);
            if (week) route += '&week=' + encodeURIComponent(week);
            const d = await Api.get(route);
            body.innerHTML = d.rowsHtml;
            pager.innerHTML = d.paginationHtml;
            if (total) total.textContent = d.total;
            if (push !== false) {
                const u = new URL(location.href);
                if (d.page > 1) u.searchParams.set('p', d.page); else u.searchParams.delete('p');
                history.pushState({ p: d.page }, '', u);
            }
        } catch (err) {
            alert(err.message);
        } finally {
            loading = false;
            box.classList.remove('is-loading');
        }
    }

    pager.addEventListener('click', e => {
        const a = e.target.closest('a.pg-btn');
        if (!a) return;
        e.preventDefault();
        load(new URL(a.href, location.href).searchParams.get('p') || 1);
    });

    // Nút Back / Forward của trình duyệt
    window.addEventListener('popstate', () => {
        load(new URLSearchParams(location.search).get('p') || 1, false);
    });
})();