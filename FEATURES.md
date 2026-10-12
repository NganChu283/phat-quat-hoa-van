# Các tính năng bổ sung

Website vẫn chạy trên PHP/MySQL trong XAMPP hoặc hosting PHP.

Trên Windows, bật MySQL trong XAMPP rồi chạy `./start.ps1` từ thư mục dự án. Website: http://127.0.0.1:8000; quản trị: http://127.0.0.1:8000/admin/login.php. Script dùng thư mục phiên trong `.local` và bật GD cho server này khi có DLL. Nhấn Ctrl+C để dừng.

## Database

Cài mới: nhập `database/schema.sql`, rồi nhập `database/seed.sql` một lần.

Database đã có: chạy từ thư mục dự án:

```powershell
C:/xampp/php/php.exe app/migrate.php
```

Migration bổ sung cột địa điểm, lịch, giờ kết thúc, hàng đợi email và giới hạn gửi; có thể chạy lại mà không xóa dữ liệu. Không cần nhập lại seed.

## Email

Thêm hoặc cập nhật các khóa sau trong `app/config.local.php` (không chia sẻ mật khẩu SMTP):

```php
'site_url' => 'https://ten-mien-cua-ban.example',
'organizer_email' => 'phatquathoavan.stu@gmail.com',
'mail_from' => 'email-gui-cua-ban@example.com',
'smtp_host' => 'smtp.example.com',
'smtp_port' => 587,
'smtp_security' => 'tls', // tls: STARTTLS; ssl: TLS ngay khi kết nối (thường port 465)
'smtp_username' => 'email-gui-cua-ban@example.com',
'smtp_password' => 'mat-khau-ung-dung-hoac-thong-tin-SMTP',
```

Đăng ký và hai email được lưu trong cùng một transaction. Email tự động xác nhận đã nhận yêu cầu; ban tổ chức vẫn xác nhận lịch và chỗ riêng. Không gửi thư từ request của khách, để lỗi SMTP không làm mất đăng ký hoặc khiến trang chờ lâu.

Cấu hình cron trên hosting hoặc Task Scheduler trên Windows chạy mỗi phút:

```powershell
C:/xampp/php/php.exe C:/xampp/htdocs/phat-quat-hoa-van/app/send-mail.php
```

Worker xử lý tối đa 20 email mỗi lần, khóa để tránh hai worker chạy cùng lúc, thử tối đa 5 lần. Admin → Đăng ký hiển thị trạng thái/lỗi; nút **Thử lại email** đặt lại số lần thử. SMTP cần PHP OpenSSL, CA chứng chỉ hợp lệ, và cổng gửi thư được hosting cho phép. Chưa có SMTP thì email nằm trong hàng đợi; website không tuyên bố đã gửi thư.

## Sự kiện và nội dung

Admin → Sự kiện: nhập giờ bắt đầu/kết thúc theo UTC+7, tên địa điểm, địa chỉ và lịch hoạt động. Chọn dự án hiển thị. Trang chủ có bản đồ/chỉ đường; nút thêm lịch xuất `.ics` khi đã có địa điểm, địa chỉ và giờ kết thúc. Không tự tạo lịch hoặc địa chỉ giả.

Admin → Đăng ký: xem phân trang, trạng thái email, xuất toàn bộ CSV UTF-8 có BOM và chống công thức Excel. Xuất yêu cầu đăng nhập và CSRF.

Các trang mới: `posts.php` (chỉ bài xuất bản, 12 bài/trang), `faq.php`, `partners.php`. Nội dung đối tác dùng thông tin có sẵn trong dự án; FAQ không tự khẳng định giá vé.

## SEO và ảnh

Điền `site_url` bằng URL công khai, gồm đường dẫn con nếu có, để canonical/Open Graph/sitemap đúng tên miền. `sitemap.php` sinh các trang công khai và bài xuất bản. Thêm URL đầy đủ của sitemap vào `public/robots.txt` khi có tên miền. Robots chỉ có hiệu lực khi được phục vụ tại gốc tên miền; các trang admin vẫn có `noindex` và yêu cầu đăng nhập.

