# Xoè 2026

Website PHP 8+ và MySQL/MariaDB, chạy với XAMPP hoặc hosting PHP. Không cần Composer hay Node.js để chạy website.

## Chạy trên máy

1. Bật MySQL trong XAMPP.
2. Giữ cấu hình riêng trong `app/config.local.php`; máy mới sao chép từ `app/config.example.php` và điền thông tin MySQL.
3. Cài mới: nhập `database/schema.sql`, rồi `database/seed.sql` **một lần**. Database đã có: không nhập lại seed.
4. Chạy migration từ thư mục dự án:

```powershell
C:/xampp/php/php.exe app/migrate.php
```

5. Windows: chạy `./start.ps1`. Nếu PowerShell chặn script, dùng lệnh PHP trực tiếp:

```powershell
C:/xampp/php/php.exe -d session.save_path=C:/xampp/htdocs/phat-quat-hoa-van/.local/sessions -d extension=gd -S 127.0.0.1:8000 -t . app/router.php
```

Tạo `.local/sessions` nếu chưa có. `start.ps1` tự tạo thư mục này và bật GD khi có DLL. Trên macOS/XAMPP dùng `./start.command`; có thể đổi đường dẫn PHP trong script theo máy. Hoặc dùng PHP trong PATH:

```sh
php -S 127.0.0.1:8000 -t . app/router.php
```

Website: http://127.0.0.1:8000. Quản trị: http://127.0.0.1:8000/admin/login.php. Nhấn Ctrl+C để dừng server.

## Cấu trúc đã gộp

File `database/import.sql` là bản xuất đầy đủ cấu trúc và dữ liệu tại thời điểm tạo file. Để chuyển lên hosting: tạo một database trống, chọn database đó trong phpMyAdmin, mở **Import**, chọn `import.sql` rồi thực hiện. Không import thêm `schema.sql` hoặc `seed.sql` sau đó. Bản xuất giữ tài khoản hiện tại với mật khẩu đã băm, sự kiện, bài viết và các nội dung đang có; không chứa thông tin kết nối MySQL. Sau khi import, cập nhật thông tin kết nối trong `app/config.local.php`. Không upload file SQL vào `htdocs`; file được bỏ qua trong Git vì chứa dữ liệu riêng. Nếu có thay đổi nội dung sau thời điểm xuất, cần xuất lại database để lấy dữ liệu mới nhất.

```text
app/
  bootstrap.php       Cấu hình, PDO, phiên, đăng nhập, CSRF, ảnh/upload, chống spam
  content.php         Sự kiện, câu chuyện, bài viết, HTML an toàn, bộ ảnh mặc định
  views.php           Header/footer, SEO, menu quản trị và các giao diện dùng chung
  mail.php            SMTP và hàng đợi email
  migrate.php         Migration database; --photos nhập bổ sung bộ ảnh mẫu
  send-mail.php       Tác vụ gửi thư qua CLI
  router.php          Tương thích URL tài nguyên cũ cho PHP development server
  config.example.php  Cấu hình mẫu
  config.local.php    Cấu hình riêng của máy, không đưa vào Git
database/
  schema.sql          Toàn bộ cấu trúc bảng
  seed.sql            Nội dung ban đầu, chỉ nhập một lần
index.php             Trang chủ ở thư mục gốc
public/
  index.php           Chuyển URL trang chủ cũ về index.php ở gốc
  posts.php, post.php  Danh sách bài viết và trang đọc bài
  faq.php, partners.php
  submit.php, calendar.php, sitemap.php, robots.txt
  admin/              Đăng nhập, sự kiện, nội dung, tài khoản, CSV
  assets/
    style.css         CSS của website và quản trị
    app.js            Menu, thư viện ảnh, đồng hồ, soạn thảo
    ảnh + WebP        Ảnh gốc và các kích thước đáp ứng
    uploads/          Tự tạo khi tải ảnh lên
.local/
  admin-access.txt                  Thông tin đăng nhập trên máy này
  sessions/                         Phiên làm việc, không xóa khi đang dùng
  project-before-consolidation.zip   Mã nguồn và database trước khi gộp
  history.zip                       Tài liệu thử, ảnh xem trước và công cụ cục bộ cũ
  verify-project.php                Kiểm tra tính toàn vẹn và các chức năng chính
```

`app`, `database` và `public` vẫn tách biệt để bảo vệ cấu hình và dữ liệu. `.git` được giữ nguyên. Ảnh được đưa từ `assets/photos/` vào `assets/`, giữ nguyên nội dung file và các kích thước đáp ứng. Đường dẫn ảnh trong database đã được cập nhật; URL ảnh cũ, `admin.css`, `editor.js` vẫn hoạt động qua alias Apache và `app/router.php`. URL `admin/projects.php` vẫn mở quản lý sự kiện. Các helper/giao diện nhỏ đã gộp vào ba file PHP chung; SQL bổ sung đã gộp vào schema, CSS/JS vào một file mỗi loại. Toàn bộ hướng dẫn hiện ở README này.

