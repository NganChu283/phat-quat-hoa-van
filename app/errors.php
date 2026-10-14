<?php
// Public pages must show a recoverable error, never a blank response.
function report_error(Throwable $error, string $context=''): string {
    $id=bin2hex(random_bytes(6));
    $message='['.$id.'] '.$context.' '.get_class($error).': '.$error->getMessage().' in '.$error->getFile().':'.$error->getLine();
    foreach (['db_password','smtp_password','admin_password_hash'] as $key) {
        $secret=(string)(config()[$key]??'');
        if ($secret!=='') $message=str_replace($secret,'[redacted]',$message);
    }
    error_log($message);
    $directory=__DIR__.'/../.local';
    if (is_dir($directory) || @mkdir($directory,0700,true)) {
        @file_put_contents($directory.'/php-errors.log',date('c').' '.$message.PHP_EOL,FILE_APPEND|LOCK_EX);
        @chmod($directory.'/php-errors.log',0600);
    }
    return $id;
}
function error_page(string $id, int $status=500): void {
    while (ob_get_level()>0) ob_end_clean();
    if (!headers_sent()) { http_response_code($status); header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store'); }
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Không thể hoàn tất thao tác</title></head><body style="font-family:Arial,sans-serif;max-width:650px;margin:60px auto;padding:24px;line-height:1.7"><h1>Chưa thể hoàn tất thao tác</h1><p>Vui lòng quay lại trang quản trị và thử lại. Nếu lỗi lặp lại, gửi mã lỗi này cho người quản lý website.</p><p>Mã lỗi: <strong>'.htmlspecialchars($id,ENT_QUOTES,'UTF-8').'</strong></p><p><a href="javascript:history.back()">Quay lại</a></p></body></html>';
}
set_exception_handler(function(Throwable $error): void {
    $id=report_error($error,'Uncaught');
    if (PHP_SAPI==='cli') { fwrite(STDERR,'Lỗi '.$id.': '.$error->getMessage().PHP_EOL); exit(1); }
    error_page($id);
});
register_shutdown_function(function(): void {
    $error=error_get_last();
    if (!$error || !in_array($error['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true)) return;
    $id=report_error(new ErrorException($error['message'],0,$error['type'],$error['file'],$error['line']),'Fatal');
    if (PHP_SAPI!=='cli') error_page($id);
});
