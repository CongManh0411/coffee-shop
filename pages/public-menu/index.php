<header class="site-header">
    <a class="site-brand" href="index.php?page=home" aria-label="<?= e(brand_full()) ?>, trang chủ">
        <img src="<?= e(shop_logo()) ?>" alt=""><span><b><?= e(brand_name()) ?></b><small><?= e(mb_strtoupper(brand_subtitle(), "UTF-8")) ?></small></span>
    </a>
    <nav class="site-nav" aria-label="Điều hướng chính">
        <a href="index.php?page=home">Trang chủ</a>
        <a href="index.php?page=public-menu" aria-current="page">Thực đơn</a>
        <a href="index.php?page=home#booking">Đặt bàn</a>
    </nav>
    <a class="staff-login" href="admin/index.php?page=login"><span class="staff-login-icon" aria-hidden="true">↗</span><span>Nhân viên</span></a>
</header>
<main class="public-menu-main">
    <section class="public-menu-intro">
        <p class="eyebrow">PHA CHUẨN VỊ · CHỌN MÓN BẠN THÍCH</p>
        <h1>Một chút ngon<br><em>cho ngày của bạn.</em></h1>
        <p>Thực đơn <?= e(brand_name()) ?> được làm mới từ những món quen thuộc, pha chế chỉn chu và phục vụ thật vừa vặn.</p>
        <a class="button button-dark" href="index.php?page=home#booking">Đặt bàn ghé chơi <span aria-hidden="true">→</span></a>
        <div class="public-menu-stamp"><?= e(mb_strtoupper(brand_name(), "UTF-8")) ?><br><span><?= e(mb_strtoupper(brand_subtitle(), "UTF-8")) ?></span></div>
    </section>
    <section class="public-menu-list" aria-label="Danh sách thực đơn">
        <div class="public-menu-heading"><div><p class="eyebrow">THỰC ĐƠN</p><h2>Chọn món bạn thích.</h2></div><p>Giá và món hiện có được cập nhật trực tiếp từ quán.</p></div>
        <form id="menuSearchForm" class="catalog-search" role="search" method="get" action="index.php">
            <input type="hidden" name="page" value="public-menu">
            <?php if ($pmCategoryName !== ''): ?><input type="hidden" name="category" value="<?= e($pmCategoryName) ?>"><?php endif; ?>
            <label for="menuSearch" class="sr-only">Tìm món</label>
            <input type="search" id="menuSearch" name="q" placeholder="Tìm món theo tên hoặc mô tả…" maxlength="100" autocomplete="off" value="<?= e($pmSearch) ?>">
            <button type="submit">Tìm</button>
            <?php if ($pmSearch !== ''): ?><a id="menuSearchClear" class="catalog-search-clear" href="<?= e(pm_url($pmCategoryName, '')) ?>">Xóa lọc</a><?php endif; ?>
        </form>
        <nav id="menuCategories" class="menu-category-tabs" aria-label="Lọc theo danh mục">
            <a class="menu-category-tab<?= $pmCategoryName === '' ? ' is-active' : '' ?>" href="<?= e(pm_url('', $pmSearch)) ?>">Tất cả</a>
            <?php foreach ($pmCategories as $c): ?>
                <a class="menu-category-tab<?= $pmCategoryName === $c['category_name'] ? ' is-active' : '' ?>" href="<?= e(pm_url($c['category_name'], $pmSearch)) ?>"><?= e($c['category_name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($pmError): ?>
            <p id="menuStatus" class="menu-status is-error" role="status"><?= e($pmError) ?></p>
        <?php elseif (!$pmItems): ?>
            <p id="menuStatus" class="menu-status" role="status"><?= $pmSearch !== '' ? 'Không có món nào khớp với “' . e($pmSearch) . '”.' : 'Chưa có món nào trong danh mục này.' ?></p>
        <?php elseif ($pmSearch !== ''): ?>
            <p id="menuStatus" class="menu-status" role="status">Tìm thấy <?= (int) $pmPager['total'] ?> món cho “<?= e($pmSearch) ?>”.</p>
        <?php endif; ?>
        <div data-ajax-region="public-menu">
        <div id="publicMenuGrid" class="public-menu-grid">
            <?php foreach ($pmItems as $item): ?>
                <article class="public-menu-card"><div class="public-menu-photo"><img src="<?= e(menu_item_image($item)) ?>" alt="<?= e($item['item_name']) ?>" loading="lazy"><span><?= e($item['category_name']) ?></span></div><div class="public-menu-card-body"><div><h3><?= e($item['item_name']) ?></h3><p><?= e($item['description'] ?? '') ?></p></div><strong><?= e(money($item['price'])) ?></strong></div></article>
            <?php endforeach; ?>
        </div>
        <div id="menuPagination"><?= render_pagination($pmPager['page'], $pmPager['pages'], array('page' => 'public-menu') + ($pmCategoryName !== '' ? array('category' => $pmCategoryName) : array()) + ($pmSearch !== '' ? array('q' => $pmSearch) : array())) ?></div>
        </div>
    </section>
    <section class="menu-booking-banner"><div><p class="eyebrow">MỘT CHỖ NGỒI ĐANG CHỜ BẠN</p><h2>Hẹn nhau ở <?= e(brand_name()) ?> nhé.</h2></div><a class="button button-dark" href="index.php?page=home#booking">Đặt bàn <span aria-hidden="true">→</span></a></section>
</main>
<footer class="site-footer"><a class="footer-brand" href="index.php?page=home"><?= e(brand_name()) ?> <small><?= e(mb_strtoupper(brand_subtitle(), "UTF-8")) ?></small></a><span>© <?= date('Y') ?> <?= e(brand_full()) ?></span><a href="admin/index.php?page=login">Đăng nhập nhân viên <span aria-hidden="true">↗</span></a></footer>
