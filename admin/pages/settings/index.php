<!-- admin/pages/settings/index.php — Giao diện Cài đặt Website -->
<main class="main">
    <div class="settings-container">
        <!-- Header -->
        <div class="settings-header">
            <div class="settings-header-title">
                <h2>Cài đặt Website &amp; Trang chủ</h2>
                <p>Tùy chỉnh thông tin quán, hotline, địa chỉ và các nội dung hiển thị trên trang chủ.</p>
            </div>
            <div class="settings-header-actions">
                <a class="btn-preview-site" href="../index.php?page=home" target="_blank" rel="noopener">
                    <span>Xem trang chủ</span>
                    <span aria-hidden="true">↗</span>
                </a>
            </div>
        </div>

        <?php if (!empty($savedMessage)): ?>
            <div class="settings-alert success">
                <span aria-hidden="true">✓</span>
                <span><?= e($savedMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="settings-alert error">
                <span aria-hidden="true">✕</span>
                <span><?= e($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=settings" enctype="multipart/form-data">
            <input type="hidden" name="save_settings" value="1">

            <div class="settings-card">
                <h3 class="settings-card-title"><span aria-hidden="true">🖼️</span> Quản trị Logo</h3>
                <p class="settings-card-subtitle">Logo được dùng trên trang chủ và các khu vực nhận diện thương hiệu.</p>
                <div class="settings-logo-box">
                    <div class="settings-logo-preview-wrap">
                        <div class="settings-logo-avatar">
                            <img id="shop-logo-preview" src="<?= e(shop_logo(true)) ?>" alt="Logo hiện tại của quán">
                        </div>
                        <small>LOGO HIỆN TẠI</small>
                    </div>
                    <div class="settings-logo-upload-controls">
                        <label class="custom-file-upload">
                            <span aria-hidden="true">📁</span> Chọn ảnh logo
                            <input type="file" id="shop_logo_file" name="shop_logo_file" accept="image/jpeg,image/png,image/webp,image/svg+xml,image/gif">
                        </label>
                        <span class="logo-file-name" id="shop-logo-file-name">JPG, PNG, WEBP, SVG hoặc GIF · tối đa 5 MB</span>
                        <label class="reset-logo-label">
                            <input type="checkbox" name="reset_logo" value="1">
                            Dùng lại logo mặc định
                        </label>
                    </div>
                </div>
            </div>

            <!-- Card 1: Thông tin thương hiệu & Liên hệ -->
            <div class="settings-card">
                <h3 class="settings-card-title">
                    <span aria-hidden="true">📍</span> Thông tin Quán &amp; Liên hệ
                </h3>
                <p class="settings-card-subtitle">Hiển thị ở Header, phần liên hệ và Chân trang (Footer).</p>

                <div class="settings-grid-2">
                    <div class="settings-form-group">
                        <label for="brand_name">Tên thương hiệu quán</label>
                        <input type="text" id="brand_name" name="brand_name" value="<?= e(setting('brand_name', 'Góc Nhỏ')) ?>" required>
                    </div>

                    <div class="settings-form-group">
                        <label for="brand_tagline">Khẩu hiệu / Năm thành lập</label>
                        <input type="text" id="brand_tagline" name="brand_tagline" value="<?= e(setting('brand_tagline', 'COFFEE & TEA • EST. 2024')) ?>">
                    </div>

                    <div class="settings-form-group">
                        <label for="shop_phone">Hotline đặt bàn / Liên hệ</label>
                        <input type="text" id="shop_phone" name="shop_phone" value="<?= e(setting('shop_phone', '0901 234 567')) ?>" required>
                        <span class="settings-field-hint">Số hotline hỗ trợ khách đặt bàn nhanh.</span>
                    </div>

                    <div class="settings-form-group">
                        <label for="shop_email">Email liên hệ</label>
                        <input type="email" id="shop_email" name="shop_email" value="<?= e(setting('shop_email', 'contact@gocnhocoffee.vn')) ?>">
                    </div>

                    <div class="settings-form-group">
                        <label for="shop_hours">Giờ mở cửa ngày thường (T2 - T6)</label>
                        <input type="text" id="shop_hours" name="shop_hours" value="<?= e(setting('shop_hours', '07:00 – 22:30')) ?>">
                    </div>

                    <div class="settings-form-group">
                        <label for="shop_hours_weekend">Giờ mở cửa cuối tuần (T7 - CN)</label>
                        <input type="text" id="shop_hours_weekend" name="shop_hours_weekend" value="<?= e(setting('shop_hours_weekend', '06:30 – 23:00')) ?>">
                    </div>
                </div>

                <div class="settings-form-group" style="margin-top: 14px;">
                    <label for="shop_address">Địa chỉ quán</label>
                    <input type="text" id="shop_address" name="shop_address" value="<?= e(setting('shop_address', '123 Đường Cà Phê, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh')) ?>">
                    <span class="settings-field-hint">Hiển thị ở khu vực liên hệ và chân trang.</span>
                </div>
            </div>

            <!-- Card 2: Nội dung Hero Banner (Đầu trang chủ) -->
            <div class="settings-card">
                <h3 class="settings-card-title">
                    <span aria-hidden="true">✨</span> Nội dung Banner Đầu trang (Hero)
                </h3>
                <p class="settings-card-subtitle">Phần đầu tiên khách nhìn thấy khi mở trang chủ.</p>

                <div class="settings-form-group">
                    <label for="hero_badge">Huy hiệu nhỏ mở đầu (Badge)</label>
                    <input type="text" id="hero_badge" name="hero_badge" value="<?= e(setting('hero_badge', 'CHÀO MỪNG BẠN ĐẾN VỚI GÓC NHỎ')) ?>">
                </div>

                <div class="settings-grid-2">
                    <div class="settings-form-group">
                        <label for="hero_title">Tiêu đề chính (Dòng 1)</label>
                        <input type="text" id="hero_title" name="hero_title" value="<?= e(setting('hero_title', 'Cà phê thơm đượm vị.')) ?>">
                    </div>

                    <div class="settings-form-group">
                        <label for="hero_subtitle">Tiêu đề chính (Dòng 2 - In nghiêng)</label>
                        <input type="text" id="hero_subtitle" name="hero_subtitle" value="<?= e(setting('hero_subtitle', 'Chuyện trò thật an yên.')) ?>">
                    </div>
                </div>

                <div class="settings-form-group">
                    <label for="hero_lede">Đoạn văn giới thiệu ngắn</label>
                    <textarea id="hero_lede" name="hero_lede" rows="3"><?= e(setting('hero_lede', 'Một chiếc bàn thân quen ngập tràn ánh nắng, tách cà phê rang mộc chuẩn gu và những khoảng thời gian thư thái dành riêng cho bạn. Ghé Góc Nhỏ để tìm lại nhịp thở chậm giữa phố thị tất bật.')) ?></textarea>
                </div>

                <div class="settings-grid-2" style="margin-top: 14px;">
                    <div class="settings-form-group">
                        <label for="hero_suggestion">Tên món gợi ý hôm nay</label>
                        <input type="text" id="hero_suggestion" name="hero_suggestion" value="<?= e(setting('hero_suggestion', 'Cà phê sữa đá phin')) ?>">
                    </div>

                    <div class="settings-form-group">
                        <label for="hero_suggestion_desc">Mô tả ngắn món gợi ý</label>
                        <input type="text" id="hero_suggestion_desc" name="hero_suggestion_desc" value="<?= e(setting('hero_suggestion_desc', 'Phin truyền thống đậm đà, ngọt béo.')) ?>">
                    </div>
                </div>
            </div>

            <!-- Card 3: Câu chuyện & Triết lý thương hiệu -->
            <div class="settings-card">
                <h3 class="settings-card-title">
                    <span aria-hidden="true">☕</span> Câu chuyện Quán &amp; Triết lý (Story)
                </h3>
                <p class="settings-card-subtitle">Khu vực kể câu chuyện, nguồn cảm hứng và sự gắn kết với khách hàng.</p>

                <div class="settings-grid-2">
                    <div class="settings-form-group">
                        <label for="story_title">Tiêu đề câu chuyện (Dòng 1)</label>
                        <input type="text" id="story_title" name="story_title" value="<?= e(setting('story_title', 'Đến vì hương vị cà phê,')) ?>">
                    </div>

                    <div class="settings-form-group">
                        <label for="story_subtitle">Tiêu đề câu chuyện (Dòng 2)</label>
                        <input type="text" id="story_subtitle" name="story_subtitle" value="<?= e(setting('story_subtitle', 'ở lại vì cảm giác thân quen.')) ?>">
                    </div>
                </div>

                <div class="settings-form-group">
                    <label for="story_quote">Câu trích dẫn tâm đắc (Quote)</label>
                    <input type="text" id="story_quote" name="story_quote" value="<?= e(setting('story_quote', '“Chúng tôi tin rằng ly cà phê tuyệt vời nhất là ly cà phê được thưởng thức trong sự an yên, thoải mái và trọn vẹn nhất.”')) ?>">
                </div>

                <div class="settings-form-group">
                    <label for="story_desc">Đoạn văn câu chuyện quán</label>
                    <textarea id="story_desc" name="story_desc" rows="4"><?= e(setting('story_desc', 'Góc Nhỏ được xây dựng từ niềm đam mê với những hạt cà phê Việt Nam và khát khao tạo ra một không gian dừng chân ấm áp. Từng góc bàn, chậu cây cho đến từng giai điệu lofi êm dịu đều được chăm chút kỹ lưỡng để mang lại cảm giác thân quen như trở về nhà.')) ?></textarea>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="settings-actions-bar">
                <span>Nhớ bấm <strong>Lưu cài đặt</strong> sau khi thay đổi để áp dụng ra trang chủ.</span>
                <button type="submit" class="btn-save-settings">
                    Lưu cài đặt Website
                </button>
            </div>
        </form>
    </div>
</main>
<script>
(() => {
    const input = document.getElementById('shop_logo_file');
    const preview = document.getElementById('shop-logo-preview');
    const fileName = document.getElementById('shop-logo-file-name');
    if (!input || !preview || !fileName) return;
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;
        fileName.textContent = file.name;
        if (file.type.startsWith('image/')) preview.src = URL.createObjectURL(file);
    });
})();
</script>

