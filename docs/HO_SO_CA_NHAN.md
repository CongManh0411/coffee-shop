# Hồ sơ module Menu, Bàn và Shifts

Ứng dụng mô phỏng phục vụ bài tập. Đăng nhập và phân quyền là chức năng mở rộng.

## Phạm vi trình bày

Sinh viên: Trần Công Mạnh — MSSV 2531540635 — Nhóm 5. Home là phần của thành viên khác, không tính vào đóng góp cá nhân. Hai trang Public thuộc Menu là `index.php?page=menu-categories` (tổng hợp danh mục) và `index.php?page=menu` (danh sách món). Trang chi tiết là route bổ sung. Hai trang Admin dùng Table là Bàn và Shifts; Menu có danh sách nhóm và form POST.

Trang tổng hợp danh mục được bổ sung trong BT03 để đáp ứng số lượng trang; cần ghi bổ sung này trong bảng phân công và xin nhóm trưởng xác nhận nếu làm thay đổi phân công đã chốt.

Chọn Menu làm module chính vì có giao diện Public, Admin, truy vấn JOIN và form POST. Bàn và Shifts là module bổ sung. Người nộp cần điền họ tên, mã sinh viên và xác nhận phạm vi đóng góp thực tế trước khi nộp; danh sách dưới đây mô tả mã liên quan, không xác nhận quyền tác giả.

| Module | File giao diện và xử lý | Hàm và bảng liên quan |
| --- | --- | --- |
| Danh mục Public | `pages/menu-categories/{action,index}.php` | `public_menu_category_summary()`; LEFT JOIN danh mục và món, đếm món đang phục vụ và giá thấp nhất |
| Menu Public | `pages/menu/{action,index}.php`, `pages/menu-detail/{action,index}.php` | `public_menu_items()`, `public_menu_categories()`, `get_menu_item()` trong `functions.php`; `menu_items`, `categories` |
| Menu Admin | `admin/pages/menu/{action,index}.php`, `assets/js/menu.js`, `assets/css/menu.css` | `admin_menu_items()` trong `functions.php`; `menu_create()`, `menu_update()` trong `api/modules/menu.php` |
| Bàn | `admin/pages/tablemanage/{action,index}.php`, `assets/js/table-manage.js` | `admin_tables()` trong `functions.php`, `api/modules/tables.php`; `dining_tables` |
| Shifts | `admin/pages/shifts/{action,index}.php`, `assets/js/shifts.js` | `admin_shifts()` trong `functions.php`, `api/modules/shifts.php`; `shifts`, `staff` |

Router, lớp Database, cấu hình, API chung và các module kho, doanh thu, bếp cần được ghi nhận theo phân công thực tế của nhóm.

## Bảng đối chiếu UI và PHP

Đây là đối chiếu cơ chế hiện có. Cần bổ sung tên file hoặc commit của UI cũ để trở thành bảng trước/sau có thể kiểm chứng.

| Thành phần UI | PHP hiện tại | Dữ liệu |
| --- | --- | --- |
| Danh sách món Public | `pages/menu/index.php` dùng `foreach ($menuItems ...)` | JOIN món và danh mục, lọc món đang hiển thị |
| Chi tiết món | `pages/menu-detail/action.php` và `index.php` | Truy vấn theo ID; ID không hợp lệ trả 404 |
| Danh sách món Admin | `admin/pages/menu/index.php` dùng foreach theo nhóm danh mục từ `$adminMenuItems` | JOIN `menu_items` và `categories` |
| Form thêm/sửa món | POST đến `admin/index.php?page=menu` | Kiểm tra dữ liệu, INSERT/UPDATE, redirect khi thành công |
| Danh sách bàn | `admin/pages/tablemanage/index.php` | PHP render ban đầu; JS bổ sung thao tác API |
| Lịch ca | `admin/pages/shifts/index.php` | PHP render bảng ca theo tuần từ JOIN shifts + staff; JS đồng bộ bảng và lịch sau thao tác, chuyển tuần và tải định kỳ |

## Giải thích luồng xử lý (150–250 chữ)

Khi người dùng mở trang quản lý thực đơn, yêu cầu đi vào Router `admin/index.php` với tham số `page=menu`. Router kiểm tra trang hợp lệ, phiên đăng nhập và quyền truy cập, sau đó nạp `admin/pages/menu/action.php` trước khi xuất giao diện. File action gọi hàm `admin_menu_items()` trong `functions.php`. Hàm này sử dụng đối tượng Database thông qua `db()` để thực thi câu SQL SELECT có JOIN giữa bảng `menu_items` và bảng `categories` theo khóa `category_id`. Lớp Database chuẩn bị câu lệnh, thực thi và trả về danh sách các dòng dữ liệu. Kết quả được gán vào biến `$adminMenuItems`. Tiếp theo, Router nạp layout và file `admin/pages/menu/index.php`. Action nhóm các món theo danh mục. Giao diện dùng foreach để xuất danh sách HTML có ảnh, tên món, giá, trạng thái và nút Sửa/Ẩn/Xóa; các chuỗi được escape bằng hàm `e()`. Khi gửi form thêm hoặc sửa món, trình duyệt gửi POST về cùng Router. Action lấy dữ liệu, gọi `menu_create()` hoặc `menu_update()` để kiểm tra và ghi MySQL. Nếu thành công, hệ thống chuyển hướng về trang Menu để đọc lại dữ liệu; nếu thất bại, giao diện hiển thị thông báo lỗi.

## Ảnh và kiểm thử cần bổ sung

Chưa có ảnh minh chứng trong hồ sơ này. Chụp hai ảnh UI cũ từ bản gốc và hai ảnh PHP mới tương ứng (đề xuất Public Menu và Admin Menu). Không dùng ảnh mới thay cho ảnh trước.

T01–T04 dưới đây bám đề gốc; chỉ đánh dấu đạt sau khi thực hiện và lưu minh chứng:

| Mã đề xuất | Thao tác | Kết quả cần xác nhận |
| --- | --- | --- |
| T01 | Mở Danh mục và Menu, đối chiếu SQL | Số món, giá thấp nhất và danh sách món khớp MySQL |
| T02 | Mở Admin Bàn, Shifts và Menu | Layout, sidebar, hai bảng dữ liệu và danh sách Menu hoạt động |
| T03 | Thêm món với tên riêng và giá hợp lệ | Redirect, báo thành công; món xuất hiện trong Admin, Public và MySQL |
| T04 | Gửi form với tên trống hoặc giá không hợp lệ | Báo lỗi và không thêm món; kiểm tra phía PHP bằng cách tắt JS |

Database nộp tại `03_Database/coffee.sql`, có 4 danh mục và 11 món mẫu. File nguồn duy trì tại `database/coffee.sql`; đồng bộ bản nộp nếu thay đổi schema hoặc seed.
