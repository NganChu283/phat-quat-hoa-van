<?php
ini_set('display_errors','0');
require __DIR__.'/../../app/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
try {
    if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');throw new InvalidArgumentException('Yêu cầu tải ảnh không hợp lệ.');}
    if(!current_user()){http_response_code(401);throw new InvalidArgumentException('Phiên đăng nhập đã hết hạn. Hãy đăng nhập lại.');}
    if(!is_string($_POST['csrf']??null)||!hash_equals(csrf(),$_POST['csrf'])){http_response_code(403);throw new InvalidArgumentException('Phiên làm việc không hợp lệ. Hãy tải lại trang.');}
    if(empty($_FILES['photo']))throw new InvalidArgumentException('Chọn ảnh JPG, PNG hoặc WebP để chèn.');
    $path=upload_image('');
    echo json_encode(['url'=>html_entity_decode(asset_url($path),ENT_QUOTES,'UTF-8'),'path'=>$path],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch(InvalidArgumentException $error){
    if(http_response_code()<400)http_response_code(422);
    echo json_encode(['error'=>$error->getMessage()],JSON_UNESCAPED_UNICODE);
} catch(Throwable $error){
    error_log('Editor image upload: '.$error->getMessage());http_response_code(500);
    echo json_encode(['error'=>'Không thể tải ảnh. Kiểm tra quyền ghi thư mục assets/uploads.'],JSON_UNESCAPED_UNICODE);
}
