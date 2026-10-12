# Phất Quạt Họa Văn

Website PHP 8.0+ / HTML / CSS / JavaScript / MySQL 8+ (hoặc MariaDB 10.4+), không cần Composer hay Node.js.

Thiết kế và nội dung tham khảo: https://phatquathoavan.my.canva.site
Giữ nội dung dự án từ trang mẫu; phần đầu theo ảnh tham chiếu mới với header sáng, logo gốc, nền đỏ, tre hai bên, quạt vàng và đồng hồ đặt trên quạt. Navbar gồm Dự án, Thư viện, Blog và Liên hệ; phần câu chuyện Chàng Sơn vẫn nằm trong nội dung. Các phần phía dưới dùng nền giấy vàng ấm xen kẽ nền đỏ; bố cục responsive. Chưa đối chiếu trực quan bằng trình duyệt.

## Chức năng

- Giới thiệu dự án, câu chuyện Chàng Sơn, quy trình làm quạt, các đơn vị đồng hành.
- Thư viện ảnh: lọc nhóm, xem ảnh lớn; tải lên/sửa/xóa ảnh từ quản trị.
- Blog: tạo/sửa/xóa, lưu nháp, xuất bản; trang đọc bài viết riêng.
- Đăng ký tham gia: lưu họ tên, email, điện thoại, hoạt động và lời nhắn vào MySQL.
- Liên hệ: lưu lời nhắn vào MySQL; xem và xóa trong quản trị.
- Quản trị: đăng nhập từ bảng users, quản lý tài khoản, sự kiện và nội dung; bài viết có thanh công cụ định dạng bám khi cuộn.
- Dự án: thêm/sửa/xóa tên, phụ đề và ngày giờ bắt đầu; chọn một dự án hiển thị trên trang chủ; đồng hồ đếm ngược cập nhật mỗi giây theo giờ Việt Nam (UTC+7), dừng ở 0 khi đến giờ.

## Thiết lập đồng hồ dự án

1. Đăng nhập quản trị và mở **Dự án**: http://localhost:8000/admin/projects.php.
2. Nhập tên dự án (hiển thị trên quạt), phụ đề và ngày giờ bắt đầu **theo giờ Việt Nam**.
3. Chọn **Hiển thị trên trang chủ và chạy đồng hồ đếm ngược**, rồi lưu.
4. Tải lại trang chủ để lấy lịch mới. Một dự án được hiển thị tại một thời điểm; chọn dự án mới sẽ thay dự án cũ. Bỏ chọn khi sửa để ngừng hiển thị.

Khi chưa có dự án được chọn, đồng hồ hiển thị dấu gạch và thông báo lịch sẽ cập nhật. Khi đến giờ, đồng hồ giữ 00 và hiển thị “Dự án đã đến giờ bắt đầu”. Không tự chuyển sang dự án khác hay tự thêm ngày giờ minh họa.

Database cài mới dùng schema.sql đã bao gồm bảng projects. Với database từ bản cũ, nhập **database/projects.sql** vào database đang dùng một lần (có thể chạy lại, không mất dữ liệu). Máy này đã được bổ sung bảng.

Biểu mẫu lưu vào cơ sở dữ liệu và hàng đợi email xác nhận/thông báo cho ban tổ chức. Cấu hình SMTP và tác vụ gửi thư theo [FEATURES.md](FEATURES.md) để gửi email thực tế. Thời gian và chỗ tham gia do ban tổ chức xác nhận; nội dung mẫu chưa có lịch đang mở được xác minh. Hai lựa chọn hoạt động trong biểu mẫu có thể sửa đồng thời tại public/index.php và public/submit.php.

## Cài đặt với XAMPP

1. Cài XAMPP có PHP >= 8.0 và bật Apache, MySQL. Cần extension pdo_mysql, mbstring, fileinfo (thường có sẵn).
2. Chép thư mục này vào `htdocs/phat-quat-hoa-van`.
3. Mở phpMyAdmin, nhập `database/schema.sql`, sau đó nhập `database/seed.sql` **một lần** để có bài viết và ảnh ban đầu. Nhập seed lại sẽ tạo bản ghi trùng.
4. Sao chép `app/config.example.php` thành `app/config.local.php`, điền host, port, tên database, tài khoản và mật khẩu MySQL.
5. Tạo mật khẩu quản trị bằng lệnh PHP dưới đây và chép chuỗi kết quả vào `admin_password_hash` trong cấu hình. Có thể đổi `admin_username`.

