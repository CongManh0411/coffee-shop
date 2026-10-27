<main class="login-card" role="main">
    <div class="login-logo" aria-hidden="true">404</div>
    <h1 class="login-title">Không tìm thấy trang</h1>
    <p class="login-sub">Trang bạn đang tìm không tồn tại hoặc đã được chuyển đi.</p>
    <a class="login-btn" href="<?= e(!empty($isAdmin) ? url('login') : 'admin/index.php?page=login') ?>" style="display:block;text-align:center;text-decoration:none;">
        Về trang đăng nhập
    </a>
</main>
