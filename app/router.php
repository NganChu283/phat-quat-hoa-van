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
return false;
