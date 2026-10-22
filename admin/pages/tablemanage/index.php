<!-- pages/tablemanage/index.php — giao diện trang "Bàn" (header/footer do index.php gốc in) -->
    <main class="main">
        <div class="main-content">
            <div class="menu-page-header">
                <h2>Quản lý bàn</h2>
                <p>Thêm, sửa tên hoặc xóa bàn. Trạng thái bàn cập nhật theo thời gian thực từ trang Phục vụ.</p>
            </div>

            <div class="tm-toolbar">
                <p class="tm-summary" id="tableSummary">Tổng cộng <?= count($adminTables) ?> bàn</p>
                <button type="button" class="btn-submit" id="btnAddTable">+ Thêm bàn</button>
            </div>

            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tên bàn</th>
                            <th>Trạng thái</th>
                            <th>Vào lúc</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="tableManageBody">
                        <?php foreach ($adminTables as $table): ?>
                            <tr><td><b><?= e($table['table_name']) ?></b></td><td><?= (int) $table['is_active'] ? 'Đang hoạt động' : 'Ngừng hoạt động' ?></td><td>—</td><td>—</td></tr>
                        <?php endforeach; ?>
                        <?php if (!$adminTables): ?>
                            <tr><td colspan="4"><div class="empty-table-state">Chưa có bàn nào. Bấm "+ Thêm bàn" để bắt đầu.</div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <details class="table-server-data" open>
                <summary>Danh sách bàn từ MySQL (PHP)</summary>
                <div class="data-table-wrapper"><table class="data-table">
                    <thead><tr><th>Mã bàn</th><th>Tên bàn</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                    <?php foreach ($adminTables as $table): ?>
                        <tr><td><?= (int) $table['table_id'] ?></td><td><?= e($table['table_name']) ?></td>
                            <td><?= (int) $table['is_active'] ? 'Đang hoạt động' : 'Ngừng hoạt động' ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$adminTables): ?><tr><td colspan="3">Chưa có bàn nào.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </details>

            <p class="sync-hint">Chỉ xóa được bàn đang trống. Bàn đang phục vụ hoặc chờ thanh toán sẽ xóa được sau khi Phục vụ thanh toán xong. Đổi tên bàn không ảnh hưởng hóa đơn đã lập.</p>
        </div>
    </main>

    <!-- MODAL: Thêm / sửa bàn -->
    <div id="tableModal" class="modal-overlay">
        <form class="modal-box" id="tableForm" novalidate>
            <h3 class="modal-title" id="tableModalTitle">Thêm bàn</h3>
            <p class="modal-sub" id="tableModalSub">Nhập tên bàn mới.</p>
            <div class="form-group">
                <label for="tableNameInput">Tên bàn</label>
                <input type="text" id="tableNameInput" maxlength="30" autocomplete="off" placeholder="Ví dụ: Bàn 09">
            </div>
            <p class="tm-error" id="tableNameError" role="alert" hidden></p>
            <div class="modal-actions">
                <button type="button" class="btn-outline" id="btnTableCancel">Đóng</button>
                <button type="submit" class="btn-submit" id="btnTableSave">Thêm bàn</button>
            </div>
        </form>
    </div>

    <!-- MODAL: Xác nhận xóa bàn -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <h3 class="modal-title">Xóa bàn</h3>
            <p class="modal-sub">Bạn có chắc muốn xóa <b id="deleteModalName">--</b>? Bàn sẽ biến mất khỏi sơ đồ bàn của Phục vụ.</p>
            <div class="modal-actions">
                <button type="button" class="btn-outline" id="btnDeleteCancel">Đóng</button>
                <button type="button" class="tm-danger-solid" id="btnDeleteConfirm">Xác nhận xóa</button>
            </div>
        </div>
    </div>
