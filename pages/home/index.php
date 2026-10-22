<?php
// pages/home/index.php — Trang chủ hiện đại kết nối quản trị Cài đặt Website và Thực đơn động từ Database
$brandName = setting('brand_name', 'Góc Nhỏ');
$brandTagline = setting('brand_tagline', 'COFFEE & TEA • EST. 2024');
$shopPhone = setting('shop_phone', '0901 234 567');
$shopEmail = setting('shop_email', 'contact@gocnhocoffee.vn');
$shopAddress = setting('shop_address', '123 Đường Cà Phê, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh');
$shopHours = setting('shop_hours', '07:00 – 22:30');
$shopHoursWeekend = setting('shop_hours_weekend', '06:30 – 23:00');

// Danh sách món được tải từ MySQL trong action.php
function get_cat_filter($catName) {
    $low = mb_strtolower((string) $catName, 'UTF-8');
    if (strpos($low, 'cà phê') !== false || strpos($low, 'cafe') !== false || strpos($low, 'coffee') !== false) return 'coffee';
    if (strpos($low, 'trà') !== false || strpos($low, 'tea') !== false) return 'tea';
    if (strpos($low, 'bánh') !== false || strpos($low, 'cake') !== false || strpos($low, 'bakery') !== false) return 'bakery';
    return 'other';
}

function get_item_badge($index, $catFilter, $price) {
    if ($index === 0) return array('★ BEST SELLER', 'best-seller');
    if ($catFilter === 'coffee' && $price >= 40000) return array('CHUẨN VỊ Ý', '');
    if ($catFilter === 'coffee') return array('ĐẬM ĐÀ', '');
    if ($catFilter === 'tea' && $index === 3) return array('★ GIẢI NHIỆT', 'best-seller');
    if ($catFilter === 'tea') return array('MÁT LÀNH', '');
    if ($catFilter === 'bakery') return array('MÓN NGỌT', '');
    return array('ĐƯỢC YÊU THÍCH', '');
}
?>
<!-- Header & Navigation -->
<header class="site-header">
    <a class="site-brand" href="index.php?page=home" aria-label="<?= e($brandName) ?>, trang chủ">
        <div class="brand-avatar-wrap">
            <img src="assets/images/images.jpg" alt="Logo <?= e($brandName) ?>">
        </div>
        <div class="brand-text">
            <span class="brand-title"><?= e($brandName) ?></span>
            <small class="brand-tagline"><?= e($brandTagline) ?></small>
        </div>
    </a>

    <nav class="site-nav" aria-label="Điều hướng chính">
        <a href="#story">Về <?= e($brandName) ?></a>
        <a href="#space">Không gian</a>
        <a href="#menu">Thực đơn</a>
        <a href="#reviews">Đánh giá</a>
        <a href="#booking">Đặt bàn</a>
        <a href="#contact">Liên hệ</a>
    </nav>

    <div class="header-actions">
        <a class="header-booking-btn" href="#booking">
            <span aria-hidden="true">📅</span>
            <span>Đặt bàn ngay</span>
        </a>
        <a class="staff-login" href="admin/index.php?page=login" aria-label="Đăng nhập dành cho nhân viên">
            <span class="staff-login-icon" aria-hidden="true">↗</span>
            <span>Nhân viên</span>
        </a>
        <button id="mobileNavToggle" class="mobile-nav-toggle" type="button" aria-label="Mở menu điều hướng">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<!-- Mobile Navigation Drawer -->
