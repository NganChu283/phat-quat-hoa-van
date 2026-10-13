<?php
require __DIR__.'/../../app/bootstrap.php';
admin_required();
$ready=gallery_groups_ready();$edit=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();$pdo=db();
    try{
        if(!$ready)throw new InvalidArgumentException('Cần import database/gallery-groups.sql để quản lý nhóm ảnh.');
        $id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT);
        if($id===false || $id<0)throw new InvalidArgumentException('Nhóm ảnh không hợp lệ.');
        $action=field('action',20);$pdo->beginTransaction();
        $existing=null;
        if($id){$q=$pdo->prepare('SELECT * FROM gallery_groups WHERE id=? FOR UPDATE');$q->execute([$id]);$existing=$q->fetch();if(!$existing)throw new InvalidArgumentException('Nhóm ảnh không còn tồn tại.');}
        if($action==='save'){
            $title=field('title',40);if($title==='')throw new InvalidArgumentException('Vui lòng nhập tên nhóm ảnh.');
            if($existing){
                $q=$pdo->prepare('UPDATE gallery_groups SET title=? WHERE id=?');$q->execute([$title,$id]);
                $q=$pdo->prepare('UPDATE gallery SET category=? WHERE category=?');$q->execute([$title,$existing['title']]);
            }else{$q=$pdo->prepare('INSERT INTO gallery_groups(title) VALUES(?)');$q->execute([$title]);}
            $pdo->commit();flash($id?'Đã đổi tên nhóm và cập nhật các ảnh trong nhóm.':'Đã thêm nhóm ảnh.');unset($_SESSION['gallery_group_draft']);
        }elseif($action==='delete'&&$existing){
            $q=$pdo->prepare('SELECT COUNT(*) FROM gallery WHERE category=?');$q->execute([$existing['title']]);$count=(int)$q->fetchColumn();
            if($count){
                $target=filter_var($_POST['destination_id']??0,FILTER_VALIDATE_INT);
                if(!$target||$target===$id)throw new InvalidArgumentException('Chọn một nhóm khác để chuyển ảnh trước khi xóa nhóm.');
                $q=$pdo->prepare('SELECT title FROM gallery_groups WHERE id=? FOR UPDATE');$q->execute([$target]);$destination=$q->fetchColumn();
                if(!$destination)throw new InvalidArgumentException('Nhóm nhận ảnh không còn tồn tại.');
                $q=$pdo->prepare('UPDATE gallery SET category=? WHERE category=?');$q->execute([$destination,$existing['title']]);
            }
            $q=$pdo->prepare('DELETE FROM gallery_groups WHERE id=?');$q->execute([$id]);$pdo->commit();
            flash($count?'Đã xóa nhóm và chuyển '.$count.' ảnh sang nhóm đã chọn.':'Đã xóa nhóm ảnh.');
        }else throw new InvalidArgumentException('Thao tác không hợp lệ.');
    }catch(Throwable $error){
        if($pdo->inTransaction())$pdo->rollBack();
        if($error instanceof InvalidArgumentException)flash($error->getMessage(),'error');
        elseif($error instanceof PDOException && (int)($error->errorInfo[1]??0)===1062)flash('Tên nhóm ảnh đã tồn tại. Hãy chọn tên khác.','error');
        else{error_log('Gallery groups: '.$error->getMessage());flash('Chưa thể lưu nhóm ảnh. Kiểm tra database.','error');}
        if(($_POST['action']??'')==='save')$_SESSION['gallery_group_draft']=['id'=>(int)($_POST['id']??0),'title'=>is_string($_POST['title']??null)?$_POST['title']:''];
    }
    redirect('gallery_groups.php');
}
$groups=gallery_groups();$id=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);
if($id)foreach($groups as $group)if((int)($group['id']??0)===$id)$edit=$group;
if(isset($_SESSION['gallery_group_draft'])){$edit=$_SESSION['gallery_group_draft'];unset($_SESSION['gallery_group_draft']);}
admin_page_head('Nhóm ảnh','gallery_groups');notice(); ?>
<?php if(!$ready): ?><p class="notice error">Import database/gallery-groups.sql bằng phpMyAdmin hoặc chạy php app/migrate-gallery-groups.php để mở quản lý nhóm ảnh.</p><?php endif; ?>
<div class="admin-group-grid">
<section class="admin-card"><h2><?= $edit?'Sửa nhóm ảnh':'Thêm nhóm ảnh' ?></h2><p class="help">Nhóm giúp sắp xếp ảnh và tạo bộ lọc trên trang chủ.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><label>Tên nhóm<input name="title" maxlength="40" required placeholder="Ví dụ: Workshop, Nghệ nhân…" value="<?= e($edit['title']??'') ?>"<?= !$ready?' disabled':'' ?>></label><div class="admin-actions"><button class="button" type="submit"<?= !$ready?' disabled':'' ?>><?= $edit?'Lưu thay đổi':'+ Thêm nhóm' ?></button><?php if($edit): ?><a class="text-link" href="gallery_groups.php">Hủy</a><?php endif; ?></div></form></section>
<section><div class="admin-card-heading"><div><h2>Danh sách nhóm</h2><p><?= count($groups) ?> nhóm · đổi tên hoặc chuyển ảnh khi xóa</p></div><a class="admin-text-link" href="index.php?tab=gallery">Quản lý ảnh ↗</a></div>
<div class="admin-group-list">
<?php foreach($groups as $group): ?><article class="admin-group-row"><div class="admin-group-row-heading"><div><h3><?= e($group['title']) ?></h3><p><?= (int)$group['image_count'] ?> ảnh</p></div><?php if($ready): ?><a class="admin-text-link" href="?edit=<?= (int)$group['id'] ?>">Sửa</a><?php endif; ?></div>
<?php if($ready): ?><form method="post" class="admin-group-delete"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$group['id'] ?>">
<?php if($group['image_count']): ?><select name="destination_id" required aria-label="<?= e('Chuyển ảnh từ '.$group['title'].' sang nhóm') ?>"><option value="">Chuyển ảnh sang nhóm…</option><?php foreach($groups as $destination)if($destination['id']!==$group['id']): ?><option value="<?= (int)$destination['id'] ?>"><?= e($destination['title']) ?></option><?php endif; ?></select><?php endif; ?>
<button type="submit" class="button small">Xóa nhóm</button></form><?php endif; ?></article><?php endforeach; ?>
</div></section></div>
<?php admin_page_foot(); ?><script>document.querySelectorAll('.admin-group-delete').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Xóa nhóm này? Các ảnh sẽ được chuyển sang nhóm bạn chọn.'))event.preventDefault();}));</script></body></html>
