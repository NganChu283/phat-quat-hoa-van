<?php
// Rename only the requested phrases; safe to run again on an existing database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/bootstrap.php';
$pdo=db();
$replacements=[
    'Phất Quạt Họa Văn'=>'Xoè 2026',
    'PHẤT QUẠT HỌA VĂN'=>'XOÈ 2026',
    'Chạm Ký Hồn Quê'=>'Xoè nếp xưa, đón gió lành'
];
$fields=[
    'projects'=>['title','subtitle','description'],
    'story_sections'=>['title','body'],
    'story_settings'=>['eyebrow','title','title_accent','intro','image_alt','image_caption'],
    'posts'=>['title','excerpt','body']
];
$pdo->beginTransaction();
try {
    foreach($fields as $table=>$columns){
        foreach($columns as $column){
            $update=$pdo->prepare("UPDATE `$table` SET `$column`=REPLACE(`$column`,?,?) WHERE LOCATE(?,`$column`)>0");
            foreach($replacements as $old=>$new) $update->execute([$old,$new,$old]);
        }
    }
    $pdo->commit();
    echo "Brand names updated.\n";
} catch(Throwable $error){
    if($pdo->inTransaction())$pdo->rollBack();
    throw $error;
}