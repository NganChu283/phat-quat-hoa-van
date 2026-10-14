<?php require __DIR__.'/../app/bootstrap.php';
$description='Dự án được tổ chức bởi nhóm sinh viên năm 3 lớp Digital Marketing, thuộc Khoa Quản trị Kinh doanh và Du lịch — Trường Đại học Hà Nội.';
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php seo('Đơn vị đồng hành — Xoè 2026',$description,'partners.php'); ?>
<link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>">
<script src="<?= asset_url('assets/app.js') ?>" defer></script>
</head>
<body>
<?php site_header(); ?>
<main class="article-page" id="noi-dung">
<a class="back" href="<?= e(site_path('index.php')) ?>">← Trang chủ</a>
<h1>Đơn vị đồng hành.</h1>
<p class="lead"><?= e($description) ?></p>
<a class="button" href="<?= e(site_path('index.php')) ?>#lien-he">Liên hệ hợp tác ↗</a>
</main>
<?php site_footer(); ?>
</body>
</html>
