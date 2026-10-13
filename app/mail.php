<?php
function queue_registration(int $id, string $name, string $email, string $phone, string $activity, string $message): void {
    $q=db()->prepare('INSERT INTO mail_queue (registration_id,recipient,subject,body) VALUES (?,?,?,?)');
    $q->execute([$id,$email,'Đã nhận đăng ký — Xoè 2026',"Xin chào $name,\n\nChúng tôi đã nhận đăng ký #$id của bạn cho hoạt động: $activity.\nBan tổ chức sẽ liên hệ để xác nhận lịch và chỗ tham gia. Email này xác nhận đã nhận yêu cầu, chưa phải vé tham dự.\n\nXoè 2026"]);
    $organizer=config()['organizer_email'] ?? '';
    if (filter_var($organizer,FILTER_VALIDATE_EMAIL)) $q->execute([$id,$organizer,"Đăng ký mới #$id — Xoè 2026","Họ tên: $name\nEmail: $email\nĐiện thoại: $phone\nHoạt động: $activity\nLời nhắn: $message"]);
}
function smtp_send(string $recipient, string $subject, string $body): void {
    $c=config(); $host=$c['smtp_host'] ?? ''; $from=$c['mail_from'] ?? '';
    if (!$host || !filter_var($from,FILTER_VALIDATE_EMAIL) || !filter_var($recipient,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Chưa cấu hình SMTP / email gửi.');
    $security=$c['smtp_security'] ?? 'tls';
    if (!in_array($security,['tls','ssl'],true) || !preg_match('/^[a-zA-Z0-9.-]+$/',$host)) throw new RuntimeException('Cấu hình SMTP không hợp lệ.');
    $socket=stream_socket_client(($security==='ssl'?'ssl://':'tcp://').$host.':'.(int)($c['smtp_port'] ?? 587),$errno,$error,10,STREAM_CLIENT_CONNECT,stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]));
    if (!$socket) throw new RuntimeException('Không kết nối được SMTP.');
    stream_set_timeout($socket,10);
    $read=function(array $codes) use ($socket): void {
        do { $line=fgets($socket,2048); if ($line===false) throw new RuntimeException('SMTP không phản hồi.'); } while (isset($line[3]) && $line[3]==='-');
        if (!in_array((int)substr($line,0,3),$codes,true)) throw new RuntimeException('SMTP từ chối: '.substr($line,0,3));
    };
    $send=function(string $command,array $codes) use ($socket,$read): void { fwrite($socket,$command."\r\n"); $read($codes); };
    try {
        $read([220]); $send('EHLO localhost',[250]);
        if ($security==='tls') { $send('STARTTLS',[220]); if (!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('Không thiết lập được TLS.'); $send('EHLO localhost',[250]); }
        if (!empty($c['smtp_username'])) { $send('AUTH LOGIN',[334]); $send(base64_encode($c['smtp_username']),[334]); $send(base64_encode($c['smtp_password'] ?? ''),[235]); }
        $send('MAIL FROM:<'.$from.'>',[250]); $send('RCPT TO:<'.$recipient.'>',[250,251]); $send('DATA',[354]);
        $headers='From: '.$from."\r\nTo: ".$recipient."\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nDate: ".date(DATE_RFC2822)."\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $send($headers.rtrim(chunk_split(base64_encode($body),76,"\r\n"))."\r\n.",[250]);
        fwrite($socket,"QUIT\r\n");
    } finally { fclose($socket); }
}
