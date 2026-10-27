<header class="catalog-header">
    <a href="<?= e(url('home')) ?>" class="catalog-brand">Góc Nhỏ <span>Coffee &amp; Tea</span></a>
    <nav aria-label="Điều hướng thực đơn"><a href="<?= e(url('home')) ?>">Trang chủ</a><a href="<?= e(url('menu-categories')) ?>">Danh mục</a><a href="<?= e(url('menu')) ?>">Thực đơn</a></nav>
</header>
<main class="catalog-main">
    <p class="catalog-eyebrow">MENU GÓC NHỎ</p>
    <h1>Danh mục thực đơn</h1>
    <p class="catalog-intro">Chọn danh mục để khám phá các món đang phục vụ.</p>
    <?php if ($categorySummaries): ?>
        <div class="catalog-grid">
            <?php foreach ($categorySummaries as $category): ?>
                <article class="catalog-card">
                    <div class="catalog-card-body">
                        <h2><a href="<?= e(url('menu')) ?>&amp;category=<?= (int) $category['category_id'] ?>"><?= e($category['category_name']) ?></a></h2>
                        <p><?= (int) $category['item_count'] ?> món đang phục vụ</p>
                        <?php if ((int) $category['item_count'] > 0): ?>
                            <strong>Giá từ <?= e(money($category['min_price'])) ?></strong>
                        <?php else: ?>
                            <p>Danh mục hiện chưa có món.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="catalog-empty">Chưa có danh mục thực đơn.</p>
    <?php endif; ?>
</main>
