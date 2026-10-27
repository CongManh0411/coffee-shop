<!-- admin/pages/tablemanage/index.php — giao diện "Quản lý bàn" (header/sidebar/footer do admin/index.php in) -->
    <main class="main">
        <div class="main-content">
            <div class="menu-page-header">
                <h2>Quản lý bàn</h2>
                <p>Thêm, sửa tên hoặc xóa bàn. Trạng thái bàn lấy từ các đơn đang mở trong cơ sở dữ liệu.</p>
            </div>

            <?php if ($tableFlash): ?>
                <p class="<?= $tableFlash['type'] === 'ok' ? 'tm-success' : 'tm-error' ?>" role="alert"><?= e($tableFlash['text']) ?></p>
            <?php endif; ?>

            <!-- FORM THÊM / SỬA BÀN (POST) -->
            <form class="tm-form" method="post" action="<?= e(admin_url('tablemanage')) ?>" novalidate>
                <h3 class="tm-form-title"><?= $formTableId > 0 ? 'Sửa tên bàn' : 'Thêm bàn mới' ?></h3>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="table_id" value="<?= (int) $formTableId ?>">
                <div class="form-group">
                    <label for="tableNameInput">Tên bàn</label>
                    <input type="text" id="tableNameInput" name="name" maxlength="60" autocomplete="off"
                           placeholder="Ví dụ: Bàn 09" value="<?= e($formTableName) ?>">
                </div>
                <?php if ($tableFormError !== ''): ?>
                    <p class="tm-error" role="alert"><?= e($tableFormError) ?></p>
                <?php endif; ?>
                <div class="tm-form-actions">
                    <button type="submit" class="btn-submit"><?= $formTableId > 0 ? 'Lưu thay đổi' : '+ Thêm bàn' ?></button>
                    <?php if ($formTableId > 0): ?>
                        <a class="btn-outline tm-cancel" href="<?= e(admin_url('tablemanage')) ?>">Hủy sửa</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="tm-toolbar">
                <p class="tm-summary">Tổng cộng <?= count($adminTables) ?> bàn — <?= count($adminTables) - $tablesOccupied ?> trống, <?= $tablesOccupied ?> đang phục vụ</p>
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
                            <tr>
                                <td><b><?= e($table['table_name']) ?></b><?= (int) $table['is_active'] ? '' : ' <small>(ngừng hoạt động)</small>' ?></td>
                                <td>
                                    <?php if ($table['status'] === 'occupied'): ?>
                                        <span class="badge-serving">Đang phục vụ</span>
                                    <?php else: ?>
                                        <span class="badge-active">Bàn trống</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $table['created_at'] ? e(date('H:i d/m', strtotime($table['created_at']))) : '—' ?></td>
                                <td>
                                    <div class="tm-row-actions">
                                        <a class="btn-outline" href="<?= e(admin_url('tablemanage', array('edit' => $table['table_id']))) ?>">Sửa</a>
                                        <?php if ($table['status'] === 'empty'): ?>
                                            <form method="post" action="<?= e(admin_url('tablemanage')) ?>" class="tm-inline-form"
                                                  onsubmit="return confirm('Xóa <?= e(addslashes($table['table_name'])) ?>?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="table_id" value="<?= (int) $table['table_id'] ?>">
                                                <button type="submit" class="tm-danger-solid">Xóa</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$adminTables): ?>
                            <tr><td colspan="4"><div class="empty-table-state">Chưa có bàn nào. Nhập tên bàn ở form phía trên để bắt đầu.</div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="sync-hint">Chỉ xóa được bàn đang trống. Bàn đang phục vụ sẽ xóa được sau khi Phục vụ thanh toán xong. Đổi tên bàn không ảnh hưởng hóa đơn đã lập.</p>
        </div>
    </main>
