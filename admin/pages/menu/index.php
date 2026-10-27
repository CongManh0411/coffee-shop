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
                    <p>Thêm, sửa, ẩn món trực tiếp. Số lượng tồn kho quản lý ở trang <a href="<?= e(url('warehouse')) ?>">Kho</a>.</p>
                </div>
 
                <div class="menu-toolbar">
                    <label for="menuSearchInput" class="sr-only">Tìm món theo tên</label>
                    <input type="text" id="menuSearchInput" class="menu-search-input" placeholder="Tìm món theo tên...">
                </div>

                <!-- Danh sách món sẽ được đổ vào đây -->
                <div class="menu-category-list" id="menuCategoryList">
                    <div class="menu-empty-state">
                        Chưa có món nào. Vào tab "Thêm món" để bắt đầu.
                    </div>
                </div>
            </div>
 
            <!-- ============ PANEL: Thêm món ============ -->
            <div class="tab-panel" id="panel-add">
                <div class="menu-page-header">
                    <h2>Thêm món mới</h2>
                </div>
 
                <form class="add-item-form" method="POST" action="index.php?page=menu">
                    <input type="hidden" name="save_menu_item" value="1">
                    <input type="hidden" id="editItemId" name="id" value="">
                    <input type="hidden" id="itemImageData" value="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="itemCategory">Danh mục</label>
                            <select id="itemCategory" name="category">
                                <option value="">-- Chọn danh mục --</option>
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
                        <input type="file" id="itemImage" accept="image/*">
                        <img id="itemImagePreview" alt="" style="display:none;width:90px;height:90px;object-fit:cover;border-radius:10px;margin-top:8px">
                    </div>
 
                    <button type="submit" class="btn-submit">Thêm món + danh mục (nếu có)</button>
                    <button type="button" class="btn-cancel-edit" id="btnCancelEdit" style="display:none;">Hủy sửa</button>
                </form>
            </div>
        </div>
    </main>
