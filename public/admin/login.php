<?php
require __DIR__ . '/../../app/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
try { if (current_user()) redirect('index.php'); } catch (PDOException $ex) { /* Thông báo cài database bên dưới khi đăng nhập. */ }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $now = time();
    if (($now - ($_SESSION['login_window'] ?? 0)) > 900) { $_SESSION['login_window'] = $now; $_SESSION['login_attempts'] = 0; }
    if (($_SESSION['login_attempts'] ?? 0) >= 5) flash('Vui lòng thử lại sau 15 phút.', 'error');
    else {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $username=$_POST['username'] ?? ''; $password=$_POST['password'] ?? '';
        try {
            if (is_string($username) && is_string($password) && strlen($username)<=100 && strlen($password)<=1024 && authenticate($username,$password)) redirect('index.php');
        } catch (PDOException $ex) {
            flash('Chưa thể đăng nhập. Kiểm tra MySQL và chạy app/migrate.php.','error'); redirect('login.php');
        }
        flash('Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.', 'error');
    }
    redirect('login.php');
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Đăng nhập quản trị — Phất Quạt Họa Văn</title><link rel="stylesheet" href="../assets/style.css?v=compact-1"></head><body><main class="login"><a class="brand" href="../index.php"><img class="admin-brand" src="../assets/xoe-logo.webp" alt="Xòe"></a><h1>Quản trị<br><em>dự án.</em></h1><?php notice(); ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><label>Tên đăng nhập<input name="username" required autocomplete="username" maxlength="100"></label><label>Mật khẩu<input type="password" name="password" required autocomplete="current-password" maxlength="1024"></label><button class="button" type="submit">Đăng nhập ↗</button></form><p style="margin-top:25px"><a href="../index.php">← Về website</a></p></main></body></html>
