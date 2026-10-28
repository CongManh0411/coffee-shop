<!-- pages/warehouse/index.php — giao diện trang "Kho" (header/footer do index.php gốc in) -->
<main class="main">
    <nav class="topnav">
        <button class="topnav-item active" data-action="list">Danh sách kho</button>
        <button class="topnav-item" data-action="add">Nhập / điều chỉnh kho</button>
        <button class="topnav-item" data-action="history">Lịch sử kho</button>
    </nav>
    <div class="main-content">
        <!-- ============ TAB 1: DANH SÁCH TỒN KHO ============ -->
        <div class="tab-panel active" id="panel-list">
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
                        <?php if (empty($warehouseData['items'])): ?>
                            <tr><td colspan="7" class="empty-table-state">Chưa có món trong Thực đơn. Hãy thêm món ở trang Thực đơn.</td></tr>
                        <?php else: ?>
                            <?php foreach ($warehouseData['items'] as $item): ?>
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
                                            <button type="button" class="btn-outline" data-edit="<?= e($item['id']) ?>">Nhập / điều chỉnh</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============ TAB 2: NHẬP / ĐIỀU CHỈNH KHO ============ -->
        <div class="tab-panel" id="panel-add">
            <div class="menu-page-header">
                <h2>Nhập / điều chỉnh tồn kho</h2>
                <p>Chọn món đã có trong Menu. Nhập thêm hàng mới hoặc trừ do hao hụt, kiểm kê; bán hàng sẽ tự trừ kho khi thanh toán.</p>
            </div>
            <form id="inventoryForm" class="add-item-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="stockItem">Món trong Menu</label>
                        <select id="stockItem" required>
                            <?php foreach (($warehouseData['items'] ?? array()) as $item): ?>
                                <option value="<?= e($item['id']) ?>"><?= e($item['name']) ?> (<?= e($item['unit']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Số lượng hiện tại</label>
                        <div id="stockCurrent" class="stock-current">
                            <?php
                            $first = !empty($warehouseData['items']) ? $warehouseData['items'][0] : null;
                            echo $first ? number_format($first['stock'], 0, ',', '.') . ' ' . e($first['unit']) : '0 ly';
                            ?>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="stockMode">Thao tác</label>
                        <select id="stockMode">
                            <option value="import">Nhập thêm (+)</option>
                            <option value="adjust">Điều chỉnh giảm (-)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stockAmount">Số lượng (<span id="stockUnit"><?= $first ? e($first['unit']) : 'ly' ?></span>)</label>
                        <input type="number" id="stockAmount" min="1" step="1" value="1" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="minLevel">Cảnh báo khi tồn ≤</label>
                        <input type="number" id="minLevel" min="0" step="1" value="<?= $first ? (int) $first['minLevel'] : 10 ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="stockReason">Ghi chú / lý do</label>
                        <input type="text" id="stockReason" maxlength="180" placeholder="Ví dụ: Nhập hàng sáng nay, hao hụt nguyên liệu...">
                    </div>
                </div>
                <button type="submit" class="btn-submit" id="btnSubmitStock">Lưu và cập nhật kho</button>
                <button type="button" class="btn-outline btn-cancel-edit" id="btnCancelEdit">Quay về danh sách</button>
                <p class="form-subtext">Mỗi lần nhập hoặc điều chỉnh kho đều được máy chủ ghi nhận danh tính người thực hiện, thời gian và số lượng biến động vào lịch sử cơ sở dữ liệu.</p>
            </form>
        </div>

        <!-- ============ TAB 3: LỊCH SỬ BIẾN ĐỘNG KHO ============ -->
        <div class="tab-panel" id="panel-history">
            <div class="menu-page-header">
                <h2>Lịch sử biến động kho</h2>
                <p>Theo dõi chi tiết nhập hàng, điều chỉnh kiểm kê và tự động trừ kho từ hóa đơn thanh toán.</p>
            </div>

            <!-- Thanh bộ lọc lịch sử -->
            <div class="warehouse-history-toolbar">
                <div class="filter-group">
                    <label for="historyFilterItem">Món</label>
                    <select id="historyFilterItem">
                        <option value="">-- Tất cả món --</option>
                        <?php foreach (($warehouseData['items'] ?? array()) as $item): ?>
                            <option value="<?= e($item['id']) ?>"><?= e($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="historyFilterType">Thao tác</label>
                    <select id="historyFilterType">
                        <option value="">-- Tất cả thao tác --</option>
                        <option value="import">Nhập kho (+)</option>
                        <option value="adjustment">Điều chỉnh giảm (-)</option>
                        <option value="sale">Bán hàng (-)</option>
                        <option value="sale_rollback">Hoàn tác</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="historyFilterDateFrom">Từ ngày</label>
                    <input type="date" id="historyFilterDateFrom">
                </div>
                <div class="filter-group">
                    <label for="historyFilterDateTo">Đến ngày</label>
                    <input type="date" id="historyFilterDateTo">
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn-submit" id="btnApplyHistoryFilter">Lọc</button>
                    <button type="button" class="btn-outline" id="btnResetHistoryFilter">Đặt lại</button>
                </div>
            </div>

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
                            <tr><td colspan="6" class="empty-table-state">Chưa có biến động kho nào.</td></tr>
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
        </div>
    </div>
</main>

<script>
window.INITIAL_WAREHOUSE_DATA = <?= json_encode($warehouseData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.INITIAL_STOCK_LOG = <?= json_encode($stockMovements, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
