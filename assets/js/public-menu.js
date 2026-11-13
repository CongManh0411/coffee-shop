// public-menu.js — tìm món / đổi danh mục trên trang Thực đơn bằng AJAX (dùng cơ chế của ajax-pager.js).
// Không có JS thì form GET và các link vẫn hoạt động như cũ.
(function () {
    'use strict';
    const form = document.getElementById('menuSearchForm');
    if (!form || !window.AjaxPager) return;
    const input = document.getElementById('menuSearch');
    const hidden = form.querySelector('input[name="category"]');
    const clear = document.getElementById('menuSearchClear');
    let timer = 0;

    function buildUrl(q, category) {
        const p = new URLSearchParams({ page: 'public-menu' });
        if (category) p.set('category', category);
        if (q) p.set('q', q);
        return 'index.php?' + p.toString();
    }

    function search() {
        clearTimeout(timer);
        AjaxPager.load(buildUrl(input.value.trim(), hidden.value));
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); search(); });
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(search, 350);   // chờ người dùng ngừng gõ rồi mới tìm
    });

    // Sau mỗi lần đổi vùng (tìm, đổi tab, trang, Back/Forward): đồng bộ ô tìm, danh mục ẩn và nút "Xóa lọc" theo URL
    document.addEventListener('ajax-pager:updated', function () {
        setTimeout(function () {
            const params = new URLSearchParams(location.search);
            const q = params.get('q') || '';
            hidden.value = params.get('category') || '';
            if (document.activeElement !== input) input.value = q;
            if (clear) {
                clear.hidden = q === '';
                clear.href = buildUrl('', hidden.value);
            }
        }, 0);
    });
})();
