<?php
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
