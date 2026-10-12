<?php
require __DIR__ . '/../../app/bootstrap.php';
admin_required();
$edit = null; $rows = []; $ready = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        if ($id === false || $id < 0) throw new InvalidArgumentException('Mã sự kiện không hợp lệ.');
        $action = field('action',20);
        if ($action === 'delete' && $id > 0) {
            $q=db()->prepare('DELETE FROM projects WHERE id=?'); $q->execute([$id]);
            flash('Đã xóa sự kiện.');
        } elseif ($action === 'save') {
            $title=field('title',180); $subtitle=field('subtitle',180);
            if ($title==='') throw new InvalidArgumentException('Vui lòng nhập tên sự kiện.');
            $startValue=field('starts_at',30); $start=$startValue!==''?project_datetime($startValue):null; $published=isset($_POST['published'])?1:0;
            $description=field('description',500);
            if (isset($_POST['featured']) && !$published) throw new InvalidArgumentException('Sự kiện nổi bật phải được xuất bản.'); $featured=isset($_POST['featured']) ? 1 : null;
            $endValue=field('ends_at',30); $end=$endValue!==''?project_datetime($endValue):null;
            if ($end && (!$start || $end<=$start)) throw new InvalidArgumentException('Giờ kết thúc phải sau giờ bắt đầu.');
            $venue=field('venue',180); $address=field('address',500); $schedule=field('schedule',10000);
            $pdo=db(); $pdo->beginTransaction();
            if ($id) { $q=$pdo->prepare('SELECT id FROM projects WHERE id=? FOR UPDATE'); $q->execute([$id]); if (!$q->fetch()) throw new InvalidArgumentException('Sự kiện không còn tồn tại.'); }
            if ($featured) $pdo->exec('UPDATE projects SET featured=NULL WHERE featured=1');
            if ($id) { $q=$pdo->prepare('UPDATE projects SET title=?,subtitle=?,starts_at=?,featured=? WHERE id=?'); $q->execute([$title,$subtitle,$start?$start->format('Y-m-d H:i:s'):null,$featured,$id]); }
            else { $q=$pdo->prepare('INSERT INTO projects (title,subtitle,starts_at,featured) VALUES (?,?,?,?)'); $q->execute([$title,$subtitle,$start?$start->format('Y-m-d H:i:s'):null,$featured]); }
            $savedId=$id ?: (int)$pdo->lastInsertId();
            $q=$pdo->prepare('UPDATE projects SET ends_at=?,venue=?,address=?,schedule=?,description=?,published=? WHERE id=?');
            $q->execute([$end?$end->format('Y-m-d H:i:s'):null,$venue,$address,$schedule,$description,$published,$savedId]);
            $pdo->commit(); unset($_SESSION['project_draft']);
            flash($featured ? 'Đã lưu sự kiện nổi bật và cập nhật trang chủ.' : ($published ? 'Đã lưu và xuất bản sự kiện trên trang chủ.' : 'Đã lưu bản nháp sự kiện.'));
        } else throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $ex) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        if ($ex instanceof InvalidArgumentException) flash($ex->getMessage(),'error');
        else { error_log('Projects error: '.$ex->getCode()); flash('Không thể lưu sự kiện. Vui lòng kiểm tra kết nối MySQL.','error'); }
        if (($_POST['action'] ?? '')==='save') $_SESSION['project_draft']=array_filter($_POST,fn($v)=>is_string($v));
    }
    redirect('events.php');
}
try {
    $rows=query_all('SELECT * FROM projects ORDER BY starts_at DESC,id DESC LIMIT 200');
    $id=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);
    if ($id) { $q=db()->prepare('SELECT * FROM projects WHERE id=?'); $q->execute([$id]); $edit=$q->fetch() ?: null; }
} catch (PDOException $ex) { $ready=false; }
if (isset($_SESSION['project_draft'])) { $edit=$_SESSION['project_draft']; unset($_SESSION['project_draft']); }
$inputDate = $edit['starts_at'] ?? '';
if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $inputDate)) $inputDate = project_start($edit)->format('Y-m-d\TH:i');
$tabs=admin_tabs();
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Sự kiện & đồng hồ — Phất Quạt Họa Văn</title><link rel="stylesheet" href="../assets/style.css?v=compact-1"></head><body><main class="admin-page"><header class="admin-header"><a href="../index.php"><img class="admin-brand" src="../assets/xoe-logo.webp" alt="Xòe"></a><h1>Quản lý <em>sự kiện.</em></h1><a class="text-link" href="../index.php">Xem website ↗</a><form action="logout.php" method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="button small">Đăng xuất</button></form></header><nav class="admin-nav" aria-label="Quản trị"><?php foreach($tabs as $key=>$label): ?><a href="<?= e(admin_tab_url($key)) ?>" <?= $key==='projects'?'aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach; ?></nav><?php notice(); ?>
<?php if(!$ready): ?><p class="notice error">Chưa có bảng sự kiện. Chạy php app/migrate.php để cập nhật database.</p><?php else: ?>
<section class="admin-card"><h2><?= $edit?'Chỉnh sửa sự kiện':'Thêm sự kiện' ?></h2><p class="help">Đặt ngày giờ theo giờ Việt Nam (UTC+7). Chọn “Sự kiện nổi bật” để dùng tên và đồng hồ của sự kiện này ở đầu trang chủ. Mọi sự kiện được xuất bản đều hiển thị trong mục Dự án. Chọn một sự kiện nổi bật để dùng tiêu đề và bộ đếm ở đầu trang.</p><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>"><div class="admin-grid"><label>Tên sự kiện / tiêu đề trang chủ<input name="title" maxlength="180" required placeholder="Chạm Ký Hồn Quê" value="<?= e($edit['title']??'') ?>"></label><label>Phụ đề<input name="subtitle" maxlength="180" placeholder="Triển lãm tháng 4" value="<?= e($edit['subtitle']??'') ?>"></label></div><label>Mô tả ngắn<textarea name="description" rows="3" maxlength="500"><?= e($edit['description']??'') ?></textarea></label><label>Ngày giờ bắt đầu · giờ Việt Nam<input type="datetime-local" name="starts_at" min="2000-01-01T00:00" max="2100-12-31T23:59" value="<?= e($inputDate) ?>"></label><label>Giờ kết thúc · giờ Việt Nam<input type="datetime-local" name="ends_at" value="<?= e(!empty($edit['ends_at']) ? str_replace(' ','T',substr($edit['ends_at'],0,16)) : '') ?>"></label><label>Tên địa điểm<input name="venue" maxlength="180" value="<?= e($edit['venue']??'') ?>"></label><label>Địa chỉ đầy đủ<input name="address" maxlength="500" value="<?= e($edit['address']??'') ?>"></label><label>Lịch hoạt động (mỗi dòng một mốc giờ)<textarea name="schedule" rows="5" maxlength="10000"><?= e($edit['schedule']??'') ?></textarea></label><label class="check"><input type="checkbox" name="published" value="1" <?= !$edit || !empty($edit['published'])?'checked':'' ?>><span>Xuất bản trên trang chủ</span></label><label class="check"><input type="checkbox" name="featured" value="1" <?= !$edit || !empty($edit['featured'])?'checked':'' ?>><span>Sự kiện nổi bật: dùng tiêu đề và bộ đếm trên trang chủ</span></label><div class="admin-actions"><button class="button" type="submit">Lưu sự kiện ↗</button><a href="events.php" class="text-link">Hủy / thêm mới</a></div></form></section>
<div class="table-wrap" style="margin-top:30px"><table><thead><tr><th>Sự kiện</th><th>Bắt đầu (giờ Việt Nam)</th><th>Hiển thị</th><th>Thao tác</th></tr></thead><tbody><?php if(!$rows): ?><tr><td colspan="4">Chưa có sự kiện. Thêm sự kiện để thiết lập đồng hồ.</td></tr><?php endif; ?><?php foreach($rows as $row): ?><tr><td><strong><?= e($row['title']) ?></strong><br><?= e($row['subtitle']) ?></td><td><?= !empty($row['starts_at'])?e(project_start($row)->format('H:i · d/m/Y')):'Chưa có lịch' ?><br><?= !empty($row['starts_at'])?(project_start($row)->getTimestamp()<=time()?'Đã đến giờ bắt đầu':'Sắp diễn ra'):'' ?></td><td><?= !$row['published']?'Bản nháp':($row['featured']?'Nổi bật trên trang chủ':'Đã xuất bản') ?></td><td><div class="admin-actions"><a class="text-link" href="?edit=<?= (int)$row['id'] ?>">Sửa</a><form method="post" class="delete-form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="button small" type="submit">Xóa</button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></main><script>document.querySelectorAll('.delete-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Xóa sự kiện này?'))event.preventDefault();}));</script></body></html>
