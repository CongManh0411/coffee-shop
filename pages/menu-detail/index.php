<header class="catalog-header">
    <a href="index.php?page=home" class="catalog-brand">Góc Nhỏ <span>Coffee &amp; Tea</span></a>
    <nav aria-label="Điều hướng chính">
        <a href="index.php?page=home">Trang chủ</a>
        <a href="index.php?page=menu">Thực đơn</a>
    </nav>
</header>

<main class="catalog-main catalog-detail-page">
    <?php if (!$menuItem): ?>
        <section class="catalog-not-found">
            <p class="catalog-eyebrow">404 · KHÔNG TÌM THẤY</p>
            <h1>Món này không có trong thực đơn</h1>
            <p>Món có thể đã bị ẩn hoặc đường dẫn không còn hợp lệ.</p>
            <a class="catalog-back" href="index.php?page=menu">Quay lại thực đơn <span aria-hidden="true">→</span></a>
        </section>
    <?php else: ?>
        <nav class="catalog-breadcrumb" aria-label="Đường dẫn">
            <a href="index.php?page=home">Trang chủ</a><span aria-hidden="true">/</span>
            <a href="index.php?page=menu">Thực đơn</a><span aria-hidden="true">/</span>
            <span><?= e($menuItem['item_name']) ?></span>
        </nav>

        <article class="catalog-detail">
            <div class="catalog-detail-visual">
                <img src="<?= e(menu_item_image($menuItem)) ?>" alt="<?= e($menuItem['item_name']) ?>">
                <span class="catalog-photo-caption"><span aria-hidden="true">✦</span> Góc Nhỏ Coffee &amp; Tea</span>
            </div>
            <div class="catalog-detail-info">
                <span class="catalog-detail-kicker">HƯƠNG VỊ GÓC NHỎ</span>
                <p class="catalog-category-pill"><span aria-hidden="true">☕</span> <?= e($menuItem['category_name']) ?></p>
                <h1><?= e($menuItem['item_name']) ?></h1>
                <p class="catalog-detail-description"><?= e($menuItem['description'] ?: 'Được chuẩn bị tại quán với sự chăm chút trong từng phần.') ?></p>

                <div class="catalog-detail-price">
                    <span>Giá niêm yết</span>
                    <strong><?= e(number_format((int) $menuItem['price'], 0, ',', '.')) ?> đ</strong>
                </div>

                <div class="catalog-detail-note">
                    <span class="catalog-note-icon" aria-hidden="true">✓</span>
                    <p><b>Món đang có trong thực đơn</b><small>Ghé quán để thưởng thức món này tại Góc Nhỏ.</small></p>
                </div>

                <a class="catalog-back catalog-back-link" href="index.php?page=menu"><span aria-hidden="true">←</span> Khám phá thêm món</a>
            </div>
        </article>

        <?php if ($relatedMenuItems): ?>
            <section class="catalog-related" aria-labelledby="relatedMenuHeading">
                <div class="catalog-related-heading">
                    <div><p class="catalog-eyebrow">CÙNG DANH MỤC</p><h2 id="relatedMenuHeading">Có thể bạn cũng thích</h2></div>
                    <a href="index.php?page=menu&amp;category=<?= (int) $menuItem['category_id'] ?>">Xem danh mục <span aria-hidden="true">→</span></a>
                </div>
                <div class="catalog-related-grid">
                    <?php foreach ($relatedMenuItems as $related): ?>
                        <a class="catalog-related-card" href="index.php?page=menu-detail&amp;id=<?= (int) $related['item_id'] ?>">
                            <img src="<?= e(menu_item_image($related)) ?>" alt="<?= e($related['item_name']) ?>" loading="lazy">
                            <span><small><?= e($related['category_name']) ?></small><b><?= e($related['item_name']) ?></b>
                                <strong><?= e(number_format((int) $related['price'], 0, ',', '.')) ?> đ</strong></span>
                            <i aria-hidden="true">↗</i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