```sh
php -r 'echo password_hash("thay-bang-mat-khau-rieng", PASSWORD_DEFAULT), PHP_EOL;'
```

Nếu PHP chưa nằm trong PATH, dùng đường dẫn PHP của XAMPP, ví dụ macOS: `/Applications/XAMPP/xamppfiles/bin/php`.

6. Mở `http://localhost/phat-quat-hoa-van/public/`.
7. Quản trị: `http://localhost/phat-quat-hoa-van/public/admin/login.php`.

Không có mật khẩu quản trị mặc định: đăng nhập chỉ được bật khi đã cấu hình hash mật khẩu.

## Chạy bằng PHP development server

Sau khi nhập schema/seed và cấu hình kết nối MySQL, chạy từ thư mục dự án:

```sh
php -S 127.0.0.1:8000 -t public
```

Truy cập `http://127.0.0.1:8000`. Server này dành cho phát triển tại máy cá nhân.

## Đưa lên hosting PHP/MySQL

- Đặt document root vào thư mục `public/`. Giữ `app/`, `database/` ngoài vùng public.
- Với shared hosting, đưa **nội dung** `public/` vào `public_html/`, đặt `app/` ở thư mục anh em bên ngoài public_html. Các đường dẫn `../app` vẫn khớp.
- Nếu provider cấp sẵn tên database, bỏ hai câu `CREATE DATABASE` / `USE` trong schema và chọn database đã cấp trước khi import. Làm tương tự với `USE` trong seed.
- Điền thông tin MySQL của hosting trong `app/config.local.php`. Dùng HTTPS.
- PHP cần ghi được vào `public/assets/uploads/`; tạo thư mục này nếu hosting không cho tạo tự động. Không dùng quyền 777.
- Đặt timezone PHP thành `Asia/Ho_Chi_Minh`; mã nguồn đã đặt múi giờ PHP và phiên kết nối MySQL về UTC+7.
- Google Fonts cần kết nối internet; nếu không có, giao diện dùng font hệ thống.

Hosting Sites chạy Workers, không thực thi trực tiếp PHP/MySQL. Bản này được bàn giao để dùng với hosting PHP/MySQL.

## Cấu trúc

```text
app/
  bootstrap.php           Phiên đăng nhập, PDO, CSRF, tiện ích
  config.example.php      Mẫu cấu hình; sao chép thành config.local.php
  layout.php              Header/footer công khai dùng chung và đường dẫn theo thư mục cài đặt
  upload.php              Kiểm tra và lưu ảnh
public/
  index.php               Trang giới thiệu và các biểu mẫu
  post.php                Trang đọc bài viết
  submit.php              Xử lý đăng ký/liên hệ
  admin/                  Đăng nhập và quản lý nội dung
  assets/                 CSS, JS và ảnh
  assets/uploads/         Ảnh tải lên (tạo khi cần)
database/
  schema.sql              Bảng MySQL
  seed.sql                Nội dung ban đầu từ mẫu
  projects.sql            Bổ sung bảng dự án cho bản cũ
```

Ảnh `village.jpg` và họa tiết tre `fan.png` lấy từ tài nguyên công khai của trang tham chiếu; giữ ảnh có watermark như bản gốc. Thay bằng ảnh thuộc quyền sử dụng của dự án khi cần. Nguồn ảnh:
- https://phatquathoavan.my.canva.site/_assets/media/2a325885b6013663ec5e5373d4d2712e.jpg
- https://phatquathoavan.my.canva.site/_assets/media/e2762ddbbf5c2f8305f87d5f5170200a.png

## Kiểm thử nghiệm thu

