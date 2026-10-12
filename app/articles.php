<?php
function save_article(int $id): int {
    $title=field('title',180); $excerpt=field('excerpt',500); $body=field('body',60000); $format=field('body_format',10) ?: 'plain';
    if (!in_array($format,['plain','html'],true)) throw new InvalidArgumentException('Định dạng bài viết không hợp lệ.');
    if ($format==='html') $body=sanitize_rich_text($body);
    if ($title==='' || $excerpt==='' || !rich_text_has_content($body)) throw new InvalidArgumentException('Nhập tiêu đề, mô tả và nội dung bài viết.');
    $pdo=db();
    if ($id) { $q=$pdo->prepare('SELECT id FROM posts WHERE id=?'); $q->execute([$id]); if (!$q->fetch()) throw new InvalidArgumentException('Bài viết không còn tồn tại.'); }
    $image=upload_image(field('image',255)); $published=isset($_POST['published'])?1:0; $home=isset($_POST['show_on_home'])?1:0;
    if ($id) { $q=$pdo->prepare('UPDATE posts SET title=?,excerpt=?,body=?,body_format=?,image=?,published=?,show_on_home=? WHERE id=?'); $q->execute([$title,$excerpt,$body,$format,$image,$published,$home,$id]); }
    else { $q=$pdo->prepare('INSERT INTO posts (title,excerpt,body,body_format,image,published,show_on_home) VALUES (?,?,?,?,?,?,?)'); $q->execute([$title,$excerpt,$body,$format,$image,$published,$home]); $id=(int)$pdo->lastInsertId(); }
    return $id;
}
function delete_article(int $id): void {
    if ($id<=0) throw new InvalidArgumentException('Mã bài viết không hợp lệ.');
    $q=db()->prepare('DELETE FROM posts WHERE id=?'); $q->execute([$id]);
    if (!$q->rowCount()) throw new InvalidArgumentException('Bài viết không còn tồn tại.');
}
