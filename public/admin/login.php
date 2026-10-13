<?php
require __DIR__ . '/../../app/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
function login_database_error(PDOException $error): string {
    $code=(int)($error->errorInfo[1] ?? 0);
    $messages=[
        1044=>'Tài khoản MySQL chưa được cấp quyền vào database. Kiểm tra quyền database trên hosting.',
        1045=>'Thông tin đăng nhập MySQL chưa đúng. Kiểm tra db_user và db_password trong app/config.local.php theo hosting.',
        1049=>'Không tìm thấy database. Kiểm tra db_name trong app/config.local.php theo hosting.',
        1054=>'Database đang thiếu cột dùng cho tài khoản. Cần cập nhật cấu trúc bảng users.',
        1142=>'Tài khoản MySQL thiếu quyền đọc hoặc cập nhật bảng users. Kiểm tra quyền database trên hosting.',
        1146=>'Database đang thiếu bảng dùng để đăng nhập. Cần import đầy đủ database có bảng users và tài khoản quản trị.',
        2002=>'Không kết nối được máy chủ MySQL. Kiểm tra db_host và db_port theo thông tin hosting, không dùng thông tin XAMPP.',
        2003=>'Không kết nối được máy chủ MySQL. Kiểm tra db_host và db_port theo thông tin hosting.',
        2005=>'Tên máy chủ MySQL không hợp lệ. Kiểm tra db_host trong app/config.local.php.',
        2006=>'Kết nối MySQL bị ngắt. Hãy thử lại và kiểm tra dịch vụ database trên hosting.'
    ];
    $detail=$messages[$code] ?? 'Máy chủ không thể truy cập dữ liệu đăng nhập. Kiểm tra PHP error log trên hosting.';
    if($code===1054 && preg_match("/Unknown column '([a-zA-Z0-9_.]+)'/",$error->getMessage(),$match)){
        $detail='Database đang thiếu cột '.$match[1].' dùng cho tài khoản. Cần cập nhật cấu trúc bảng users.';
    }
    error_log('Xoe admin login database error ['.$code.']: '.$error->getMessage());
    return 'Chưa thể đăng nhập. '.$detail.' Mã lỗi: '.($code ?: $error->getCode()).'.';
}
$databaseError='';
try {
    // Check the required account fields before asking the user to retry a login.
    db()->query('SELECT id,username,display_name,password_hash,role,is_active,auth_version,last_login_at FROM users LIMIT 0');
    if (current_user()) redirect('index.php');
} catch (PDOException $ex) { $databaseError=login_database_error($ex); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_valid()){
        $username=$_POST['username']??'';
        $_SESSION['login_username']=is_string($username)?mb_substr($username,0,100):'';
        unset($_SESSION['csrf']);
        flash('Phiên đăng nhập đã hết hạn hoặc vừa thay đổi. Vui lòng nhập lại mật khẩu để đăng nhập.','error');
        redirect('login.php');
    }
    $now = time();
    if (($now - ($_SESSION['login_window'] ?? 0)) > 900) { $_SESSION['login_window'] = $now; $_SESSION['login_attempts'] = 0; }
    if (($_SESSION['login_attempts'] ?? 0) >= 5) flash('Vui lòng thử lại sau 15 phút.', 'error');
    else {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $username=$_POST['username'] ?? ''; $password=$_POST['password'] ?? '';
        try {
            if (is_string($username) && is_string($password) && strlen($username)<=100 && strlen($password)<=1024 && authenticate($username,$password)) redirect('index.php');
        } catch (PDOException $ex) {
            $_SESSION['login_attempts']=max(0,(int)($_SESSION['login_attempts']??1)-1);
            flash(login_database_error($ex),'error'); redirect('login.php');
        }
        flash('Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.', 'error');
    }
    redirect('login.php');
}
$loginUsername=$_SESSION['login_username']??'';unset($_SESSION['login_username']);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Đăng nhập quản trị — Xoè 2026</title><link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>"></head><body><main class="login"><a class="brand" href="../index.php"><img class="admin-brand" src="../assets/xoe-logo.webp" alt="Xòe"></a><h1>Quản trị<br><em>dự án.</em></h1><?php if($databaseError!==''){ echo '<p class="notice error" role="alert">'.e($databaseError).'</p>'; unset($_SESSION['flash']); } else notice(); ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><label>Tên đăng nhập<input name="username" required autocomplete="username" maxlength="100" value="<?= e($loginUsername) ?>"></label><label>Mật khẩu<input type="password" name="password" required autocomplete="current-password" maxlength="1024"></label><button class="button" type="submit">Đăng nhập ↗</button></form><p style="margin-top:25px"><a href="../index.php">← Về website</a></p></main></body></html>