Trang chủ có Event JSON-LD khi thông tin đầy đủ; bài viết có BlogPosting JSON-LD. Ảnh hiện có đã có bản WebP và kích thước 480/960/1600px, dùng `srcset`, kích thước cố định, lazy loading và decoding bất đồng bộ; ảnh quạt chính được ưu tiên tải.

Bật extension **GD** trong PHP để ảnh mới tải lên được tự động tạo bản WebP đáp ứng. Khi GD chưa bật, ảnh gốc vẫn được lưu và hiển thị. Upload tối đa 3 MB, 8000px và 16 megapixel. Ảnh gốc được giữ để xem lớn và chia sẻ. Apache cache ảnh một tháng, chặn thực thi script trong thư mục assets.

### Bộ ảnh cập nhật ngày 12/10/2026

Đã chọn 12 ảnh từ file ZIP người dùng cung cấp, chuyển cả ảnh HEIC sang WebP trong `public/assets/photos/`: ảnh nan quạt cho Câu chuyện, bốn ảnh minh họa Quy trình, 10 ảnh Thư viện, ảnh bàn nghề ở phần Đăng ký và ảnh bìa hai bài viết mẫu. Các ảnh giữ màu tự nhiên, có mô tả thay thế và kích thước đáp ứng. Thư viện được quản lý qua admin; khi database chưa có ảnh, trang chủ dùng bộ ảnh mặc định.

Database hiện tại đã cập nhật. Với database cũ ở máy khác, chạy `php app/import-photo-pack.php` một lần để thêm ảnh và thay bìa bài viết mẫu. Có thể chạy lại mà không tạo bản ghi trùng; bìa bài viết đã tùy chỉnh được giữ lại. Schema/seed cho cài mới đã dùng bộ ảnh này. Đã kiểm tra bố cục tại 1440px và 390px, bộ lọc Hoạt động và hộp xem ảnh lớn bằng Chrome.

## Chống spam và kiểm tra

CSRF, honeypot, token biểu mẫu một lần có thời hạn 2 giờ, chờ tối thiểu 3 giây, giới hạn theo phiên 30 giây và theo IP 5 lần/10 phút lưu trong MySQL. IP lấy từ kết nối trực tiếp; nếu đặt sau reverse proxy cần cấu hình tin cậy tại máy chủ thay vì tin header do khách gửi.

Đã kiểm tra cú pháp PHP và HTTP: trang công khai, quyền truy cập admin/CSV, lưu đăng ký cùng hai email, chống gửi lặp, giới hạn IP, đặt lại email lỗi, CSV chống công thức, lịch đúng UTC+7 và từ chối giờ kết thúc trước giờ bắt đầu. Dữ liệu kiểm thử được xóa sau khi chạy. Chưa kiểm tra gửi email đến hộp thư thực tế hoặc giao diện bằng trình duyệt điều khiển.


## Quản trị cập nhật ngày 12/10/2026

Chạy `php app/migrate.php` sau khi cập nhật mã nguồn. Migration bổ sung bảng `users`, trạng thái xuất bản và mô tả sự kiện, định dạng bài viết; giữ dữ liệu hiện có và có thể chạy lại. Sự kiện vẫn dùng bảng `projects` để giữ tương thích với dữ liệu cũ.

**Sự kiện:** `/admin/events.php` quản lý tên, phụ đề, mô tả, ngày bắt đầu/kết thúc, địa điểm, địa chỉ, lịch hoạt động, xuất bản và sự kiện nổi bật. Trang chủ đọc trực tiếp cùng database. Sự kiện nổi bật dùng tiêu đề và bộ đếm; các sự kiện đã xuất bản khác hiển thị trong mục Dự án; bản nháp được ẩn. Có thể để trống ngày khi lịch chưa chốt. Sự kiện Chạm Ký Hồn Quê đã được bổ sung nếu trước đây chưa có, không đặt ngày giờ minh họa và không thay thế sự kiện nổi bật đã chọn. Đường dẫn quản trị cũ `projects.php` vẫn hoạt động.

