<?php
require __DIR__ . '/../../app/bootstrap.php';
admin_required();
$tabs=admin_tabs();
if (($_GET['tab'] ?? '')==='posts' && $_SERVER['REQUEST_METHOD']!=='POST') {
    $legacyEdit=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);
    $target='stories.php';
    if ($legacyEdit && $legacyEdit>0) $target.='?mode=edit&edit='.$legacyEdit;
    elseif (isset($_GET['story']) || isset($_GET['new']) || ($_GET['return_to'] ?? '')==='stories') $target.='?mode=edit';
    redirect($target);
}
$tab = is_string($_GET['tab'] ?? null) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : 'dashboard';
if ($tab === 'projects') redirect('events.php');
if ($tab === 'users') redirect('users.php');
if ($tab === 'stories') redirect('stories.php');
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
                $category = field('category', 40);
                if (!in_array($category,['Quạt Chàng Sơn','Hoạt động'],true)) throw new InvalidArgumentException('Nhóm ảnh không hợp lệ.');
                $image = upload_image(field('image', 255));
                if ($id) { $q = db()->prepare('UPDATE gallery SET title=?,image=?,category=? WHERE id=?'); $q->execute([$title,$image,$category,$id]); }
                else { $q = db()->prepare('INSERT INTO gallery (title,image,category) VALUES (?,?,?)'); $q->execute([$title,$image,$category]); }
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
    $returnTab = $_POST['entity'] ?? '';
    redirect('index.php?tab=' . (is_string($returnTab) && isset($tabs[$returnTab]) ? $returnTab : $tab));
}
try {
    foreach (array_diff(array_keys($tabs),['dashboard']) as $key) { $table=$key==='stories'?'posts':$key; $stats[$key]=(int)db()->query("SELECT COUNT(*) FROM $table")->fetchColumn(); }
    if ($tab !== 'dashboard') {
        $total = $stats[$tab];
        $rows = query_all("SELECT * FROM $tab ORDER BY id DESC LIMIT 25 OFFSET $offset");
        if ($tab==='registrations' && $rows) {
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
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Quản trị — Xoè 2026</title><link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>"></head><body><main class="admin-page"><header class="admin-header"><a href="../index.php"><img class="admin-brand" src="../assets/xoe-logo.webp" alt="Xòe"></a><h1>Quản trị <em>dự án.</em></h1><a class="text-link" href="../index.php">Xem website ↗</a><form action="logout.php" method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="button small">Đăng xuất</button></form></header><nav class="admin-nav" style="display:flex" aria-label="Quản trị"><?php foreach ($tabs as $key=>$label): ?><a href="<?= e(admin_tab_url($key)) ?>" <?= $tab===$key?'aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach; ?></nav><?php notice(); ?>
<?php if (!$ready): ?><p class="notice error">Chưa kết nối được MySQL. Hãy nhập database/schema.sql và cấu hình app/config.local.php theo README.</p>
<?php elseif ($tab === 'dashboard'): ?><div class="stats"><?php foreach ($stats as $key=>$count): ?><article><a href="<?= e(admin_tab_url($key)) ?>"><strong><?= $count ?></strong><span><?= e($tabs[$key]) ?> ↗</span></a></article><?php endforeach; ?></div><p class="help" style="margin-top:30px">Quản lý lịch ở mục Sự kiện; quản lý giới thiệu và bài viết ở Câu chuyện & bài viết; tải ảnh ở Ảnh dự án. Biểu mẫu trên website lưu đăng ký và lời nhắn để bạn xem tại đây.</p>
<?php else: ?>
<?php if ($tab==='registrations'): ?><form action="export.php" method="post" class="admin-actions"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="button">Xuất toàn bộ đăng ký CSV ↗</button></form><p class="help">Email: chờ gửi / đã gửi / cần kiểm tra. Chạy tác vụ gửi thư để xử lý hàng đợi.</p><?php endif; ?>
<?php if (in_array($tab,['posts','gallery'],true)): ?><section class="admin-card" style="margin-bottom:30px"><h2><?= $edit?'Chỉnh sửa':'Thêm mới' ?> <?= $tab==='posts'?($storyContext?'câu chuyện':'bài viết'):'ảnh' ?></h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="<?= e($tab) ?>"><input type="hidden" name="return_to" value="<?= $storyContext?'stories':'' ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><label>Tiêu đề<input name="title" maxlength="180" required value="<?= e($edit['title']??'') ?>"></label><div class="admin-grid"><label>Ảnh hiện tại / đường dẫn có sẵn<input name="image" maxlength="255" value="<?= e($edit['image']??'assets/village.jpg') ?>"></label><label>Tải ảnh mới (JPG, PNG, WebP · tối đa 3 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div>
<?php if ($tab==='posts'): ?><label>Mô tả ngắn<textarea name="excerpt" rows="2" maxlength="500" required><?= e($edit['excerpt']??'') ?></textarea></label><?php render_editor($edit,$editorLabel ?? 'Nội dung bài viết'); ?><label class="check"><input type="checkbox" name="published" value="1" <?= !empty($edit['published'])?'checked':'' ?>><span>Xuất bản trên website (bỏ chọn để lưu nháp)</span></label><label class="check"><input type="checkbox" name="show_on_home" value="1" <?= !empty($edit['show_on_home']) || (!$edit && $storyContext)?'checked':'' ?>><span>Hiển thị trong mục Câu chuyện trên trang chủ (khi đã xuất bản)</span></label><?php else: ?><label>Nhóm ảnh<select name="category"><option <?= ($edit['category']??'')==='Quạt Chàng Sơn'?'selected':'' ?>>Quạt Chàng Sơn</option><option <?= ($edit['category']??'')==='Hoạt động'?'selected':'' ?>>Hoạt động</option></select></label><?php endif; ?><div class="admin-actions"><button class="button" type="submit">Lưu <?= $tab==='posts'?($storyContext?'câu chuyện':'bài viết'):'ảnh' ?> ↗</button><a class="text-link" href="<?= $storyContext?'stories.php':'?tab='.e($tab) ?>">Hủy / tạo mới</a></div></form></section><?php endif; ?>
<div class="table-wrap"><table><thead><tr><th>#</th><?php if ($tab==='posts' || $tab==='gallery'): ?><th>Ảnh</th><th>Tiêu đề</th><th><?= $tab==='posts'?'Trạng thái':'Nhóm' ?></th><?php else: ?><th>Họ tên</th><th>Liên hệ</th><?php if ($tab==='registrations'): ?><th>Hoạt động</th><?php endif; ?><th>Lời nhắn</th><?php endif; ?><th>Ngày</th><th>Thao tác</th></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="8">Chưa có dữ liệu.</td></tr><?php endif; ?><?php foreach ($rows as $row): ?><tr><td><?= (int)$row['id'] ?></td><?php if (in_array($tab,['posts','gallery'],true)): ?><td><img class="admin-photo" src="../<?= e($row['image']) ?>" alt="<?= e($row['title']) ?>"></td><td><?= e($row['title']) ?></td><td><?= e($tab==='posts'?($row['published']?(!empty($row['show_on_home'])?'Xuất bản · Câu chuyện trang chủ':'Đã xuất bản'):'Bản nháp'):$row['category']) ?></td><?php else: ?><td><?= e($row['name']) ?></td><td><?= e($row['email']) ?><?php if ($tab==='registrations'): ?><br><?= e($row['phone']) ?><?php endif; ?></td><?php if ($tab==='registrations'): ?><td><?= e($row['activity']) ?><br><?php foreach ($mailStats[$row['id']] ?? [] as $m): ?><small><?= $m['status']==='sent'?'Đã gửi email':($m['last_error']?'Cần kiểm tra email: '.e($m['last_error']):'Email chờ gửi') ?></small><br><?php endforeach; ?></td><?php endif; ?><td class="message"><?= e($row['message']) ?></td><?php endif; ?><td><?= e(date('d/m/Y H:i',strtotime($row['created_at']))) ?></td><td><div class="admin-actions"><?php if (in_array($tab,['posts','gallery'],true)): ?><a class="text-link" href="?tab=<?= e($tab) ?>&edit=<?= (int)$row['id'] ?>">Sửa</a><?php endif; ?><?php if ($tab==='registrations' && array_filter($mailStats[$row['id']] ?? [],fn($m)=>$m['status']==='pending' && $m['last_error'])): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="registrations"><input type="hidden" name="action" value="retry_mail"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="button small">Thử lại email</button></form><?php endif; ?><?php delete_form($tab,(int)$row['id']); ?></div></td></tr><?php endforeach; ?></tbody></table></div><div class="pagination"><span>Trang <?= $page ?> · <?= $total ?> mục</span><div><?php if($page>1): ?><a href="?tab=<?= e($tab) ?>&page=<?= $page-1 ?>">← Trước</a><?php endif; ?> <?php if($offset+25<$total): ?><a href="?tab=<?= e($tab) ?>&page=<?= $page+1 ?>">Tiếp →</a><?php endif; ?></div></div>
<?php endif; ?></main><script src="<?= asset_url('assets/app.js') ?>" defer></script><script>document.querySelectorAll('.delete-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Xóa mục này? Thao tác không thể hoàn tác.'))event.preventDefault();}));</script></body></html>
