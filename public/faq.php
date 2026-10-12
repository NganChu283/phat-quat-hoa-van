<?php require __DIR__.'/../app/bootstrap.php'; require __DIR__.'/../app/seo.php'; require __DIR__.'/../app/layout.php';
$faq=[
 'Đăng ký tham gia bằng cách nào?' => 'Điền biểu mẫu đăng ký trên trang chủ. Ban tổ chức sẽ liên hệ qua email hoặc số điện thoại để xác nhận lịch và chỗ tham gia.',
 'Email xác nhận có phải vé tham dự không?' => 'Email tự động xác nhận rằng dự án đã nhận yêu cầu đăng ký. Lịch và chỗ tham gia cần được ban tổ chức xác nhận riêng.',
 'Sự kiện diễn ra ở đâu và khi nào?' => 'Xem mục Thông tin sự kiện trên trang chủ. Địa điểm, bản đồ và lịch chi tiết hiển thị khi ban tổ chức cập nhật.',
 'Chi phí tham gia là bao nhiêu?' => 'Chi phí và điều kiện tham gia sẽ được ban tổ chức thông báo khi xác nhận đăng ký.',
 'Tôi có thể thay đổi hoặc hủy đăng ký không?' => 'Gửi email đến phatquathoavan.stu@gmail.com với họ tên, email đăng ký và yêu cầu thay đổi hoặc hủy.',
 'Thông tin cá nhân được sử dụng thế nào?' => 'Ban tổ chức sử dụng họ tên, email, điện thoại và lời nhắn để xử lý đăng ký và phản hồi. Liên hệ email của dự án nếu bạn cần điều chỉnh hoặc xóa thông tin.'
]; ?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?php seo('Câu hỏi thường gặp — Phất Quạt Họa Văn','Giải đáp về đăng ký, lịch sự kiện và thông tin tham gia.','faq.php'); ?><link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>"><script src="<?= asset_url('assets/app.js') ?>" defer></script></head><body><?php site_header(); ?><main class="article-page" id="noi-dung"><a class="back" href="<?= e(site_path('index.php')) ?>">← Trang chủ</a><h1>Câu hỏi thường gặp.</h1><?php foreach ($faq as $question=>$answer): ?><details><summary><?= e($question) ?></summary><p><?= e($answer) ?></p></details><?php endforeach; ?><p><a class="button" href="<?= e(site_path('index.php')) ?>#dang-ky">Đăng ký tham gia ↗</a></p></main><?php site_footer(); ?></body></html>
