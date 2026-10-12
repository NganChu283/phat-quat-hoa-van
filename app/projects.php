<?php
function project_datetime(string $value): DateTimeImmutable {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Asia/Ho_Chi_Minh'));
    if (!$date || $date->format('Y-m-d\TH:i') !== $value || (int)$date->format('Y') < 2000 || (int)$date->format('Y') > 2100) {
        throw new InvalidArgumentException('Vui lòng nhập ngày giờ hợp lệ (năm 2000–2100), theo giờ Việt Nam.');
    }
    return $date;
}
function project_start(array $project): DateTimeImmutable {
    if (empty($project['starts_at'])) throw new InvalidArgumentException('Sự kiện chưa có giờ bắt đầu.');
    return new DateTimeImmutable($project['starts_at'], new DateTimeZone('Asia/Ho_Chi_Minh'));
}
function published_events(): array { return query_all('SELECT * FROM projects WHERE published=1 ORDER BY featured DESC, starts_at IS NULL, starts_at ASC,id DESC'); }
function featured_event(array $events): ?array {
    foreach ($events as $event) if (!empty($event['featured'])) return $event;
    return null;
}
