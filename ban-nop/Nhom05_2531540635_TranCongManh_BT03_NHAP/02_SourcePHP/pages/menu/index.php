<header class="catalog-header">
    <a href="index.php?page=home" class="catalog-brand">Góc Nhỏ <span>Coffee &amp; Tea</span></a>
    <nav><a href="index.php?page=home">Trang chủ</a><a href="<?= e(url('menu-categories')) ?>">Danh mục</a><a href="index.php?page=menu">Thực đơn</a></nav>
</header>
<main class="catalog-main">
    <p class="catalog-eyebrow">MENU GÓC NHỎ</p>
    <h1>Thực đơn</h1>
    <p class="catalog-intro">Khám phá đầy đủ các món đang phục vụ tại quán.</p>
    <nav class="catalog-filters" aria-label="Lọc theo danh mục">
        <a class="<?= $selectedCategory === 0 ? 'active' : '' ?>" href="index.php?page=menu">Tất cả</a>
        <?php foreach ($menuCategories as $category): ?>
            <a class="<?= $selectedCategory === (int) $category['category_id'] ? 'active' : '' ?>"
               href="index.php?page=menu&amp;category=<?= (int) $category['category_id'] ?>"><?= e($category['category_name']) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($menuItems): ?>
        <div class="catalog-grid">
            <?php foreach ($menuItems as $item): ?>
                <article class="catalog-card">
                    <a href="index.php?page=menu-detail&amp;id=<?= (int) $item['item_id'] ?>" class="catalog-image-link">
                        <img src="<?= e(menu_item_image($item)) ?>" alt="<?= e($item['item_name']) ?>" loading="lazy">
                    </a>
                    <div class="catalog-card-body">
                        <p class="catalog-category"><?= e($item['category_name']) ?></p>
                        <h2><a href="index.php?page=menu-detail&amp;id=<?= (int) $item['item_id'] ?>"><?= e($item['item_name']) ?></a></h2>
                        <p><?= e($item['description'] ?? '') ?></p>
                        <strong><?= e(number_format((int) $item['price'], 0, ',', '.')) ?> đ</strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="catalog-empty">Danh mục này hiện chưa có món.</p>
    <?php endif; ?>
</main>
