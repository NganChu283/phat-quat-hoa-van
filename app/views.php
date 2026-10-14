<?php

// layout.php
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
    // Resolve the physical asset directory without depending on URL rewriting.
    $documentRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $publicRoot = realpath(__DIR__.'/../public');
    $url = null;
    if ($documentRoot && $publicRoot) {
        $documentRoot = rtrim(str_replace('\\','/',$documentRoot),'/');
        $publicRoot = str_replace('\\','/',$publicRoot);
        if (strncasecmp($publicRoot.'/', $documentRoot.'/', strlen($documentRoot)+1)===0) {
            $url = substr($publicRoot,strlen($documentRoot)).'/'.ltrim($path,'/');
        }
    }
    if ($url===null) {
        $script = str_replace('\\','/',$_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = preg_replace('~/(?:public(?:/admin)?|admin)/[^/]*$~','',$script);
        if ($base===$script) $base=dirname($script);
        $base=str_replace('\\','/',$base);
        $url = ($base==='/' || $base==='.' ? '' : rtrim($base,'/')).'/public/'.ltrim($path,'/');
    }
    return e($url . (is_file($file) ? '?v=' . filemtime($file) : ''));
}
function site_header(bool $home = false): void {
    $section = fn(string $id): string => e($home ? '#' . $id : site_path('index.php') . '#' . $id);
    ?>
<a class="skip" href="#noi-dung">Đến nội dung chính</a>
<header class="header"><a class="brand" href="<?= e(site_path('index.php')) ?>" aria-label="Xoè 2026 — trang chủ"><img class="brand-logo xoe-logo" src="<?= asset_url('assets/xoe-logo.webp') ?>" width="1536" height="1024" decoding="async" alt="Xòe — Xoè 2026"></a>
<button type="button" class="menu-toggle" aria-expanded="false" aria-controls="navigation">Danh mục <span aria-hidden="true">☰</span></button>
<nav id="navigation" aria-label="Điều hướng chính"><a href="<?= $section('cau-chuyen') ?>">Câu chuyện</a><a href="<?= $section('lich-su-kien') ?>">Sự kiện</a><a class="button small nav-cta" href="<?= $section('dang-ky') ?>">Đăng ký sự kiện <span aria-hidden="true">↗</span></a></nav></header>
<?php
}
function site_footer(bool $home = false): void {
    $contact = e($home ? '#lien-he' : site_path('index.php') . '#lien-he');
    ?>
<footer><a class="brand" href="<?= e(site_path('index.php')) ?>"><img class="footer-logo" src="<?= asset_url('assets/xoe-logo.webp') ?>" width="1536" height="1024" alt="Xòe — Xoè 2026" loading="lazy"></a><p>Xòe nếp xưa, đón gió lành</p>
<div class="footer-links" role="navigation" aria-label="Liên kết chân trang"><a href="<?= e(site_path('partners.php')) ?>">Đơn vị đồng hành</a><a href="<?= $contact ?>">Liên hệ</a><a href="<?= e(site_path('admin/login.php')) ?>">Quản trị ↗</a><a href="#top">Về đầu trang ↑</a></div></footer>
<?php
}

// admin-nav.php
function admin_tabs(): array {
    $tabs=['dashboard'=>'Tổng quan','projects'=>'Sự kiện','stories'=>'Câu chuyện & bài viết','gallery'=>'Ảnh dự án','gallery_groups'=>'Nhóm ảnh','contacts'=>'Liên hệ'];
    if ((current_user()['role'] ?? '')==='admin') $tabs['users']='Tài khoản';
    return $tabs;
}
function admin_tab_url(string $tab): string {
    if ($tab==='registrations') return 'index.php?tab=contacts&source=legacy';
    if ($tab==='projects') return 'events.php';
    if ($tab==='gallery_groups') return 'gallery_groups.php';
    if ($tab==='users') return 'users.php';
    if ($tab==='stories') return 'stories.php';
    return 'index.php?tab='.rawurlencode($tab);
}

