# Bản sửa quản lý kho và nhân viên

## Cách thay bản trên XAMPP

1. Sao lưu thư mục dự án đang chạy và xuất database coffee_shop từ phpMyAdmin.
2. Giải nén ZIP này. Dùng thư mục coffee-shop bên trong thay cho thư mục C:\xampp\htdocs\coffee-shop. Đảm bảo index.php nằm ngay trong coffee-shop, không bị lồng hai cấp.
3. Nếu bạn có cấu hình kết nối database riêng, giữ cấu hình đó trong config/database.php.
4. Giữ database đang sử dụng. Bản sửa này KHÔNG yêu cầu đổi cấu trúc bảng, KHÔNG cần nhập lại coffee.sql trên database đã có đủ bảng từ bản gửi kiểm tra.
5. Bật Apache và MySQL, mở http://localhost/coffee-shop/admin/index.php?page=login, đăng nhập lại. Nhấn Ctrl+F5 để trình duyệt tải JavaScript mới.
6. Nếu cài trên máy mới chưa có database, nhập database/coffee.sql một lần rồi tạo quản lý theo README.md. SQL đi kèm là cấu trúc và dữ liệu mẫu, không phải bản xuất dữ liệu thực tế của bạn.

## Các thay đổi

- Công khai đúng đối tượng window.Api cho các module hiện có.
- Nhập/điều chỉnh kho và thay mức cảnh báo chỉ ghi qua API/MySQL; không âm thầm lưu cục bộ khi API không tải được.
- Đọc dữ liệu máy chủ vào bộ nhớ để tiếp tục hiển thị đúng ngay cả khi bộ nhớ trình duyệt không ghi được.
- Sửa tham số lọc lịch sử theo món, loại biến động, khoảng ngày. Lỗi tải lịch sử được báo rõ.
- Giữ nguyên mức cảnh báo bằng 0.
- Thanh toán không tự trừ/hoàn kho cục bộ. PHP kiểm tra và trừ kho trong cùng giao dịch với hóa đơn. Chặn gửi thanh toán nhiều lần khi đang chờ phản hồi.
- Nếu máy chủ đã chốt đơn mà không lưu được bản sao hóa đơn trên trình duyệt, báo đúng trạng thái đã thanh toán; không tự cộng kho.
- PHP từ chối số lượng thập phân, số âm, boolean, mảng và số vượt giới hạn. Kiểm tra món tồn tại và giới hạn tồn kho trước khi lưu.
- Khóa các dòng kho theo thứ tự mã món khi thanh toán để giảm nguy cơ tranh chấp khóa.
- Khi sửa nhân viên, cho phép để trống mật khẩu; khi thêm mới vẫn bắt buộc. Không tự cắt khoảng trắng của mật khẩu.
- Kiểm tra trạng thái và vai trò nhân viên ở mỗi yêu cầu có đăng nhập. Khóa tài khoản/đổi quyền sẽ chặn phiên cũ ở yêu cầu kế tiếp. Phiên được cấp theo bản mới cũng bị thu hồi khi mật khẩu thay đổi.

## Kiểm tra đã thực hiện

- Kiểm tra cú pháp 76 tệp PHP và 26 tệp JavaScript.
- Chạy kiểm tra JavaScript cho API, nhập kho, lỗi kết nối, bộ lọc, mức cảnh báo 0, bộ nhớ trình duyệt đầy và trạng thái bắt buộc mật khẩu.
- Mô phỏng bấm thanh toán hai lần: chỉ một yêu cầu; mô phỏng lỗi lưu bản sao hóa đơn: vẫn hoàn tất, không hoàn kho cục bộ.
- Chạy PHP 8.3 với MariaDB 10.11 trên database kiểm thử riêng: tạo/sửa nhân viên, băm mật khẩu, tài khoản trùng, nhập kho, chống tồn âm, số lượng không hợp lệ, lọc lịch sử và người thao tác.
- Chạy thanh toán thực qua hàm PHP: trừ đúng số lượng; thanh toán lại không trừ thêm; thiếu tồn giữ đơn chưa thanh toán; phát sinh exception trong giao dịch thì tồn kho được rollback.
- Kiểm tra phiên nhân viên sau khi đổi quyền, khóa tài khoản, đổi mật khẩu.
- Chạy qua HTTP: đăng nhập, trang kho và nhân viên render, nhập kho và tải lại vẫn giữ dữ liệu, bộ lọc lịch sử, chặn CSRF sai và yêu cầu chưa đăng nhập.
- Chưa kiểm tra trực quan bố cục trên trình duyệt hoặc chạy trên cấu hình XAMPP riêng của bạn. Chưa kiểm thử tải đồng thời nhiều người dùng.

## Cách tự kiểm tra nhanh

1. Vào Kho, ghi lại tồn hiện tại của một món. Nhập thêm 5, đặt mức cảnh báo 0.
2. Tải lại trang: tồn phải tăng đúng 5 và mức cảnh báo vẫn là 0.
3. Vào Lịch sử kho, chọn món và loại Nhập kho: phải có giao dịch vừa lưu, đúng người thao tác.
4. Thử giảm vượt tồn: hệ thống phải từ chối, số tồn giữ nguyên.
5. Tạo đơn mua 2 phần của món đó, xác nhận và thanh toán: kho giảm đúng 2, có lịch sử bán hàng và mã đơn. Nếu dùng tài khoản thu ngân, cần mở ca trước.
6. Vào Nhân viên, sửa họ tên/số điện thoại và để mật khẩu trống: lưu được, mật khẩu cũ vẫn đăng nhập được.
7. Đăng nhập một nhân viên ở trình duyệt khác, rồi dùng quản lý khóa tài khoản: yêu cầu tiếp theo của nhân viên phải bị từ chối.

## Dữ liệu cũ và phạm vi

Tồn kho cũ trong localStorage không tự chuyển sang MySQL. Nếu trước đây nhập kho chỉ lưu trên trình duyệt, cần đối chiếu số thực tế rồi nhập/điều chỉnh lại qua trang Kho mới; tránh nhập trùng. Không có tài khoản hoặc dữ liệu kiểm thử nào được đưa vào SQL bàn giao.

Hủy/hoàn tiền món đã pha không tự nhập lại kho; quản lý chỉ điều chỉnh nếu thực tế còn có thể bán. Nhật ký chung, ca tiền mặt và tích hợp ứng dụng giao hàng giữ phạm vi như dự án trước. Kiểm tra tồn tại thời điểm thanh toán là quyết định cuối cùng; không cam kết giữ chỗ tồn kho giữa nhiều thiết bị tại bước gọi món.
