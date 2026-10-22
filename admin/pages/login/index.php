<!-- pages/login/index.php — giao diện trang "Đăng nhập" (header/footer do index.php gốc in) -->
  <main class="login-card">
    <div class="login-logo" aria-hidden="true">☕</div>
    <h1 class="login-title">Đăng nhập</h1>
    <p class="login-sub">Dùng tài khoản do Quản lý cấp</p>

    <form id="loginForm" class="login-form" novalidate>
      <label class="login-field">
        <span>Tên đăng nhập</span>
        <input type="text" id="username" autocomplete="username" required autofocus>
      </label>

      <label class="login-field">
        <span>Mật khẩu</span>
        <div class="login-pass">
          <input type="password" id="password" autocomplete="current-password" required>
          <button type="button" id="togglePass" class="login-eye" aria-label="Hiện mật khẩu">Hiện</button>
        </div>
      </label>

      <p id="loginError" class="login-error" role="alert" hidden></p>

      <button type="submit" class="login-btn">Đăng nhập</button>
    </form>

    <div class="login-demo">
      <strong>Tài khoản thử</strong>
      <ul>
        <li>quanly / 123456 — Quản lý</li>
        <li>phucvu / 123456 — Phục Vụ</li>
        <li>thungan / 123456 — Thu Ngân</li>
        <li>bep / 123456 — Bếp</li>
      </ul>
    </div>
    <a class="login-home-link" href="../index.php?page=home">← Về trang chủ</a>
  </main>