<div id="mobileDrawerOverlay" class="mobile-drawer-overlay"></div>
<aside id="mobileDrawer" class="mobile-drawer" aria-label="Menu trên thiết bị di động">
    <div class="mobile-drawer-header">
        <a class="site-brand" href="index.php?page=home">
            <div class="brand-avatar-wrap">
                <img src="assets/images/images.jpg" alt="Logo <?= e($brandName) ?>">
            </div>
            <div class="brand-text">
                <span class="brand-title"><?= e($brandName) ?></span>
                <small class="brand-tagline"><?= e($brandTagline) ?></small>
            </div>
        </a>
        <button id="mobileDrawerClose" class="mobile-drawer-close" type="button" aria-label="Đóng menu">✕</button>
    </div>
    <nav class="mobile-drawer-links">
        <a href="#story">Về <?= e($brandName) ?> <span aria-hidden="true">→</span></a>
        <a href="#space">Không gian quán <span aria-hidden="true">→</span></a>
        <a href="#menu">Thực đơn <span aria-hidden="true">→</span></a>
        <a href="#menu">Thực đơn <span aria-hidden="true">↗</span></a>
        <a href="#reviews">Đánh giá của khách <span aria-hidden="true">→</span></a>
        <a href="#booking">Đặt bàn trước <span aria-hidden="true">→</span></a>
        <a href="#contact">Địa chỉ &amp; Giờ mở cửa <span aria-hidden="true">→</span></a>
    </nav>
    <div class="mobile-drawer-bottom">
        <a class="button button-dark" href="#booking" style="width: 100%;">
            <span>📅 Đặt bàn giữ chỗ</span>
        </a>
        <a class="staff-login" href="admin/index.php?page=login" style="justify-content: center;">
            <span class="staff-login-icon">↗</span> Cổng nhân viên
        </a>
    </div>
</aside>