## Quản trị

- **Sự kiện:** thêm/sửa/xóa tên, phụ đề, mô tả, thời gian UTC+7, địa điểm, địa chỉ, lịch hoạt động. Chọn xuất bản và sự kiện nổi bật; trang chủ dùng cùng database. Có thể để trống lịch chưa chốt. Sự kiện đã xuất bản khác hiển thị trong mục Dự án; bản nháp được ẩn. Dải lịch và bộ đếm ở ngay dưới phần đầu. Tệp `.ics` tải từ `calendar.php` khi lịch/địa điểm đã đầy đủ.
- **Câu chuyện & bài viết:** dùng một danh sách và một bộ soạn bài. Thêm/sửa/xóa, lưu nháp, xuất bản và chọn hiển thị trong Câu chuyện. Trang chủ hiển thị tối đa 6 bài được chọn đã xuất bản. Thanh công cụ bám khi cuộn có in đậm/nghiêng/gạch chân, H2/H3, danh sách, trích dẫn, liên kết, hoàn tác. Nội dung được lọc HTML tại máy chủ; bài cũ dạng văn bản vẫn đọc được.
- **Giới thiệu trên trang chủ:** thêm/sửa/xóa từng khối, chỉnh ảnh/tiêu đề/chú thích, ẩn/hiện và thứ tự. Khối đã xóa không tự khôi phục. **Nội dung mở rộng** quản lý các mục mở như Lịch sử hình thành; hỗ trợ định dạng, ẩn/hiện và thứ tự.
- **Ảnh dự án:** tải ảnh, sửa tiêu đề/nhóm và xóa mục. Bộ lọc và xem ảnh lớn được tích hợp trong Dự án.
- **Tài khoản:** bảng `users` lưu hash mật khẩu, quyền admin/editor và trạng thái. Quản trị viên thêm/sửa/đổi mật khẩu/khóa tài khoản. Thay mật khẩu, quyền hoặc trạng thái thu hồi phiên cũ. Không thể khóa/hạ quyền quản trị viên hoạt động cuối cùng. Mật khẩu mới ít nhất 12 ký tự, tối đa 72 byte. Migration nhập tài khoản cấu hình ban đầu một lần; sau đó đăng nhập dùng database.
- **Đăng ký & liên hệ:** xem dữ liệu, phân trang; xuất CSV UTF-8 có BOM, chống công thức Excel, yêu cầu đăng nhập/CSRF. Xóa bản ghi trong admin không tự xóa ảnh gốc.

Nếu cần nhập bổ sung bộ ảnh mẫu cho database cũ:

```sh
php app/migrate.php --photos
```

Không chạy `--photos` để khôi phục các mục ảnh bạn chủ động xóa. Migration thường không nhập lại ảnh hoặc các nội dung đã xóa.

## Email

Đăng ký và email xác nhận/thông báo được lưu cùng transaction. Email xác nhận đã nhận yêu cầu; ban tổ chức xác nhận lịch và chỗ riêng. Điền các khóa trong `app/config.local.php`:

```php
'site_url' => 'https://ten-mien-cua-ban.example',
'organizer_email' => 'email-ban-to-chuc@example.com',
'mail_from' => 'email-gui@example.com',
'smtp_host' => 'smtp.example.com',
'smtp_port' => 587,
'smtp_security' => 'tls',
'smtp_username' => 'email-gui@example.com',
'smtp_password' => 'mat-khau-ung-dung',
```

Dùng `tls` cho STARTTLS, thường cổng 587; `ssl` cho TLS ngay khi kết nối, thường cổng 465. Cần OpenSSL và chứng chỉ CA hợp lệ. Cấu hình cron/Task Scheduler mỗi phút:

```powershell
C:/xampp/php/php.exe C:/xampp/htdocs/phat-quat-hoa-van/app/send-mail.php
```

Worker xử lý tối đa 20 email/lần, có khóa chống chạy trùng và thử tối đa 5 lần. Admin hiển thị trạng thái/lỗi và nút thử lại. Chưa cấu hình SMTP thì thư nằm trong hàng đợi. Tác vụ kiểm tra không gửi email thực tế.

## Hosting, SEO và ảnh

