<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/bootstrap.php';
$photos=require __DIR__.'/photo-pack.php'; $pdo=db(); $pdo->beginTransaction();
try {
    $exists=$pdo->prepare('SELECT id FROM gallery WHERE image=? LIMIT 1');
    $insert=$pdo->prepare('INSERT INTO gallery (title,image,category) VALUES (?,?,?)');
    foreach (array_reverse($photos) as $photo) {
        if (!is_file(__DIR__.'/../public/'.$photo['image'])) throw new RuntimeException('Missing photo: '.$photo['image']);
        $exists->execute([$photo['image']]);
        if (!$exists->fetch()) $insert->execute([$photo['title'],$photo['image'],$photo['category']]);
    }
    $pdo->exec("DELETE FROM gallery WHERE image='assets/village.jpg' AND title='Sắc màu quạt Chàng Sơn'");
    $update=$pdo->prepare("UPDATE posts SET image=? WHERE title=? AND image='assets/village.jpg'");
    $update->execute(['assets/photos/chang-son-fans.webp','Chàng Sơn — Giữ trọn ký ức trong từng nan quạt']);
    $update->execute(['assets/photos/making-fans.webp','Phất Quạt Họa Văn — Câu chuyện của dự án']);
    $pdo->commit(); echo "Photo gallery and sample article covers updated.\n";
} catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $ex; }
