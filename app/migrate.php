<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/bootstrap.php';
$schema=file_get_contents(__DIR__.'/../database/schema.sql');
$schema=preg_replace('/^(?:CREATE DATABASE|USE )[^\n]*\n/m','',$schema);
db()->exec($schema);
require __DIR__.'/migrate-gallery-groups.php';


$columns=array_column(query_all('SHOW COLUMNS FROM projects'),'Field');
foreach (['ends_at'=>'DATETIME NULL','venue'=>"VARCHAR(180) NOT NULL DEFAULT ''",'address'=>"VARCHAR(500) NOT NULL DEFAULT ''",'schedule'=>'TEXT NULL'] as $name=>$definition) {
    if (!in_array($name,$columns,true)) db()->exec("ALTER TABLE projects ADD COLUMN $name $definition");
}



$introColumns=array_column(query_all('SHOW COLUMNS FROM story_settings'),'Field');
foreach (['published'=>'TINYINT(1) NOT NULL DEFAULT 1','sort_order'=>'INT NOT NULL DEFAULT 0'] as $name=>$definition) {
    if (!in_array($name,$introColumns,true)) db()->exec("ALTER TABLE story_settings ADD COLUMN $name $definition");
}
db()->exec('ALTER TABLE story_settings MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');
$columns=array_column(query_all('SHOW COLUMNS FROM projects'),'Field');
foreach (['description'=>"VARCHAR(500) NOT NULL DEFAULT ''",'published'=>'TINYINT(1) NOT NULL DEFAULT 1'] as $name=>$definition) {
    if (!in_array($name,$columns,true)) db()->exec("ALTER TABLE projects ADD COLUMN $name $definition");
}
db()->exec('ALTER TABLE projects MODIFY starts_at DATETIME NULL');
$columns=array_column(query_all('SHOW COLUMNS FROM posts'),'Field');
if (!in_array('body_format',$columns,true)) db()->exec("ALTER TABLE posts ADD COLUMN body_format VARCHAR(10) NOT NULL DEFAULT 'plain'");
if (!in_array('show_on_home',$columns,true)) db()->exec('ALTER TABLE posts ADD COLUMN show_on_home TINYINT(1) NOT NULL DEFAULT 0');
$columns=array_column(query_all('SHOW COLUMNS FROM story_sections'),'Field');
if (!in_array('body_format',$columns,true)) db()->exec("ALTER TABLE story_sections ADD COLUMN body_format VARCHAR(10) NOT NULL DEFAULT 'plain'");
$pdo=db(); $pdo->beginTransaction();
try {
    $name='admin-upgrade-20261012';
    $q=$pdo->prepare('SELECT name FROM app_migrations WHERE name=?'); $q->execute([$name]);
    if (!$q->fetch()) {
        if (!(int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn()) {
            $q=$pdo->prepare('INSERT INTO projects (title,description,starts_at,featured,published) VALUES (?,?,NULL,1,1)');
            $q->execute(['Xoè nếp xưa, đón gió lành','Một nếp quạt mở. Một miền ký ức. Cùng khám phá vẻ đẹp của làng nghề quạt Chàng Sơn.']);
        }
        $c=config();
        if (!empty($c['admin_password_hash'])) {
            $q=$pdo->prepare("INSERT IGNORE INTO users (username,display_name,password_hash,role) VALUES (?,?,?,'admin')");
            $q->execute([$c['admin_username'],'Quản trị viên',$c['admin_password_hash']]);
        }
        $q=$pdo->prepare('INSERT INTO app_migrations (name) VALUES (?)'); $q->execute([$name]);
    }
    $knownEventMigration='known-event-cham-ky-hon-que-20261012';
    $q=$pdo->prepare('SELECT name FROM app_migrations WHERE name=?'); $q->execute([$knownEventMigration]);
    if (!$q->fetch()) {
        $q=$pdo->prepare('SELECT id FROM projects WHERE title=? LIMIT 1'); $q->execute(['Xoè nếp xưa, đón gió lành']);
        if (!$q->fetch()) {
            $q=$pdo->prepare('INSERT INTO projects (title,description,starts_at,featured,published) VALUES (?,?,NULL,NULL,1)');
            $q->execute(['Xoè nếp xưa, đón gió lành','Một nếp quạt mở. Một miền ký ức. Cùng khám phá vẻ đẹp của làng nghề quạt Chàng Sơn.']);
        }
        $q=$pdo->prepare('INSERT INTO app_migrations (name) VALUES (?)'); $q->execute([$knownEventMigration]);
    }
    $storyMigration='story-management-20261012';
    $q=$pdo->prepare('SELECT name FROM app_migrations WHERE name=?'); $q->execute([$storyMigration]);
    if (!$q->fetch()) {
        $s=default_story_settings();
        $q=$pdo->prepare('INSERT IGNORE INTO story_settings (id,eyebrow,title,title_accent,intro,image,image_alt,image_caption) VALUES (1,?,?,?,?,?,?,?)');
        $q->execute([$s['eyebrow'],$s['title'],$s['title_accent'],$s['intro'],$s['image'],$s['image_alt'],$s['image_caption']]);
        if (!(int)$pdo->query('SELECT COUNT(*) FROM story_sections')->fetchColumn()) {
            $q=$pdo->prepare('INSERT INTO story_sections (title,body,body_format,published,sort_order) VALUES (?,?,?,?,?)');
            foreach (default_story_sections() as $section) $q->execute([$section['title'],$section['body'],$section['body_format'],$section['published'],$section['sort_order']]);
        }
        $q=$pdo->prepare('UPDATE posts SET show_on_home=1 WHERE title IN (?,?) AND published=1');
        $q->execute(['Chàng Sơn — Giữ trọn ký ức trong từng nan quạt','Xoè 2026 — Câu chuyện của dự án']);
        $q=$pdo->prepare('INSERT INTO app_migrations (name) VALUES (?)'); $q->execute([$storyMigration]);
    }
    $pdo->commit();
} catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $ex; }
echo "Features migration applied.\n";
require __DIR__.'/rename-brand.php';
require __DIR__.'/migrate-event-forms.php';

if (in_array('--photos',$argv,true)) {
$photos=default_gallery(); $pdo=db(); $pdo->beginTransaction();
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
    $update->execute(['assets/chang-son-fans.webp','Chàng Sơn — Giữ trọn ký ức trong từng nan quạt']);
    $update->execute(['assets/making-fans.webp','Xoè 2026 — Câu chuyện của dự án']);
    $pdo->commit(); echo "Photo gallery and sample article covers updated.\n";
} catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $ex; }
}
