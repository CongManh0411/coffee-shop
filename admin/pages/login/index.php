<!-- pages/login/index.php — giao diện trang "Đăng nhập" (header/footer do index.php gốc in) -->
  <main class="login-card">
    <div class="login-logo" aria-hidden="true">☕</div>
    <h1 class="login-title">Đăng nhập</h1>
    <p class="login-sub">Dùng tài khoản do Quản lý cấp</p>

    <form id="loginForm" class="login-form" method="post" action="index.php?page=login" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="auth_action" value="login">
      <label class="login-field">
        <span>Tên đăng nhập</span>
        <input type="text" id="username" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= e($loginUsername) ?>" required autofocus>
      </label>

      <label class="login-field">
        <span>Mật khẩu</span>
        <div class="login-pass">
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
      </label>

      <label class="login-remember">
        <input type="checkbox" id="rememberMe" name="remember" value="1">
        <span>Lưu Mật khẩu</span>
      </label>

      <?php if ($loginError): ?><p id="loginError" class="login-error" role="alert"><?= e($loginError) ?></p><?php endif; ?>

      <button type="submit" class="login-btn">Đăng nhập</button>
    </form>
    <a class="login-home-link" href="../index.php?page=home">← Về trang chủ</a>
  </main>
