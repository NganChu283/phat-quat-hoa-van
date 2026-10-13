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
<nav id="navigation" aria-label="Điều hướng chính"><a href="<?= $section('cau-chuyen') ?>">Câu chuyện</a><a href="<?= $section('lich-su-kien') ?>">Sự kiện</a><a class="button small nav-cta" href="<?= $section('dang-ky') ?>">Đăng ký tham gia <span aria-hidden="true">↗</span></a></nav></header>
<?php
}
function site_footer(bool $home = false): void {
    $contact = e($home ? '#lien-he' : site_path('index.php') . '#lien-he');
    ?>
<footer><a class="brand" href="<?= e(site_path('index.php')) ?>"><img class="footer-logo" src="<?= asset_url('assets/xoe-logo.webp') ?>" width="1536" height="1024" alt="Xòe — Xoè 2026" loading="lazy"></a><p>Giữ trọn ký ức trong từng nan quạt.</p>
<div class="footer-links" role="navigation" aria-label="Liên kết chân trang"><a href="<?= e(site_path('faq.php')) ?>">FAQ</a><a href="<?= e(site_path('partners.php')) ?>">Đơn vị đồng hành</a><a href="<?= $contact ?>">Liên hệ</a><a href="<?= e(site_path('admin/login.php')) ?>">Quản trị ↗</a><a href="#top">Về đầu trang ↑</a></div></footer>
<?php
}

// admin-nav.php
function admin_tabs(): array {
    $tabs=['dashboard'=>'Tổng quan','projects'=>'Sự kiện','stories'=>'Câu chuyện & bài viết','gallery'=>'Ảnh dự án','registrations'=>'Đăng ký','contacts'=>'Liên hệ'];
    if ((current_user()['role'] ?? '')==='admin') $tabs['users']='Tài khoản';
    return $tabs;
}
function admin_tab_url(string $tab): string {
    if ($tab==='projects') return 'events.php';
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
  </div>
  <div class="event-strip-clock">
    <div class="countdown" <?= $start ? 'data-target="'.e($start->format('c')).'" data-server-now="'.(time()*1000).'"' : '' ?> role="group" aria-label="Đếm ngược đến giờ bắt đầu dự án">
      <?php foreach (['Ngày','Giờ','Phút','Giây'] as $i=>$label): ?><div class="countdown-unit"><strong data-unit="<?= $i ?>"><?= $start ? str_pad((string)$clock[$i],2,'0',STR_PAD_LEFT) : '– –' ?></strong><span><?= e($label) ?></span></div><?php endforeach; ?>
    </div>
    <p class="countdown-status" role="status"><?= $start ? ($remaining > 0 ? 'Đếm ngược đến giờ bắt đầu.' : 'Dự án đã đến giờ bắt đầu.') : 'Lịch tham gia sẽ được cập nhật.' ?></p>
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
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php
}

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
