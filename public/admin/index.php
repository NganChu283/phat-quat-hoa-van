<?php
require __DIR__ . '/../../app/bootstrap.php';
admin_required();
$tabs=admin_tabs();
if (($_GET['tab']??'')==='registrations' && $_SERVER['REQUEST_METHOD']!=='POST') redirect('index.php?tab=contacts&source=legacy');
if (($_GET['tab'] ?? '')==='posts' && $_SERVER['REQUEST_METHOD']!=='POST') {
    $legacyEdit=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);
    $target='stories.php';
    if ($legacyEdit && $legacyEdit>0) $target.='?mode=edit&edit='.$legacyEdit;
    elseif (isset($_GET['story']) || isset($_GET['new']) || ($_GET['return_to'] ?? '')==='stories') $target.='?mode=edit';
    redirect($target);
}
$tab = is_string($_GET['tab'] ?? null) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : 'dashboard';
if ($tab === 'projects') redirect('events.php');
if ($tab === 'gallery_groups') redirect('gallery_groups.php');
if ($tab === 'users') redirect('users.php');
if ($tab === 'stories') redirect('stories.php');
$viewTable=$tab==='contacts' && ($_GET['source']??'')==='legacy' ? 'registrations' : $tab;
$contactCounts=['website'=>0,'legacy'=>0];
$storyContext=($_GET['return_to'] ?? '')==='stories';
$mailStats=[];
$edit = null; $rows = []; $stats = []; $ready = true; $total = 0;
$page = max(1, min(100000, (int) ($_GET['page'] ?? 1))); $offset = ($page - 1) * 25;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $entity = field('entity', 30); $action = field('action', 20);
        if (!in_array($entity, ['posts','gallery','registrations','contacts'], true)) throw new InvalidArgumentException('Yêu cầu không hợp lệ.');
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        if ($id === false || $id < 0) throw new InvalidArgumentException('Mã không hợp lệ.');
        if ($action === 'retry_mail' && $entity==='registrations' && $id>0) {
            $q=db()->prepare("UPDATE mail_queue SET attempts=0,last_error=NULL WHERE registration_id=? AND status='pending'"); $q->execute([$id]); flash('Đã đặt lại email chờ gửi. Tác vụ SMTP sẽ thử lại.');
        } elseif ($action === 'delete' && $id > 0) {
            if ($entity==='registrations') { $q=db()->prepare('DELETE FROM mail_queue WHERE registration_id=?'); $q->execute([$id]); }
            if ($entity==='posts') delete_article($id);
            else { $q = db()->prepare("DELETE FROM $entity WHERE id=?"); $q->execute([$id]); }
            flash('Đã xóa mục đã chọn.');
        } elseif ($action === 'save' && in_array($entity, ['posts','gallery'], true)) {
            $title = field('title', 180); if ($title === '') throw new InvalidArgumentException('Vui lòng nhập tiêu đề.');
            if ($entity === 'posts') {
                save_article($id);
            } else {
                if ($id) { $q=db()->prepare('SELECT id FROM gallery WHERE id=?'); $q->execute([$id]); if (!$q->fetch()) throw new InvalidArgumentException('Ảnh không còn tồn tại. Hãy tải lại danh sách ảnh.'); }
                $category = field('category', 40);
                if (!in_array($category,array_column(gallery_groups(),'title'),true)) throw new InvalidArgumentException('Nhóm ảnh không hợp lệ.');
                $image = upload_image(field('image', 255));
                $pdo=db();$pdo->beginTransaction();
                try {
                    if(gallery_groups_ready()){$q=$pdo->prepare('SELECT id FROM gallery_groups WHERE title=? FOR UPDATE');$q->execute([$category]);if(!$q->fetch())throw new InvalidArgumentException('Nhóm ảnh đã thay đổi. Hãy chọn lại nhóm.');}
                    if ($id) { $q=$pdo->prepare('UPDATE gallery SET title=?,image=?,category=? WHERE id=?');$q->execute([$title,$image,$category,$id]); }
                    else { $q=$pdo->prepare('INSERT INTO gallery (title,image,category) VALUES (?,?,?)');$q->execute([$title,$image,$category]); }
                    $pdo->commit();
                }catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
            }
            unset($_SESSION['admin_draft']); flash('Đã lưu nội dung.');
        } else throw new InvalidArgumentException('Yêu cầu không hợp lệ.');
    } catch (InvalidArgumentException $ex) {
        if (($_POST['action'] ?? '') === 'save') $_SESSION['admin_draft'] = array_filter($_POST, fn($v) => is_string($v));
        flash($ex->getMessage(), 'error');
    } catch (Throwable $ex) {
        error_log('Admin error: ' . $ex->getCode()); flash('Chưa thể lưu thay đổi. Kiểm tra cấu hình MySQL và quyền ghi thư mục ảnh.', 'error');
    }
    if (($_POST['entity'] ?? '')==='posts') {
        if (($_SESSION['flash']['type'] ?? '')==='error') {
            $_SESSION['story_draft']=array_replace($_SESSION['admin_draft'] ?? [],['entity'=>'article']);
            unset($_SESSION['admin_draft']); redirect('stories.php?mode=edit');
        }
        redirect('stories.php');
    }
    if (($_POST['entity']??'')==='registrations') redirect('index.php?tab=contacts&source=legacy');
    $returnTab = $_POST['entity'] ?? '';
    redirect('index.php?tab=' . (is_string($returnTab) && isset($tabs[$returnTab]) ? $returnTab : $tab));
}
try {
    foreach (array_diff(array_keys($tabs),['dashboard']) as $key) { $table=$key==='stories'?'posts':$key; $stats[$key]=$key==='gallery_groups'?count(gallery_groups()):(int)db()->query("SELECT COUNT(*) FROM $table")->fetchColumn(); }
    $contactCounts=['website'=>(int)$stats['contacts'],'legacy'=>(int)db()->query('SELECT COUNT(*) FROM registrations')->fetchColumn()];
    $stats['contacts']=array_sum($contactCounts);
    if ($tab !== 'dashboard') {
        $total = $tab==='contacts' ? $contactCounts[$viewTable==='registrations'?'legacy':'website'] : $stats[$tab];
        $rows = query_all("SELECT * FROM $viewTable ORDER BY id DESC LIMIT 25 OFFSET $offset");
        if ($viewTable==='registrations' && $rows) {
            $ids=implode(',',array_map(fn($r)=>(int)$r['id'],$rows));
            foreach (query_all("SELECT registration_id,status,attempts,last_error FROM mail_queue WHERE registration_id IN ($ids)") as $m) $mailStats[$m['registration_id']][]=$m;
        }
        $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
        if ($editId && in_array($tab, ['posts','gallery'], true)) { $q = db()->prepare("SELECT * FROM $tab WHERE id=?"); $q->execute([$editId]); $edit = $q->fetch() ?: null; }
        if (isset($_SESSION['admin_draft']) && ($_SESSION['admin_draft']['entity'] ?? '') === $tab) { $edit = $_SESSION['admin_draft']; $storyContext=$storyContext || (($edit['return_to'] ?? '')==='stories'); unset($_SESSION['admin_draft']); }
    }
} catch (PDOException $ex) { $ready = false; }
function delete_form(string $entity, int $id): void { ?>
<form method="post" class="delete-form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="<?= e($entity) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="button" type="submit">Xóa</button></form>
<?php }
?>
<?php admin_page_head($tabs[$tab]??'Tổng quan',$tab);notice(); ?>
<?php if (!$ready): ?><p class="notice error">Chưa kết nối được MySQL. Hãy nhập database/schema.sql và cấu hình app/config.local.php theo README.</p>
<?php elseif ($tab === 'dashboard'): render_admin_dashboard($stats); ?>
<?php else: ?>
<?php if($tab==='contacts'): ?><h2>Liên hệ</h2>
<nav class="admin-nav" aria-label="Nguồn liên hệ"><a href="index.php?tab=contacts"<?= $viewTable==='contacts'?' aria-current="page"':'' ?>>Lời nhắn website (<?= $contactCounts['website'] ?>)</a>
<?php if($contactCounts['legacy'] || $viewTable==='registrations'): ?><a href="index.php?tab=contacts&amp;source=legacy"<?= $viewTable==='registrations'?' aria-current="page"':'' ?>>Liên hệ trước đây (<?= $contactCounts['legacy'] ?>)</a><?php endif; ?></nav>
<p class="help">Đăng ký sự kiện được thực hiện qua Google Form của từng sự kiện. Mục này hiển thị lời nhắn gửi từ website.</p>
<form action="export.php" method="post" class="admin-actions"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="source" value="<?= $viewTable==='registrations'?'legacy':'website' ?>"><button class="button" type="submit">Xuất liên hệ CSV ↗</button></form>
<?php if($viewTable==='registrations'): ?><p class="help">Dữ liệu từ biểu mẫu cũ được giữ lại để tra cứu và liên hệ.</p><?php endif; ?>
<?php endif; ?>
<?php if (in_array($tab,['posts','gallery'],true)): ?><section class="admin-card" style="margin-bottom:30px"><h2><?= $edit?'Chỉnh sửa':'Thêm mới' ?> <?= $tab==='posts'?($storyContext?'câu chuyện':'bài viết'):'ảnh' ?></h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="<?= e($tab) ?>"><input type="hidden" name="return_to" value="<?= $storyContext?'stories':'' ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><label>Tiêu đề<input name="title" maxlength="180" required value="<?= e($edit['title']??'') ?>"></label><div class="admin-grid"><label>Ảnh hiện tại / đường dẫn có sẵn<input name="image" maxlength="255" value="<?= e($edit['image']??'assets/village.jpg') ?>"></label><label>Tải ảnh mới (JPG, PNG, WebP · tối đa 3 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div>
<?php if ($tab==='posts'): ?><label>Mô tả ngắn<textarea name="excerpt" rows="2" maxlength="500" required><?= e($edit['excerpt']??'') ?></textarea></label><?php render_editor($edit,$editorLabel ?? 'Nội dung bài viết'); ?><label class="check"><input type="checkbox" name="published" value="1" <?= !empty($edit['published'])?'checked':'' ?>><span>Xuất bản trên website (bỏ chọn để lưu nháp)</span></label><label class="check"><input type="checkbox" name="show_on_home" value="1" <?= !empty($edit['show_on_home']) || (!$edit && $storyContext)?'checked':'' ?>><span>Hiển thị trong mục Câu chuyện trên trang chủ (khi đã xuất bản)</span></label><?php else: ?><label>Nhóm ảnh<select name="category" required><?php foreach(gallery_groups() as $group): ?><option value="<?= e($group['title']) ?>"<?= ($edit['category']??'')===$group['title']?' selected':'' ?>><?= e($group['title']) ?></option><?php endforeach; ?></select></label><p class="help"><a class="text-link" href="gallery_groups.php">+ Thêm hoặc sửa nhóm ảnh ↗</a></p><?php endif; ?><div class="admin-actions"><button class="button" type="submit">Lưu <?= $tab==='posts'?($storyContext?'câu chuyện':'bài viết'):'ảnh' ?> ↗</button><a class="text-link" href="<?= $storyContext?'stories.php':'?tab='.e($tab) ?>">Hủy / tạo mới</a></div></form></section><?php endif; ?>
<div class="table-wrap"><table><thead><tr><th>#</th><?php if ($tab==='posts' || $tab==='gallery'): ?><th>Ảnh</th><th>Tiêu đề</th><th><?= $tab==='posts'?'Trạng thái':'Nhóm' ?></th><?php else: ?><th>Họ tên</th><th>Liên hệ</th><?php if ($viewTable==='registrations'): ?><th>Hoạt động</th><?php endif; ?><th>Lời nhắn</th><?php endif; ?><th>Ngày</th><th>Thao tác</th></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="8">Chưa có dữ liệu.</td></tr><?php endif; ?><?php foreach ($rows as $row): ?><tr><td><?= (int)$row['id'] ?></td><?php if (in_array($tab,['posts','gallery'],true)): ?><td><img class="admin-photo" src="../<?= e($row['image']) ?>" alt="<?= e($row['title']) ?>"></td><td><?= e($row['title']) ?></td><td><?= e($tab==='posts'?($row['published']?(!empty($row['show_on_home'])?'Xuất bản · Câu chuyện trang chủ':'Đã xuất bản'):'Bản nháp'):$row['category']) ?></td><?php else: ?><td><?= e($row['name']) ?></td><td><?= e($row['email']) ?><?php if ($viewTable==='registrations'): ?><br><?= e($row['phone']) ?><?php endif; ?></td><?php if ($viewTable==='registrations'): ?><td><?= e($row['activity']) ?><br><?php foreach ($mailStats[$row['id']] ?? [] as $m): ?><small><?= $m['status']==='sent'?'Đã gửi email':($m['last_error']?'Cần kiểm tra email: '.e($m['last_error']):'Email chờ gửi') ?></small><br><?php endforeach; ?></td><?php endif; ?><td class="message"><?= e($row['message']) ?></td><?php endif; ?><td><?= e(date('d/m/Y H:i',strtotime($row['created_at']))) ?></td><td><div class="admin-actions"><?php if (in_array($tab,['posts','gallery'],true)): ?><a class="text-link" href="?tab=<?= e($tab) ?>&edit=<?= (int)$row['id'] ?>">Sửa</a><?php endif; ?><?php if ($viewTable==='registrations' && array_filter($mailStats[$row['id']] ?? [],fn($m)=>$m['status']==='pending' && $m['last_error'])): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="registrations"><input type="hidden" name="action" value="retry_mail"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="button small">Thử lại email</button></form><?php endif; ?><?php delete_form($viewTable,(int)$row['id']); ?></div></td></tr><?php endforeach; ?></tbody></table></div><div class="pagination"><span>Trang <?= $page ?> · <?= $total ?> mục</span><div><?php if($page>1): ?><a href="?tab=<?= e($tab) ?><?= $viewTable==='registrations'?'&amp;source=legacy':'' ?>&amp;page=<?= $page-1 ?>">← Trước</a><?php endif; ?> <?php if($offset+25<$total): ?><a href="?tab=<?= e($tab) ?><?= $viewTable==='registrations'?'&amp;source=legacy':'' ?>&amp;page=<?= $page+1 ?>">Tiếp →</a><?php endif; ?></div></div>
<?php endif; ?><?php admin_page_foot(); ?><script>document.querySelectorAll('.delete-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Xóa mục này? Thao tác không thể hoàn tác.'))event.preventDefault();}));</script></body></html>
