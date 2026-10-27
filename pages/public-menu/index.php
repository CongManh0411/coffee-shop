<header class="site-header">
    <a class="site-brand" href="index.php?page=home" aria-label="Góc Nhỏ Coffee, trang chủ">
        <img src="assets/images/images.jpg" alt=""><span><b>Góc Nhỏ</b><small>COFFEE &amp; TEA</small></span>
    </a>
    <nav class="site-nav" aria-label="Điều hướng chính">
        <a href="index.php?page=home#story">Về Góc Nhỏ</a>
        <a href="index.php?page=public-menu" aria-current="page">Thực đơn</a>
        <a href="index.php?page=home#booking">Đặt bàn</a>
    </nav>
    <a class="staff-login" href="admin/index.php?page=login"><span class="staff-login-icon" aria-hidden="true">↗</span><span>Nhân viên</span></a>
</header>
<main class="public-menu-main">
    <section class="public-menu-intro">
        <p class="eyebrow">PHA CHUẨN VỊ · CHỌN MÓN BẠN THÍCH</p>
        <h1>Một chút ngon<br><em>cho ngày của bạn.</em></h1>
        <p>Thực đơn Góc Nhỏ được làm mới từ những món quen thuộc, pha chế chỉn chu và phục vụ thật vừa vặn.</p>
        <a class="button button-dark" href="index.php?page=home#booking">Đặt bàn ghé chơi <span aria-hidden="true">→</span></a>
        <div class="public-menu-stamp">GÓC NHỎ<br><span>COFFEE &amp; TEA</span></div>
    </section>
    <section class="public-menu-list" aria-label="Danh sách thực đơn">
        <div class="public-menu-heading"><div><p class="eyebrow">THỰC ĐƠN</p><h2>Chọn món bạn thích.</h2></div><p>Giá và món hiện có được cập nhật trực tiếp từ quán.</p></div>
        <nav id="menuCategories" class="menu-category-tabs" aria-label="Lọc theo danh mục"></nav>
        <p id="menuStatus" class="menu-status" role="status">Đang tải thực đơn…</p>
        <div id="publicMenuGrid" class="public-menu-grid"></div>
    </section>
    <section class="menu-booking-banner"><div><p class="eyebrow">MỘT CHỖ NGỒI ĐANG CHỜ BẠN</p><h2>Hẹn nhau ở Góc Nhỏ nhé.</h2></div><a class="button button-dark" href="index.php?page=home#booking">Đặt bàn <span aria-hidden="true">→</span></a></section>
</main>
<footer class="site-footer"><a class="footer-brand" href="index.php?page=home">Góc Nhỏ <small>COFFEE &amp; TEA</small></a><span>© <?= date('Y') ?> Góc Nhỏ Coffee &amp; Tea</span><a href="admin/index.php?page=login">Đăng nhập nhân viên <span aria-hidden="true">↗</span></a></footer>
