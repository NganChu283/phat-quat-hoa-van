<?php
require __DIR__.'/../../app/bootstrap.php';
admin_required();
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: POST'); exit; } check_csrf();
$q=db()->query('SELECT id,name,email,phone,activity,message,created_at FROM registrations ORDER BY id DESC');
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="dang-ky-'.date('Y-m-d').'.csv"'); header('Cache-Control: no-store');
$out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,['Mã','Họ tên','Email','Điện thoại','Hoạt động','Lời nhắn','Ngày đăng ký']);
while ($row=$q->fetch(PDO::FETCH_NUM)) { fputcsv($out,array_map(function($v) { $v=(string)$v; return preg_match('/^[\s]*[=+@\-]/u',$v) ? "'".$v : $v; },$row)); } fclose($out);
