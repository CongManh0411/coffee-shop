<!-- admin/pages/staff/index.php — giao diện trang "Nhân viên". PHP in danh sách bằng foreach; thêm/sửa/xóa là form POST. -->
    <?php $formOpen = $staffForm !== null; $editing = $formOpen && (int) $staffForm['id'] > 0; ?>
    <main class="main">
        <nav class="topnav">
            <a class="topnav-item<?= $formOpen ? '' : ' active' ?>" href="<?= e(admin_url('staff')) ?>">Danh sách nhân viên</a>
            <a class="topnav-item<?= $formOpen && !$editing ? ' active' : '' ?>" href="<?= e(admin_url('staff', array('add' => 1))) ?>">Thêm nhân viên</a>
        </nav>

        <div class="main-content">
            <?php if ($staffSavedMessage): ?><div class="menu-empty-state" role="status"><?= e($staffSavedMessage) ?></div><?php endif; ?>
            <?php if ($staffError && !$formOpen): ?><div class="menu-empty-state" role="alert"><?= e($staffError) ?></div><?php endif; ?>

            <?php if (!$formOpen): ?>
            <div class="tab-panel active" id="panel-list">
                <div class="menu-page-header">
                    <h2>Nhân viên</h2>
                    <p>Danh sách tài khoản, chức vụ và thông tin liên hệ của nhân viên.</p>
                </div>
                <div class="staff-list" id="staffList">
                    <?php foreach ($staffRows as $st): ?>
                        <div class="staff-item">
                            <div class="staff-info">
                                <div class="staff-name"><?= e($st['fullName']) ?></div>
                                <div class="staff-username">@<?= e($st['username']) ?></div>
                                <div class="staff-contact">
                                    <span><i class="ri-phone-line"></i> <?= $st['phone'] ? e($st['phone']) : '<em>Chưa có SĐT</em>' ?></span>
                                    <span><i class="ri-mail-line"></i> <?= $st['email'] ? e($st['email']) : '<em>Chưa có email</em>' ?></span>
                                    <span><i class="ri-user-line"></i> <?= $st['gender'] ? e($st['gender']) : '<em>Chưa rõ giới tính</em>' ?></span>
                                    <span><i class="ri-cake-2-line"></i> <?= $st['birthDate'] ? e(date('d/m/Y', strtotime($st['birthDate']))) . ' (' . staff_age_of($st['birthDate']) . ' tuổi)' : '<em>Chưa có ngày sinh</em>' ?></span>
                                </div>
                            </div>
                            <div class="staff-actions">
                                <div class="staff-role"><?= e($st['role']) ?></div>
                                <div class="action-buttons">
                                    <a class="btn-outline staff-link-btn" href="<?= e(admin_url('staff', array('edit' => $st['id']))) ?>">Sửa</a>
                                    <form method="post" action="<?= e(admin_url('staff')) ?>" class="staff-inline-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="staff_action" value="delete">
                                        <input type="hidden" name="staff_id" value="<?= (int) $st['id'] ?>">
                                        <button type="submit" class="btn-danger">Xóa</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$staffRows): ?>
                        <div class="menu-empty-state">Chưa có nhân viên nào. Bấm "Thêm nhân viên" để bắt đầu.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="tab-panel active" id="panel-add">
                <div class="menu-page-header">
                    <h2><?= $editing ? 'Sửa nhân viên' : 'Thêm nhân viên mới' ?></h2>
                    <p>Tạo tài khoản, chọn chức vụ và nhập thông tin liên hệ.</p>
                </div>
                <?php if ($editing): ?><div class="edit-mode-indicator active">📝 Đang chỉnh sửa thông tin nhân viên</div><?php endif; ?>
                <?php if ($staffError): ?><div class="edit-mode-indicator active" role="alert"><?= e($staffError) ?></div><?php endif; ?>

                <form method="post" action="<?= e(admin_url('staff')) ?>" class="add-item-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="staff_action" value="save">
                    <input type="hidden" name="staff_id" value="<?= (int) $staffForm['id'] ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullName">Họ tên</label>
                            <input type="text" id="fullName" name="fullName" placeholder="Nhập họ tên" value="<?= e($staffForm['fullName']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Chức vụ</label>
                            <select id="role" name="role" required>
                                <option value="">-- Chọn chức vụ --</option>
                                <?php foreach (array_keys(STAFF_ROLES) as $roleName): ?>
                                    <option value="<?= e($roleName) ?>"<?= $staffForm['role'] === $roleName ? ' selected' : '' ?>><?= e($roleName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Tài khoản</label>
                            <input type="text" id="username" name="username" placeholder="Nhập tài khoản" value="<?= e($staffForm['username']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <div class="pass-wrap"><input type="password" id="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" placeholder="<?= $editing ? 'Để trống nếu không đổi mật khẩu' : 'Ít nhất 12 ký tự' ?><button type="button" class="pass-eye" data-toggle-password="password" aria-pressed="false" aria-label="Hiện hoặc ẩn mật khẩu">Hiện</button></div>"<?= $editing ? '' : ' required' ?>>
                            <small>Mật khẩu mới cần ít nhất 12 ký tự. Khi sửa hồ sơ, để trống nếu không đổi mật khẩu.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="tel" id="phone" name="phone" inputmode="tel" maxlength="11" placeholder="Ví dụ: 0901234567" value="<?= e($staffForm['phone']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email (không bắt buộc)</label>
                            <input type="email" id="email" name="email" placeholder="Ví dụ: ten@gmail.com" value="<?= e($staffForm['email']) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Giới tính</label>
                            <select id="gender" name="gender">
                                <option value="">-- Chọn giới tính --</option>
                                <?php foreach (array('Nam', 'Nữ', 'Khác') as $g): ?>
                                    <option value="<?= e($g) ?>"<?= $staffForm['gender'] === $g ? ' selected' : '' ?>><?= e($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="birthDate">Ngày sinh</label>
                            <input type="date" id="birthDate" name="birthDate" max="<?= e(date('Y-m-d')) ?>" value="<?= e($staffForm['birthDate']) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit"><?= $editing ? 'Lưu thay đổi' : 'Tạo tài khoản' ?></button>
                    <a class="btn-outline btn-cancel-edit staff-link-btn" href="<?= e(admin_url('staff')) ?>">Hủy</a>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </main>
