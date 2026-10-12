<?php
require __DIR__.'/../../app/bootstrap.php';
require __DIR__.'/../../app/admin-nav.php';
require __DIR__.'/../../app/upload.php';
require __DIR__.'/../../app/rich-text.php';
require __DIR__.'/../../app/articles.php';
require __DIR__.'/../../app/stories.php';
require __DIR__.'/../../app/layout.php';
admin_required();
$mode=in_array($_GET['mode'] ?? '',['articles','intro','sections','edit'],true)?$_GET['mode']:'articles'; $articles=[]; $articleTotal=0; $articlePage=max(1,min(100000,(int)($_GET['page'] ?? 1))); $articleOffset=($articlePage-1)*25; $edit=null; $ready=true; $rows=[]; $story=default_story_settings(); $introRows=[]; $introFormVisible=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf(); $entity=field('entity',20); $mode=$entity==='section'?'sections':($entity==='article'?((($_POST['action'] ?? '')==='save')?'edit':'articles'):'intro');
    try {
        if ($entity==='article') {
            $id=filter_var($_POST['id'] ?? 0,FILTER_VALIDATE_INT);
            if ($id===false || $id<0) throw new InvalidArgumentException('Mã bài viết không hợp lệ.');
            $action=field('action',20);
            if ($action==='delete') { delete_article($id); flash('Đã xóa bài viết khỏi website.'); }
            elseif ($action==='save') { save_article($id); flash('Đã lưu bài viết.'); }
            else throw new InvalidArgumentException('Thao tác không hợp lệ.');
            $mode='articles';
        } elseif ($entity==='intro') {
            $id=filter_var($_POST['id'] ?? 0,FILTER_VALIDATE_INT);
            if ($id===false || $id<0) throw new InvalidArgumentException('Mã phần giới thiệu không hợp lệ.');
            $action=field('action',20) ?: 'save';
            if ($action==='delete' && $id>0) {
                $q=db()->prepare('DELETE FROM story_settings WHERE id=?'); $q->execute([$id]);
                if (!$q->rowCount()) throw new InvalidArgumentException('Phần giới thiệu không còn tồn tại.');
                flash('Đã xóa phần giới thiệu khỏi trang chủ.');
            } elseif ($action==='save') {
                $title=field('title',180); $intro=field('intro',2000); $alt=field('image_alt',180);
                if ($title==='' || $intro==='' || $alt==='') throw new InvalidArgumentException('Nhập tiêu đề, giới thiệu và mô tả ảnh.');
                $order=filter_var($_POST['sort_order'] ?? 0,FILTER_VALIDATE_INT);
                if ($order===false || $order<0 || $order>9999) throw new InvalidArgumentException('Thứ tự phải từ 0 đến 9999.');
                if ($id) { $q=db()->prepare('SELECT id FROM story_settings WHERE id=?'); $q->execute([$id]); if (!$q->fetch()) throw new InvalidArgumentException('Phần giới thiệu không còn tồn tại.'); }
                $image=upload_image(field('image',255)); $published=isset($_POST['published'])?1:0;
                $values=[field('eyebrow',120),$title,field('title_accent',180),$intro,$image,$alt,field('image_caption',255),$published,$order];
                if ($id) { $values[]=$id; $q=db()->prepare('UPDATE story_settings SET eyebrow=?,title=?,title_accent=?,intro=?,image=?,image_alt=?,image_caption=?,published=?,sort_order=? WHERE id=?'); }
                else $q=db()->prepare('INSERT INTO story_settings (eyebrow,title,title_accent,intro,image,image_alt,image_caption,published,sort_order) VALUES (?,?,?,?,?,?,?,?,?)');
                $q->execute($values); flash($id?'Đã sửa phần giới thiệu.':'Đã thêm phần giới thiệu.');
            } else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        } elseif ($entity==='section') {
            $id=filter_var($_POST['id'] ?? 0,FILTER_VALIDATE_INT);
            if ($id===false || $id<0) throw new InvalidArgumentException('Mã nội dung không hợp lệ.');
            $action=field('action',20);
            if ($action==='delete' && $id>0) { $q=db()->prepare('DELETE FROM story_sections WHERE id=?'); $q->execute([$id]); flash('Đã xóa mục câu chuyện.'); }
            elseif ($action==='save') {
                $title=field('title',180); $body=field('body',60000); $format=field('body_format',10) ?: 'plain';
                if (!in_array($format,['plain','html'],true)) throw new InvalidArgumentException('Định dạng không hợp lệ.');
                if ($format==='html') $body=sanitize_rich_text($body);
                if ($title==='' || !rich_text_has_content($body)) throw new InvalidArgumentException('Nhập tiêu đề và nội dung câu chuyện.');
                $order=filter_var($_POST['sort_order'] ?? 0,FILTER_VALIDATE_INT);
                if ($order===false || $order<0 || $order>9999) throw new InvalidArgumentException('Thứ tự phải từ 0 đến 9999.');
                $published=isset($_POST['published'])?1:0;
                if ($id) {
                    $q=db()->prepare('SELECT id FROM story_sections WHERE id=?'); $q->execute([$id]);
                    if (!$q->fetch()) throw new InvalidArgumentException('Mục câu chuyện không còn tồn tại.');
                    $q=db()->prepare('UPDATE story_sections SET title=?,body=?,body_format=?,published=?,sort_order=? WHERE id=?'); $q->execute([$title,$body,$format,$published,$order,$id]);
                } else { $q=db()->prepare('INSERT INTO story_sections (title,body,body_format,published,sort_order) VALUES (?,?,?,?,?)'); $q->execute([$title,$body,$format,$published,$order]); }
                flash('Đã lưu nội dung câu chuyện.');
            } else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        } else throw new InvalidArgumentException('Yêu cầu không hợp lệ.');
        unset($_SESSION['story_draft']);
    } catch (Throwable $ex) {
        if ($ex instanceof InvalidArgumentException) flash($ex->getMessage(),'error');
        else { error_log('Story error: '.$ex->getCode()); flash('Chưa thể lưu câu chuyện. Kiểm tra database và quyền ghi ảnh.','error'); }
        if (($_POST['action'] ?? '')!=='delete') $_SESSION['story_draft']=array_filter($_POST,fn($v)=>is_string($v));
    }
    redirect('stories.php?mode='.$mode);
}
try {
    $introRows=query_all('SELECT * FROM story_settings ORDER BY sort_order,id');
    if ($mode==='intro') {
        $introEdit=filter_input(INPUT_GET,'intro_edit',FILTER_VALIDATE_INT);
        if ($introEdit && $introEdit>0) { $q=db()->prepare('SELECT * FROM story_settings WHERE id=?'); $q->execute([$introEdit]); $selected=$q->fetch(); if ($selected) { $story=$selected; $introFormVisible=true; } else flash('Phần giới thiệu không còn tồn tại.','error'); }
        elseif (isset($_GET['new'])) { $story=array_fill_keys(['eyebrow','title','title_accent','intro','image_alt','image_caption'],''); $story+=['id'=>0,'image'=>'assets/photos/carved-bamboo-fan.webp','published'=>1,'sort_order'=>0]; $introFormVisible=true; }
    }
    $rows=query_all('SELECT * FROM story_sections ORDER BY sort_order,id');
    if ($mode==='articles') {
        $articleTotal=(int)db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        $articles=query_all("SELECT id,title,image,published,show_on_home,created_at FROM posts ORDER BY created_at DESC,id DESC LIMIT 25 OFFSET $articleOffset");
    }
    $id=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);
    if ($id && $mode==='edit') { $q=db()->prepare('SELECT * FROM posts WHERE id=?'); $q->execute([$id]); $edit=$q->fetch() ?: null; if (!$edit) flash('Bài viết không còn tồn tại.','error'); }
    if ($id && $mode==='sections') { $q=db()->prepare('SELECT * FROM story_sections WHERE id=?'); $q->execute([$id]); $edit=$q->fetch() ?: null; }
} catch (PDOException $ex) { $ready=false; }
if (isset($_SESSION['story_draft'])) { $draft=$_SESSION['story_draft']; unset($_SESSION['story_draft']); if (($draft['entity'] ?? '')==='article' && $mode==='edit') $edit=$draft; elseif (($draft['entity'] ?? '')==='section' && $mode==='sections') $edit=$draft; elseif (($draft['entity'] ?? '')==='intro' && $mode==='intro') { $story=array_replace($story,$draft); $story['published']=isset($draft['published'])?1:0; $introFormVisible=true; } }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Câu chuyện & bài viết — Quản trị</title><link rel="stylesheet" href="../assets/style.css?v=unified-content-1"><link rel="stylesheet" href="../assets/admin.css?v=unified-content-1"><script src="../assets/editor.js?v=unified-content-1" defer></script></head><body><main class="admin-page">
<header class="admin-header"><a href="../index.php"><img class="admin-brand" src="../assets/xoe-logo.webp" alt="Xòe"></a><h1>Quản lý <em>câu chuyện & bài viết.</em></h1><a class="text-link" href="../index.php#cau-chuyen">Xem trên trang chủ ↗</a><form action="logout.php" method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="button small">Đăng xuất</button></form></header>
<nav class="admin-nav" aria-label="Quản trị"><?php foreach(admin_tabs() as $key=>$label): ?><a href="<?= e(admin_tab_url($key)) ?>" <?= $key==='stories'?'aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach; ?></nav><?php notice(); ?>
<nav class="story-admin-nav" aria-label="Quản lý nội dung"><a href="stories.php" <?= in_array($mode,['articles','edit'],true)?'aria-current="page"':'' ?>>Danh sách bài viết</a><a href="stories.php?mode=intro" <?= $mode==='intro'?'aria-current="page"':'' ?>>Giới thiệu trên trang chủ</a><a href="stories.php?mode=sections" <?= $mode==='sections'?'aria-current="page"':'' ?>>Nội dung mở rộng</a></nav>
<?php if (!$ready): ?><p class="notice error">Chạy php app/migrate.php để thêm bảng quản lý câu chuyện.</p>
<?php elseif ($mode==='articles'): ?>
<section class="admin-card"><div class="story-crud-heading"><div><h2>Danh sách bài viết</h2><p class="help">Quản lý tất cả bài viết tại đây; chọn bài hiển thị trong mục Câu chuyện ngay trong bộ soạn thảo.</p></div><a class="button" href="stories.php?mode=edit">+ Thêm bài viết</a></div>
<div class="table-wrap"><table><thead><tr><th>Ảnh</th><th>Bài viết</th><th>Trạng thái</th><th>Vị trí hiển thị</th><th>Thao tác</th></tr></thead><tbody><?php if (!$articles): ?><tr><td colspan="5">Chưa có bài viết. Chọn “Thêm bài viết” để tạo nội dung mới.</td></tr><?php endif; ?><?php foreach ($articles as $article): ?><tr><td><img class="admin-photo" src="../<?= e($article['image']) ?>" alt="<?= e($article['title']) ?>"></td><td><strong><?= e($article['title']) ?></strong><br><?= e(date('d/m/Y',strtotime($article['created_at']))) ?></td><td><?= $article['published']?'Đã xuất bản':'Bản nháp' ?></td><td><?= $article['show_on_home']?'Câu chuyện trang chủ':'Trang bài viết' ?></td><td><div class="admin-actions story-crud-actions"><a class="button small" href="stories.php?mode=edit&amp;edit=<?= (int)$article['id'] ?>">Sửa</a><?php if ($article['published']): ?><a class="text-link" href="../post.php?id=<?= (int)$article['id'] ?>">Xem</a><?php endif; ?><form method="post" class="story-delete"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="article"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$article['id'] ?>"><button class="button small story-delete-button">Xóa</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
<nav class="pagination" aria-label="Phân trang bài viết"><span>Trang <?= $articlePage ?> · <?= $articleTotal ?> bài</span><div><?php if ($articlePage>1): ?><a href="stories.php?page=<?= $articlePage-1 ?>">← Trước</a><?php endif; ?> <?php if ($articlePage*25<$articleTotal): ?><a href="stories.php?page=<?= $articlePage+1 ?>">Tiếp →</a><?php endif; ?></div></nav>
<?php elseif ($mode==='edit'): require __DIR__.'/../../app/article-form.php'; ?>
<?php elseif ($mode==='intro'): require __DIR__.'/../../app/story-intro-list.php'; ?>
<?php else: ?><section class="admin-card"><h2><?= $edit?'Chỉnh sửa mục câu chuyện':'Thêm mục câu chuyện' ?></h2><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="section"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><label>Tiêu đề<input name="title" maxlength="180" required value="<?= e($edit['title'] ?? '') ?>"></label><?php $editorLabel='Nội dung câu chuyện'; require __DIR__.'/../../app/editor-view.php'; ?><label>Thứ tự hiển thị (số nhỏ đứng trước)<input type="number" name="sort_order" min="0" max="9999" value="<?= (int)($edit['sort_order'] ?? 0) ?>" required></label><label class="check"><input type="checkbox" name="published" value="1" <?= !$edit || !empty($edit['published'])?'checked':'' ?>><span>Hiển thị trên trang chủ</span></label><div class="admin-actions"><button class="button">Lưu nội dung</button><a href="stories.php?mode=sections" class="text-link">Hủy / thêm mới</a></div></form></section><div class="table-wrap"><table><thead><tr><th>Tiêu đề</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="4">Chưa có mục câu chuyện.</td></tr><?php endif; ?><?php foreach ($rows as $row): ?><tr><td><?= e($row['title']) ?></td><td><?= (int)$row['sort_order'] ?></td><td><?= $row['published']?'Đang hiển thị':'Đã ẩn' ?></td><td><div class="admin-actions"><a class="text-link" href="?mode=sections&amp;edit=<?= (int)$row['id'] ?>">Sửa</a><form method="post" class="story-delete"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="section"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="button small">Xóa</button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</main><script>document.querySelectorAll('.story-delete').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Xóa nội dung này? Bài đã xóa sẽ không còn trên website.'))event.preventDefault();}));</script></body></html>
