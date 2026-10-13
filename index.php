<?php
require __DIR__ . '/app/bootstrap.php';
$project = null; $events=[];
try { $events=published_events(); $project=featured_event($events); }
catch (PDOException $ex) { /* Chưa cài bảng dự án: hiển thị phần đầu với lịch chưa thiết lập. */ }
$start = $project && !empty($project['starts_at']) ? project_start($project) : null;
$remaining = $start ? max(0, $start->getTimestamp() - time()) : 0;
$clock = [intdiv($remaining,86400), intdiv($remaining % 86400,3600), intdiv($remaining % 3600,60), $remaining % 60];
$gallery = [];
try { $gallery = query_all('SELECT * FROM gallery ORDER BY id DESC'); }
catch (PDOException $ex) { /* Dùng bộ ảnh dự án mặc định nếu chưa kết nối được database. */ }
if (!$gallery) $gallery=default_gallery();
$storyIntros=[default_story_settings()]; $storySections=default_story_sections(); $storyPosts=[];
try {
    $storyIntros=query_all('SELECT * FROM story_settings WHERE published=1 ORDER BY sort_order,id');
    $storySections=query_all('SELECT * FROM story_sections WHERE published=1 ORDER BY sort_order,id');
    $storyPosts=query_all('SELECT id,title,excerpt,image FROM posts WHERE published=1 AND show_on_home=1 ORDER BY created_at DESC,id DESC LIMIT 6');
} catch (PDOException $ex) { /* Chờ migration: vẫn hiển thị câu chuyện hiện có. */ }
$oldContact = $_SESSION['old']['contact'] ?? [];
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php seo('Xoè 2026 — Xoè nếp xưa, đón gió lành','Câu chuyện làng nghề quạt Chàng Sơn. Khám phá dự án và đăng ký sự kiện.','index.php');
if ($start && $project && !empty($project['ends_at']) && !empty($project['venue']) && !empty($project['address'])) json_ld(['@context'=>'https://schema.org','@type'=>'Event','name'=>$project['title'],'startDate'=>$start->format('c'),'endDate'=>(new DateTimeImmutable($project['ends_at']))->format('c'),'eventAttendanceMode'=>'https://schema.org/OfflineEventAttendanceMode','location'=>['@type'=>'Place','name'=>$project['venue'],'address'=>$project['address']],'image'=>site_url('assets/chang-son-fans.webp'),'description'=>$project['subtitle'],'url'=>site_url('index.php#lich-su-kien')]); ?>
<meta name="theme-color" content="#741c23">
<link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>"><script src="<?= asset_url('assets/app.js') ?>" defer></script>
</head>
<body>
<?php site_header(true); ?>
<main id="noi-dung">
<section class="project" id="du-an" aria-label="Dự án Xoè 2026">
<section class="hero" aria-label="Giới thiệu dự án">
  <img class="hero-bamboo left" src="assets/fan.webp" alt="" aria-hidden="true">
  <img class="hero-bamboo right" src="assets/fan.webp" alt="" aria-hidden="true">
  <img class="hero-hills hero-hills-left" src="<?= asset_url('assets/hero-hills.webp') ?>" alt="" aria-hidden="true">
  <img class="hero-hills hero-hills-right" src="<?= asset_url('assets/hero-hills.webp') ?>" alt="" aria-hidden="true">
    <div class="hero-copy">
      <p class="eyebrow">XOÈ 2026</p>
      <h1 class="<?= mb_strlen($project['title'] ?? '') > 40 ? 'long-title' : '' ?>"><?= e($project['title'] ?? 'Cùng mở miền ký ức') ?></h1>
      <p class="hero-description"><?= e(($project['description'] ?? '') ?: 'Một nếp quạt mở. Một miền ký ức. Cùng khám phá vẻ đẹp của làng nghề quạt Chàng Sơn.') ?></p>
      <div class="hero-actions"><a class="button" href="<?= e($projectRegistrationUrl=($project?event_registration_url($project):'') ?: '#dang-ky') ?>"<?= $projectRegistrationUrl!=='#dang-ky'?' target="_blank" rel="noopener noreferrer"':'' ?>>Đăng ký sự kiện <span aria-hidden="true">↗</span></a><a class="button secondary" href="#cau-chuyen">Khám phá câu chuyện <span aria-hidden="true">↓</span></a></div>
    </div>
  <figure class="fan-stage"><div class="fan-halo" aria-hidden="true"></div><?= image_tag('assets/hero-quat-8065.webp','Những chiếc quạt Chàng Sơn nhiều màu sắc với chữ thư pháp','class="fan-art" fetchpriority="high"','(max-width: 800px) 88vw, 46vw') ?><figcaption><span aria-hidden="true">✦</span> GIỮ TRỌN KÝ ỨC TRONG TỪNG NAN QUẠT</figcaption></figure>
