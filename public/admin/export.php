<?php
require __DIR__.'/../../app/bootstrap.php';
admin_required();
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: POST'); exit; } check_csrf();
$legacy=($_POST['source']??'')==='legacy';
$q=db()->query($legacy ? 'SELECT id,name,email,phone,activity,message,created_at FROM registrations ORDER BY id DESC' : 'SELECT id,name,email,message,created_at FROM contacts ORDER BY id DESC');
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="lien-he-'.date('Y-m-d').'.csv"'); header('Cache-Control: no-store');
$out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,$legacy?['Mã','Họ tên','Email','Điện thoại','Hoạt động','Lời nhắn','Ngày gửi']:['Mã','Họ tên','Email','Lời nhắn','Ngày gửi']);
while ($row=$q->fetch(PDO::FETCH_NUM)) { fputcsv($out,array_map(function($v) { $v=(string)$v; return preg_match('/^[\s]*[=+@\-]/u',$v) ? "'".$v : $v; },$row)); } fclose($out);
