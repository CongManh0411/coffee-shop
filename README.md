# Coffee Shop

Ứng dụng quản lý quán cà phê bằng PHP, MySQL và JavaScript, chạy cục bộ với XAMPP.

## Cài đặt cục bộ

1. Đặt thư mục dự án tại `C:\xampp\htdocs\coffee-shop`.
2. Mở Apache và MySQL trong XAMPP.
3. Dùng phpMyAdmin để nhập `database/coffee.sql`. Tệp này tạo database `coffee_shop` và dữ liệu mẫu. Chỉ nhập trên database mới; script hiện không phải migration có thể chạy lặp lại.
4. Mở `http://localhost/coffee-shop/`.

Database mặc định trong `config/database.php` dùng `127.0.0.1:3306`, database `coffee_shop`, user `root`, mật khẩu trống theo cấu hình XAMPP thường gặp. Có thể cấu hình bằng các biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`; đặt chúng ở cấu hình máy chủ, không commit mật khẩu vào Git.

## Tài khoản dữ liệu mẫu

Sau khi nhập SQL, tài khoản thử nghiệm dùng mật khẩu `123456`:

| Vai trò | Tên đăng nhập |
|---|---|
| Quản lý | `quanly` |
| Phục vụ | `phucvu` |
| Bếp | `bep` |
| Thu ngân | `thungan` |

Đây là tài khoản demo. Đổi mật khẩu và tạo tài khoản database riêng trước khi triển khai ra Internet.

## Các trang chính

- Trang giới thiệu khách: `/` hoặc `index.php?page=home`.
- Thực đơn nổi bật: hiển thị ngay trên trang chủ tại `index.php?page=home#menu`.
- Đăng nhập nhân viên: `admin/index.php?page=login`.
- Router nhân viên: `index.php?page=tables`, `schedule`, `ops`, `orders`, `cashshift`, `kitchen`.
- Router quản lý: `admin/index.php?page=tablemanage`, `menu`, `revenue`, `history`, `staff`, `shifts`, `warehouse`, `ops`, `logs`.

## Dữ liệu

Tài khoản, thực đơn, bàn, ca làm, đơn hàng, đặt bàn, hàng chờ, khách hàng, khuyến mãi, nhà cung cấp, vật tư kho và đơn giao hàng được lưu ở MySQL. Trạng thái ca tiền mặt, nhật ký giao diện, món tạm hết và tồn kho thành phẩm trên màn bán hàng vẫn còn lưu phía trình duyệt; chúng chưa đồng bộ đầy đủ giữa các thiết bị. Dữ liệu cũ trong localStorage không tự chuyển sang database.

Đơn giao hàng nhập tay được lưu vào database. Kết nối webhook GrabFood, ShopeeFood và BeFood chưa có. Đơn giao hàng chưa tự tạo hóa đơn doanh thu khi hoàn tất.

## Cấu trúc thư mục

- `admin/`: router, trang và giao diện quản trị.
- `pages/`: home khách và các trang nhân viên.
- `api/`: JSON API và module thao tác database.
- `config/`, `helpers/`, `classes/`: cấu hình và lớp dùng chung.
- `layouts/`, `assets/`: giao diện, CSS, JavaScript và ảnh.
- `database/coffee.sql`: schema và dữ liệu mẫu ban đầu.