</section>
<?php render_event_strip($project,$start,$clock); ?>
<?php render_event_list($events,$project); ?>
<section class="project-gallery section" id="anh-du-an" aria-labelledby="project-gallery-title"><div class="section-head"><div><p class="eyebrow">HÌNH ẢNH DỰ ÁN</p><h2 id="project-gallery-title">Dự án qua<br><em>từng khung hình.</em></h2></div><div class="filters" role="group" aria-label="Lọc hình ảnh dự án"><button class="active" data-filter="all" aria-pressed="true">Tất cả</button><?php foreach(array_values(array_unique(array_column($gallery,'category'))) as $category): ?><button data-filter="<?= e($category) ?>" aria-pressed="false"><?= e($category) ?></button><?php endforeach; ?></div></div><div class="gallery-grid">
<?php if (!$gallery): ?><button class="gallery-item" data-category="Quạt Chàng Sơn" data-image="assets/village.jpg" data-title="Sắc màu quạt Chàng Sơn"><?= image_tag('assets/village.jpg','Sắc màu quạt Chàng Sơn') ?><span>Sắc màu quạt Chàng Sơn <b>↗</b></span></button><?php endif; ?>
<?php foreach ($gallery as $photo): ?><button class="gallery-item" data-category="<?= e($photo['category']) ?>" data-image="<?= e($photo['image']) ?>" data-title="<?= e($photo['title']) ?>"><?= image_tag($photo['image'],$photo['title']) ?><span><?= e($photo['title']) ?> <b>↗</b></span></button><?php endforeach; ?></div><p id="gallery-empty" class="empty" hidden>Chưa có ảnh trong nhóm này.</p></section>
</section>
<div class="ribbon" aria-hidden="true"><span>CHÀNG SƠN</span><span>✳</span><span>GIỮ TRỌN KÝ ỨC</span><span>✳</span><span>XOÈ 2026</span><span>✳</span></div>
<?php render_story($storyIntros,$storySections,$storyPosts); ?>
<section class="craft section"><div class="section-head"><div><p class="eyebrow">02 / QUY TRÌNH LÀM QUẠT</p><h2>Từ nan tre đến<br><em>một nét văn hóa.</em></h2></div><p>Mỗi chiếc quạt là sự kết hợp của chất liệu, sự khéo léo và những nét vẽ mang hồn quê.</p></div><div class="steps"><article><?= image_tag('assets/bamboo-slats.webp','Những thanh nan được chuẩn bị để làm quạt','class="craft-photo" loading="lazy"','(max-width: 800px) 42vw, 22vw') ?><span>01</span><h3>Chọn & xử lí nan tre</h3><p>Chuẩn bị chất liệu để bắt đầu tạo nên bộ khung của chiếc quạt.</p></article><article><?= image_tag('assets/carved-bamboo-fan.webp','Nan quạt xòe đều cùng họa tiết chạm khắc','class="craft-photo" loading="lazy"','(max-width: 800px) 42vw, 22vw') ?><span>02</span><h3>Chuốt & tạo nan quạt</h3><p>Từng nan được chuốt, tạo hình và sắp xếp thành dáng quạt.</p></article><article><?= image_tag('assets/making-fans.webp','Đôi tay ghép nan tre lên mặt quạt giấy','class="craft-photo" loading="lazy"','(max-width: 800px) 42vw, 22vw') ?><span>03</span><h3>Làm mặt quạt</h3><p>Mặt quạt được ghép với bộ nan, tạo nên những nếp mở mềm mại.</p></article><article><?= image_tag('assets/trang-tri-hoa-tiet.webp','Quạt đỏ trang trí họa tiết hoa tại làng nghề Chàng Sơn','class="craft-photo" loading="lazy"','(max-width: 800px) 42vw, 22vw') ?><span>04</span><h3>Trang trí họa tiết</h3><p>Nét vẽ hoàn thiện chiếc quạt, gửi gắm phong cảnh và câu chuyện.</p></article></div></section>



