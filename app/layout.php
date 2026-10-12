<?php
// Header/footer công khai dùng chung. Mọi đường dẫn tính từ thư mục chứa script đang chạy
// nên hoạt động ở thư mục gốc lẫn khi website nằm trong thư mục con.
function site_path(string $path = ''): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = ($dir === '/' || $dir === '.') ? '' : implode('/', array_map('rawurlencode', explode('/', rtrim($dir, '/'))));
    return $base . '/' . ltrim($path, '/');
}
// Trả về URL đã escape cho thuộc tính HTML, kèm mã phiên bản theo thời gian sửa file.
function asset_url(string $path): string {
    $file = __DIR__ . '/../public/' . $path;
    return e(site_path($path) . (is_file($file) ? '?v=' . filemtime($file) : ''));
}
function site_header(bool $home = false): void {
    $section = fn(string $id): string => e($home ? '#' . $id : site_path('index.php') . '#' . $id);
    ?>
<a class="skip" href="#noi-dung">Đến nội dung chính</a>
<header class="header"><a class="brand" href="<?= e(site_path('index.php')) ?>" aria-label="Phất Quạt Họa Văn — trang chủ"><img class="brand-logo xoe-logo" src="<?= asset_url('assets/xoe-logo.webp') ?>" width="1536" height="1024" decoding="async" alt="Xòe — Phất Quạt Họa Văn"></a>
<button type="button" class="menu-toggle" aria-expanded="false" aria-controls="navigation">Danh mục <span aria-hidden="true">☰</span></button>
<nav id="navigation" aria-label="Điều hướng chính"><a href="<?= $section('cau-chuyen') ?>">Câu chuyện</a><a href="<?= $section('lich-su-kien') ?>">Sự kiện</a><a class="button small nav-cta" href="<?= $section('dang-ky') ?>">Đăng ký tham gia <span aria-hidden="true">↗</span></a></nav></header>
<?php
}
function site_footer(bool $home = false): void {
    $contact = e($home ? '#lien-he' : site_path('index.php') . '#lien-he');
    ?>
<footer><a class="brand" href="<?= e(site_path('index.php')) ?>"><img class="footer-logo" src="<?= asset_url('assets/xoe-logo.webp') ?>" width="1536" height="1024" alt="Xòe — Phất Quạt Họa Văn" loading="lazy"></a><p>Giữ trọn ký ức trong từng nan quạt.</p>
<div class="footer-links" role="navigation" aria-label="Liên kết chân trang"><a href="<?= e(site_path('faq.php')) ?>">FAQ</a><a href="<?= e(site_path('partners.php')) ?>">Đơn vị đồng hành</a><a href="<?= $contact ?>">Liên hệ</a><a href="<?= e(site_path('admin/login.php')) ?>">Quản trị ↗</a><a href="#top">Về đầu trang ↑</a></div></footer>
<?php
}