Đặt document root vào thư mục gốc dự án, nơi có `index.php` và `.htaccess`. Trên InfinityFree, upload `index.php`, `.htaccess`, toàn bộ `app/` và `public/` vào `htdocs` của tên miền. Không upload `.local`, `.git`, `database/` và script khởi động. Xuất database hiện tại rồi import vào database đã tạo trên hosting để giữ tài khoản và nội dung. Sửa thông tin MySQL trong `app/config.local.php` và đặt `site_url` thành URL tên miền (không thêm `/public`). Trang quản trị ở `/admin/login.php`. File `.htaccess` gốc chuyển URL ảnh, trang phụ và quản trị tới `public/`, đồng thời chặn truy cập HTTP vào cấu hình riêng.

Cấu hình HTTPS, PDO MySQL, mbstring, DOM, fileinfo; GD để tạo ảnh WebP từ upload. Cấp quyền ghi phù hợp cho `public/assets/uploads`, không dùng 777. Trên hosting có tên database riêng, bỏ dòng CREATE DATABASE/USE khi nhập schema/seed. Giữ các `.htaccess` trong thư mục con để chặn script trong assets và tạo alias đường dẫn cũ.

Điền `site_url` bằng URL công khai (gồm đường dẫn con nếu có) để canonical/Open Graph đúng. `sitemap.php` sinh trang công khai và bài đã xuất bản; thêm URL đầy đủ vào robots.txt ở gốc tên miền. Có Event/BlogPosting JSON-LD. Google Fonts cần mạng; giao diện có font thay thế.

Ảnh gốc và WebP 480/960/1600px được giữ, có srcset, kích thước cố định, lazy loading; quạt đầu trang ưu tiên tải. Upload tối đa 3 MB, 8000px và 16 megapixel. Logo dùng ảnh Xòe do người dùng cung cấp. Bộ ảnh dự án lấy từ ZIP người dùng cung cấp; tài nguyên Canva cũ được giữ nguyên. Nguồn tham khảo ban đầu: https://phatquathoavan.my.canva.site.

## Chống spam và kiểm tra

Biểu mẫu có CSRF, honeypot, token dùng một lần 2 giờ, chờ tối thiểu 3 giây, giới hạn theo phiên 30 giây và theo IP 5 lần/10 phút trong MySQL. IP lấy từ kết nối trực tiếp; reverse proxy cần cấu hình tin cậy tại máy chủ.

Kiểm tra sau khi bật MySQL:

```powershell
C:/xampp/php/php.exe .local/verify-project.php
```

Khi server localhost:8000 đang chạy, có thể kiểm tra thêm các chức năng bằng dữ liệu tạm được dọn sau khi chạy:

```powershell
C:/xampp/php/php.exe .local/verify-project.php --exercise
```

Đã kiểm tra cú pháp PHP, tất cả ảnh/cấu hình bằng SHA-256 và dữ liệu của 11 bảng so với bản trước khi gộp (chỉ đổi đường dẫn ảnh). Kiểm tra HTTP: các trang công khai, quyền/CSRF, nội dung và giới thiệu, sự kiện và lịch UTC, tài khoản, CSV, tải ảnh/WebP và hàng đợi thư. Chrome 1440px/390px: thêm/sửa/xóa bài và khối giới thiệu, menu, lọc/xem ảnh, soạn bài, lưu lỗi và không tràn ngang. Chưa kiểm tra gửi SMTP tới hộp thư thật hoặc Apache trên hosting ngoài máy.

Thêm `--baseline` nếu muốn đối chiếu ảnh, cấu hình và database với bản **trước khi gộp**. Sau khi bạn chủ động thay nội dung/cấu hình, phép đối chiếu này sẽ báo khác biệt; kiểm tra thông thường dùng dữ liệu hiện tại.

## Khôi phục bản trước khi gộp

Giữ `.local/project-before-consolidation.zip` và `.local/history.zip` trong vùng riêng. Archive mã nguồn chứa cả cấu hình cục bộ và SQL database trong `backup/`, không đưa lên vùng public hoặc chia sẻ công khai. Có thể giải nén archive sang thư mục khác để đối chiếu hoặc khôi phục. Chỉ nhập SQL sao lưu khi chủ động muốn thay database bằng bản trước khi gộp; file SQL này là bản khôi phục, không phải migration. `history.zip` giữ các file/công cụ thử cũ; website không cần giải nén nó để chạy.

Đổi tên nội dung (12/10/2026): sau khi cập nhật mã nguồn trên database hiện có, chạy `php app/rename-brand.php` để đổi tên dự án thành Xoè 2026 và sự kiện thành Xoè nếp xưa, đón gió lành. Lệnh chỉ thay các cụm tên cũ, có thể chạy lại.

## Google Form cho từng sự kiện

Database hiện có: import `database/event-google-forms.sql` trong phpMyAdmin sau khi chọn database website, hoặc chạy `php app/migrate-event-forms.php`. Lệnh có thể chạy lại và giữ nội dung hiện có.