**Bài viết:** thanh công cụ bám trên đầu màn hình trong phạm vi vùng soạn bài. Có in đậm, nghiêng, gạch chân, tiêu đề H2/H3, danh sách, trích dẫn, liên kết, xóa định dạng, hoàn tác/làm lại. Nội dung lưu HTML đã lọc tại máy chủ, chỉ giữ các thẻ định dạng và liên kết hợp lệ; bài viết cũ giữ cách hiển thị văn bản. Có textarea thay thế khi JavaScript không chạy.

**Tài khoản:** `/admin/users.php` cho quản trị viên thêm/sửa tài khoản, đổi mật khẩu, đặt quyền quản trị viên/biên tập viên và khóa đăng nhập. Bảng `users` chỉ lưu hash mật khẩu. Tài khoản quản trị cấu hình trước đây được chuyển sang database với cùng mật khẩu. Đăng nhập không còn đọc mật khẩu từ config; config chỉ dùng để nhập quản trị viên ban đầu khi migration. Thay đổi mật khẩu/quyền/trạng thái yêu cầu tài khoản đó đăng nhập lại. Không thể khóa hoặc hạ quyền quản trị viên hoạt động cuối cùng. Tài khoản mới dùng mật khẩu ít nhất 12 ký tự, tối đa 72 byte.

**Logo:** dùng ảnh `logo xòe.png` do người dùng cung cấp, giữ bản PNG trong assets và bản WebP để hiển thị ở đầu/cuối trang chủ và các trang quản trị.

Đã kiểm tra HTTP/MySQL: đăng nhập từ bảng users, CSRF, tạo tài khoản/hash, giới hạn quyền tài khoản, khóa và thu hồi phiên, thêm/sửa/xuất bản/ẩn sự kiện, nội dung HTML an toàn và giữ văn bản bài viết cũ. Chrome tại 1440px và 390px: đăng nhập, đồng bộ tên sự kiện, thao tác in đậm, thanh công cụ bám khi cuộn, logo và JavaScript không có lỗi. Dữ liệu kiểm thử được dọn sau khi chạy.


## Quản lý Câu chuyện và bài viết

Admin → **Câu chuyện** (`/admin/stories.php`):

- **Giới thiệu & ảnh:** sửa nhãn, tiêu đề, dòng nhấn mạnh, giới thiệu, thay ảnh, mô tả ảnh và chú thích. Lưu sẽ cập nhật phần Câu chuyện trên trang chủ.
- **Các mục câu chuyện:** thêm/sửa/xóa các mục mở rộng như Lịch sử hình thành; soạn nội dung có định dạng, đặt thứ tự và bật/tắt hiển thị. Nội dung được lọc HTML như bài viết.
- **Quản lý bài viết:** chuyển sang mục Bài viết để thêm/sửa/xóa và xuất bản bài.

Admin → **Bài viết**: chọn **Hiển thị trong mục Câu chuyện trên trang chủ** để đưa bài vào phần Đọc thêm câu chuyện. Trang chủ chỉ hiển thị bài đã xuất bản và được chọn, tối đa 6 bài mới nhất. Bài nháp không công khai; bỏ chọn chỉ gỡ bài khỏi trang chủ. Trang `posts.php` dùng tên Câu chuyện & bài viết, có danh sách bài đã xuất bản và phân trang. Mục Nhật ký riêng không được thêm lại.

Chạy `php app/migrate.php` để bổ sung `story_settings`, `story_sections` và `posts.show_on_home`. Nội dung giới thiệu và hai mục câu chuyện hiện có được chuyển vào database; hai bài mẫu liên quan được chọn hiển thị trong Câu chuyện nếu vẫn xuất bản. Migration chỉ khởi tạo một lần, không khôi phục mục đã xóa/ẩn khi chạy lại.

