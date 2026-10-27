<!-- pages/menu/index.php — giao diện trang "Thực đơn" (header/footer do index.php gốc in) -->
 
    <!-- Nội dung chính -->
    <main class="main">
        <!-- Thanh điều hướng ngang — các thao tác trong trang Thực đơn -->
        <nav class="topnav">
            <button class="topnav-item active" data-action="edit">Sửa món</button>
            <button class="topnav-item" data-action="add">Thêm món</button>
        </nav>
 
        <div class="main-content">
            <p class="menu-demo-note">Ứng dụng mô phỏng phục vụ bài tập. Đăng nhập và phân quyền là chức năng mở rộng.</p>
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
                    <p>Thêm, sửa, ẩn món trực tiếp. Số lượng tồn kho quản lý ở trang <a href="<?= e(admin_url('warehouse')) ?>">Kho</a>.</p>
                </div>
 
                <div class="menu-toolbar">
                    <label for="menuSearchInput" class="sr-only">Tìm món theo tên</label>
                    <input type="text" id="menuSearchInput" class="menu-search-input" placeholder="Tìm món theo tên...">
                </div>

                <!-- PHP xuất danh sách từ truy vấn JOIN; JS chỉ bổ sung tương tác. -->
                <div class="menu-category-list" id="menuCategoryList">
                    <?php foreach ($adminMenuGroups as $group): ?>
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
                                            <button type="button" class="btn-outline" data-action="hide" data-id="<?= $item['id'] ?>"><?= $item['hidden'] ? 'Hiện' : 'Ẩn' ?></button>
                                            <button type="button" class="btn-danger" data-action="delete" data-id="<?= $item['id'] ?>">Xóa</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                    <p class="menu-empty-state" id="menuEmptyState"<?= $adminMenuItems ? ' hidden' : '' ?>>Chưa có món nào phù hợp.</p>
                </div>
                <script type="application/json" id="adminMenuData"><?= json_encode(array_values($adminMenuGroups), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
            </div>
 
            <!-- ============ PANEL: Thêm món ============ -->
            <div class="tab-panel" id="panel-add">
                <div class="menu-page-header">
                    <h2>Thêm món mới</h2>
                </div>
 
                <form class="add-item-form" method="POST" enctype="multipart/form-data" action="<?= e(admin_url('menu')) ?>">
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
