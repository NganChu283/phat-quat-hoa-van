<?php
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