1. Mở trang trên máy tính/điện thoại, kiểm tra menu, liên kết mục, lọc ảnh và đóng ảnh lớn bằng Escape.
2. Gửi đăng ký hợp lệ và lời nhắn; kiểm tra xuất hiện trong quản trị. Dữ liệu sai phải bị từ chối; gửi tiếp trong 30 giây phải bị giới hạn.
3. Đăng nhập đúng/sai, đăng xuất; URL quản trị phải yêu cầu đăng nhập khi chưa xác thực.
4. Tạo bài nháp: không được hiển thị công khai. Xuất bản, mở trang bài viết, sửa và xóa.
5. Thêm/sửa ảnh và nhóm; kiểm tra thư viện cập nhật. File ngoài JPG/PNG/WebP hoặc lớn hơn 3 MB phải bị từ chối.
6. Nội dung có thẻ HTML phải hiển thị như văn bản, không thực thi. Các thao tác POST cần CSRF.
7. Thêm dự án có lịch tương lai, bật hiển thị, kiểm tra đồng hồ; sửa lịch và tải lại trang chủ. Ngày không hợp lệ bị từ chối; lịch đã qua hiển thị 00 và trạng thái đã đến giờ. Xóa dự án để đưa trang chủ về trạng thái chưa có lịch.

Đã chạy trên XAMPP PHP 8.0.28 và MariaDB 10.4.28: toàn bộ file PHP qua kiểm tra cú pháp; trang chủ và bài blog trả HTTP 200; khu vực quản trị chuyển sang trang đăng nhập khi chưa xác thực; đăng nhập đúng vào được tổng quan và đọc dữ liệu MySQL. Chưa kiểm tra giao diện bằng trình duyệt điều khiển hoặc toàn bộ luồng tải ảnh/CRUD/biểu mẫu. Giới hạn đăng nhập hiện áp dụng theo phiên; khi triển khai công khai có thể cấu hình thêm giới hạn ở máy chủ/proxy. Xóa mục trong quản trị không tự xóa file ảnh để tránh làm mất ảnh đang dùng ở bài khác.

Phần dự án đã kiểm thử thêm/sửa/xóa qua HTTP và MySQL, từ chối ngày 30/02, xuất lịch đúng UTC+7 và trạng thái khi hết giờ. JavaScript đã được kiểm tra tính ngày/giờ/phút/giây, cập nhật mỗi giây, lệch giờ máy người dùng và chặn số âm. Dữ liệu dự án dùng để kiểm thử đã xóa.

Tài nguyên trang mẫu bổ sung: `hero-fan.png` (quạt vàng), `logo.png`, `hero-scroll.png` (dải lụa vàng), `hero-hills.png` (họa tiết nền); `nav-fan.svg` được dựng bằng SVG để làm biểu tượng điều hướng.

## Môi trường đã chạy trên máy này

- Website: http://localhost:8000
- Quản trị: http://localhost:8000/admin/login.php
- Tài khoản quản trị được lưu tại `.local/admin-access.txt` (chỉ ở máy này, không đưa vào bản ZIP).
- MySQL dùng dịch vụ đang chạy của XAMPP; không cần nhập lại seed.
- Để chạy lại sau khi đóng phiên server, bật MySQL trong XAMPP rồi chạy `./start.command`.

## Cập nhật chức năng

Xem [FEATURES.md](FEATURES.md) để cấu hình email, xuất CSV, địa điểm/bản đồ/lịch, SEO, ảnh WebP, chống spam và các trang bài viết/FAQ/đối tác. Trên Windows, bật MySQL rồi chạy `./start.ps1`.

## Cập nhật quản trị và tài khoản

Sau khi cập nhật mã nguồn hoặc cài database mới, cấu hình thông tin MySQL và hash quản trị ban đầu rồi chạy `php app/migrate.php`. Xem [FEATURES.md](FEATURES.md) để biết chi tiết. Đăng nhập sau migration dùng bảng `users`; tài khoản quản trị hiện có giữ cùng mật khẩu. Quản lý sự kiện tại `/admin/events.php`, tài khoản tại `/admin/users.php`. Mật khẩu thực tế chỉ nằm trong file cấu hình cục bộ/hash database, không ghi vào README.

Quản lý phần Câu chuyện trang chủ tại `/admin/stories.php`. Ở Bài viết, chọn “Hiển thị trong mục Câu chuyện trên trang chủ” để giới thiệu bài đã xuất bản trong phần này. Xem [FEATURES.md](FEATURES.md) để cập nhật database và sử dụng.

**Quản lý nội dung hiện tại:** Câu chuyện và Bài viết đã gộp vào một mục **Câu chuyện & bài viết** tại `/admin/stories.php`. Dùng cùng danh sách, bộ soạn thảo và các thao tác thêm/sửa/xóa; lựa chọn hiển thị trang chủ nằm trong từng bài.
