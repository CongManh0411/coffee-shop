<!-- pages/menu/index.php — giao diện trang "Thực đơn" (header/footer do index.php gốc in) -->
 
    <!-- Nội dung chính -->
    <main class="main">
        <!-- Thanh điều hướng ngang — các thao tác trong trang Thực đơn -->
        <nav class="topnav">
            <button class="topnav-item active" data-action="edit">Sửa món</button>
            <button class="topnav-item" data-action="add">Thêm món</button>
        </nav>
 
        <div class="main-content">
            <?php if ($menuSavedMessage !== ''): ?>
                <p class="menu-success-state" role="status"><?= e($menuSavedMessage) ?></p>
            <?php endif; ?>
            <?php if ($menuFormError !== ''): ?>
                <p class="menu-error-state" role="alert"><?= e($menuFormError) ?></p>
            <?php endif; ?>

            <!-- ============ PANEL: Sửa món ============ -->
            <div class="tab-panel active" id="panel-edit">
                <div class="menu-page-header">
                    <h2>Thực đơn</h2>
                    <p>Thêm, sửa, ẩn, xóa món trực tiếp.</p>
                </div>
 
                <details class="menu-category-manager" style="margin:0 0 16px">
                    <summary style="cursor:pointer;font-weight:600">Quản lý danh mục (<?= count($adminCategoryRows) ?>)</summary>
                    <div style="margin-top:10px;display:grid;gap:8px">
                        <?php foreach ($adminCategoryRows as $cat): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                                <form method="post" action="<?= e(admin_url('menu')) ?>" class="menu-inline-form" style="display:flex;gap:8px;align-items:center;flex:1 1 320px">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="menu_action" value="rename_category">
                                    <input type="hidden" name="category_id" value="<?= (int) $cat['category_id'] ?>">
                                    <label class="sr-only" for="catName<?= (int) $cat['category_id'] ?>">Tên danh mục</label>
                                    <input type="text" id="catName<?= (int) $cat['category_id'] ?>" name="category_name" class="menu-search-input" maxlength="100" value="<?= e($cat['category_name']) ?>" style="flex:1">
                                    <button type="submit" class="btn-outline">Đổi tên</button>
                                </form>
                                <span style="font-size:13px;color:#725f50"><?= (int) $cat['item_count'] ?> món</span>
                                <?php if ((int) $cat['item_count'] === 0): ?>
                                    <form method="post" action="<?= e(admin_url('menu')) ?>" class="menu-inline-form" onsubmit="return confirm('Xóa danh mục <?= e(addslashes($cat['category_name'])) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="menu_action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?= (int) $cat['category_id'] ?>">
                                        <button type="submit" class="btn-danger">Xóa</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:12px;color:#8a7766">Còn món nên chưa xóa được</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>

                <form class="menu-toolbar" method="get" action="<?= e(admin_url('menu')) ?>" role="search">
                    <input type="hidden" name="page" value="menu">
                    <label for="menuSearchInput" class="sr-only">Tìm món theo tên</label>
                    <input type="text" id="menuSearchInput" name="q" class="menu-search-input" value="<?= e($adminSearch) ?>" placeholder="Tìm món theo tên, mô tả, danh mục..." maxlength="100" autocomplete="off">
                    <button type="submit" class="btn-outline">Tìm</button>
                    <?php if ($adminSearch !== ''): ?><a class="btn-outline" href="<?= e(admin_url('menu')) ?>">Xóa lọc</a><?php endif; ?>
                </form>

                <!-- PHP xuất danh sách từ truy vấn JOIN; JS chỉ bổ sung tương tác. -->
                <div class="menu-category-list" id="menuCategoryList" data-ajax-region="admin-menu" data-ajax-sync="adminMenuData">
                    <?php foreach ($adminMenuPageGroups as $group): ?>
                        <?php if (!$group['items']) continue; ?>
                        <section class="menu-category">
                            <h3><?= e($group['category']) ?></h3>
                            <?php foreach ($group['items'] as $item): ?>
                                <div class="menu-item<?= $item['hidden'] ? ' is-hidden' : '' ?>" data-item-id="<?= $item['id'] ?>">
                                    <img class="menu-item-thumb" src="<?= e($item['thumb']) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                                    <div>
                                        <div class="menu-item-name"><?= e($item['name']) ?></div>
                                        <?php if ($item['desc'] !== ''): ?><div class="menu-item-desc"><?= e($item['desc']) ?></div><?php endif; ?>
                                        <?php if ($item['hidden']): ?><span class="menu-item-status">Đang ẩn</span><?php endif; ?>
                                    </div>
                                    <div class="menu-item-right">
                                        <div class="menu-item-price"><?= e(number_format($item['price'], 0, ',', '.')) ?>đ</div>
                                        <div class="menu-item-actions">
                                            <button type="button" class="btn-outline" data-action="edit" data-id="<?= $item['id'] ?>">Sửa</button>
                                            <form method="post" action="<?= e(admin_url('menu', array_filter(array('q' => $adminSearch, 'p' => $adminPager['page'] > 1 ? $adminPager['page'] : null)))) ?>" class="menu-inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="menu_action" value="toggle_hide">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-outline"><?= $item['hidden'] ? 'Hiện' : 'Ẩn' ?></button>
                                            </form>
                                            <form method="post" action="<?= e(admin_url('menu', array_filter(array('q' => $adminSearch, 'p' => $adminPager['page'] > 1 ? $adminPager['page'] : null)))) ?>" class="menu-inline-form" onsubmit="return confirm('Xóa món này?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="menu_action" value="delete">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-danger">Xóa</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                    <p class="menu-empty-state" id="menuEmptyState"<?= $adminPageItems ? ' hidden' : '' ?>>Chưa có món nào phù hợp.</p>
                    <?= render_pagination($adminPager['page'], $adminPager['pages'], array_filter(array('page' => 'menu', 'q' => $adminSearch))) ?>
                </div>
                <script type="application/json" id="adminMenuData"><?= json_encode(array_values($adminMenuGroups), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
            </div>
 
            <!-- ============ PANEL: Thêm món ============ -->
            <div class="tab-panel" id="panel-add">
                <div class="menu-page-header">
                    <h2>Thêm món mới</h2>
                    <p id="addPanelHint">Chọn danh mục có sẵn hoặc tạo danh mục mới, rồi nhập tên món và giá. Mô tả và ảnh không bắt buộc. Món mới có tồn kho là 0 và chưa bán được, hãy nhập hàng ở mục Kho.</p>
                </div>
 
                <form class="add-item-form" method="POST" enctype="multipart/form-data" action="<?= e(admin_url('menu')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="save_menu_item" value="1">
                    <input type="hidden" id="editItemId" name="id" value="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="itemCategory">Danh mục</label>
                            <select id="itemCategory" name="category">
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($adminMenuCategories as $category): ?>
                                    <option value="<?= e($category['category_name']) ?>"><?= e($category['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newCategory">Hoặc tạo danh mục mới</label>
                            <input type="text" id="newCategory" name="newCategory" placeholder="Ví dụ: Trà trái cây">
                        </div>
                    </div>
 
                    <div class="form-group">
                        <label for="itemPrice">Giá (đ)</label>
                        <input type="number" id="itemPrice" name="price" placeholder="Ví dụ: 35000">
                    </div>
 
                    <div class="form-group">
                        <label for="itemName">Tên món</label>
                        <input type="text" id="itemName" name="name" placeholder="Ví dụ: Trà đào cam sả">
                    </div>

                    <div class="form-group">
                        <label for="itemDescription">Mô tả món (không bắt buộc)</label>
                        <textarea id="itemDescription" name="desc" maxlength="255" rows="3" placeholder="Ví dụ: Trà đào, cam tươi và sả"></textarea>
                    </div>
 
                    <div class="form-group">
                        <label for="itemImage">Ảnh món (không bắt buộc)</label>
                        <input type="file" id="itemImage" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
                        <img id="itemImagePreview" alt="" style="display:none;width:90px;height:90px;object-fit:cover;border-radius:10px;margin-top:8px">
                    </div>
 
                    <button type="submit" class="btn-submit">Thêm món + danh mục (nếu có)</button>
                    <button type="button" class="btn-cancel-edit" id="btnCancelEdit" style="display:none;">Hủy sửa</button>
                </form>
            </div>
        </div>
    </main>
