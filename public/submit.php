<?php
require __DIR__ . '/../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
check_csrf();
// Cached legacy registration forms should lead to the event Google Forms.
if (($_POST['kind']??'')==='register') redirect('index.php#dang-ky');
if (($_POST['kind']??'')!=='contact') { http_response_code(400); exit('Biểu mẫu không hợp lệ.'); }
$kind='contact';
$back='index.php#lien-he';
try {
    if (field('website', 200) !== '') throw new InvalidArgumentException('Không thể gửi biểu mẫu này.');
    if (time() - (int) ($_SESSION['last_submit'] ?? 0) < 30) throw new InvalidArgumentException('Vui lòng chờ 30 giây trước khi gửi tiếp.');
    $pdo=db();
    spam_check($kind);
    $name = field('name', 120); $email = field('email', 254); $message = field('message', 3000);
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Vui lòng nhập họ tên và email hợp lệ.');
    if (($_POST['consent'] ?? '') !== '1') throw new InvalidArgumentException('Vui lòng đồng ý cho phép sử dụng thông tin để phản hồi yêu cầu.');
    if ($message === '') throw new InvalidArgumentException('Vui lòng nhập lời nhắn.');
    $q=db()->prepare('INSERT INTO contacts (name,email,message) VALUES (?,?,?)');
    $q->execute([$name,$email,$message]);
    $_SESSION['last_submit'] = time(); unset($_SESSION['old'][$kind]);
    flash('Lời nhắn đã được lưu. Ban tổ chức có thể xem và phản hồi qua thông tin bạn cung cấp.');
} catch (InvalidArgumentException $ex) {
    $_SESSION['old'][$kind] = array_filter($_POST, fn($v, $k) => is_string($v) && in_array($k, ['name','email','phone','message','activity'], true), ARRAY_FILTER_USE_BOTH);
    flash($ex->getMessage(), 'error');
} catch (PDOException $ex) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Form database error: ' . $ex->getCode());
    flash('Chưa thể lưu dữ liệu. Vui lòng thử lại sau hoặc liên hệ qua email bên dưới.', 'error');
}
$_SESSION['flash_scope'] = $kind;
redirect($back);
