(() => {
    const tabs = document.getElementById('menuCategories');
    const grid = document.getElementById('publicMenuGrid');
    const status = document.getElementById('menuStatus');
    if (!tabs || !grid || !status) return;

    const money = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 });
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch]);
    let groups = [];
    let active = 'Tất cả';

    function render() {
        const shown = active === 'Tất cả' ? groups : groups.filter(group => group.category === active);
        tabs.innerHTML = ['Tất cả', ...groups.map(group => group.category)].map(category =>
            `<button type="button" class="menu-category-tab${active === category ? ' is-active' : ''}" data-category="${escapeHtml(category)}" aria-pressed="${active === category}">${escapeHtml(category)}</button>`
        ).join('');
        grid.innerHTML = shown.flatMap(group => group.items.map(item => {
            const image = itemImage(item, group.category);
            return `<article class="public-menu-card"><div class="public-menu-photo"><img src="${escapeHtml(image)}" alt="${escapeHtml(item.name)}" loading="lazy"><span>${escapeHtml(group.category)}</span></div><div class="public-menu-card-body"><div><h3>${escapeHtml(item.name)}</h3><p>${escapeHtml(item.desc || '')}</p></div><strong>${money.format(Number(item.price) || 0)}</strong></div></article>`;
        })).join('');
        status.hidden = shown.some(group => group.items.length);
        status.textContent = shown.length ? '' : 'Chưa có món nào trong danh mục này.';
    }

    tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-category]');
        if (!button) return;
        active = button.dataset.category;
        render();
    });

    Api.get('menu/public').then(data => {
        groups = Array.isArray(data) ? data : [];
        if (!groups.length) status.textContent = 'Thực đơn đang được cập nhật. Mời bạn quay lại sau nhé.';
        render();
    }).catch(error => {
        status.textContent = error.message || 'Chưa tải được thực đơn. Vui lòng thử tải lại trang.';
        status.classList.add('is-error');
    });
})();
