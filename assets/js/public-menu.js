(() => {
    const tabs = document.getElementById('menuCategories');
    const grid = document.getElementById('publicMenuGrid');
    const status = document.getElementById('menuStatus');
    const pager = document.getElementById('menuPagination');
    const form = document.getElementById('menuSearchForm');
    const searchInput = document.getElementById('menuSearch');
    const clearLink = document.getElementById('menuSearchClear');
    if (!tabs || !grid || !status) return;

    const PER_PAGE = 9;
    const ALL = 'Tất cả';
    const money = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 });
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch]);
    const fold = value => String(value ?? '').normalize('NFC').toLocaleLowerCase('vi-VN');

    let groups = [];
    // Trạng thái đọc từ URL: ?page=public-menu&category=...&q=...&p=...
    const params = new URLSearchParams(window.location.search);
    const state = {
        category: params.get('category') || ALL,
        q: (params.get('q') || '').trim().slice(0, 100),
        p: Math.max(1, parseInt(params.get('p'), 10) || 1),
    };

    // Ghi trạng thái lên URL (không tải lại trang). push = tạo mục lịch sử để nút Back hoạt động.
    function syncUrl(push) {
        const next = new URLSearchParams({ page: 'public-menu' });
        if (state.category !== ALL) next.set('category', state.category);
        if (state.q) next.set('q', state.q);
        if (state.p > 1) next.set('p', String(state.p));
        const url = window.location.pathname + '?' + next.toString();
        if (url === window.location.pathname + window.location.search) return;
        window.history[push ? 'pushState' : 'replaceState'](null, '', url);
    }

    function visibleItems() {
        const needle = fold(state.q);
        return groups
            .filter(group => state.category === ALL || group.category === state.category)
            .flatMap(group => group.items.map(item => ({ item, category: group.category })))
            .filter(({ item }) => !needle || fold(item.name).includes(needle) || fold(item.desc).includes(needle));
    }

    function pageButtons(page, pages) {
        const nums = [...new Set([1, pages, page - 1, page, page + 1])].filter(n => n >= 1 && n <= pages).sort((a, b) => a - b);
        let html = `<nav class="pagination" aria-label="Phân trang">`;
        html += page > 1 ? `<button type="button" class="pg-btn" data-page="${page - 1}">&laquo; Trước</button>` : `<span class="pg-btn is-disabled">&laquo; Trước</span>`;
        let prev = 0;
        nums.forEach(n => {
            if (prev && n - prev > 1) html += '<span class="pg-gap">…</span>';
            html += n === page ? `<span class="pg-btn is-current" aria-current="page">${n}</span>` : `<button type="button" class="pg-btn" data-page="${n}">${n}</button>`;
            prev = n;
        });
        html += page < pages ? `<button type="button" class="pg-btn" data-page="${page + 1}">Sau &raquo;</button>` : `<span class="pg-btn is-disabled">Sau &raquo;</span>`;
        return html + '</nav>';
    }

    function render() {
        const all = visibleItems();
        const pages = Math.max(1, Math.ceil(all.length / PER_PAGE));
        state.p = Math.min(Math.max(1, state.p), pages);
        const shown = all.slice((state.p - 1) * PER_PAGE, state.p * PER_PAGE);

        tabs.innerHTML = [ALL, ...groups.map(group => group.category)].map(category =>
            `<button type="button" class="menu-category-tab${state.category === category ? ' is-active' : ''}" data-category="${escapeHtml(category)}" aria-pressed="${state.category === category}">${escapeHtml(category)}</button>`
        ).join('');
        grid.innerHTML = shown.map(({ item, category }) => {
            const image = itemImage(item, category);
            return `<article class="public-menu-card"><div class="public-menu-photo"><img src="${escapeHtml(image)}" alt="${escapeHtml(item.name)}" loading="lazy"><span>${escapeHtml(category)}</span></div><div class="public-menu-card-body"><div><h3>${escapeHtml(item.name)}</h3><p>${escapeHtml(item.desc || '')}</p></div><strong>${money.format(Number(item.price) || 0)}</strong></div></article>`;
        }).join('');
        pager.innerHTML = pages > 1 ? pageButtons(state.p, pages) : '';

        if (searchInput && document.activeElement !== searchInput) searchInput.value = state.q;
        if (clearLink) clearLink.hidden = !state.q;

        status.classList.remove('is-error');
        status.hidden = all.length > 0 && !state.q;
        status.textContent = !all.length
            ? (state.q ? `Không có món nào khớp với “${state.q}”.` : 'Chưa có món nào trong danh mục này.')
            : (state.q ? `Tìm thấy ${all.length} món cho “${state.q}”.` : '');
    }

    function update(push) {
        render();
        syncUrl(push);
    }

    tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-category]');
        if (!button) return;
        state.category = button.dataset.category;
        state.p = 1;
        update(true);
    });

    pager.addEventListener('click', event => {
        const button = event.target.closest('[data-page]');
        if (!button) return;
        state.p = parseInt(button.dataset.page, 10) || 1;
        update(true);
        tabs.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    if (form) {
        form.addEventListener('submit', event => {
            event.preventDefault();
            state.q = searchInput.value.trim().slice(0, 100);
            state.p = 1;
            update(true);
        });
    }
    if (clearLink) {
        clearLink.addEventListener('click', event => {
            event.preventDefault();
            state.q = '';
            state.p = 1;
            if (searchInput) searchInput.value = '';
            update(true);
        });
    }

    // Nút Back/Forward của trình duyệt: đọc lại trạng thái từ URL.
    window.addEventListener('popstate', () => {
        const back = new URLSearchParams(window.location.search);
        state.category = back.get('category') || ALL;
        state.q = (back.get('q') || '').trim().slice(0, 100);
        state.p = Math.max(1, parseInt(back.get('p'), 10) || 1);
        if (searchInput) searchInput.value = state.q;
        render();
    });

    if (searchInput) searchInput.value = state.q;
    Api.get('menu/public').then(data => {
        groups = Array.isArray(data) ? data : [];
        if (!groups.length) {
            status.textContent = 'Thực đơn đang được cập nhật. Mời bạn quay lại sau nhé.';
            return;
        }
        // Danh mục trên URL không tồn tại -> về "Tất cả".
        if (state.category !== ALL && !groups.some(group => group.category === state.category)) state.category = ALL;
        update(false);
    }).catch(error => {
        status.textContent = error.message || 'Chưa tải được thực đơn. Vui lòng thử tải lại trang.';
        status.classList.add('is-error');
    });
})();
