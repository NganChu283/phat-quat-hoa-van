<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/bootstrap.php';
require __DIR__.'/mail.php';
$pdo=db();
if (!(int)$pdo->query("SELECT GET_LOCK('phat_quat_mail_worker',0)")->fetchColumn()) exit;
try {
    foreach (query_all("SELECT * FROM mail_queue WHERE status='pending' AND attempts<5 ORDER BY id LIMIT 20") as $row) {
        try { smtp_send($row['recipient'],$row['subject'],$row['body']); $q=$pdo->prepare("UPDATE mail_queue SET status='sent',sent_at=NOW(),attempts=attempts+1,last_error=NULL WHERE id=?"); $q->execute([$row['id']]); }
        catch (Throwable $ex) { $q=$pdo->prepare("UPDATE mail_queue SET last_error=?,attempts=attempts+1 WHERE id=?"); $q->execute([substr($ex->getMessage(),0,255),$row['id']]); }
    }
} finally { $pdo->query("SELECT RELEASE_LOCK('phat_quat_mail_worker')"); }
