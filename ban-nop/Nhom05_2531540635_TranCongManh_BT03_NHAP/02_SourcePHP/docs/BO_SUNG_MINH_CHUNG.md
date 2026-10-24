# Phần bổ sung cho hồ sơ Word

Home thuộc thành viên khác, không tính vào số trang Public cá nhân. Hai trang Public của module Menu là Danh mục (`index.php?page=menu-categories`) và Thực đơn (`index.php?page=menu`). Chi tiết món là trang liên quan bổ sung.

Trang Danh mục được bổ sung trong BT03, không có ảnh UI cũ riêng. Không mô tả đây là trang đã tồn tại ở Chương 1–2. Dùng các ảnh Menu/Bàn/Shifts cũ đã có để đáp ứng đối chiếu tối thiểu; bổ sung ảnh trang Danh mục hiện tại và xác nhận phân công với nhóm trưởng nếu cần theo đề.

| Trang mới | File xử lý và giao diện | Chức năng | Bảng | Người thực hiện |
| --- | --- | --- | --- | --- |
| `index.php?page=menu-categories` | `pages/menu-categories/action.php`, `index.php`; `public_menu_category_summary()` trong `functions.php` | Đếm món đang hiển thị theo danh mục, giá thấp nhất; liên kết tới Menu lọc theo danh mục | `categories` LEFT JOIN `menu_items` | Trần Công Mạnh |

Sửa trong Word trước khi nộp:

- “Hai trang Public: menu và menu-detail” thành “Hai trang Public danh sách/tổng hợp: menu-categories và menu; menu-detail là trang chi tiết bổ sung”.
- “Trang Menu có bảng dữ liệu” thành “Menu có danh sách nhóm theo danh mục và form POST; Bàn và Shifts có bảng Table lấy dữ liệu MySQL”.
- “Mỗi module Menu” thành “Module Menu”.
- Bổ sung ảnh trang Danh mục và kết quả SQL đối chiếu số món, giá thấp nhất.

SQL bàn giao là seed ban đầu, không phải bản sao database trong ảnh kiểm thử. Các ID và món thêm khi T03 được tạo sau import; không cam kết tái hiện cùng ID. Đọc README nguồn để tạo tài khoản quản lý trước khi chạy Admin.