<main>
    <!-- Section 1: Hero Banner -->
    <section class="landing-hero" id="hero">
        <div class="landing-copy">
            <div class="hero-pill-badge">
                <span class="sparkle">✨</span>
                <span><?= e(setting('hero_badge', 'CHÀO MỪNG BẠN ĐẾN VỚI GÓC NHỎ')) ?></span>
            </div>
            <p class="eyebrow">COFFEE · TEA · A LITTLE PAUSE</p>
            <h1><?= e(setting('hero_title', 'Cà phê thơm đượm vị.')) ?><br><em><?= e(setting('hero_subtitle', 'Chuyện trò thật an yên.')) ?></em></h1>
            <p class="landing-lede"><?= nl2br(e(setting('hero_lede', 'Một chiếc bàn thân quen ngập tràn ánh nắng, tách cà phê rang mộc chuẩn gu và những khoảng thời gian thư thái dành riêng cho bạn. Ghé Góc Nhỏ để tìm lại nhịp thở chậm giữa phố thị tất bật.'))) ?></p>
            
            <div class="landing-actions">
                <a class="button button-dark" href="#booking">
                    <span>Đặt bàn giữ chỗ</span>
                    <span aria-hidden="true">→</span>
                </a>
                <a class="button button-outline" href="#menu">
                    <span>Khám phá thực đơn</span>
                    <span aria-hidden="true">☕</span>
                </a>
            </div>

            <div class="hero-stats-row">
                <div class="hero-stat-item">
                    <div class="hero-stat-num">4.9<span>★</span></div>
                    <div class="hero-stat-label">Google Reviews</div>
                </div>
                <div class="hero-stat-item">
                    <div class="hero-stat-num">100<span>%</span></div>
                    <div class="hero-stat-label">Hạt mộc nguyên chất</div>
                </div>
                <div class="hero-stat-item">
                    <div class="hero-stat-num"><?= e($shopHours) ?></div>
                    <div class="hero-stat-label">Mở cửa mỗi ngày</div>
                </div>
            </div>
        </div>

        <div class="landing-visual">
            <div class="hero-visual-backdrop"></div>
            
            <!-- Floating Rating Card -->
            <div class="floating-card-rating">
                <span class="rating-stars">★★★★★</span>
                <div class="rating-text">
                    <b>Đậm vị &amp; Cực Chill</b>
                    <small>Hơn 1.200+ lượt khách yêu thích</small>
                </div>
            </div>

            <!-- Main Arched Image -->
            <div class="hero-image-main">
                <img src="assets/images/menu/01-ca-phe-sua-da.jpg" alt="Ly cà phê sữa đá phin truyền thống tại <?= e($brandName) ?>">
            </div>

            <!-- Overlaid Secondary Image -->
            <div class="hero-image-small">
                <img src="assets/images/menu/08-tra-dao-cam-sa.jpg" alt="Trà đào cam sả tươi mát">
            </div>

            <!-- Floating Product Note -->
            <div class="floating-card-feature">
                <span class="feature-tag">GÓC NHỎ GỢI Ý</span>
                <b><?= e(setting('hero_suggestion', 'Cà phê sữa đá phin')) ?></b>
                <small><?= e(setting('hero_suggestion_desc', 'Phin truyền thống đậm đà, ngọt béo.')) ?></small>
            </div>
        </div>
    </section>

    <!-- Section 2: Core Qualities / Value Proposition -->
    <section class="qualities-section" aria-label="Điểm nổi bật của quán">
        <div class="qualities-grid">
            <div class="quality-card">
                <div class="quality-icon-wrap" aria-hidden="true">☕</div>
                <div class="quality-text">
                    <b>Hạt Cà Phê Tuyển Chọn</b>
                    <p>100% Robusta &amp; Arabica mộc rang xay thủ công, đậm đà chuẩn vị.</p>
                </div>
            </div>
            <div class="quality-card">
                <div class="quality-icon-wrap" aria-hidden="true">🌿</div>
                <div class="quality-text">
                    <b>Không Gian Thư Giãn</b>
                    <p>Nhiều cây xanh, góc làm việc yên tĩnh và view ngắm phố thoáng mát.</p>
                </div>
            </div>
            <div class="quality-card">
                <div class="quality-icon-wrap" aria-hidden="true">🍰</div>
                <div class="quality-text">
                    <b>Bánh Tươi &amp; Trà Mát</b>
                    <p>Bánh làm mới mỗi ngày, trà trái cây 100% nguyên liệu tự nhiên tươi sạch.</p>
                </div>
            </div>
            <div class="quality-card">
                <div class="quality-icon-wrap" aria-hidden="true">🛎️</div>
                <div class="quality-text">
                    <b>Đặt Bàn Thuận Tiện</b>
                    <p>Giữ chỗ nhanh chóng trong 30 giây, không cần trả trước hay đặt cọc.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Story Section -->
    <section class="story-section" id="story">
        <div class="story-visual-wrap">
            <div class="story-image-wrap">
                <img src="assets/images/menu/10-banh-tiramisu.jpg" alt="Bánh Tiramisu và không gian quán <?= e($brandName) ?>">
            </div>
            <div class="story-image-tag">
                <span class="story-tag-title">A GOOD PLACE TO SLOW DOWN</span>
                <span class="story-tag-desc">Khoảng lặng êm đềm giữa lòng thành phố</span>
            </div>
        </div>

        <div class="story-copy">
            <p class="eyebrow">CÂU CHUYỆN CỦA <?= mb_strtoupper(e($brandName), 'UTF-8') ?></p>
            <h2><?= e(setting('story_title', 'Đến vì hương vị cà phê,')) ?><br><em><?= e(setting('story_subtitle', 'ở lại vì cảm giác thân quen.')) ?></em></h2>
            
            <div class="story-quote">
                <?= e(setting('story_quote', '“Chúng tôi tin rằng ly cà phê tuyệt vời nhất là ly cà phê được thưởng thức trong sự an yên, thoải mái và trọn vẹn nhất.”')) ?>
            </div>

            <p><?= nl2br(e(setting('story_desc', 'Góc Nhỏ được xây dựng từ niềm đam mê với những hạt cà phê Việt Nam và khát khao tạo ra một không gian dừng chân ấm áp. Từng góc bàn, chậu cây cho đến từng giai điệu lofi êm dịu đều được chăm chút kỹ lưỡng để mang lại cảm giác thân quen như trở về nhà.'))) ?></p>

            <div class="story-highlights-grid">
                <div class="story-highlight-box">
                    <span class="box-icon" aria-hidden="true">✨</span>
                    <div>
                        <b>12+ Món nước &amp; Bánh</b>
                        <small>Pha chế chuẩn vị mỗi ngày</small>
                    </div>
                </div>
                <div class="story-highlight-box">
                    <span class="box-icon" aria-hidden="true">⚡</span>
                    <div>
                        <b>Wifi &amp; Ổ cắm đầy đủ</b>
                        <small>Tiện lợi cho việc học &amp; làm việc</small>
                    </div>
                </div>
                <div class="story-highlight-box">
                    <span class="box-icon" aria-hidden="true">🪴</span>
                    <div>
                        <b>3 Không gian trải nghiệm</b>
                        <small>Sân vườn, máy lạnh, góc sofa</small>
                    </div>
                </div>
                <div class="story-highlight-box">
                    <span class="box-icon" aria-hidden="true">💛</span>
                    <div>
                        <b>Phục vụ tận tâm</b>
                        <small>Luôn đón tiếp bằng nụ cười</small>
                    </div>
                </div>
            </div>

            <div class="story-actions">
                <a class="button button-dark" href="#booking">
                    <span>Ghé chơi <?= e($brandName) ?></span>
                    <span aria-hidden="true">→</span>
                </a>
                <a class="button-link" href="#menu">
                    <span>Xem thực đơn</span>
                    <span aria-hidden="true">↗</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Section 4: Signature Menu Section (ĐỘNG TỪ DATABASE) -->
    <section class="menu-section" id="menu">
        <div class="section-heading">
            <div>
                <p class="eyebrow">THỰC ĐƠN ĐƯỢC YÊU THÍCH</p>
                <h2>Món ngon phải thử hôm nay.</h2>
            </div>
            <p>Từ ly cà phê pha phin truyền thống đậm vị đến tách trà trái cây mát lành, mỗi món uống đều được pha chế tỉ mỉ và phục vụ thật vừa vặn.</p>
        </div>

        <!-- Category filter tabs -->
        <div class="menu-filter-bar" role="tablist" aria-label="Lọc theo loại món">
            <button class="filter-tab is-active" type="button" data-filter="all">Tất cả món</button>
            <button class="filter-tab" type="button" data-filter="coffee">Cà phê chuẩn vị</button>
            <button class="filter-tab" type="button" data-filter="tea">Trà &amp; Trà sữa</button>
            <button class="filter-tab" type="button" data-filter="bakery">Bánh ngọt</button>
        </div>

        <!-- Featured Menu Cards (ĐƯỢC ĐỔ TỰ ĐỘNG TỪ BẢNG MENU_ITEMS TRONG DATABASE) -->
        <div class="menu-cards-grid">
            <?php if (!empty($dbMenuItems)): ?>
                <?php foreach ($dbMenuItems as $idx => $item): 
                    $catFilter = get_cat_filter($item['category_name']);
                    list($badgeText, $badgeClass) = get_item_badge($idx, $catFilter, (int) $item['price']);
                    $imgSrc = menu_item_image($item);
                ?>
                <article class="menu-card" data-category="<?= e($catFilter) ?>" <?= $idx >= 6 ? 'hidden' : '' ?>>
                    <div class="menu-photo">
                        <img src="<?= e($imgSrc) ?>" alt="<?= e($item['item_name']) ?>" loading="lazy">
                        <span class="menu-badge<?= $badgeClass ? ' ' . e($badgeClass) : '' ?>"><?= e($badgeText) ?></span>
                    </div>
                    <div class="menu-card-body">
                        <div class="menu-card-meta">
                            <span class="menu-card-cat"><?= e($item['category_name']) ?></span>
                            <strong class="menu-card-price"><?= money($item['price']) ?></strong>
                        </div>
                        <h3><?= e($item['item_name']) ?></h3>
                        <p class="menu-card-desc"><?= e(!empty($item['description']) ? $item['description'] : 'Món uống được pha chế chỉn chu, đậm đà chuẩn vị tại ' . $brandName . '.') ?></p>
                        <div class="menu-card-footer">
                            <a class="menu-order-btn" href="#booking">Đặt bàn thưởng thức <span aria-hidden="true">→</span></a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback nếu database chưa có món -->
                <p style="grid-column: 1 / -1; text-align: center; color: var(--c-text-muted); padding: 40px 0;">
                    Thực đơn đang được cập nhật từ hệ thống quản lý.
                </p>
            <?php endif; ?>
        </div>

        <div class="menu-section-cta">
            <button class="button button-dark menu-show-more" type="button" id="menuShowMore" <?= count($dbMenuItems) <= 6 ? 'hidden' : '' ?>>
                <span id="menuShowMoreLabel">Xem thêm</span>
                <span aria-hidden="true">↓</span>
            </button>
            <p>Giá niêm yết rõ ràng, đồng bộ trực tiếp từ quầy thu ngân và quản trị quán.</p>
        </div>
    </section>

    <!-- Section 5: Ambiance & Space Showcase -->
    <section class="space-section" id="space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">KHÔNG GIAN TẠI <?= mb_strtoupper(e($brandName), 'UTF-8') ?></p>
                <h2>Tìm góc ngồi dành riêng cho bạn.</h2>
            </div>
            <p>Dù bạn đi một mình, đi cùng người thương hay họp mặt nhóm bạn thân, luôn có một vị trí thoải mái nhất chờ bạn.</p>
        </div>

        <div class="space-grid">
            <div class="space-card">
                <div class="space-photo">
                    <img src="assets/images/menu/08-tra-dao-cam-sa.jpg" alt="Góc sân vườn thoáng mát" loading="lazy">
                    <span class="space-tag">🌿 NGOÀI TRỜI THOÁNG ĐÃNG</span>
                </div>
                <div class="space-body">
                    <h3>Góc Sân Vườn Xanh Mát</h3>
                    <p>Bàn ghế gỗ mộc dưới tán cây râm mát, đón nắng sớm ban mai và gió trời dễ chịu. Rất thích hợp cho buổi sáng thư giãn ngắm phố.</p>
                    <ul class="space-perks">
                        <li>Bóng cây mát rượi</li>
                        <li>Gió trời tự nhiên</li>
                        <li>Được mang thú cưng</li>
                    </ul>
                </div>
            </div>

            <div class="space-card">
                <div class="space-photo">
                    <img src="assets/images/menu/05-ca-phe-cappuccino.jpg" alt="Không gian làm việc yên tĩnh" loading="lazy">
                    <span class="space-tag">💼 YÊN TĨNH MÁY LẠNH</span>
                </div>
                <div class="space-body">
                    <h3>Góc Làm Việc &amp; Đọc Sách</h3>
                    <p>Không gian máy lạnh êm ái, bàn dài rộng rãi, trang bị ổ cắm điện cho từng vị trí và wifi cáp quang tốc độ cao 200Mbps.</p>
                    <ul class="space-perks">
                        <li>Ổ cắm từng bàn</li>
                        <li>Wifi tốc độ cao</li>
                        <li>Nhạc lofi tập trung</li>
                    </ul>
                </div>
            </div>

            <div class="space-card">
                <div class="space-photo">
                    <img src="assets/images/menu/01-ca-phe-sua-da.jpg" alt="Khu vực sofa ấm cúng" loading="lazy">
                    <span class="space-tag">🛋️ ẤM CÚNG &amp; HẸN HÒ</span>
                </div>
                <div class="space-body">
                    <h3>Khu Vực Sofa &amp; Trò Chuyện</h3>
                    <p>Những chiếc ghế sofa êm ái, ánh đèn vàng ấm áp và góc decor phong cách mộc mạc. Điểm hẹn lý tưởng cho những câu chuyện không vội vã.</p>
                    <ul class="space-perks">
                        <li>Sofa êm ái</li>
                        <li>Ánh sáng vàng ấm</li>
                        <li>Góc check-in xinh</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6: Customer Reviews -->
    <section class="reviews-section" id="reviews">
        <div class="section-heading" style="margin-bottom: 24px;">
            <div>
                <p class="eyebrow">CẢM NHẬN TỪ KHÁCH HÀNG</p>
                <h2>Những khoảnh khắc đáng quý.</h2>
            </div>
            <p>Niềm vui lớn nhất của <?= e($brandName) ?> là nhìn thấy nụ cười hài lòng của bạn sau mỗi lần ghé quán.</p>
        </div>

        <div class="reviews-grid">
            <div class="review-card">
                <div>
                    <div class="review-stars">★★★★★</div>
                    <p class="review-quote">“Cà phê sữa đá ở đây đậm đà đúng gu mình thích. Quán mở nhạc lofi rất dễ chịu, mình hay ngồi làm việc ở đây từ sáng đến chiều. Không gian thoáng và nhân viên rất dễ thương!”</p>
                </div>
                <div class="review-author">
                    <div class="author-avatar">N</div>
                    <div class="author-meta">
                        <b>Nguyễn Hoàng Nam</b>
                        <small>Freelance Designer · Khách quen</small>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div>
                    <div class="review-stars">★★★★★</div>
                    <p class="review-quote">“Trà đào cam sả cực ngon, thơm lừng mùi sả tươi. Đặt bàn trước qua trang web rất tiện, tới nơi đã được giữ sẵn bàn view cửa sổ cực xinh cho nhóm mình. 10/10 điểm!”</p>
                </div>
                <div class="review-author">
                    <div class="author-avatar">T</div>
                    <div class="author-meta">
                        <b>Trần Mai Anh</b>
                        <small>Nhân viên văn phòng · Ghé dịp cuối tuần</small>
                    </div>
                </div>
            </div>

            <div class="review-card">
                <div>
                    <div class="review-stars">★★★★★</div>
                    <p class="review-quote">“Bánh Tiramisu mềm mịn và thơm nức mùi cà phê! Bàn ghế sạch sẽ, chậu cây xanh mát mắt, góc nào chụp ảnh cũng ra hình thơ mộng. Chắc chắn sẽ rủ bạn bè ghé lại thường xuyên.”</p>
                </div>
                <div class="review-author">
                    <div class="author-avatar">P</div>
                    <div class="author-meta">
                        <b>Lê Minh Phong</b>
                        <small>Nhiếp ảnh gia tự do · Khách hàng</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 7: Booking & Reservation -->
    <section class="booking-section" id="booking">
        <div class="booking-copy">
            <p class="eyebrow">HẸN GẶP BẠN TẠI <?= mb_strtoupper(e($brandName), 'UTF-8') ?></p>
            <h2>Chỗ ngồi đẹp nhất<br><em>đang chờ cuộc hẹn của bạn.</em></h2>
            <p>Để lại thông tin và thời gian bạn muốn ghé. Nhân viên <?= e($brandName) ?> sẽ liên hệ xác nhận và chuẩn bị sẵn bàn chu đáo nhất trước khi bạn đến.</p>

            <div class="booking-perks-list">
                <div class="booking-perk-item">
                    <div class="perk-icon-wrap" aria-hidden="true">✓</div>
                    <div class="perk-content">
                        <b>Giữ chỗ ưu tiên trong 15 phút</b>
                        <small>Không lo hết bàn vào giờ cao điểm hay cuối tuần.</small>
                    </div>
                </div>
                <div class="booking-perk-item">
                    <div class="perk-icon-wrap" aria-hidden="true">✓</div>
                    <div class="perk-content">
                        <b>Miễn phí chọn khu vực</b>
                        <small>Góc sân vườn, phòng máy lạnh yên tĩnh hoặc bàn sofa rộng rãi.</small>
                    </div>
                </div>
                <div class="booking-perk-item">
                    <div class="perk-icon-wrap" aria-hidden="true">✓</div>
                    <div class="perk-content">
                        <b>Đặt chỗ không cần cọc tiền</b>
                        <small>Thao tác nhanh chóng, hoàn toàn miễn phí chỉ trong 30 giây.</small>
                    </div>
                </div>
            </div>

            <div class="booking-hotline-card">
                <div class="hotline-icon" aria-hidden="true">📞</div>
                <div class="hotline-text">
                    <small>Cần hỗ trợ đặt bàn gấp?</small>
                    <a href="tel:<?= e(preg_replace('/\s+/', '', $shopPhone)) ?>"><?= e($shopPhone) ?></a>
                </div>
            </div>
        </div>

        <!-- Reservation Form -->
        <form id="reservationForm" class="reservation-form">
            <div class="form-heading">
                <span>ĐẶT BÀN TRƯỚC</span>
                <small>Vui lòng điền thông tin của bạn</small>
            </div>

            <div class="form-group">
                <label for="resName">Họ và tên của bạn</label>
                <input id="resName" name="name" autocomplete="name" maxlength="150" required placeholder="Ví dụ: Nguyễn Văn A">
            </div>

            <div class="form-group">
                <label for="resPhone">Số điện thoại liên hệ</label>
                <input id="resPhone" name="phone" type="tel" autocomplete="tel" maxlength="30" required placeholder="Ví dụ: <?= e($shopPhone) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="reservedAt">Ngày và giờ bạn đến</label>
                    <input name="reservedAt" id="reservedAt" type="datetime-local" required>
                </div>

                <div class="form-group">
                    <label for="partySizeSelect">Số lượng khách</label>
                    <select name="partySize" id="partySizeSelect" required>
                        <option value="1">1 khách</option>
                        <option value="2" selected>2 khách</option>
                        <option value="3">3 khách</option>
                        <option value="4">4 khách</option>
                        <option value="5">5 khách</option>
                        <option value="6">6 khách</option>
                        <option value="7">7 khách</option>
                        <option value="8">8 khách</option>
                        <option value="10">10 khách</option>
                        <option value="12">12 khách</option>
                        <option value="15">15 khách</option>
                        <option value="20">20 khách (Nhóm lớn)</option>
                    </select>
                </div>
            </div>

            <!-- Quick Party Size Select Pills -->
            <div class="form-group">
                <label>Chọn nhanh số lượng khách:</label>
                <div class="party-pills">
                    <button type="button" class="party-pill is-active" data-size="2">2 khách (Cặp đôi)</button>
                    <button type="button" class="party-pill" data-size="4">4 khách (Nhóm nhỏ)</button>
                    <button type="button" class="party-pill" data-size="6">6 khách</button>
                    <button type="button" class="party-pill" data-size="8">8 khách</button>
                    <button type="button" class="party-pill" data-size="12">12+ khách (Họp mặt)</button>
                </div>
            </div>

            <div class="form-group">
                <label for="resNote">Ghi chú thêm <span class="optional">(không bắt buộc)</span></label>
                <textarea id="resNote" name="note" maxlength="500" rows="3" placeholder="Ví dụ: cần bàn gần cửa sổ, ghế trẻ em hoặc tổ chức sinh nhật..."></textarea>
            </div>

            <!-- Anti-spam Honeypot Trap -->
            <label class="form-trap" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>

            <!-- Status message container -->
            <p id="reservationMessage" class="reservation-message" role="status" aria-live="polite" hidden></p>

            <button class="button button-dark booking-submit" type="submit">
                <span>Gửi yêu cầu đặt bàn</span>
                <span aria-hidden="true">→</span>
            </button>
            <small class="form-footnote">Quán sẽ liên hệ lại qua điện thoại trong vòng 10-15 phút để xác nhận giữ chỗ.</small>
        </form>
    </section>

    <!-- Section 8: Contact & Location -->
    <section class="contact-section" id="contact">
        <div class="section-heading">
            <div>
                <p class="eyebrow">THÔNG TIN LIÊN HỆ</p>
                <h2>Ghé thăm <?= e($brandName) ?>.</h2>
            </div>
            <p>Chúng tôi luôn mở cửa đón chào bạn từ sáng sớm tinh mơ đến tối muộn.</p>
        </div>

        <div class="contact-cards-grid">
            <div class="contact-card">
                <div class="contact-card-icon" aria-hidden="true">📍</div>
                <h4>Địa chỉ quán</h4>
                <p><?= e($shopAddress) ?></p>
                <p style="margin-top: 8px; font-size: 11.5px; color: var(--c-primary);">(Có chỗ đậu xe máy &amp; ô tô miễn phí)</p>
            </div>

            <div class="contact-card">
                <div class="contact-card-icon" aria-hidden="true">⏰</div>
                <h4>Giờ mở cửa</h4>
                <p>Thứ 2 – Thứ 6: <strong><?= e($shopHours) ?></strong></p>
                <p style="margin-top: 4px; font-size: 12.5px;">Thứ 7 – CN: <strong><?= e($shopHoursWeekend) ?></strong></p>
                <p style="margin-top: 8px; font-size: 11.5px; color: var(--c-primary);">Phục vụ xuyên Lễ &amp; Tết</p>
            </div>

            <div class="contact-card">
                <div class="contact-card-icon" aria-hidden="true">📞</div>
                <h4>Hotline hỗ trợ</h4>
                <p><a href="tel:<?= e(preg_replace('/\s+/', '', $shopPhone)) ?>"><?= e($shopPhone) ?></a></p>
                <p style="margin-top: 8px; font-size: 11.5px; color: var(--c-text-light);">Hỗ trợ đặt bàn &amp; mang đi</p>
            </div>

            <div class="contact-card">
                <div class="contact-card-icon" aria-hidden="true">✉️</div>
                <h4>Email &amp; Mạng xã hội</h4>
                <p><a href="mailto:<?= e($shopEmail) ?>"><?= e($shopEmail) ?></a></p>
                <p style="margin-top: 8px; font-size: 11.5px; color: var(--c-text-light);">Theo dõi @gocnhocoffee</p>
            </div>
        </div>
    </section>