Quản trị > Sự kiện > Sửa: nhập “Link Google Form đăng ký” bằng link dành cho người trả lời (`forms.gle` hoặc `docs.google.com/forms/.../viewform`), rồi Lưu sự kiện. Các sự kiện đã xuất bản xuất hiện ở phần Đăng ký trên trang chủ; link mở trong tab mới. Chưa có link thì hiển thị chờ cập nhật. Qua giờ kết thúc thì đóng nút đăng ký. Phản hồi được tiếp nhận trong Google Form của ban tổ chức.

## Đồng nhất đăng ký sự kiện và liên hệ

Trang chủ dùng “Đăng ký sự kiện” cho các link Google Form. Quản trị “Sự kiện & đăng ký” dùng để thêm sự kiện và chỉnh form; “Liên hệ” dùng để xem lời nhắn website. Dữ liệu biểu mẫu cũ được xem ở “Liên hệ trước đây”, không tạo mục Đăng ký riêng. Các phản hồi đăng ký sự kiện mới được quản lý trong Google Forms. Không cần thay đổi database cho cập nhật nhãn và điều hướng này.

## Giao diện quản trị và nhóm ảnh

Quản trị dùng sidebar có logo, thanh tìm nhanh, dashboard và giao diện đỏ–vàng. Số liệu và biểu đồ lấy từ dữ liệu bài viết thực tế.
Database hiện có: import `database/gallery-groups.sql` hoặc chạy `php app/migrate-gallery-groups.php`. Mục “Nhóm ảnh” cho phép thêm, đổi tên và xóa nhóm. Khi xóa nhóm có ảnh, chọn nhóm nhận ảnh; các ảnh được chuyển và giữ nguyên. Đổi tên nhóm cập nhật các ảnh cùng nhóm. Bộ lọc trang chủ lấy các nhóm đang có ảnh.

## Sửa lưu nội dung mở rộng và kiểm tra đồng bộ (14/10/2026)

Bản cập nhật xử lý lỗi ngoài khối lưu, giữ nội dung nhập lại khi lưu thất bại, và có mã lỗi thay cho màn hình trắng. Nội dung định dạng từ trình soạn thảo được truyền bằng Base64 UTF-8 rồi giải mã, kiểm tra độ dài và lọc HTML trên server; không thay đổi quyền/CSRF hoặc cho phép HTML không an toàn. Bản không bật JavaScript vẫn gửi văn bản bình thường.

Đã sửa migration bổ sung `story_sections.body_format`, lỗi ảnh mẫu tự xuất hiện khi xóa hết thư viện và giới hạn sáu bài trên trang chủ. Bài đã xuất bản và chọn hiển thị sẽ xuất hiện; các mục ẩn/xóa không hiển thị lại. Thao tác sửa ảnh đã xóa báo lỗi thay vì thông báo thành công giả.

### Cập nhật hosting đang dùng

1. Giải nén `xoe2026-fix-20261014.zip`, tải các file lên đúng đường dẫn tương ứng trong thư mục website. Archive không chứa cấu hình/mật khẩu hay dữ liệu database. Giữ `app/config.local.php` và ảnh tải lên hiện tại.
2. Mở `/admin/health.php` bằng tài khoản quản trị, hoặc bấm **Kiểm tra hệ thống** trên đầu trang quản trị. Trang kiểm tra PHP DOM/XML, mbstring, PDO MySQL, fileinfo, các cột cần thiết và quyền ghi ảnh.
3. Chọn database website trong phpMyAdmin và nhập `database/story-upgrade.sql` nếu thiếu cột `story_sections.body_format`. File này chỉ bổ sung cột, giữ dữ liệu, chạy lại được. Nếu hosting hỗ trợ CLI, có thể chạy `php app/migrate.php` để cập nhật đầy đủ cấu trúc.
4. Tải lại trang quản trị, mở Câu chuyện > Nội dung mở rộng > Lưu nội dung, sau đó mở trang chủ để đối chiếu. Nếu vẫn lỗi, gửi mã lỗi và dòng cùng mã từ PHP error log trên hosting hoặc `.local/php-errors.log` (vùng riêng bị chặn truy cập web).

Máy chủ hosting không trả lời khi truy cập từ môi trường kiểm thử, nên chưa xác nhận nguyên nhân trực tiếp của phiên bị trắng trên hosting hoặc việc upload lên hosting. Bộ kiểm thử chạy trên bản sao với PHP 8.0.28/MariaDB 10.4.28, cổng riêng 8091/3307, không thay đổi database của người dùng. Đã kiểm tra các luồng HTTP và database, cú pháp PHP/JS, truyền tiếng Việt/emoji, sanitization, lịch ICS và 7 bài được chọn hiển thị. Chưa kiểm tra thao tác trực quan Safari/Chrome, phản hồi trong Google Forms hay SMTP tới hộp thư thật ở lần kiểm thử này.
