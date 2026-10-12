<?php
// Compatibility for asset URLs used before the project was consolidated.
// PHP's development server ignores .htaccess, so keep the same aliases here.
$urlPath=rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH) ?: '/');
$asset=null;
if ($urlPath==='/assets/admin.css') $asset='assets/style.css';
elseif ($urlPath==='/assets/editor.js') $asset='assets/app.js';
elseif (preg_match('~^/assets/photos/([a-zA-Z0-9._-]+\.(?:webp|png|jpe?g))$~i',$urlPath,$matches)) $asset='assets/'.$matches[1];
if ($asset!==null) {
    $path=__DIR__.'/../public/'.$asset;
    if (!is_file($path)) { http_response_code(404); return true; }
    $mime=['css'=>'text/css; charset=utf-8','js'=>'application/javascript; charset=utf-8','webp'=>'image/webp','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
    header('Content-Type: '.$mime[strtolower(pathinfo($path,PATHINFO_EXTENSION))]);
    header('X-Content-Type-Options: nosniff'); readfile($path); return true;
}
// Serve the same root layout as Apache, including the private-directory rules.
if (preg_match('~(?:^|/)\.|^/(?:app|database)(?:/|$)|^/(?:README\.md|start\.ps1|start\.command)$~i', $urlPath)) {
    http_response_code(403); return true;
}
if ($urlPath==='/' || $urlPath==='/index.php') {
    $_SERVER['SCRIPT_NAME']='/index.php';
    require __DIR__.'/../index.php'; return true;
}
if (preg_match('~^/(assets/.*|admin(?:/.*)?|robots\.txt|[a-zA-Z0-9_-]+\.php)$~', $urlPath, $matches)) {
    $relative=$matches[1];
    if ($relative==='admin' || str_ends_with($relative,'/')) {
        if (!str_ends_with($urlPath,'/')) { header('Location: '.$urlPath.'/'); return true; }
        $relative=rtrim($relative,'/').'/index.php';
    }
    $publicRoot=realpath(__DIR__.'/../public');
    $file=realpath($publicRoot.'/'.$relative);
    if (!$file || !str_starts_with($file,$publicRoot.DIRECTORY_SEPARATOR) || !is_file($file)) {
        http_response_code(404); return true;
    }
    if (strtolower(pathinfo($file,PATHINFO_EXTENSION))==='php') {
        // Uploaded files must never run as PHP.
        if (str_starts_with($relative,'assets/')) { http_response_code(403); return true; }
        $_SERVER['SCRIPT_NAME']='/'.$relative;
        require $file; return true;
    }
    $mime=['css'=>'text/css; charset=utf-8','js'=>'application/javascript; charset=utf-8','webp'=>'image/webp','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','svg'=>'image/svg+xml'];
    $mime['txt']='text/plain; charset=utf-8';
    header('Content-Type: '.($mime[strtolower(pathinfo($file,PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff'); readfile($file); return true;
}
return false;
