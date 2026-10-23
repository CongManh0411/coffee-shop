const ROLE_LABELS = {
    manager: 'Quản lý',
    waiter: 'Phục vụ',
    kitchen: 'Bếp',
    cashier: 'Thu ngân',
};

const PAGES_BY_ROLE = {
    manager: [
        { path: 'index.php?page=tablemanage', label: 'Bàn',    view: 'tablemanage', id: 'navTables' },
        { path: 'index.php?page=menu',    label: 'Thực đơn',  view: 'menu',    id: 'navMenu' },
        { path: 'index.php?page=revenue', label: 'Doanh thu',  view: 'revenue', id: 'navRevenue' },
        { path: 'index.php?page=history', label: 'Lịch sử',    view: 'history', id: 'navHistory' },
        { path: 'index.php?page=staff',   label: 'Nhân viên',  view: 'staff',   id: 'navStaff' },
        { path: 'index.php?page=shifts',  label: 'Lịch ca',    view: 'shifts',  id: 'navShifts' },
        { path: 'index.php?page=warehouse', label: 'Kho', view: 'warehouse', id: 'navWarehouse' },
        { path: 'index.php?page=ops', label: 'Vận hành', view: 'ops', id: 'navOps' },
        { path: 'index.php?page=logs', label: 'Nhật ký', view: 'logs', id: 'navLogs' },
        { path: 'index.php?page=settings', label: 'Cài đặt Web', view: 'settings', id: 'navSettings' },
    ],
    waiter: [
        { path: 'index.php?page=tables', label: 'Bàn', view: 'tables' },
        { path: 'index.php?page=schedule', label: 'Lịch ca', view: 'schedule', id: 'navShift' },
    ],
    // THU NGÂN: bán hàng (dùng trang Bàn của Phục vụ + đơn mang đi/giao hàng), quản lý đơn, ca & quỹ, lịch ca (xem + đăng ký)
    cashier: [
        { path: 'index.php?page=tables', label: 'Bán hàng', view: 'tables', id: 'navPOS' },
        { path: 'index.php?page=ops', label: 'Vận hành', view: 'ops', id: 'navOps' },
        { path: 'index.php?page=orders', label: 'Đơn hàng', view: 'orders', id: 'navOrders' },
        { path: 'index.php?page=cashshift', label: 'Ca & Quỹ', view: 'cashshift', id: 'navCashShift' },
        { path: 'index.php?page=schedule', label: 'Lịch ca', view: 'schedule', id: 'navShift' },
    ],
    kitchen: [
        { path: 'index.php?page=kitchen', label: 'Bếp', view: 'kitchen', id: 'navKitchen' },
        { path: 'index.php?page=schedule', label: 'Lịch ca', view: 'schedule', id: 'navShift' },
    ],
};

const AuthAPI = {
    async login(username, password) {
        const result = await Api.post('auth/login', { username: username.trim(), password });
        window.SERVER_USER = result.user;
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) tokenMeta.content = result.csrfToken;
        return result.user;
    },
    async logout() {
        try { await Api.post('auth/logout'); } catch (err) {}
        window.SERVER_USER = null;
        try { sessionStorage.clear(); } catch (err) {}
        window.location.replace((window.COFFEE_ROOT || './') + 'admin/index.php?page=login');
    },
    getSession() { return window.SERVER_USER || null; },
};

const UserAPI = {
    async getProfile() {
        const session = AuthAPI.getSession();
        const roleKey = session ? session.roleKey : null;
        return {
            shopLogoUrl: (window.COFFEE_ROOT || './') + 'assets/images/images.jpg',
            name: (session && session.name) || 'Người dùng',
            role: ROLE_LABELS[roleKey] || '—',
            roleKey,
        };
    },
};
// 5) CHẶN TRUY CẬP TRANG KHI CHƯA ĐĂNG NHẬP HOẶC SAI VAI TRÒ
// ------------------------------------------------------------
// Chạy NGAY khi auth.js được nạp (không chờ DOMContentLoaded), vì
// vậy auth.js phải được đặt TRƯỚC script.js trong <body>. Trang
// Login.html có data-page="login" nên không bị chặn ở đây.
// ============================================================
(function guardPage() {
    const currentPage = document.body && document.body.dataset.page;
    if (!currentPage || currentPage === 'login') return; // trang đăng nhập / trang không cần bảo vệ

    const session = AuthAPI.getSession();
    if (!session) {
        window.location.replace((window.COFFEE_ROOT || './') + 'admin/index.php?page=login');
        return;
    }

    const allowedPages = PAGES_BY_ROLE[session.roleKey];
    if (!allowedPages) {
        // Vai trò lạ (dữ liệu hỏng) -> coi như chưa đăng nhập hợp lệ
        window.location.replace((window.COFFEE_ROOT || './') + 'admin/index.php?page=login');
        return;
    }

    const isAllowed = allowedPages.some(p => p.view === currentPage);
    if (!isAllowed) {
        // Đăng nhập rồi nhưng vai trò này không được xem trang hiện tại
        // -> đưa về trang mặc định của vai trò đó (không văng ra ngoài).
        window.location.replace(allowedPages[0].path);
    }
})();

// ============================================================
// 6) XỬ LÝ FORM ĐĂNG NHẬP — chỉ chạy khi trang có #loginForm
//    (tức là chỉ có tác dụng trên trang đăng nhập, các trang khác bỏ qua)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    if (!form) return;

    // Router PHP đã tự chuyển người đã đăng nhập khỏi trang này, nên nếu còn ở đây
    // The server session is authoritative; an anonymous visitor stays on the login page.
    const userEl = document.getElementById('username');
    const passEl = document.getElementById('password');
    const errEl = document.getElementById('loginError');
    const toggleBtn = document.getElementById('togglePass');
    const submitBtn = form.querySelector('.login-btn');

    // Nút "Hiện/Ẩn" mật khẩu
    if (toggleBtn && passEl) {
        toggleBtn.addEventListener('click', () => {
            const showing = passEl.type === 'text';
            passEl.type = showing ? 'password' : 'text';
            toggleBtn.textContent = showing ? 'Hiện' : 'Ẩn';
            toggleBtn.setAttribute('aria-label', showing ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (errEl) errEl.hidden = true;
        if (submitBtn) submitBtn.disabled = true;

        try {
            const session = await AuthAPI.login(userEl.value, passEl.value);
            const home = PAGES_BY_ROLE[session.roleKey]
                ? PAGES_BY_ROLE[session.roleKey][0].path
                : (window.COFFEE_ROOT || './') + 'admin/index.php?page=login';
            window.location.href = home;
        } catch (err) {
            if (errEl) {
                errEl.textContent = err.message || 'Đăng nhập thất bại. Vui lòng thử lại.';
                errEl.hidden = false;
            }
            if (submitBtn) submitBtn.disabled = false;
        }
    });
});

// Nút "Đăng xuất" ở BẤT KỲ đâu (sidebar chung #btnLogout, nút .logout-btn trong giao diện Bàn...) đều chạy qua đây.
// Dùng capture + delegation nên nút được vẽ muộn bằng JS vẫn hoạt động.
document.addEventListener('click', function (e) {
    const b = e.target.closest && e.target.closest('#btnLogout, .btn-logout, .logout-btn');
    if (!b) return;
    e.preventDefault(); e.stopPropagation();
    AuthAPI.logout();
}, true);
