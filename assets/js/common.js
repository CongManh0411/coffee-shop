// common.js — chỉ còn phần giao diện: nút ☰ và sidebar dạng ngăn kéo trên điện thoại.
// Menu, tên người dùng, đăng nhập / đăng xuất đều do PHP in và xử lý (helpers/nav.php), không gọi API.
function initMobileNav() {
    const main = document.querySelector('.main');
    if (!main || document.querySelector('.mobile-topbar')) return; // trang không có .main, hoặc đã khởi tạo rồi
 
    // Thanh topbar: nút mở menu + tên trang (lấy từ <title>)
    const topbar = document.createElement('div');
    topbar.className = 'mobile-topbar';
    topbar.innerHTML = `
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Mở menu">
            <i class="ri-menu-line"></i>
        </button>
        <span class="mobile-topbar-title"></span>
    `;
    // Tiêu đề trang đưa vào bằng textContent để ký tự HTML trong tên quán / tên món không bị thực thi
    topbar.querySelector('.mobile-topbar-title').textContent = document.title;
    main.prepend(topbar);
 
    // Lớp phủ tối, bấm vào để đóng sidebar
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    overlay.id = 'sidebarOverlay';
    document.body.appendChild(overlay);
 
    const openSidebar = () => {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.classList.add('open');
        overlay.classList.add('active');
    };
 
    const closeSidebar = () => {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.classList.remove('open');
        overlay.classList.remove('active');
    };
 
    document.getElementById('sidebarToggleBtn').addEventListener('click', () => {
        const sidebar = document.querySelector('.sidebar');
        const isOpen = sidebar && sidebar.classList.contains('open');
        if (isOpen) closeSidebar(); else openSidebar();
    });
 
    overlay.addEventListener('click', closeSidebar);
 
    // Bấm chọn 1 mục trong sidebar (Bàn/Menu/Doanh thu...) thì tự đóng drawer lại
    document.addEventListener('click', (e) => {
        if (e.target.closest('.sidebar-menu .tab')) closeSidebar();
    });
 
    // Xoay ngang <-> dọc hoặc resize cửa sổ qua breakpoint desktop: đảm bảo
    // sidebar/overlay không bị kẹt ở trạng thái "đang mở" khi quay lại màn lớn
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) closeSidebar();
    });
}
 
initMobileNav();

// Nút Hiện/Ẩn mật khẩu (chỉ đổi kiểu ô nhập trên trình duyệt, không gọi API)
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;
    const input = document.getElementById(btn.getAttribute('data-toggle-password'));
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Ẩn' : 'Hiện';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
});
