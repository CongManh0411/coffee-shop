
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

// ---- 2) TÀI KHOẢN DEMO (CHỈ DÙNG KHI CHƯA CÓ BACKEND — xoá cả khối
//         DEMO_ACCOUNTS này khi nối API đăng nhập thật) ----
// Khớp với phần "Tài khoản thử" hiển thị trên Login.html.
const DEMO_ACCOUNTS = [
    { id: 1, username: 'quanly',  password: '123456', roleKey: 'manager', name: 'Nguyễn Văn A' },
    { id: 3, username: 'phucvu',  password: '123456', roleKey: 'waiter',  name: 'Lê Văn C' },
    { id: 2, username: 'bep',     password: '123456', roleKey: 'kitchen', name: 'Phạm Văn D' },
    { id: 4, username: 'thungan', password: '123456', roleKey: 'cashier', name: 'Trần Thị B' },
];

// ---- Tài khoản tạo ở trang Nhân viên (lưu trong localStorage) cũng đăng nhập được ----
const STAFF_STORAGE_KEY = 'coffee_staff_v1';
const STAFF_ROLE_MAP = { 'Quản lý': 'manager', 'Phục vụ': 'waiter', 'Bếp': 'kitchen', 'Thu ngân': 'cashier' };
function getAllAccounts() {
    let extra = [];
    try {
        const list = JSON.parse(localStorage.getItem(STAFF_STORAGE_KEY));
        if (Array.isArray(list)) extra = list.filter(x => STAFF_ROLE_MAP[x.role])
            .map(x => ({ username: x.username, password: x.password, roleKey: STAFF_ROLE_MAP[x.role], name: x.fullName, id: x.id }));
    } catch (err) {}
    return DEMO_ACCOUNTS.concat(extra);
}

// ---- 3) NƠI LƯU PHIÊN ĐĂNG NHẬP (BẢN DEMO: localStorage) ----
const AUTH_STORAGE_KEY = 'coffee_auth_session_v1'; // lưu { roleKey, name }
// BẢN BACKEND: thường sẽ lưu 1 "token" thay vì lưu thẳng roleKey/name,
// ví dụ localStorage.setItem('token', data.token) — xem gợi ý trong
// AuthAPI.login()/getSession() bên dưới.

// ---- 4) AuthAPI — NƠI DUY NHẤT CẦN SỬA KHI CÓ BACKEND THẬT ----
// Toàn bộ phần còn lại của file (chặn trang, form đăng nhập, sidebar ở
// script.js) chỉ gọi qua các hàm của AuthAPI, không đụng trực tiếp vào
// localStorage. Vì vậy khi có backend, chỉ cần sửa ĐÚNG các hàm trong
// object này.
const AuthAPI = {
    // Gọi khi bấm nút "Đăng nhập". Trả về { roleKey, name } nếu đúng,
    // ném lỗi (throw) nếu sai tài khoản/mật khẩu.
    async login(username, password) {
    const user = await Api.post('auth/login', { username: username.trim(), password });
    localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(user));
    return user;
    },

    async logout() {
        try { await Api.post('auth/logout'); } catch (err) {}
        try { localStorage.removeItem(AUTH_STORAGE_KEY); } catch (err) {}
        try { sessionStorage.clear(); } catch (err) {}
        window.location.replace((window.COFFEE_ROOT || './') + 'admin/index.php?page=login');
    },

    // Đọc phiên đăng nhập hiện tại (đồng bộ — không chờ mạng), dùng để
    // chặn trang ngay khi tải trang, tránh nháy nội dung trước khi đá về
    // Login.html. Trả về null nếu chưa đăng nhập.
    getSession() {
        // ---- BẢN DEMO ----
        try {
            const raw = localStorage.getItem(AUTH_STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (err) {
            return null;
        }

        // ---- BẢN BACKEND ----
        // Token không tự nói lên vai trò/tên người dùng, nên bản backend
        // thường cần lưu tạm { roleKey, name } lúc login (như DEMO ở trên)
        // để đọc đồng bộ ở đây, còn quyền THẬT SỰ vẫn phải được kiểm tra
        // lại ở server cho từng API — phần chặn trang ở đây chỉ để tránh
        // hiện nhầm giao diện, không thay được bảo mật phía backend.
    },
};

// Thông tin hiển thị trên sidebar: logo quán + tên/chức vụ người đang
// đăng nhập. script.js gọi UserAPI.getProfile() (qua getProfileOnce())
// để đổ dữ liệu này vào sidebar.
const UserAPI = {
    async getProfile() {
        // ---- BẢN DEMO ----
        const session = AuthAPI.getSession();
        const roleKey = session ? session.roleKey : null;
        return {
            shopLogoUrl: (window.COFFEE_ROOT || './') + 'assets/images/images.jpg',
            name: (session && session.name) || 'Người dùng',
            role: ROLE_LABELS[roleKey] || '—',
            roleKey,
        };

        // ---- BẢN BACKEND ----
        // const res = await fetch('/api/me'); // hoặc /api/auth/me tuỳ backend đặt tên
        // if (!res.ok) throw new Error('Không tải được thông tin người dùng');
        // return await res.json(); // kỳ vọng { shopLogoUrl, name, role, roleKey }
    },
};

// ============================================================
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
    // nghĩa là server chưa có phiên -> xóa phiên cũ trong localStorage (tránh vòng lặp chuyển trang).
    try { localStorage.removeItem(AUTH_STORAGE_KEY); } catch (err) {}

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