<section class="register section" id="dang-ky">
<div><p class="eyebrow">03 / ĐĂNG KÝ SỰ KIỆN</p><h2>Xoè nếp xưa,<br><em>đón gió lành.</em></h2>
<p class="lead">Chọn sự kiện bạn quan tâm và mở Google Form để đăng ký sự kiện.</p>
<p>Điền thông tin trong biểu mẫu đăng ký. Ban tổ chức sẽ liên hệ theo thông tin bạn cung cấp.</p>
<a class="text-link" href="#lien-he">Kết nối với dự án ↗</a>
<figure class="register-photo"><?= image_tag('assets/craft-table.webp','Góc bàn làm quạt cùng những dụng cụ thủ công') ?><figcaption>Một góc bàn nghề. Một câu chuyện để cùng khám phá.</figcaption></figure></div>
<div class="form-panel"><h3>Chọn sự kiện để đăng ký</h3><?php render_event_registration($events); ?></div>
</section>


<section class="contact section" id="lien-he"><div><p class="eyebrow">04 / LIÊN HỆ</p><h2>Một lời nhắn.<br><em>Một kết nối.</em></h2><a class="contact-email" href="mailto:Xoe2026@gmail.com">Xoe2026@gmail.com ↗</a><div class="socials"><a href="https://www.instagram.com/phatquathoavan.ajc/" target="_blank" rel="noopener noreferrer">Instagram ↗</a><a href="https://www.threads.net/@phatquathoavan.ajc" target="_blank" rel="noopener noreferrer">Threads ↗</a><span>Facebook · Xoè 2026</span></div></div><div><?php notice('contact'); ?><form action="submit.php" method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="kind" value="contact"><input type="hidden" name="form_token" value="<?= e(form_token('contact')) ?>"><div class="trap" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div><label>Họ và tên <input name="name" maxlength="120" required autocomplete="name" value="<?= e($oldContact['name'] ?? '') ?>"></label><label>Email <input type="email" name="email" maxlength="254" required autocomplete="email" value="<?= e($oldContact['email'] ?? '') ?>"></label><label>Lời nhắn <textarea name="message" maxlength="3000" rows="4" required><?= e($oldContact['message'] ?? '') ?></textarea></label><label class="check"><input type="checkbox" name="consent" value="1" required><span>Tôi đồng ý để dự án sử dụng thông tin này nhằm phản hồi lời nhắn.</span></label><button class="button" type="submit">Gửi lời nhắn ↗</button></form></div></section>
</main>
<?php site_footer(true); ?>
<dialog id="lightbox" aria-label="Xem hình ảnh dự án" aria-describedby="lightbox-caption"><button class="close-lightbox" aria-label="Đóng ảnh">×</button><img src="assets/village.jpg" alt=""><p id="lightbox-caption"></p></dialog>
</body></html>
