<?php

// projects.php
function project_datetime(string $value): DateTimeImmutable {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Asia/Ho_Chi_Minh'));
    if (!$date || $date->format('Y-m-d\TH:i') !== $value || (int)$date->format('Y') < 2000 || (int)$date->format('Y') > 2100) {
        throw new InvalidArgumentException('Vui lòng nhập ngày giờ hợp lệ (năm 2000–2100), theo giờ Việt Nam.');
    }
    return $date;
}
function project_start(array $project): DateTimeImmutable {
    if (empty($project['starts_at'])) throw new InvalidArgumentException('Sự kiện chưa có giờ bắt đầu.');
    return new DateTimeImmutable($project['starts_at'], new DateTimeZone('Asia/Ho_Chi_Minh'));
}
function published_events(): array { return query_all('SELECT * FROM projects WHERE published=1 ORDER BY featured DESC, starts_at IS NULL, starts_at ASC,id DESC'); }
function featured_event(array $events): ?array {
    foreach ($events as $event) if (!empty($event['featured'])) return $event;
    return null;
}

// stories.php
function default_story_settings(): array {
    return ['id'=>1,'eyebrow'=>'01 / CÂU CHUYỆN CHÀNG SƠN','title'=>'Ký ức mở ra','title_accent'=>'từ một nan quạt.','intro'=>'Chiếc quạt không chỉ mang làn gió mát. Trong từng nếp gấp còn có câu chuyện về bàn tay, chất liệu và ký ức làng nghề.','image'=>'assets/carved-bamboo-fan.webp','image_alt'=>'Đôi tay cầm chiếc quạt với họa tiết chạm khắc trên nan tre','image_caption'=>'Chàng Sơn · Nét chạm trong từng nan quạt'];
}
function default_story_sections(): array {
    return [
        ['title'=>'Lịch sử hình thành','body'=>'Theo nội dung dự án Phất Quạt Họa Văn, làng nghề quạt Chàng Sơn có lịch sử hơn 200 năm, hình thành từ thế kỷ XIX. Nghề làm quạt được người dân giữ gìn và truyền dạy qua nhiều thế hệ.','body_format'=>'plain','published'=>1,'sort_order'=>10],
        ['title'=>'Phất Quạt Họa Văn','body'=>"Dự án được tổ chức bởi nhóm sinh viên năm 3 lớp Truyền thông Marketing K43A1, thuộc Khoa Quan hệ Công chúng & Quảng cáo — Học viện Báo chí và Tuyên truyền.\n\nDự án giới thiệu và lan tỏa giá trị của làng nghề quạt Chàng Sơn tới cộng đồng.",'body_format'=>'plain','published'=>1,'sort_order'=>20],
    ];
}

// rich-text.php
function sanitize_rich_text(string $html): string {
    if (trim($html)==='') return '';
    $dom=new DOMDocument('1.0','UTF-8'); $prior=libxml_use_internal_errors(true);
    try { $dom->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING); }
    finally { libxml_clear_errors(); libxml_use_internal_errors($prior); }
    $allowed=['p','br','strong','b','em','i','u','h2','h3','ul','ol','li','blockquote','a'];
    $blocked=['script','style','iframe','object','embed','svg','math','form','input','button','textarea','select','template'];
    $render=function(DOMNode $node) use (&$render,$allowed,$blocked): string {
        if ($node instanceof DOMText) return e($node->nodeValue);
        if (!($node instanceof DOMElement)) return '';
        $tag=strtolower($node->tagName); if (in_array($tag,$blocked,true)) return '';
        $inner=''; foreach ($node->childNodes as $child) $inner.=$render($child);
        if ($tag==='div') $tag='p';
        if (!in_array($tag,$allowed,true)) return $inner;
        if ($tag==='br') return '<br>';
        $attrs='';
        if ($tag==='a') {
            $href=trim($node->getAttribute('href'));
            if (!preg_match('~^(?:https?://|mailto:|/(?!/)|#)~i',$href) || preg_match('/[\x00-\x20\x7f]/',$href)) return $inner;
            $attrs=' href="'.e($href).'" rel="noopener noreferrer"';
        }
        return '<'.$tag.$attrs.'>'.$inner.'</'.$tag.'>';
    };
    $out=''; $body=$dom->getElementsByTagName('body')->item(0);
    if ($body) foreach ($body->childNodes as $child) $out.=$render($child);
    return trim($out);
}
function rich_text_has_content(string $html): bool {
    return trim(str_replace("\u{00a0}",' ',html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8')))!=='';
}
function post_body(array $post): string {
    if (($post['body_format'] ?? 'plain')==='html') return sanitize_rich_text($post['body']);
    return '<p>'.nl2br(e($post['body'])).'</p>';
}

// articles.php
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

function default_gallery(): array {
return [
    ['title'=>'Sắc màu quạt Chàng Sơn','image'=>'assets/chang-son-fans.webp','category'=>'Quạt Chàng Sơn'],
    ['title'=>'Cùng khám phá cách làm quạt','image'=>'assets/making-fans.webp','category'=>'Hoạt động'],
    ['title'=>'Nét thêu trên mặt quạt','image'=>'assets/embroidered-fans.webp','category'=>'Quạt Chàng Sơn'],
    ['title'=>'Nhịp sắc từ những nếp quạt','image'=>'assets/fan-patterns.webp','category'=>'Quạt Chàng Sơn'],
    ['title'=>'Một góc không gian trưng bày','image'=>'assets/fan-workshop.webp','category'=>'Quạt Chàng Sơn'],
    ['title'=>'Sắc màu trong từng nếp gấp','image'=>'assets/folded-fans.webp','category'=>'Quạt Chàng Sơn'],
    ['title'=>'Câu chuyện trên mặt quạt','image'=>'assets/painted-fan.webp','category'=>'Quạt Chàng Sơn'],
    ['title'=>'Bàn làm quạt và những đôi tay','image'=>'assets/craft-table.webp','category'=>'Hoạt động'],
    ['title'=>'Những chiếc quạt treo đầy sắc màu','image'=>'assets/hanging-fans.webp','category'=>'Quạt Chàng Sơn'],
    ['title'=>'Muôn dáng quạt, một câu chuyện','image'=>'assets/fan-collection.webp','category'=>'Quạt Chàng Sơn'],
];
}
