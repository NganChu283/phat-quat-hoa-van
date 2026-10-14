<?php
require __DIR__.'/../../app/bootstrap.php';
admin_required('admin');
$checks=[];
foreach(['pdo_mysql','mbstring','dom','fileinfo'] as $extension)$checks[]=['Extension '.$extension,extension_loaded($extension),'Bật extension PHP '.$extension.' trên hosting.'];
$required=['story_sections'=>['id','title','body','body_format','published','sort_order'],'story_settings'=>['id','eyebrow','title','title_accent','intro','image','image_alt','image_caption','published','sort_order'],'posts'=>['id','title','excerpt','body','body_format','image','published','show_on_home'],'projects'=>['id','title','subtitle','description','starts_at','ends_at','published','featured','venue','address','schedule','registration_url'],'gallery'=>['id','title','image','category'],'gallery_groups'=>['id','title'],'contacts'=>['id','name','email','message'],'users'=>['id','username','password_hash','auth_version','role','is_active']];
foreach($required as $table=>$columns){
 try{$actual=array_column(query_all('SHOW COLUMNS FROM '.$table),'Field');$missing=array_diff($columns,$actual);$checks[]=['Database '.$table,!$missing,$missing?'Thiếu cột: '.implode(', ',$missing).'. Cập nhật migration database.':''];}
 catch(PDOException $error){$checks[]=['Database '.$table,false,'Không thể đọc bảng. Kiểm tra migration và quyền MySQL.'];}
}
$upload=__DIR__.'/../assets/uploads';$checks[]=['Thư mục ảnh',is_dir($upload)?is_writable($upload):is_writable(dirname($upload)),'Cấp quyền ghi cho PHP vào assets/uploads.'];
admin_page_head('Kiểm tra hệ thống','dashboard'); ?>
<section class="admin-card"><h2>Kiểm tra cấu hình hosting</h2><p class="help">PHP <?= e(PHP_VERSION) ?> · Kiểm tra không thay đổi dữ liệu website. Nếu thao tác lưu báo mã lỗi, đối chiếu mã trong PHP error log hoặc file riêng .local/php-errors.log.</p><div class="table-wrap"><table><thead><tr><th>Kiểm tra</th><th>Kết quả</th><th>Cách xử lý</th></tr></thead><tbody><?php foreach($checks as [$name,$ok,$help]): ?><tr><td><?= e($name) ?></td><td><?= $ok?'Đạt':'Cần cập nhật' ?></td><td><?= $ok?'—':e($help) ?></td></tr><?php endforeach; ?></tbody></table></div><p class="help">Bổ sung cột định dạng nội dung mở rộng: import database/story-upgrade.sql trong phpMyAdmin sau khi chọn database hiện tại, hoặc chạy php app/migrate.php nếu hosting hỗ trợ CLI. Giới hạn POST của PHP: <?= e(ini_get('post_max_size')) ?>; ảnh tải lên: <?= e(ini_get('upload_max_filesize')) ?>.</p><a class="button" href="stories.php?mode=sections">Về nội dung mở rộng</a></section><?php admin_page_foot(); ?></body></html>
