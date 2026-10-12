<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
if (PHP_SAPI !== 'cli') session_start();
else $_SESSION = [];
date_default_timezone_set("Asia/Ho_Chi_Minh");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function config(): array {
    static $config;
    if ($config === null) {
        $path = __DIR__ . '/config.local.php';
        $config = require __DIR__.'/config.example.php';
        if (is_file($path)) $config = array_replace($config, require $path);
    }
    return $config;
}
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $c = config();
        $pdo = new PDO("mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $pdo->exec("SET time_zone = '+07:00'");
    }
    return $pdo;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function check_csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) {
        http_response_code(403); exit('Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
    }
}
function redirect(string $path): void { header('Location: ' . $path, true, 303); exit; }
function flash(string $text, string $type = 'success'): void { $_SESSION['flash'] = compact('text', 'type'); }
function notice(?string $scope=null): void {
    if (isset($_SESSION['flash']) && ($scope===null || ($_SESSION['flash_scope'] ?? 'register')===$scope)) {
        $f = $_SESSION['flash']; unset($_SESSION['flash']);
        unset($_SESSION['flash_scope']);
        echo '<p class="notice ' . e($f['type']) . '" role="status">' . e($f['text']) . '</p>';
    }
}
function field(string $name, int $max = 1000): string {
    $value = $_POST[$name] ?? '';
    if (!is_string($value)) throw new InvalidArgumentException('Dữ liệu không hợp lệ.');
    $value = trim($value);
    if (mb_strlen($value) > $max) throw new InvalidArgumentException('Nội dung vượt quá độ dài cho phép.');
    return $value;
}
function image_path(string $path): bool {
    return (bool) preg_match('~^assets/[a-zA-Z0-9._/-]+\.(?:png|jpg|jpeg|webp)$~i', $path) && !str_contains($path, '..');
}
function admin_required(?string $role=null): void {
    header('X-Robots-Tag: noindex, nofollow'); header('Cache-Control: no-store');
    try { $user=current_user(); }
    catch (PDOException $ex) { http_response_code(503); exit('Chưa thể tải tài khoản. Kiểm tra MySQL và chạy app/migrate.php.'); }
    if (!$user) { unset($_SESSION['admin'],$_SESSION['user_id'],$_SESSION['user_version']); redirect('login.php'); }
    if ($role && $user['role']!==$role) { http_response_code(403); exit('Tài khoản không có quyền quản lý người dùng.'); }
}
function query_all(string $sql): array { return db()->query($sql)->fetchAll(); }
require_once __DIR__.'/images.php';
require_once __DIR__.'/auth.php';
