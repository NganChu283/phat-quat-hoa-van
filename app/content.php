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

// Event registration links.
function valid_google_form_url(string $url): bool {
    if(strlen($url)>2048 || !filter_var($url,FILTER_VALIDATE_URL))return false;
    $parts=parse_url($url);
    if(($parts['scheme']??'')!=='https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']))return false;
    $host=strtolower($parts['host']??'');$path=$parts['path']??'';
    if($host==='forms.gle')return (bool)preg_match('~^/[a-zA-Z0-9_-]+/?$~',$path);
    return $host==='docs.google.com' && (bool)preg_match('~^/forms/(?:u/\d+/)?d/(?:e/)?[a-zA-Z0-9_-]+/viewform/?$~',$path);
}
function event_registration_closed(array $event): bool {
    if(empty($event['ends_at']))return false;
    try{return (new DateTimeImmutable($event['ends_at']))->getTimestamp()<=time();}
    catch(Exception $error){return false;}
}
function event_registration_url(array $event): string {
    $url=trim((string)($event['registration_url']??''));
    return !event_registration_closed($event) && valid_google_form_url($url) ? $url : '';
}

// stories.php
function default_story_settings(): array {
    return ['id'=>1,'eyebrow'=>'01 / CÂU CHUYỆN CHÀNG SƠN','title'=>'Ký ức mở ra','title_accent'=>'từ một nan quạt.','intro'=>'Chiếc quạt không chỉ mang làn gió mát. Trong từng nếp gấp còn có câu chuyện về bàn tay, chất liệu và ký ức làng nghề.','image'=>'assets/carved-bamboo-fan.webp','image_alt'=>'Đôi tay cầm chiếc quạt với họa tiết chạm khắc trên nan tre','image_caption'=>'Chàng Sơn · Nét chạm trong từng nan quạt'];
}
function default_story_sections(): array {
    return [
        ['title'=>'Lịch sử hình thành','body'=>'Theo nội dung dự án Xoè 2026, làng nghề quạt Chàng Sơn có lịch sử hơn 200 năm, hình thành từ thế kỷ XIX. Nghề làm quạt được người dân giữ gìn và truyền dạy qua nhiều thế hệ.','body_format'=>'plain','published'=>1,'sort_order'=>10],
        ['title'=>'Xoè 2026','body'=>"Dự án được tổ chức bởi nhóm sinh viên năm 3 lớp Truyền thông Marketing K43A1, thuộc Khoa Quan hệ Công chúng & Quảng cáo — Học viện Báo chí và Tuyên truyền.\n\nDự án giới thiệu và lan tỏa giá trị của làng nghề quạt Chàng Sơn tới cộng đồng.",'body_format'=>'plain','published'=>1,'sort_order'=>20],
    ];
}

