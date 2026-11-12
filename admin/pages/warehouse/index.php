<!-- pages/warehouse/index.php — giao diện trang "Kho" (header/footer do index.php gốc in) -->
<main class="main">
    <nav class="topnav">
        <a class="topnav-item<?= $tab === 'list' ? ' active' : '' ?>" href="<?= e(admin_url('warehouse')) ?>">Danh sách kho</a>
        <a class="topnav-item<?= $tab === 'add' ? ' active' : '' ?>" href="<?= e(admin_url('warehouse', array('tab' => 'add'))) ?>">Nhập / điều chỉnh kho</a>
        <a class="topnav-item<?= $tab === 'history' ? ' active' : '' ?>" href="<?= e(admin_url('warehouse', array('tab' => 'history'))) ?>">Lịch sử kho</a>
    </nav>
    <div class="main-content">
        <?php if ($warehouseSaved): ?><div class="menu-empty-state" role="status"><?= e($warehouseSaved) ?></div><?php endif; ?>
        <!-- ============ TAB 1: DANH SÁCH TỒN KHO ============ -->
        <div class="tab-panel<?= $tab === 'list' ? ' active' : '' ?>" id="panel-list">
            <div class="menu-page-header">
                <h2>Kho theo số ly / số phần</h2>
                <p>Số lượng món có thể bán, không phải số ly đã pha sẵn. Dữ liệu lưu trữ đồng bộ MySQL trên máy chủ; tự động trừ tồn kho khi thu ngân chốt thanh toán.</p>
            </div>

            <div class="stats-grid" id="inventoryStats">
                <div class="stat-card">
                    <div class="stat-card-label">Món trong Menu</div>
                    <div class="stat-card-value" id="totalIngredients"><?= (int) ($warehouseData['stats']['totalIngredients'] ?? 0) ?></div>
                </div>
                <div class="stat-card cancelled-note">
                    <div class="stat-card-label">Sắp hết</div>
                    <div class="stat-card-value" id="underMinLevel"><?= (int) ($warehouseData['stats']['underMinLevel'] ?? 0) ?></div>
                </div>
                <div class="stat-card cancelled-note">
                    <div class="stat-card-label">Hết hàng</div>
                    <div class="stat-card-value" id="outOfStock"><?= (int) ($warehouseData['stats']['outOfStock'] ?? 0) ?></div>
                </div>
                <div class="stat-card highlight">
                    <div class="stat-card-label">Tổng số ly / phần</div>
                    <div class="stat-card-value" id="totalStock"><?= number_format($warehouseData['stats']['totalStock'] ?? 0, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="content-section-title">Danh sách tồn kho theo món</div>
            <div data-ajax-region="warehouse-items">
            <div class="data-table-wrapper">
                <table class="data-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Món / Danh mục</th>
                            <th>Đơn vị</th>
                            <th>Tồn kho</th>
                            <th>Còn có thể đặt</th>
                            <th>Cảnh báo ≤</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (empty($warehousePageItems)): ?>
                            <tr><td colspan="7" class="empty-table-state">Chưa có món trong Thực đơn. Hãy thêm món ở trang Thực đơn.</td></tr>
                        <?php else: ?>
                            <?php foreach ($warehousePageItems as $item): ?>
                                <?php
                                $stock = (int) $item['stock'];
                                $minLevel = (int) $item['minLevel'];
                                $available = (int) $item['available'];
                                $isOut = ($stock <= 0);
                                $isLow = (!$isOut && $stock <= $minLevel);
                                $statusClass = $isOut ? 'badge-cancelled' : ($isLow ? 'badge-cancelled' : 'badge-active');
                                $statusLabel = $isOut ? 'Hết hàng' : ($isLow ? 'Sắp hết' : 'Còn hàng');
                                ?>
                                <tr data-item-id="<?= e($item['id']) ?>">
                                    <td>
                                        <b><?= e($item['name']) ?></b>
                                        <br><small><?= e($item['category']) ?></small>
                                    </td>
                                    <td><?= e($item['unit']) ?></td>
                                    <td><strong><?= number_format($stock, 0, ',', '.') ?></strong></td>
                                    <td><?= number_format($available, 0, ',', '.') ?></td>
                                    <td><?= number_format($minLevel, 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn-outline wh-link-btn" href="<?= e(admin_url('warehouse', array('tab' => 'add', 'item' => $item['id']))) ?>">Nhập / điều chỉnh</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= render_pagination($warehousePager['page'], $warehousePager['pages'], array('page' => 'warehouse')) ?>
            </div>
        </div>

        <!-- ============ TAB 2: NHẬP / ĐIỀU CHỈNH KHO ============ -->
        <div class="tab-panel<?= $tab === 'add' ? ' active' : '' ?>" id="panel-add">
            <div class="menu-page-header">
                <h2>Nhập / điều chỉnh tồn kho</h2>
                <p>Chọn món đã có trong Menu. Nhập thêm hàng mới hoặc trừ do hao hụt, kiểm kê; bán hàng sẽ tự trừ kho khi thanh toán.</p>
            </div>
            <?php if ($warehouseError): ?><div class="menu-empty-state" role="alert"><?= e($warehouseError) ?></div><?php endif; ?>
            <?php if (!$formItem): ?>
                <div class="menu-empty-state">Chưa có món trong Thực đơn. Hãy thêm món ở trang Thực đơn.</div>
            <?php else: ?>
            <!-- Đổi món: form GET nạp lại số liệu tồn kho của món đó -->
            <form method="get" action="index.php" class="add-item-form" style="margin-bottom:10px">
                <input type="hidden" name="page" value="warehouse">
                <input type="hidden" name="tab" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="stockItemPick">Món trong Menu</label>
                        <select id="stockItemPick" name="item" onchange="this.form.submit()">
                            <?php foreach ($warehouseItems as $it): ?>
                                <option value="<?= e($it['id']) ?>"<?= (int) $it['id'] === (int) $formItem['id'] ? ' selected' : '' ?>><?= e($it['name']) ?> (<?= e($it['unit']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <noscript><button type="submit" class="btn-outline">Chọn</button></noscript>
                    </div>
                    <div class="form-group">
                        <label>Số lượng hiện tại</label>
                        <div class="stock-current"><?= number_format((int) $formItem['stock'], 0, ',', '.') ?> <?= e($formItem['unit']) ?></div>
                    </div>
                </div>
            </form>
            <form method="post" action="<?= e(admin_url('warehouse', array('tab' => 'add', 'item' => $formItem['id']))) ?>" class="add-item-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="warehouse_action" value="adjust">
                <input type="hidden" name="item_id" value="<?= (int) $formItem['id'] ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="stockMode">Thao tác</label>
                        <select id="stockMode" name="mode">
                            <option value="import"<?= $formMode === 'import' ? ' selected' : '' ?>>Nhập thêm (+)</option>
                            <option value="adjust"<?= $formMode === 'adjust' ? ' selected' : '' ?>>Điều chỉnh giảm (-)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stockAmount">Số lượng (<?= e($formItem['unit']) ?>)</label>
                        <input type="number" id="stockAmount" name="amount" min="1" step="1" value="<?= e($formAmount) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="minLevel">Cảnh báo khi tồn ≤</label>
                        <input type="number" id="minLevel" name="min_level" min="0" step="1" value="<?= e($formMin) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="stockReason">Ghi chú / lý do</label>
                        <input type="text" id="stockReason" name="reason" maxlength="180" value="<?= e($formReason) ?>" placeholder="Ví dụ: Nhập hàng sáng nay, hao hụt nguyên liệu...">
                    </div>
                </div>
                <button type="submit" class="btn-submit">Lưu và cập nhật kho</button>
                <a class="btn-outline btn-cancel-edit wh-link-btn" href="<?= e(admin_url('warehouse')) ?>">Quay về danh sách</a>
                <p class="form-subtext">Mỗi lần nhập hoặc điều chỉnh kho đều được máy chủ ghi nhận danh tính người thực hiện, thời gian và số lượng biến động vào lịch sử cơ sở dữ liệu.</p>
            </form>
            <?php endif; ?>
        </div>

        <!-- ============ TAB 3: LỊCH SỬ BIẾN ĐỘNG KHO ============ -->
        <div class="tab-panel<?= $tab === 'history' ? ' active' : '' ?>" id="panel-history">
            <div class="menu-page-header">
                <h2>Lịch sử biến động kho</h2>
                <p>Theo dõi chi tiết nhập hàng, điều chỉnh kiểm kê và tự động trừ kho từ hóa đơn thanh toán.</p>
            </div>

            <!-- Thanh bộ lọc lịch sử -->
            <form method="get" action="index.php" class="warehouse-history-toolbar">
                <input type="hidden" name="page" value="warehouse">
                <input type="hidden" name="tab" value="history">
                <div class="filter-group">
                    <label for="historyFilterItem">Món</label>
                    <select id="historyFilterItem" name="f_item">
                        <option value="">-- Tất cả món --</option>
                        <?php foreach ($warehouseItems as $item): ?>
                            <option value="<?= e($item['id']) ?>"<?= $historyFilters['itemId'] === (string) $item['id'] ? ' selected' : '' ?>><?= e($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="historyFilterType">Thao tác</label>
                    <select id="historyFilterType" name="f_type">
                        <option value="">-- Tất cả thao tác --</option>
                        <?php foreach (array('import' => 'Nhập kho (+)', 'adjustment' => 'Điều chỉnh giảm (-)', 'sale' => 'Bán hàng (-)', 'sale_rollback' => 'Hoàn tác') as $k => $v): ?>
                            <option value="<?= e($k) ?>"<?= $historyFilters['type'] === $k ? ' selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="historyFilterDateFrom">Từ ngày</label>
                    <input type="date" id="historyFilterDateFrom" name="f_from" value="<?= e($historyFilters['dateFrom']) ?>">
                </div>
                <div class="filter-group">
                    <label for="historyFilterDateTo">Đến ngày</label>
                    <input type="date" id="historyFilterDateTo" name="f_to" value="<?= e($historyFilters['dateTo']) ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-submit">Lọc</button>
                    <a class="btn-outline wh-link-btn" href="<?= e(admin_url('warehouse', array('tab' => 'history'))) ?>">Đặt lại</a>
                </div>
            </form>

            <div data-ajax-region="warehouse-history">
            <div class="data-table-wrapper">
                <table class="data-table" id="stockLogTable">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Món</th>
                            <th>Biến động</th>
                            <th>Thao tác</th>
                            <th>Tham chiếu / Ghi chú</th>
                            <th>Người thực hiện</th>
                        </tr>
                    </thead>
                    <tbody id="stockLogBody">
                        <?php if (empty($stockMovements)): ?>
                            <tr><td colspan="6" class="empty-table-state">Không có lịch sử biến động phù hợp với bộ lọc.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stockMovements as $l): ?>
                                <?php
                                $delta = (int) $l['delta'];
                                $deltaClass = $delta > 0 ? 'delta-positive' : 'delta-negative';
                                $sign = $delta > 0 ? '+' : '';
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($l['ts'])) ?></td>
                                    <td><b><?= e($l['name']) ?></b></td>
                                    <td class="<?= $deltaClass ?>"><strong><?= $sign . number_format($delta, 0, ',', '.') ?> <?= e($l['unit']) ?></strong></td>
                                    <td><span class="badge badge-active"><?= e($l['typeName'] ?? $l['type']) ?></span></td>
                                    <td><?= e($l['note'] ?: ($l['ref'] ?: '—')) ?></td>
                                    <td><?= e($l['actor']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= render_pagination($stockPager['page'], $stockPager['pages'], $stockPagerQuery) ?>
            </div>
        </div>
    </div>
</main>