Đã kiểm tra HTTP/MySQL: quyền đăng nhập, CSRF, sửa giới thiệu/ảnh/chú thích, thêm/sửa/ẩn/xóa mục câu chuyện, lọc HTML, chọn/bỏ chọn bài viết trên trang chủ và ẩn bài nháp. Chrome tại 1440px và 390px: trang quản trị, thanh công cụ định dạng, mục mở rộng và không tràn ngang. Dữ liệu kiểm thử được dọn sau khi chạy.


### Thêm, sửa, xóa câu chuyện

Mở **Admin → Câu chuyện** để thấy ngay **Danh sách câu chuyện**. Nút **Thêm câu chuyện** mở bộ soạn bài và tự chọn hiển thị trong Câu chuyện. Lưu nháp hoặc bật Xuất bản theo nhu cầu. Nút **Sửa** mở bài hiện có; lưu xong quay về danh sách. Nút **Xóa** yêu cầu xác nhận, xóa bài khỏi database và website (không tự xóa file ảnh). Nếu bỏ chọn hiển thị trong Câu chuyện, bài vẫn được quản lý trong **Bài viết**.

Phần **Giới thiệu & ảnh** chuyển sang `stories.php?mode=intro`; **Nội dung mở rộng** vẫn ở `stories.php?mode=sections`. Đã kiểm tra tạo/sửa/xóa qua trình duyệt, cập nhật trang chủ và bố cục quản trị trên điện thoại bằng dữ liệu thử, sau đó xóa dữ liệu thử.


## Câu chuyện & bài viết — quản lý hợp nhất

**Giới thiệu trên trang chủ** hiện có danh sách riêng với **Thêm phần giới thiệu**, **Sửa**, **Xóa** (có xác nhận), thứ tự và ẩn/hiện. Mỗi khối lưu tiêu đề, giới thiệu, ảnh, mô tả ảnh và chú thích. Trang chủ hiển thị các khối đã bật theo thứ tự; khối đã xóa không tự xuất hiện lại. Chạy `php app/migrate.php` khi cập nhật để bổ sung `story_settings.published`, `sort_order` và ID tự tăng. Xóa khối giới thiệu giữ nguyên bài viết và nội dung mở rộng. Đã kiểm tra thao tác qua Chrome và đồng bộ trang chủ bằng dữ liệu thử.

Menu quản trị chỉ còn một mục **Câu chuyện & bài viết**, tại `/admin/stories.php`:

- **Danh sách bài viết:** hiển thị mọi bài trong cùng database, kể cả bài nháp và bài không được chọn cho trang chủ; phân trang 25 bài.
- **Thêm bài viết / Sửa:** mở cùng bộ soạn thảo tại `stories.php?mode=edit`, lưu xong quay về danh sách. Chọn Xuất bản và Hiển thị trong mục Câu chuyện theo nhu cầu. Lỗi nhập liệu giữ lại nội dung đã nhập.
- **Xóa:** có xác nhận và gỡ bài khỏi website.
- **Giới thiệu trên trang chủ / Nội dung mở rộng:** quản lý phần giới thiệu và các mục mở rộng trong cùng chức năng.

Không tạo bản sao bài viết hoặc thay đổi dữ liệu hiện có. Đường dẫn quản trị cũ `index.php?tab=posts` chuyển sang danh sách chung; liên kết sửa bài cũ chuyển sang bộ soạn thảo mới. Không cần migration mới cho lần gộp này.

Đã kiểm tra bằng Chrome: menu không trùng, thêm/sửa/xóa từ trang chung, bài không được chọn trên trang chủ vẫn có trong danh sách, lưu nháp khi nhập sai, các liên kết cũ, quản lý giới thiệu và bố cục điện thoại.