</main>

<!-- Floating Mobile Booking Button -->
<a class="floating-mobile-booking" href="#booking" aria-label="Đặt bàn nhanh trên di động">
    <span aria-hidden="true">📅</span>
    <span>Đặt bàn ngay</span>
</a>

<!-- Back To Top Button -->
<button id="backToTopBtn" class="back-to-top-btn" type="button" aria-label="Lên đầu trang">
    <span aria-hidden="true">↑</span>
</button>

<!-- Site Footer -->
<footer class="site-footer">
    <div class="footer-top">
        <div>
            <a class="footer-brand" href="index.php?page=home">
                <?= e($brandName) ?>
                <small><?= e($brandTagline) ?></small>
            </a>
            <p class="footer-desc">Một góc nhỏ an yên giữa phố thị tất bật. Nơi bạn tìm thấy ly cà phê chuẩn vị, bản nhạc êm dịu và những cuộc hẹn đáng nhớ.</p>
            <div class="footer-socials">
                <a class="social-btn" href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook">f</a>
                <a class="social-btn" href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram">ig</a>
                <a class="social-btn" href="https://tiktok.com" target="_blank" rel="noopener" aria-label="TikTok">tk</a>
            </div>
        </div>

        <div class="footer-col">
            <h4>Khám Phá</h4>
            <ul class="footer-links">
                <li><a href="#story">Về <?= e($brandName) ?></a></li>
                <li><a href="#space">Không gian quán</a></li>
                <li><a href="#menu">Món uống signature</a></li>
                <li><a href="#menu">Thực đơn</a></li>
                <li><a href="#reviews">Đánh giá khách hàng</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Giờ Phục Vụ</h4>
            <ul class="footer-hours-list">
                <li><span>Thứ 2 – Thứ 6:</span> <b><?= e($shopHours) ?></b></li>
                <li><span>Thứ 7 – CN:</span> <b><?= e($shopHoursWeekend) ?></b></li>
                <li><span>Ngày Lễ / Tết:</span> <b>Mở cửa bình thường</b></li>
                <li><span>Hotline:</span> <b><?= e($shopPhone) ?></b></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Dành Cho Nhân Viên</h4>
            <p style="font-size: 13px; color: #a89a87; line-height: 1.6; margin-bottom: 16px;">Hệ thống quản lý nội bộ bàn ăn, thực đơn, ca làm và đơn hàng.</p>
            <a class="button button-outline" href="admin/index.php?page=login" style="border-color: rgba(205,165,116,0.4); color: #deb179; width: 100%;">
                <span>Đăng nhập nhân viên</span>
                <span aria-hidden="true">↗</span>
            </a>
        </div>
    </div>

    <div class="footer-bottom">
        <span>© <?= date('Y') ?> <?= e($brandName) ?>. Tất cả quyền được bảo lưu.</span>
        <a href="#hero">Về đầu trang ↑</a>
    </div>
</footer>
