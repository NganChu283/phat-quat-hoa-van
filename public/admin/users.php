<?php
require __DIR__.'/../../app/bootstrap.php';
require __DIR__.'/../../app/admin-nav.php';
admin_required('admin');
$user=current_user(); $edit=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    try {
        $id=filter_var($_POST['id'] ?? 0,FILTER_VALIDATE_INT);
        if ($id===false || $id<0) throw new InvalidArgumentException('Mã tài khoản không hợp lệ.');
        $username=field('username',100); $name=field('display_name',120); $role=field('role',20);
        $password=$_POST['password'] ?? '';
        if (!preg_match('/^[a-zA-Z0-9._-]{3,100}$/',$username) || $name==='') throw new InvalidArgumentException('Nhập tên hiển thị và tên đăng nhập 3–100 ký tự (chữ, số, dấu chấm, gạch dưới, gạch ngang).');
        if (!in_array($role,['admin','editor'],true)) throw new InvalidArgumentException('Quyền tài khoản không hợp lệ.');
        if (!is_string($password) || ((!$id || $password!=='') && (mb_strlen($password)<12 || strlen($password)>72))) throw new InvalidArgumentException('Mật khẩu cần ít nhất 12 ký tự và tối đa 72 byte.');
        $active=isset($_POST['is_active']) ? 1:0;
        $pdo=db(); $pdo->beginTransaction();
        $all=$pdo->query('SELECT id,username,role,is_active,auth_version FROM users ORDER BY id FOR UPDATE')->fetchAll();
        $existing=null; $admins=0;
        foreach ($all as $row) { if ((int)$row['id']===$id) $existing=$row; if ($row['role']==='admin' && $row['is_active']) $admins++; }
        if ($id && !$existing) throw new InvalidArgumentException('Tài khoản không còn tồn tại.');
        if ($existing && $existing['role']==='admin' && $existing['is_active'] && ($role!=='admin' || !$active) && $admins<=1) throw new InvalidArgumentException('Phải giữ ít nhất một quản trị viên đang hoạt động.');
        if ($id) {
            $version=(int)$existing['auth_version']+($password!=='' || $role!==$existing['role'] || $active!==(int)$existing['is_active'] ? 1:0);
            $q=$pdo->prepare('UPDATE users SET username=?,display_name=?,role=?,is_active=?,auth_version=? WHERE id=?');
            $q->execute([$username,$name,$role,$active,$version,$id]);
            if ($password!=='') { $q=$pdo->prepare('UPDATE users SET password_hash=? WHERE id=?'); $q->execute([password_hash($password,PASSWORD_DEFAULT),$id]); }
        } else {
            $q=$pdo->prepare('INSERT INTO users (username,display_name,password_hash,role,is_active) VALUES (?,?,?,?,?)');
            $q->execute([$username,$name,password_hash($password,PASSWORD_DEFAULT),$role,$active]);
        }
        $pdo->commit(); flash('Đã lưu tài khoản. Thay đổi mật khẩu, quyền hoặc trạng thái sẽ yêu cầu tài khoản đó đăng nhập lại.');
    } catch (Throwable $ex) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        if ($ex instanceof InvalidArgumentException) flash($ex->getMessage(),'error');
        elseif ($ex instanceof PDOException && ($ex->errorInfo[1] ?? 0)===1062) flash('Tên đăng nhập đã tồn tại.','error');
        else { error_log('Users error: '.$ex->getCode()); flash('Chưa thể lưu tài khoản. Vui lòng kiểm tra database.','error'); }
        $_SESSION['user_draft']=array_intersect_key(array_filter($_POST,fn($v)=>is_string($v)),array_flip(['id','username','display_name','role','is_active']));
    }
    redirect('users.php');
}
$rows=query_all('SELECT id,username,display_name,role,is_active,last_login_at FROM users ORDER BY id');
$id=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT);
if ($id) { $q=db()->prepare('SELECT id,username,display_name,role,is_active FROM users WHERE id=?'); $q->execute([$id]); $edit=$q->fetch() ?: null; }
if (isset($_SESSION['user_draft'])) { $edit=$_SESSION['user_draft']; unset($_SESSION['user_draft']); }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Tài khoản — Quản trị</title><link rel="stylesheet" href="../assets/style.css"><link rel="stylesheet" href="../assets/admin.css?v=admin-upgrade-1"></head><body><main class="admin-page">
<header class="admin-header"><a href="../index.php"><img class="admin-brand" src="../assets/xoe-logo.webp" alt="Xòe"></a><h1>Quản lý <em>tài khoản.</em></h1><form action="logout.php" method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="button small">Đăng xuất</button></form></header>
<nav class="admin-nav" aria-label="Quản trị"><?php foreach (admin_tabs() as $key=>$label): ?><a href="<?= e(admin_tab_url($key)) ?>" <?= $key==='users'?'aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach; ?></nav><?php notice(); ?>
<section class="admin-card user-form"><h2><?= $edit?'Sửa tài khoản':'Thêm tài khoản' ?></h2><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><div class="admin-grid"><label>Tên đăng nhập<input name="username" maxlength="100" required pattern="[a-zA-Z0-9._-]{3,100}" autocomplete="off" value="<?= e($edit['username'] ?? '') ?>"></label><label>Tên hiển thị<input name="display_name" maxlength="120" required value="<?= e($edit['display_name'] ?? '') ?>"></label></div><label><?= $edit?'Mật khẩu mới (để trống để giữ mật khẩu)':'Mật khẩu' ?><input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" <?= !$edit?'required':'' ?>></label><label>Quyền<select name="role"><option value="editor" <?= ($edit['role'] ?? '')==='editor'?'selected':'' ?>>Biên tập viên — quản lý nội dung</option><option value="admin" <?= ($edit['role'] ?? '')==='admin'?'selected':'' ?>>Quản trị viên — nội dung và tài khoản</option></select></label><label class="check"><input type="checkbox" name="is_active" value="1" <?= !$edit || !empty($edit['is_active'])?'checked':'' ?>><span>Cho phép đăng nhập</span></label><div class="admin-actions"><button class="button">Lưu tài khoản</button><a class="text-link" href="users.php">Hủy / thêm mới</a></div></form></section>
<div class="table-wrap"><table><thead><tr><th>Tên đăng nhập</th><th>Tên hiển thị</th><th>Quyền</th><th>Trạng thái</th><th>Đăng nhập gần nhất</th><th>Thao tác</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['username']) ?></td><td><?= e($row['display_name']) ?></td><td><?= $row['role']==='admin'?'Quản trị viên':'Biên tập viên' ?></td><td><?= $row['is_active']?'Hoạt động':'Đã khóa' ?></td><td><?= e($row['last_login_at'] ?? 'Chưa đăng nhập') ?></td><td><a class="text-link" href="?edit=<?= (int)$row['id'] ?>">Sửa</a></td></tr><?php endforeach; ?></tbody></table></div>
</main></body></html>