// rich-text.php
function rich_text_styles(DOMElement $node): string {
    $styles=[];
    $fonts=['arial'=>'Arial','georgia'=>'Georgia','times new roman'=>'Times New Roman','verdana'=>'Verdana'];
    $colors=function(string $value): ?string {
        $value=strtolower(trim($value));
        if(preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/',$value))return $value;
        if(preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/',$value,$rgb) && max((int)$rgb[1],(int)$rgb[2],(int)$rgb[3])<=255)return 'rgb('.(int)$rgb[1].', '.(int)$rgb[2].', '.(int)$rgb[3].')';
        return null;
    };
    if(strtolower($node->tagName)==='font'){
        $size=[1=>12,2=>14,3=>16,4=>18,5=>24,6=>32,7=>48][(int)$node->getAttribute('size')]??null;
        if($size)$styles['font-size']=$size.'px';
        if($color=$colors($node->getAttribute('color')))$styles['color']=$color;
        $face=strtolower(trim($node->getAttribute('face')," \t\n\r\0\x0B\"'"));
        if(isset($fonts[$face]))$styles['font-family']=$fonts[$face];
    }
    foreach(explode(';',$node->getAttribute('style')) as $declaration){
        if(!str_contains($declaration,':'))continue;
        [$property,$value]=array_map('trim',explode(':',$declaration,2));
        $property=strtolower($property); $value=strtolower($value);
        $allowed=['text-align'=>['left','center','right','justify'],'font-weight'=>['normal','bold','400','700'],'font-style'=>['normal','italic'],'text-decoration'=>['none','underline','line-through','underline line-through'],'text-decoration-line'=>['none','underline','line-through','underline line-through'],'font-size'=>['12px','14px','16px','18px','24px','32px','48px']];
        if(isset($allowed[$property])&&in_array($value,$allowed[$property],true))$styles[$property]=$value;
        elseif(in_array($property,['color','background-color'],true)&&($color=$colors($value)))$styles[$property]=$color;
        elseif($property==='font-family'&&isset($fonts[trim($value,"\"'")]))$styles[$property]=$fonts[trim($value,"\"'")];
    }
    $out=[];foreach($styles as $property=>$value)$out[]=$property.': '.$value;
    return implode('; ',$out);
}
function sanitize_rich_text(string $html): string {
    if(trim($html)==='')return '';
    $dom=new DOMDocument('1.0','UTF-8');$prior=libxml_use_internal_errors(true);
    try{$dom->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}
    finally{libxml_clear_errors();libxml_use_internal_errors($prior);}
    $allowed=['p','br','strong','b','em','i','u','s','strike','sup','sub','span','font','h1','h2','h3','ul','ol','li','blockquote','a','img','hr','table','caption','thead','tbody','tfoot','tr','th','td'];
    $blocked=['script','style','iframe','object','embed','svg','math','form','input','button','textarea','select','template'];
    $render=function(DOMNode $node)use(&$render,$allowed,$blocked):string{
        if($node instanceof DOMText)return e($node->nodeValue);
        if(!($node instanceof DOMElement))return '';
        $tag=strtolower($node->tagName);if(in_array($tag,$blocked,true))return '';
        $inner='';foreach($node->childNodes as $child)$inner.=$render($child);
        if($tag==='div')$tag='p';
        if(!in_array($tag,$allowed,true))return $inner;
        if($tag==='font')$tag='span';if($tag==='strike')$tag='s';
        if(in_array($tag,['br','hr'],true))return '<'.$tag.'>';
        $attrs='';
        if($tag==='a'){
            $href=trim($node->getAttribute('href'));
            if(!preg_match('~^(?:https?://|mailto:|/(?!/)|#)~i',$href)||preg_match('/[\x00-\x20\x7f]/',$href))return $inner;
            $attrs=' href="'.e($href).'" rel="noopener noreferrer"';
        }
        if($tag==='img'){
            $src=$node->getAttribute('src');
            if(!str_starts_with($src,'assets/')&&!preg_match('~^/(?!/)~',$src))return '';
            $path=parse_url($src,PHP_URL_PATH);
            if(!is_string($path))return '';
            if(str_starts_with($path,'/'))$path=preg_replace('~^/(?:.*?/)?assets/~','assets/',$path);
            if(!image_path($path)||!is_file(__DIR__.'/../public/'.$path))return '';
            return '<img src="'.asset_url($path).'" alt="'.e(mb_substr($node->getAttribute('alt'),0,255)).'" loading="lazy">';
        }
        if(in_array($tag,['td','th'],true)){
            foreach(['colspan','rowspan'] as $attribute){$value=$node->getAttribute($attribute);if(ctype_digit($value)&&(int)$value>=1&&(int)$value<=50)$attrs.=' '.$attribute.'="'.(int)$value.'"';}
        }
        if($style=rich_text_styles($node))$attrs.=' style="'.e($style).'"';
        return '<'.$tag.$attrs.'>'.$inner.'</'.$tag.'>';
    };
    $out='';$body=$dom->getElementsByTagName('body')->item(0);
    if($body)foreach($body->childNodes as $child)$out.=$render($child);
    return trim($out);
}
function rich_text_has_content(string $html): bool {
    return trim(str_replace("\u{00a0}",' ',html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8')))!=='' || (bool)preg_match('/<img\b/i',$html);
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

function gallery_groups_ready(): bool {
    try{db()->query('SELECT id FROM gallery_groups LIMIT 1');return true;}
    catch(PDOException $error){if((int)($error->errorInfo[1]??0)===1146)return false;throw $error;}
}
function gallery_groups(): array {
    if(gallery_groups_ready())return query_all('SELECT g.id,g.title,COUNT(p.id) image_count FROM gallery_groups g LEFT JOIN gallery p ON p.category=g.title GROUP BY g.id,g.title ORDER BY g.title');
    $rows=query_all('SELECT category title,COUNT(*) image_count FROM gallery GROUP BY category ORDER BY category');
    return $rows ?: [['id'=>0,'title'=>'Quạt Chàng Sơn','image_count'=>0],['id'=>0,'title'=>'Hoạt động','image_count'=>0]];
}
