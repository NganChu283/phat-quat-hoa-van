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

// auth.php
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

// images.php
function image_tag(string $path, string $alt, string $attributes='loading="lazy"', string $sizes='(max-width: 700px) 100vw, 50vw'): string {
    if (!image_path($path)) return '';
    $file=__DIR__.'/../public/'.$path;
    $dim=is_file($file)?@getimagesize($file):false;
    $stem=preg_replace('/\.[^.]+$/','',$path); $sources=[];
    foreach ([480,960,1600] as $width) {
        if (is_file(__DIR__.'/../public/'.$stem.'-'.$width.'.webp')) $sources[]=$stem.'-'.$width.'.webp '.$width.'w';
    }
    $src=is_file(__DIR__.'/../public/'.$stem.'.webp') ? $stem.'.webp' : $path;
    if ($sources && $dim && !in_array($dim[0],[480,960,1600],true)) $sources[]=$src.' '.$dim[0].'w';
    return '<img src="'.e($src).'" alt="'.e($alt).'" '.($dim?'width="'.$dim[0].'" height="'.$dim[1].'" ':'').'decoding="async" '.$attributes.($sources?' srcset="'.e(implode(', ',$sources)).'" sizes="'.e($sizes).'"':'').'>';
}

// upload.php
function upload_image(string $fallback): string {
    $file = $_FILES['photo'] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if (!image_path($fallback)) throw new InvalidArgumentException('Đường dẫn ảnh phải là assets/... với định dạng JPG, PNG hoặc WebP.');
        if (!is_file(__DIR__ . '/../public/' . $fallback)) throw new InvalidArgumentException('Không tìm thấy ảnh. Vui lòng tải ảnh lên.');
        return $fallback;
    }
    if (!isset($file['error'], $file['size'], $file['tmp_name']) || !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024) throw new InvalidArgumentException('Ảnh tải lên không hợp lệ hoặc vượt quá 3 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $formats = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $dimensions = @getimagesize($file['tmp_name']);
    if (!isset($formats[$mime]) || !$dimensions || $dimensions[0] > 8000 || $dimensions[1] > 8000 || $dimensions[0]*$dimensions[1]>16000000) throw new InvalidArgumentException('Chỉ nhận ảnh JPG, PNG, WebP tối đa 8000px và 16 megapixel.');
    $directory = __DIR__ . '/../public/assets/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('Không thể tạo thư mục ảnh.');
    $path = 'assets/uploads/' . bin2hex(random_bytes(16)) . '.' . $formats[$mime];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../public/' . $path)) throw new RuntimeException('Không thể lưu ảnh.');
    if (function_exists('imagewebp')) {
        $source=@imagecreatefromstring(file_get_contents(__DIR__.'/../public/'.$path));
        if ($source) {
            $stem=preg_replace('/\.[^.]+$/','',$path);
            foreach ([480,960,1600] as $width) {
                if ($width>$dimensions[0]) continue;
                $height=max(1,(int)round($dimensions[1]*$width/$dimensions[0]));
                $target=imagecreatetruecolor($width,$height); imagealphablending($target,false); imagesavealpha($target,true);
                imagecopyresampled($target,$source,0,0,0,0,$width,$height,$dimensions[0],$dimensions[1]);
                imagewebp($target,__DIR__.'/../public/'.$stem.'-'.$width.'.webp',82); imagedestroy($target);
            }
            imagedestroy($source);
        }
    }
    return $path;
}

// spam.php
function form_token(string $kind): string {
    $token = bin2hex(random_bytes(24));
    $_SESSION['form_tokens'] = array_filter($_SESSION['form_tokens'] ?? [], fn($v) => $v['time'] > time()-7200);
    $_SESSION['form_tokens'][$token] = ['kind'=>$kind, 'time'=>time()];
    $_SESSION['form_tokens'] = array_slice($_SESSION['form_tokens'], -20, null, true);
    return $token;
}
function spam_check(string $kind): void {
    $token = field('form_token', 48);
    $entry = $_SESSION['form_tokens'][$token] ?? null;
    if (!$entry || $entry['kind'] !== $kind || time()-$entry['time'] < 3 || time()-$entry['time'] > 7200) {
        throw new InvalidArgumentException('Vui lòng tải lại trang và chờ vài giây trước khi gửi.');
    }
    unset($_SESSION['form_tokens'][$token]);
    // Use the direct connection address; do not trust user supplied proxy headers.
    $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $pdo = db(); $pdo->beginTransaction();
    try {
        $q=$pdo->prepare('INSERT IGNORE INTO form_limits VALUES (?, ?, 0)'); $q->execute([$key,time()]);
        $q=$pdo->prepare('SELECT * FROM form_limits WHERE fingerprint=? FOR UPDATE'); $q->execute([$key]); $row=$q->fetch();
        $fresh=time()-(int)$row['window_started']>=600;
        $count=$fresh ? 1 : (int)$row['attempts']+1;
        $q=$pdo->prepare('UPDATE form_limits SET window_started=?,attempts=? WHERE fingerprint=?');
        $q->execute([$fresh?time():$row['window_started'],$count,$key]); $pdo->commit();
        if ($count>5) throw new InvalidArgumentException('Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau 10 phút.');
    } catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $ex; }
}

require_once __DIR__.'/content.php';
require_once __DIR__.'/views.php';