// seo.php
function site_url(string $path = ''): string {
    $base = rtrim(config()['site_url'] ?? '', '/');
    if ($base === '') {
        // Local preview fallback. Set site_url to the public HTTPS URL in production.
        $base='http://localhost'.rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    return $base.'/'.ltrim($path,'/');
}
function json_ld(array $data): void {
    echo '<script type="application/ld+json">'.json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
}
function seo(string $title, string $description, string $path, string $image='assets/chang-son-fans.webp', string $type='website'): void {
    // Shared by the public pages so Analytics is installed once per page.
    echo <<<'HTML'
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-DCS48VY4BS"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-DCS48VY4BS');
</script>
HTML;
    echo '<title>'.e($title).'</title><meta name="description" content="'.e($description).'">';
    echo '<link rel="canonical" href="'.e(site_url($path)).'">';
    foreach (['og:locale'=>'vi_VN','og:site_name'=>'Xoè 2026','og:type'=>$type,'og:title'=>$title,'og:description'=>$description,'og:url'=>site_url($path),'og:image'=>site_url($image),'og:image:alt'=>$title] as $key=>$value) echo '<meta property="'.e($key).'" content="'.e($value).'">';
    echo '<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="'.e($title).'"><meta name="twitter:description" content="'.e($description).'"><meta name="twitter:image" content="'.e(site_url($image)).'">';
}

function render_event_strip(?array $project, ?DateTimeImmutable $start, array $clock): void { ?>
<section class="event-strip" id="lich-su-kien" aria-labelledby="event-strip-title">
  <div class="event-strip-info">
    <p class="eyebrow">LỊCH SỰ KIỆN</p>
    <h2 id="event-strip-title"><?= e($project['title'] ?? 'Hẹn bạn trong một câu chuyện mới') ?></h2>
    <?php if (!empty($project['subtitle'])): ?><p class="event-strip-meta"><?= e($project['subtitle']) ?></p><?php endif; ?>
    <p class="event-strip-date"><?= $start ? 'Bắt đầu lúc '.e($start->format('H:i · d/m/Y')).' (giờ Việt Nam)' : 'Thời gian và địa điểm sẽ được ban tổ chức cập nhật.' ?></p>
    <?php if (!empty($project['ends_at'])): ?><p class="event-strip-meta">Kết thúc lúc <?= e((new DateTimeImmutable($project['ends_at']))->format('H:i · d/m/Y')) ?></p><?php endif; ?>
    <?php if (!empty($project['venue'])): ?><p class="event-strip-meta"><?= e($project['venue']) ?><?= !empty($project['address'])?' — '.e($project['address']):'' ?></p><?php endif; ?>
    <?php if (!empty($project['schedule'])): ?><details class="event-schedule"><summary>Lịch hoạt động</summary><p><?= e($project['schedule']) ?></p></details><?php endif; ?>
    <?php if($project && ($registrationUrl=event_registration_url($project))): ?><a class="button event-strip-register" href="<?= e($registrationUrl) ?>" target="_blank" rel="noopener noreferrer">Đăng ký sự kiện <span aria-hidden="true">↗</span></a><?php endif; ?>
  </div>
  <div class="event-strip-clock">
    <div class="countdown" <?= $start ? 'data-target="'.e($start->format('c')).'" data-server-now="'.(time()*1000).'"' : '' ?> role="group" aria-label="Đếm ngược đến giờ bắt đầu dự án">
      <?php foreach (['Ngày','Giờ','Phút','Giây'] as $i=>$label): ?><div class="countdown-unit"><strong data-unit="<?= $i ?>"><?= $start ? str_pad((string)$clock[$i],2,'0',STR_PAD_LEFT) : '– –' ?></strong><span><?= e($label) ?></span></div><?php endforeach; ?>
    </div>
    <p class="countdown-status" role="status"><?= $start ? ($start->getTimestamp() > time() ? 'Đếm ngược đến giờ bắt đầu.' : 'Dự án đã đến giờ bắt đầu.') : 'Lịch tham gia sẽ được cập nhật.' ?></p>
    <noscript><p class="countdown-status">Bật JavaScript để đồng hồ cập nhật mỗi giây.</p></noscript>
  </div>
</section>

<?php
}

function render_event_list(array $events, ?array $project): void { ?>
<?php $otherEvents=array_values(array_filter($events,fn($event)=>!$project || (int)$event['id']!==(int)$project['id'])); ?>
<?php if ($otherEvents): ?>
<section class="event-list section" aria-labelledby="event-list-title">
  <div class="event-list-heading"><p class="eyebrow">HÀNH TRÌNH DỰ ÁN</p><h2 id="event-list-title"><?= $project?'Các sự kiện khác':'Sự kiện của dự án' ?></h2></div>
  <div class="event-cards">
  <?php foreach ($otherEvents as $event):
    $eventStart=!empty($event['starts_at'])?project_start($event):null;
    $eventEnd=!empty($event['ends_at'])?new DateTimeImmutable($event['ends_at']):null;
    $status=!$eventStart?'Chờ cập nhật lịch':($eventStart->getTimestamp()>time()?'Sắp diễn ra':($eventEnd && $eventEnd->getTimestamp()<time()?'Đã kết thúc':($eventEnd?'Đang diễn ra':'Đã đến giờ bắt đầu')));
  ?>
    <article class="event-card">
      <div class="event-card-date" aria-label="<?= $eventStart?e('Ngày '.$eventStart->format('d/m/Y')):'Lịch sẽ được cập nhật' ?>">
        <?php if ($eventStart): ?><time datetime="<?= e($eventStart->format('Y-m-d')) ?>"><strong><?= e($eventStart->format('d')) ?></strong><span>THÁNG <?= e($eventStart->format('m')) ?> · <?= e($eventStart->format('Y')) ?></span></time>
        <?php else: ?><strong aria-hidden="true">✦</strong><span>CHỜ LỊCH</span><?php endif; ?>
      </div>
      <div class="event-card-copy">
        <p class="event-card-status"><?= e($status) ?></p><h3><?= e($event['title']) ?></h3>
        <?php if (!empty($event['subtitle'])): ?><p class="event-card-subtitle"><?= e($event['subtitle']) ?></p><?php endif; ?>
        <?php if (!empty($event['description'])): ?><p class="event-card-description"><?= e($event['description']) ?></p><?php endif; ?>
        <?php if (!empty($event['schedule'])): ?><details class="event-schedule"><summary>Lịch hoạt động <span aria-hidden="true">+</span></summary><p><?= e($event['schedule']) ?></p></details><?php endif; ?>
      </div>
      <dl class="event-card-meta">
        <div><dt>Thời gian · giờ Việt Nam</dt><dd><?php if ($eventStart): ?><time datetime="<?= e($eventStart->format('c')) ?>"><?= e($eventStart->format('H:i · d/m/Y')) ?></time><?php else: ?>Lịch sẽ được cập nhật.<?php endif; ?><?php if ($eventEnd): ?><span class="event-card-end">Đến <?= e($eventEnd->format('H:i · d/m/Y')) ?></span><?php endif; ?></dd></div>
        <div><dt>Địa điểm</dt><dd><?= e(($event['venue'] ?? '') ?: 'Đang cập nhật') ?><?php if (!empty($event['address'])): ?><span class="event-card-address"><?= e($event['address']) ?></span><?php endif; ?></dd></div>
      </dl>
      <div class="event-card-registration"><?php render_registration_button($event); ?></div>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php
}

function render_registration_button(array $event): void {
    $url=event_registration_url($event);
    if($url){ ?>
<a class="button" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e('Đăng ký '.$event['title'].' (mở tab mới)') ?>">Đăng ký sự kiện <span aria-hidden="true">↗</span></a>
<?php } else { ?><p class="registration-status"><?= event_registration_closed($event)?'Sự kiện đã kết thúc.':'Đăng ký sự kiện sẽ được cập nhật.' ?></p><?php }
}
function render_event_registration(array $events): void { ?>
<div class="registration-events">
<?php if(!$events): ?><p class="registration-empty">Chưa có sự kiện mở đăng ký. Bạn vui lòng quay lại sau.</p><?php endif; ?>
<?php foreach($events as $event): ?>
<article class="registration-event">
<h3><?= e($event['title']) ?></h3>
<?php if(!empty($event['subtitle'])): ?><p><?= e($event['subtitle']) ?></p><?php endif; ?>
<?php if(!empty($event['starts_at'])): ?><p class="registration-event-meta">Thời gian: <?= e(project_start($event)->format('H:i · d/m/Y')) ?></p><?php endif; ?>
<?php if(!empty($event['venue'])): ?><p class="registration-event-meta">Địa điểm: <?= e($event['venue']) ?></p><?php endif; ?>
<?php render_registration_button($event); ?>
</article>
<?php endforeach; ?>
</div>
<?php }

function render_story(array $storyIntros, array $storySections, array $storyPosts): void { ?>
<?php
$visibleIntros=$storyIntros;
if (!$visibleIntros && ($storySections || $storyPosts)) $visibleIntros=[null];
?>
<?php if ($visibleIntros): ?><div id="cau-chuyen" class="story-group">
<?php foreach ($visibleIntros as $introIndex=>$story): ?>
<section class="story section <?= !$story?'story-without-intro':'' ?>" aria-labelledby="story-title-<?= $introIndex ?>">
<div class="story-copy">
<?php if ($story): ?><p class="eyebrow"><?= e($story['eyebrow']) ?></p><h2 id="story-title-<?= $introIndex ?>"><?= e($story['title']) ?><?php if ($story['title_accent']): ?><br><em><?= e($story['title_accent']) ?></em><?php endif; ?></h2><p class="lead"><?= e($story['intro']) ?></p>
<?php else: ?><h2 id="story-title-<?= $introIndex ?>">Câu chuyện</h2><?php endif; ?>
<?php if ($introIndex===0): foreach ($storySections as $section): ?><details><summary><?= e($section['title']) ?> <span aria-hidden="true">+</span></summary><div class="rich-article story-detail-body"><?= post_body($section) ?></div></details><?php endforeach; endif; ?></div>
<?php if ($story): ?><figure class="story-image"><?= image_tag($story['image'],$story['image_alt'],'loading="lazy"','(max-width: 800px) 88vw, 40vw') ?><figcaption><?= e($story['image_caption']) ?></figcaption></figure><?php endif; ?>
<?php if ($storyPosts && $introIndex===count($visibleIntros)-1): ?><div class="story-posts"><div class="story-posts-heading"><h3>Đọc thêm câu chuyện</h3><a class="text-link" href="posts.php">Xem tất cả bài viết ↗</a></div><div class="post-grid">
<?php foreach ($storyPosts as $post): ?><article class="post"><a href="post.php?id=<?= (int)$post['id'] ?>"><?= image_tag($post['image'],$post['title'],'loading="lazy"','(max-width: 450px) 88vw, (max-width: 800px) 42vw, 28vw') ?><h3><?= e($post['title']) ?></h3><p><?= e($post['excerpt']) ?></p><span class="story-read-button">Đọc câu chuyện <span aria-hidden="true">↗</span></span></a></article><?php endforeach; ?></div></div><?php endif; ?>
</section>

<?php endforeach; ?></div><?php endif; ?>

<?php
}

function render_editor(?array $edit=null, string $editorLabel='Nội dung bài viết'): void {
static $sequence=0; $editorId='post-body-'.(++$sequence); ?>
<div class="rich-editor" data-rich-editor data-upload-url="<?= e(site_path('upload_editor_image.php')) ?>">
<label id="<?= $editorId ?>-label" for="<?= $editorId ?>-source"><?= e($editorLabel) ?></label>
<div class="editor-toolbar" role="toolbar" aria-label="Định dạng nội dung bài viết" hidden>
<div class="editor-tool-group">
<?php foreach ([['undo','↶','Hoàn tác'],['redo','↷','Làm lại']] as [$command,$text,$label]): ?>
<button type="button" data-command="<?= $command ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>"><?= $text ?></button>
<?php endforeach; ?></div>
<div class="editor-tool-group">
<select data-command="formatBlock" aria-label="Kiểu đoạn" title="Kiểu đoạn"><option value="p">Đoạn văn</option><option value="h1">Tiêu đề 1</option><option value="h2">Tiêu đề 2</option><option value="h3">Tiêu đề 3</option><option value="blockquote">Trích dẫn</option></select>
<select data-command="fontName" aria-label="Phông chữ" title="Phông chữ"><option value="Arial">Arial</option><option value="Georgia">Georgia</option><option value="Times New Roman">Times New Roman</option><option value="Verdana">Verdana</option></select>
<select data-command="fontSize" aria-label="Cỡ chữ" title="Cỡ chữ"><?php foreach([1=>12,2=>14,3=>16,4=>18,5=>24,6=>32,7=>48] as $value=>$size): ?><option value="<?= $value ?>"<?= $value===3?' selected':'' ?>><?= $size ?></option><?php endforeach; ?></select>
</div>
<div class="editor-tool-group">
<?php foreach ([['bold','B','In đậm'],['italic','I','In nghiêng'],['underline','U','Gạch chân'],['strikeThrough','S̶','Gạch ngang'],['superscript','x²','Chỉ số trên'],['subscript','x₂','Chỉ số dưới']] as [$command,$text,$label]): ?>
<button type="button" data-command="<?= $command ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>"><?= $text ?></button>
<?php endforeach; ?>
<label class="editor-color" title="Màu chữ">A<input type="color" data-command="foreColor" value="#30251f" aria-label="Màu chữ"></label>
<label class="editor-color editor-highlight" title="Màu nền chữ">▰<input type="color" data-command="hiliteColor" value="#fff2a8" aria-label="Màu nền chữ"></label>
</div>
<div class="editor-tool-group">
<?php foreach ([['justifyLeft','≡ ←','Căn trái'],['justifyCenter','≡','Căn giữa'],['justifyRight','→ ≡','Căn phải'],['justifyFull','☰','Căn đều'],['insertUnorderedList','• List','Danh sách dấu đầu dòng'],['insertOrderedList','1. List','Danh sách đánh số'],['outdent','←','Giảm thụt lề'],['indent','→','Tăng thụt lề']] as [$command,$text,$label]): ?>
<button type="button" data-command="<?= $command ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>"><?= $text ?></button>
<?php endforeach; ?></div>
<div class="editor-tool-group">
<?php foreach ([['createLink','Liên kết','Chèn liên kết'],['unlink','Bỏ liên kết','Bỏ liên kết'],['insertImage','Ảnh','Chèn ảnh từ máy'],['insertTable','Bảng','Chèn bảng'],['insertHorizontalRule','—','Đường phân cách'],['removeFormat','Tx','Xóa định dạng']] as [$command,$text,$label]): ?>
<button type="button" data-command="<?= $command ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>"><?= $text ?></button>
<?php endforeach; ?></div>
<div class="editor-tool-group editor-table-tools" data-table-tools hidden>
<?php foreach (['addRow'=>'Thêm hàng','addColumn'=>'Thêm cột','deleteRow'=>'Xóa hàng','deleteColumn'=>'Xóa cột','deleteTable'=>'Xóa bảng'] as $action=>$label): ?>
<button type="button" data-table-action="<?= $action ?>"><?= e($label) ?></button>
<?php endforeach; ?></div>
</div>
<div class="editor-table-picker" data-table-picker hidden>
<label>Số hàng<input type="number" data-table-rows min="1" max="10" value="3"></label>
<label>Số cột<input type="number" data-table-columns min="1" max="8" value="3"></label>
<button type="button" data-table-insert>Chèn bảng</button><button type="button" data-table-cancel>Hủy</button>
</div>
<input type="file" data-editor-image accept="image/jpeg,image/png,image/webp" hidden>
<div class="editor-content" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="<?= $editorId ?>-label" hidden><?= post_body($edit ?? ['body'=>'','body_format'=>'plain']) ?></div>
<textarea id="<?= $editorId ?>-source" name="body" rows="12" maxlength="60000"><?= e($edit['body'] ?? '') ?></textarea>
<input type="hidden" name="body_format" value="<?= e($edit['body_format'] ?? 'plain') ?>">
<p class="editor-status" data-editor-status role="status">Dùng thanh công cụ để định dạng bài viết.</p>
</div>
<?php
}


function render_article_form(?array $edit=null): void { ?>
<section class="admin-card"><h2><?= $edit?'Chỉnh sửa bài viết':'Thêm bài viết' ?></h2>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="article"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<label>Tiêu đề<input name="title" maxlength="180" required value="<?= e($edit['title'] ?? '') ?>"></label>
<div class="admin-grid"><label>Đường dẫn ảnh<input name="image" maxlength="255" value="<?= e($edit['image'] ?? 'assets/chang-son-fans.webp') ?>"></label><label>Tải ảnh thay thế (tối đa 3 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div>
<label>Mô tả ngắn<textarea name="excerpt" rows="3" maxlength="500" required><?= e($edit['excerpt'] ?? '') ?></textarea></label>
<?php render_editor($edit); ?>
<label class="check"><input type="checkbox" name="published" value="1" <?= !empty($edit['published'])?'checked':'' ?>><span>Xuất bản trên website (bỏ chọn để lưu nháp)</span></label>
<label class="check"><input type="checkbox" name="show_on_home" value="1" <?= !$edit || !empty($edit['show_on_home'])?'checked':'' ?>><span>Hiển thị trong mục Câu chuyện trên trang chủ khi đã xuất bản</span></label>
<div class="admin-actions"><button class="button" type="submit">Lưu bài viết</button><a class="text-link" href="stories.php">Hủy / về danh sách</a></div>
</form></section>

<?php
}

function render_intro_form(array $story): void { ?>
<section class="admin-card" id="intro-form"><h2><?= !empty($story['id'])?'Sửa phần giới thiệu':'Thêm phần giới thiệu' ?></h2>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="intro"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($story['id'] ?? 0) ?>">
<label>Nhãn phía trên tiêu đề<input name="eyebrow" maxlength="120" value="<?= e($story['eyebrow']) ?>"></label><div class="admin-grid"><label>Tiêu đề dòng đầu<input name="title" maxlength="180" required value="<?= e($story['title']) ?>"></label><label>Dòng nhấn mạnh (không bắt buộc)<input name="title_accent" maxlength="180" value="<?= e($story['title_accent']) ?>"></label></div>
<label>Giới thiệu ngắn<textarea name="intro" maxlength="2000" rows="4" required><?= e($story['intro']) ?></textarea></label>
<div class="admin-grid"><label>Đường dẫn ảnh<input name="image" maxlength="255" value="<?= e($story['image']) ?>"></label><label>Tải ảnh thay thế (tối đa 3 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div>
<img class="story-admin-preview" src="../<?= e($story['image']) ?>" alt="<?= e($story['image_alt']) ?>">
<label>Mô tả ảnh<input name="image_alt" maxlength="180" required value="<?= e($story['image_alt']) ?>"></label><label>Chú thích dưới ảnh<input name="image_caption" maxlength="255" value="<?= e($story['image_caption']) ?>"></label>
<label>Thứ tự hiển thị (số nhỏ đứng trước)<input type="number" name="sort_order" min="0" max="9999" value="<?= (int)($story['sort_order'] ?? 0) ?>" required></label>
<label class="check"><input type="checkbox" name="published" value="1" <?= !empty($story['published'])?'checked':'' ?>><span>Hiển thị trên trang chủ</span></label>
<div class="admin-actions"><button class="button" type="submit">Lưu phần giới thiệu</button><a class="text-link" href="stories.php?mode=intro">Hủy / về danh sách</a></div></form></section>

<?php
}

function render_intro_list(array $introRows, bool $introFormVisible, array $story): void { ?>
<section class="admin-card intro-list"><div class="story-crud-heading"><div><h2>Phần Câu chuyện trên trang chủ</h2><p class="help">Thêm các khối giới thiệu, sắp xếp thứ tự hoặc chọn ẩn từng khối.</p></div><a class="button" href="stories.php?mode=intro&amp;new=1#intro-form">+ Thêm phần giới thiệu</a></div>
<div class="table-wrap"><table><thead><tr><th>Ảnh</th><th>Tiêu đề</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
<?php if (!$introRows): ?><tr><td colspan="5">Chưa có phần giới thiệu. Chọn “Thêm phần giới thiệu” để tạo mới.</td></tr><?php endif; ?>
<?php foreach ($introRows as $introRow): ?><tr><td><img class="admin-photo" src="../<?= e($introRow['image']) ?>" alt="<?= e($introRow['image_alt']) ?>"></td><td><strong><?= e($introRow['title']) ?></strong><br><?= e($introRow['title_accent']) ?></td><td><?= (int)$introRow['sort_order'] ?></td><td><?= $introRow['published']?'Đang hiển thị':'Đã ẩn' ?></td><td><div class="admin-actions story-crud-actions"><a class="button small" href="stories.php?mode=intro&amp;intro_edit=<?= (int)$introRow['id'] ?>#intro-form">Sửa</a><form method="post" class="intro-delete"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="intro"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$introRow['id'] ?>"><button class="button small story-delete-button">Xóa</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php if ($introFormVisible) render_intro_form($story); ?>
<script>document.querySelectorAll('.intro-delete').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Xóa phần giới thiệu này khỏi trang chủ?'))event.preventDefault();}));</script>

<?php
}

function admin_ui_icon(string $name): string {
    $paths=[
        'dashboard'=>'<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'projects'=>'<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18M8 16h3"/>',
        'stories'=>'<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M8 13h8M8 17h5"/>',
        'gallery'=>'<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 6-6 4 4 3-3 5 5"/>',
        'gallery_groups'=>'<path d="M3 7V5a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'contacts'=>'<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/>',
        'users'=>'<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 5"/>',
        'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'logout'=>'<path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4M14 8l5 4-5 4M8 12h11"/>'
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'.($paths[$name]??$paths['dashboard']).'</svg>';
}
function admin_page_head(string $title,string $active): void {
    $user=current_user();$tabs=admin_tabs();$name=$user['display_name']??'Quản trị'; ?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?= e($title) ?> — Xoè 2026</title><link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>"><link rel="stylesheet" href="<?= asset_url('assets/admin-dashboard.css') ?>"></head>
<body class="xoe-admin"><a class="admin-skip" href="#admin-content">Đến nội dung chính</a>
<div class="admin-app">
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Menu quản trị">
<a class="admin-sidebar-brand" href="index.php"><img src="<?= asset_url('assets/xoe-logo.webp') ?>" alt="Logo Xoè 2026"><span>KHÔNG GIAN QUẢN TRỊ</span></a>
<p class="admin-menu-label">QUẢN LÝ WEBSITE</p>
<nav class="admin-sidebar-nav" aria-label="Quản trị">
<?php foreach($tabs as $key=>$label): ?><a href="<?= e(admin_tab_url($key)) ?>"<?= $active===$key?' aria-current="page"':'' ?>><?= admin_ui_icon($key) ?><span><?= e($label) ?></span><?php if($active===$key): ?><span class="admin-active-dot" aria-hidden="true"></span><?php endif; ?></a><?php endforeach; ?>
</nav>
<div class="admin-sidebar-bottom"><a href="../index.php" target="_blank" rel="noopener noreferrer">Xem website <span aria-hidden="true">↗</span></a><form action="logout.php" method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit"><?= admin_ui_icon('logout') ?><span>Đăng xuất</span></button></form><small>Xoè 2026 · Nếp xưa, gió lành</small></div>
</aside>
<button class="admin-overlay" data-admin-close aria-label="Đóng menu" hidden></button>
<div class="admin-workspace">
<header class="admin-topbar">
<button class="admin-menu-toggle" type="button" data-admin-toggle aria-controls="admin-sidebar" aria-expanded="true" aria-label="Ẩn hoặc hiện menu"><?= admin_ui_icon('menu') ?></button>
<div class="admin-search-wrap"><?= admin_ui_icon('search') ?><input type="search" data-admin-search placeholder="Tìm mục quản trị…" aria-label="Tìm mục quản trị" autocomplete="off"><div class="admin-search-results" data-admin-search-results hidden></div></div>
<a class="admin-site-link" href="../index.php" target="_blank" rel="noopener noreferrer">Xem website ↗</a>
<div class="admin-profile"><span class="admin-avatar"><?= e(mb_strtoupper(mb_substr($name,0,1))) ?></span><span><strong><?= e($name) ?></strong><small><?= ($user['role']??'')==='admin'?'Quản trị viên':'Biên tập viên' ?></small></span></div>
</header>
<main class="admin-page" id="admin-content"><div class="admin-page-heading"><div><p class="admin-breadcrumb">Xoè 2026 <span>/</span> <?= e($title) ?></p><h1><?= e($title) ?></h1></div><div><span class="admin-date"><?= e(date('d/m/Y')) ?></span><?php if ((current_user()['role'] ?? '')==='admin'): ?><br><a class="admin-text-link" href="health.php">Kiểm tra hệ thống ↗</a><?php endif; ?></div></div>
<?php }
function admin_page_foot(): void { ?>
</main></div></div><script src="<?= asset_url('assets/app.js') ?>" defer></script><script src="<?= asset_url('assets/admin.js') ?>" defer></script>
<?php }
function render_admin_dashboard(array $stats): void {
    $published=(int)db()->query('SELECT COUNT(*) FROM posts WHERE published=1')->fetchColumn();
    $posts=$stats['stories']??0;$percent=$posts?(int)round($published/$posts*100):0;
    $recent=query_all('SELECT id,title,published,created_at FROM posts ORDER BY created_at DESC,id DESC LIMIT 5');
    $monthly=query_all("SELECT DATE_FORMAT(created_at,'%Y-%m') month,COUNT(*) total FROM posts WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY month");
    $monthly=array_column($monthly,'total','month');$months=[];
    $first=(new DateTimeImmutable('first day of this month'))->modify('-5 months');
    for($i=0;$i<6;$i++){$month=$first->modify('+'.$i.' months');$months[]=['label'=>$month->format('m/Y'),'count'=>(int)($monthly[$month->format('Y-m')]??0)];}
    $max=max(1,...array_column($months,'count')); ?>
<section class="admin-welcome"><div><p class="admin-eyebrow">NẾP XƯA · GIÓ LÀNH</p><h2>Chào bạn, hôm nay mình kể câu chuyện gì?</h2><p>Quản lý sự kiện, nội dung và hình ảnh của Xoè 2026 trong một không gian.</p></div><a class="button" href="events.php#event-form">+ Thêm sự kiện</a></section>
<div class="admin-metrics">
<?php foreach(['projects'=>'Sự kiện','stories'=>'Bài viết','gallery'=>'Ảnh dự án','contacts'=>'Liên hệ'] as $key=>$label): ?><a class="admin-metric" href="<?= e(admin_tab_url($key)) ?>"><span class="admin-metric-icon"><?= admin_ui_icon($key) ?></span><span class="admin-metric-label"><?= $label ?></span><strong><?= (int)($stats[$key]??0) ?></strong><span class="admin-metric-link">Quản lý <span aria-hidden="true">↗</span></span></a><?php endforeach; ?>
</div>
<div class="admin-dashboard-grid">
<section class="admin-card admin-chart-card"><div class="admin-card-heading"><div><h2>Bài viết theo tháng</h2><p>Số bài viết được tạo trong 6 tháng gần nhất</p></div><span class="admin-badge">Nội dung</span></div><div class="admin-bars" role="img" aria-label="<?= e(implode('; ',array_map(fn($m)=>$m['label'].': '.$m['count'].' bài',$months))) ?>"><?php foreach($months as $month): ?><div class="admin-bar-column"><strong><?= $month['count'] ?></strong><span class="admin-bar-track"><span style="height:<?= $month['count']?max(4,round($month['count']/$max*100)):0 ?>%"></span></span><small><?= $month['label'] ?></small></div><?php endforeach; ?></div></section>
<section class="admin-card admin-publication-card"><div class="admin-card-heading"><div><h2>Nội dung đã xuất bản</h2><p>Tiến độ xuất bản bài viết</p></div></div><div class="admin-progress-ring" style="--progress:<?= $percent ?>%"><div><strong><?= $percent ?>%</strong><span>đã xuất bản</span></div></div><div class="admin-publication-counts"><span><strong><?= $published ?></strong>Đã xuất bản</span><span><strong><?= max(0,$posts-$published) ?></strong>Bản nháp</span></div></section>
</div>
<section class="admin-card"><div class="admin-card-heading"><div><h2>Bài viết gần đây</h2><p>Tiếp tục biên tập những câu chuyện của dự án</p></div><a class="admin-text-link" href="stories.php">Xem tất cả ↗</a></div><div class="table-wrap"><table><thead><tr><th>Bài viết</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr></thead><tbody><?php if(!$recent): ?><tr><td colspan="4">Chưa có bài viết. <a href="stories.php?mode=edit">Viết bài đầu tiên ↗</a></td></tr><?php endif; ?><?php foreach($recent as $post): ?><tr><td><strong><?= e($post['title']) ?></strong></td><td><span class="admin-badge<?= !$post['published']?' is-muted':'' ?>"><?= $post['published']?'Đã xuất bản':'Bản nháp' ?></span></td><td><?= e(date('d/m/Y',strtotime($post['created_at']))) ?></td><td><a class="admin-text-link" href="stories.php?mode=edit&amp;edit=<?= (int)$post['id'] ?>">Sửa ↗</a></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php }
