<header class="catalog-header">
    <a href="index.php?page=home" class="catalog-brand"><?= e(brand_name()) ?> <span><?= e(brand_subtitle()) ?></span></a>
    <nav><a href="index.php?page=home">Trang chủ</a><a href="index.php?page=menu">Thực đơn</a></nav>
</header>
<main class="catalog-main">
    <p class="catalog-eyebrow">MENU <?= e(mb_strtoupper(brand_name(), "UTF-8")) ?></p>
    <h1>Thực đơn</h1>
    <p class="catalog-intro">Khám phá đầy đủ các món đang phục vụ tại quán.</p>
    <form class="catalog-search" method="get" action="index.php" role="search">
        <input type="hidden" name="page" value="menu">
        <?php if ($selectedCategory > 0): ?><input type="hidden" name="category" value="<?= (int) $selectedCategory ?>"><?php endif; ?>
        <label for="catalogSearch" class="sr-only">Tìm món</label>
        <input type="search" id="catalogSearch" name="q" value="<?= e($menuSearch) ?>" placeholder="Tìm món theo tên hoặc mô tả…" maxlength="100">
        <button type="submit">Tìm</button>
        <?php if ($menuSearch !== ''): ?><a class="catalog-search-clear" href="index.php?page=menu<?= $selectedCategory > 0 ? '&amp;category=' . (int) $selectedCategory : '' ?>">Xóa lọc</a><?php endif; ?>
    </form>
    <?php $keepQ = $menuSearch !== '' ? '&amp;q=' . rawurlencode($menuSearch) : ''; ?>
    <nav class="catalog-filters" aria-label="Lọc theo danh mục">
        <a class="<?= $selectedCategory === 0 ? 'active' : '' ?>" href="index.php?page=menu<?= $keepQ ?>">Tất cả</a>
        <?php foreach ($menuCategories as $category): ?>
            <a class="<?= $selectedCategory === (int) $category['category_id'] ? 'active' : '' ?>"
               href="index.php?page=menu&amp;category=<?= (int) $category['category_id'] ?><?= $keepQ ?>"><?= e($category['category_name']) ?> <small>(<?= (int) $category['item_count'] ?>)</small></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($menuSearch !== ''): ?>
        <p class="catalog-result-note">Tìm thấy <?= (int) $menuPager['total'] ?> món cho “<?= e($menuSearch) ?>”.</p>
    <?php endif; ?>
    <div data-ajax-region="catalog">
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
        <p class="catalog-empty"><?= $menuSearch !== '' ? 'Không có món nào khớp với “' . e($menuSearch) . '”.' : 'Danh mục này hiện chưa có món.' ?></p>
    <?php endif; ?>
    <?php
    $pagerQuery = array('page' => 'menu');
    if ($selectedCategory > 0) $pagerQuery['category'] = $selectedCategory;
    if ($menuSearch !== '') $pagerQuery['q'] = $menuSearch;
    echo render_pagination($menuPager['page'], $menuPager['pages'], $pagerQuery);
    ?>
    </div>
</main>
