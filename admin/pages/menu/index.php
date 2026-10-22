<!-- pages/menu/index.php â€” giao diá»‡n trang "Thá»±c Ä‘Æ¡n" (header/footer do index.php gá»‘c in) -->
 
    <!-- Ná»™i dung chÃ­nh -->
    <main class="main">
        <!-- Thanh Ä‘iá»u hÆ°á»›ng ngang â€” cÃ¡c thao tÃ¡c trong trang Thá»±c Ä‘Æ¡n -->
        <nav class="topnav">
            <button class="topnav-item active" data-action="edit">Sá»­a mÃ³n</button>
            <button class="topnav-item" data-action="add">ThÃªm mÃ³n</button>
        </nav>
 
        <div class="main-content">
            <?php if ($menuSavedMessage !== ''): ?>
                <p class="menu-success-state" role="status"><?= e($menuSavedMessage) ?></p>
            <?php endif; ?>
            <?php if ($menuFormError !== ''): ?>
                <p class="menu-error-state" role="alert"><?= e($menuFormError) ?></p>
            <?php endif; ?>
            <details class="menu-server-data" open>
                <summary>Danh sÃ¡ch mÃ³n tá»« MySQL (PHP)</summary>
                <div class="data-table-wrapper"><table class="data-table">
                    <thead><tr><th>MÃ³n</th><th>Danh má»¥c</th><th>GiÃ¡</th><th>Hiá»ƒn thá»‹</th></tr></thead>
                    <tbody>
                    <?php foreach ($adminMenuItems as $item): ?>
                        <tr><td><?= e($item['item_name']) ?></td><td><?= e($item['category_name']) ?></td>
                            <td><?= e(number_format((int) $item['price'], 0, ',', '.')) ?> Ä‘</td>
                            <td><?= (int) $item['is_hidden'] === 1 ? 'Äang áº©n' : 'Äang bÃ¡n' ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$adminMenuItems): ?><tr><td colspan="4">ChÆ°a cÃ³ mÃ³n trong cÆ¡ sá»Ÿ dá»¯ liá»‡u.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </details>
            <!-- ============ PANEL: Sá»­a mÃ³n ============ -->
            <div class="tab-panel active" id="panel-edit">
                <div class="menu-page-header">
                    <h2>Thá»±c Ä‘Æ¡n</h2>
                    <p>ThÃªm, sá»­a, áº©n mÃ³n trá»±c tiáº¿p. Sá»‘ lÆ°á»£ng tá»“n kho quáº£n lÃ½ á»Ÿ trang <a href="<?= e(url('warehouse')) ?>">Kho</a>.</p>
                </div>
 
                <div class="menu-toolbar">
                    <label for="menuSearchInput" class="sr-only">TÃ¬m mÃ³n theo tÃªn</label>
                    <input type="text" id="menuSearchInput" class="menu-search-input" placeholder="TÃ¬m mÃ³n theo tÃªn...">
                </div>

                <!-- Danh sÃ¡ch mÃ³n sáº½ Ä‘Æ°á»£c Ä‘á»• vÃ o Ä‘Ã¢y -->
                <div class="menu-category-list" id="menuCategoryList">
                    <div class="menu-empty-state">
                        ChÆ°a cÃ³ mÃ³n nÃ o. VÃ o tab "ThÃªm mÃ³n" Ä‘á»ƒ báº¯t Ä‘áº§u.
                    </div>
                </div>
            </div>
 
            <!-- ============ PANEL: ThÃªm mÃ³n ============ -->
            <div class="tab-panel" id="panel-add">
                <div class="menu-page-header">
                    <h2>ThÃªm mÃ³n má»›i</h2>
                </div>
 
                <form class="add-item-form" method="POST" action="index.php?page=menu">
                    <input type="hidden" name="save_menu_item" value="1">
                    <input type="hidden" id="editItemId" name="id" value="">
                    <input type="hidden" id="itemImageData" value="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="itemCategory">Danh má»¥c</label>
                            <select id="itemCategory" name="category">
                                <option value="">-- Chá»n danh má»¥c --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newCategory">Hoáº·c táº¡o danh má»¥c má»›i</label>
                            <input type="text" id="newCategory" name="newCategory" placeholder="VÃ­ dá»¥: TrÃ  trÃ¡i cÃ¢y">
                        </div>
                    </div>
 
                    <div class="form-group">
                        <label for="itemPrice">GiÃ¡ (Ä‘)</label>
                        <input type="number" id="itemPrice" name="price" placeholder="VÃ­ dá»¥: 35000">
                    </div>
 
                    <div class="form-group">
                        <label for="itemName">TÃªn mÃ³n</label>
                        <input type="text" id="itemName" name="name" placeholder="VÃ­ dá»¥: TrÃ  Ä‘Ã o cam sáº£">
                    </div>

                    <div class="form-group">
                        <label for="itemDescription">MÃ´ táº£ mÃ³n (khÃ´ng báº¯t buá»™c)</label>
                        <textarea id="itemDescription" name="desc" maxlength="255" rows="3" placeholder="VÃ­ dá»¥: TrÃ  Ä‘Ã o, cam tÆ°Æ¡i vÃ  sáº£"></textarea>
                    </div>
 
                    <div class="form-group">
                        <label for="itemImage">áº¢nh mÃ³n (khÃ´ng báº¯t buá»™c)</label>
                        <input type="file" id="itemImage" accept="image/*">
                        <img id="itemImagePreview" alt="" style="display:none;width:90px;height:90px;object-fit:cover;border-radius:10px;margin-top:8px">
                    </div>
 
                    <button type="submit" class="btn-submit">ThÃªm mÃ³n + danh má»¥c (náº¿u cÃ³)</button>
                    <button type="button" class="btn-cancel-edit" id="btnCancelEdit" style="display:none;">Há»§y sá»­a</button>
                </form>
            </div>
        </div>
    </main>

