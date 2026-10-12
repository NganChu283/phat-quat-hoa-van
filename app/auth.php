<?php
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $q=db()->prepare('SELECT id,username,display_name,role,is_active,auth_version FROM users WHERE id=?');
    $q->execute([$_SESSION['user_id']]); $user=$q->fetch();
    if (!$user || !$user['is_active'] || (int)$user['auth_version']!==(int)($_SESSION['user_version'] ?? 0)) return null;
    return $user;
}
function authenticate(string $username,string $password): bool {
    $q=db()->prepare('SELECT * FROM users WHERE username=?'); $q->execute([$username]); $user=$q->fetch();
    if (!$user || !$user['is_active'] || !password_verify($password,$user['password_hash'])) return false;
    if (password_needs_rehash($user['password_hash'],PASSWORD_DEFAULT)) {
        $q=db()->prepare('UPDATE users SET password_hash=? WHERE id=?'); $q->execute([password_hash($password,PASSWORD_DEFAULT),$user['id']]);
    }
    $q=db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?'); $q->execute([$user['id']]);
    session_regenerate_id(true);
    $_SESSION['user_id']=(int)$user['id']; $_SESSION['user_version']=(int)$user['auth_version'];
    $_SESSION['admin']=true; $_SESSION['csrf']=bin2hex(random_bytes(32));
    unset($_SESSION['login_attempts']); return true;
}
