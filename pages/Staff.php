<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="asset/css/style.css">
    <link rel="stylesheet" href="asset/css/staff.css">
    <title>Nhân viên</title>
</head>
<body data-page="staff">
    <div id="sidebar-container"></div>

    <main class="main">
        <!-- Thanh điều hướng ngang — các thao tác trong trang Nhân viên -->
        <nav class="topnav">
            <button class="topnav-item active" data-action="list">Danh sách nhân viên</button>
            <button class="topnav-item" data-action="add">Thêm nhân viên</button>
        </nav>

        <div class="main-content">
            <!-- ============ PANEL: Danh sách nhân viên ============ -->
            <div class="tab-panel active" id="panel-list">
                <div class="menu-page-header">
                    <h2>Nhân viên</h2>
                    <p>Danh sách tài khoản, chức vụ và thông tin liên hệ của nhân viên.</p>
                </div>

                <div class="staff-list" id="staffList">
                    <!-- Danh sách nhân viên sẽ được đổ vào đây -->
                </div>
            </div>

            <!-- ============ PANEL: Thêm nhân viên ============ -->
            <div class="tab-panel" id="panel-add">
                <div class="menu-page-header">
                    <h2 id="staffFormTitle">Thêm nhân viên mới</h2>
                    <p>Tạo tài khoản, chọn chức vụ và nhập thông tin liên hệ.</p>
                </div>

                <!-- Trạng thái đang sửa -->
                <div id="editModeIndicator" class="edit-mode-indicator">
                    📝 Đang chỉnh sửa thông tin nhân viên
                </div>

                <form id="staffForm" class="add-item-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullName">Họ tên</label>
                            <input type="text" id="fullName" placeholder="Nhập họ tên" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Chức vụ</label>
                            <select id="role" required>
                                <option value="">-- Chọn chức vụ --</option>
                                <option value="Quản lý">Quản lý</option>
                                <option value="Phục vụ">Phục vụ</option>
                                <option value="Bếp">Bếp</option>
                                <option value="Thu ngân">Thu ngân</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Tài khoản</label>
                            <input type="text" id="username" placeholder="Nhập tài khoản" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <input type="password" id="password" placeholder="Nhập mật khẩu" required>
                        </div>
                    </div>

                    <!-- Thông tin liên hệ — chỉ hiển thị ở trang này (chỉ Quản lý vào được) -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="tel" id="phone" inputmode="tel" maxlength="11" placeholder="Ví dụ: 0901234567" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email (không bắt buộc)</label>
                            <input type="email" id="email" placeholder="Ví dụ: ten@gmail.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Giới tính</label>
                            <select id="gender">
                                <option value="">-- Chọn giới tính --</option>
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="birthDate">Ngày sinh</label>
                            <input type="date" id="birthDate">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">Tạo tài khoản</button>
                    <button type="button" class="btn-outline btn-cancel-edit" id="btnCancelEdit" style="display:none;">Hủy sửa</button>
                </form>
            </div>
        </div>
    </main>

    <!-- auth.js PHẢI nạp trước script.js: chặn trang nếu chưa đăng nhập / sai quyền -->
    <script src="asset/js/api.js"></script>
    <script src="asset/js/auth.js"></script>
    <script src="asset/js/portable-shim.js"></script>
    <script src="asset/js/common.js"></script>
    <script src="asset/js/staff.js"></script>
</body>
</html>