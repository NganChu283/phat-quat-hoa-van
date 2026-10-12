<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/bootstrap.php';
require __DIR__.'/stories.php';
$columns=array_column(query_all('SHOW COLUMNS FROM projects'),'Field');
foreach (['ends_at'=>'DATETIME NULL','venue'=>"VARCHAR(180) NOT NULL DEFAULT ''",'address'=>"VARCHAR(500) NOT NULL DEFAULT ''",'schedule'=>'TEXT NULL'] as $name=>$definition) {
    if (!in_array($name,$columns,true)) db()->exec("ALTER TABLE projects ADD COLUMN $name $definition");
}
db()->exec(file_get_contents(__DIR__.'/../database/features.sql'));
db()->exec(file_get_contents(__DIR__.'/../database/admin-upgrade.sql'));
db()->exec(file_get_contents(__DIR__.'/../database/stories.sql'));
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
$pdo=db(); $pdo->beginTransaction();
try {
    $name='admin-upgrade-20261012';
    $q=$pdo->prepare('SELECT name FROM app_migrations WHERE name=?'); $q->execute([$name]);
    if (!$q->fetch()) {
        if (!(int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn()) {
            $q=$pdo->prepare('INSERT INTO projects (title,description,starts_at,featured,published) VALUES (?,?,NULL,1,1)');
            $q->execute(['Chạm Ký Hồn Quê','Một nếp quạt mở. Một miền ký ức. Cùng khám phá vẻ đẹp của làng nghề quạt Chàng Sơn.']);
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
        $q=$pdo->prepare('SELECT id FROM projects WHERE title=? LIMIT 1'); $q->execute(['Chạm Ký Hồn Quê']);
        if (!$q->fetch()) {
            $q=$pdo->prepare('INSERT INTO projects (title,description,starts_at,featured,published) VALUES (?,?,NULL,NULL,1)');
            $q->execute(['Chạm Ký Hồn Quê','Một nếp quạt mở. Một miền ký ức. Cùng khám phá vẻ đẹp của làng nghề quạt Chàng Sơn.']);
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
        $q->execute(['Chàng Sơn — Giữ trọn ký ức trong từng nan quạt','Phất Quạt Họa Văn — Câu chuyện của dự án']);
        $q=$pdo->prepare('INSERT INTO app_migrations (name) VALUES (?)'); $q->execute([$storyMigration]);
    }
    $pdo->commit();
} catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $ex; }
echo "Features migration applied.\n";
