<section class="admin-card"><h2><?= $edit?'Chỉnh sửa bài viết':'Thêm bài viết' ?></h2>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="entity" value="article"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<label>Tiêu đề<input name="title" maxlength="180" required value="<?= e($edit['title'] ?? '') ?>"></label>
<div class="admin-grid"><label>Đường dẫn ảnh<input name="image" maxlength="255" value="<?= e($edit['image'] ?? 'assets/photos/chang-son-fans.webp') ?>"></label><label>Tải ảnh thay thế (tối đa 3 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div>
<label>Mô tả ngắn<textarea name="excerpt" rows="3" maxlength="500" required><?= e($edit['excerpt'] ?? '') ?></textarea></label>
<?php require __DIR__.'/editor-view.php'; ?>
<label class="check"><input type="checkbox" name="published" value="1" <?= !empty($edit['published'])?'checked':'' ?>><span>Xuất bản trên website (bỏ chọn để lưu nháp)</span></label>
<label class="check"><input type="checkbox" name="show_on_home" value="1" <?= !$edit || !empty($edit['show_on_home'])?'checked':'' ?>><span>Hiển thị trong mục Câu chuyện trên trang chủ khi đã xuất bản</span></label>
<div class="admin-actions"><button class="button" type="submit">Lưu bài viết</button><a class="text-link" href="stories.php">Hủy / về danh sách</a></div>
</form></section>
