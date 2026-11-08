# Coffee Shop

Hồ sơ module Menu, Bàn và Shifts: [docs/HO_SO_CA_NHAN.md](docs/HO_SO_CA_NHAN.md). Bản SQL bàn giao: [03_Database](03_Database/README.md). Ảnh minh chứng và thông tin tác giả cần bổ sung theo thực tế trước khi nộp.

Ứng dụng quản lý quán cà phê bằng PHP, MySQL và JavaScript, chạy cục bộ với XAMPP.

## Cài đặt cục bộ

1. Đặt thư mục dự án tại `C:\xampp\htdocs\coffee-shop`.
2. Mở Apache và MySQL trong XAMPP.
3. Dùng phpMyAdmin để nhập `database/coffee.sql`. Tệp này tạo database `coffee_shop` và dữ liệu menu mẫu. Chỉ nhập trên database mới; script hiện không phải migration có thể chạy lặp lại.
4. Tạo mật khẩu quản lý ban đầu trong PowerShell rồi chạy trình khởi tạo:
   ```powershell
   $env:INITIAL_MANAGER_PASSWORD = Read-Host "Mật khẩu quản lý mới (ít nhất 12 ký tự)"
   C:\xampp\php\php.exe database/create_manager.php
   Remove-Item Env:\INITIAL_MANAGER_PASSWORD
   ```
5. Mở `http://localhost/coffee-shop/`. Tạo tài khoản nhân viên tiếp theo trong Admin → Nhân viên.

### Nâng cấp bản đã cài từ trước

Nếu database đang dùng tài khoản mẫu cũ, hãy tạo quản lý mới và vô hiệu hóa các tài khoản còn dùng mật khẩu mẫu bằng PowerShell:

```powershell
$env:INITIAL_MANAGER_PASSWORD = Read-Host "Mật khẩu quản lý mới (ít nhất 12 ký tự)"
C:\xampp\php\php.exe database/replace_demo_accounts.php
Remove-Item Env:\INITIAL_MANAGER_PASSWORD
```

Script chỉ chạy bằng CLI. Nó tạo tài khoản quản lý mới trước, sau đó vô hiệu hóa các tài khoản có mật khẩu mẫu cũ; tài khoản và dữ liệu khác được giữ nguyên.

## Cấu hình database

Cấu hình localhost mặc định dùng user `root` và mật khẩu trống để phù hợp XAMPP. Khi triển khai, đặt `APP_ENV=production` cùng `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`; ứng dụng sẽ từ chối chạy production với user `root` hoặc mật khẩu database trống.

## Các trang chính

- Danh mục thực đơn: đã gộp vào trang Thực đơn (thanh lọc có số món); `page=menu-categories` tự chuyển về `page=menu`.

- Trang giới thiệu khách: `/` hoặc `index.php?page=home`.
- Thực đơn đầy đủ, lọc theo danh mục: `index.php?page=menu`.
- Chi tiết món: `index.php?page=menu-detail&id=1` (mã món không hợp lệ trả HTTP 404).
- Thực đơn nổi bật: hiển thị ngay trên trang chủ tại `index.php?page=home#menu`.
- Đăng nhập nhân viên: `admin/index.php?page=login`.
- Router nhân viên: `index.php?page=tables`, `schedule`, `ops`, `orders`, `cashshift`, `kitchen`.
- Router quản lý: `admin/index.php?page=tablemanage`, `menu`, `revenue`, `history`, `staff`, `shifts`, `warehouse`, `ops`, `logs`.

## Dữ liệu

Đây là ứng dụng mô phỏng phục vụ bài tập, chạy trên localhost/XAMPP; cần cấu hình riêng và rà soát bảo mật trước khi triển khai dịch vụ thật.

Tài khoản, thực đơn (kể cả món tạm hết), bàn, trạng thái bàn / đơn đang mở, ca làm, đăng ký ca, chấm công giao ca, ca tiền mặt, nhật ký thao tác, đơn hàng, đặt bàn, hàng chờ, khách hàng, khuyến mãi, nhà cung cấp, vật tư kho và đơn giao hàng đều được lưu ở MySQL. Kho theo ly/phần và lịch sử biến động kho được lưu MySQL; thanh toán trừ kho tại máy chủ cùng giao dịch với hóa đơn. Ứng dụng không còn đọc hay ghi `localStorage` / `sessionStorage`: trình duyệt chỉ giữ bản sao tạm trong bộ nhớ của trang và tải lại từ API (trạng thái bàn mỗi ~5 giây), nên dữ liệu đồng bộ giữa các thiết bị. Dữ liệu cũ còn trong localStorage của trình duyệt không được chuyển sang database.

Muốn có dữ liệu demo cho mọi chức năng (nhân viên, lịch ca, 14 ngày đơn hàng, ca thu ngân, kho, đặt bàn, giao hàng, nhật ký): sau khi nhập `database/coffee.sql`, nhập thêm `database/demo_data.sql` (nhập một lần; file không dùng ID cố định nên chạy được cả khi database đã có dữ liệu của bạn và không sửa hay xóa dữ liệu đó; nếu đã tạo quản lý bằng `create_manager.php` thì tài khoản `quanly` được bỏ qua, bạn đăng nhập bằng tài khoản quản lý của mình; nhập lần hai sẽ báo lỗi ngay ở dòng đầu mà không ghi thêm gì; muốn nhập lại thì xóa database `coffee_shop` rồi nhập từ `coffee.sql`). Mật khẩu chung của tài khoản demo là `Demo@2026pass` (`quanly`, `thungan1`, `thungan2`, `phucvu1-3`, `bep1`, `bep2`). Ngày giờ trong file tính theo lúc nhập nên dữ liệu luôn mới. Trước khi dùng thật hãy chạy `php database/replace_demo_accounts.php` để tắt các tài khoản này.

Bản nâng cấp từ trước: nhập lại `database/coffee.sql` trong phpMyAdmin để tạo bảng `timesheets` (chấm công); các bảng dùng `CREATE TABLE IF NOT EXISTS` nên không ghi đè dữ liệu đã có.

Đơn giao hàng nhập tay được lưu vào database. Kết nối webhook GrabFood, ShopeeFood và BeFood chưa có. Đơn giao hàng chưa tự tạo hóa đơn doanh thu khi hoàn tất.

## Cấu trúc thư mục

- `admin/`: router, trang và giao diện quản trị.
- `pages/`: home khách và các trang nhân viên.
- `api/`: JSON API và module thao tác database.
- `config/`, `helpers/`, `classes/`: cấu hình và lớp dùng chung.
- `layouts/`, `assets/`: giao diện, CSS, JavaScript và ảnh.
- `database/coffee.sql`: schema và dữ liệu menu mẫu ban đầu.
- `database/demo_data.sql`: dữ liệu demo cho mọi chức năng, nhập sau `coffee.sql` trên database mới.
- `database/create_manager.php`: tạo quản lý đầu tiên, chỉ chạy được từ CLI.
- `database/replace_demo_accounts.php`: thay tài khoản mẫu cũ trên database đang dùng, chỉ chạy được từ CLI.

## Bản sửa kho và nhân viên

Xem `HUONG_DAN_BAN_SUA.md` để thay bản chạy, xem thay đổi và kết quả kiểm tra.
