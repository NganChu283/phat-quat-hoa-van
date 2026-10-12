<?php
require __DIR__.'/../app/bootstrap.php';

$project=db()->query('SELECT * FROM projects WHERE featured=1 AND published=1 LIMIT 1')->fetch();
if (!$project || empty($project['starts_at']) || empty($project['ends_at']) || empty($project['venue']) || empty($project['address'])) { http_response_code(404); exit('Lịch sự kiện chưa đầy đủ.'); }
function ics_text(string $s): string { return str_replace(["\\","\r","\n",';',','],['\\\\','', '\\n','\\;','\\,'],$s); }
function ics_line(string $s): string {
    $lines=[]; $limit=75;
    while (strlen($s)>$limit) { $part=mb_strcut($s,0,$limit,'UTF-8'); $lines[]=$part; $s=substr($s,strlen($part)); $limit=74; }
    $lines[]=$s; return implode("\r\n ",$lines)."\r\n";
}
$utc=new DateTimeZone('UTC');
header('Content-Type: text/calendar; charset=utf-8'); header('Content-Disposition: attachment; filename="phat-quat-hoa-van.ics"');
foreach (['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Phat Quat Hoa Van//Event//VI','CALSCALE:GREGORIAN','BEGIN:VEVENT','UID:project-'.$project['id'].'@'.(parse_url(config()['site_url'] ?? '',PHP_URL_HOST) ?: 'phatquathoavan.local'),'DTSTAMP:'.gmdate('Ymd\THis\Z'),'DTSTART:'.project_start($project)->setTimezone($utc)->format('Ymd\THis\Z'),'DTEND:'.(new DateTimeImmutable($project['ends_at'],new DateTimeZone('Asia/Ho_Chi_Minh')))->setTimezone($utc)->format('Ymd\THis\Z'),'SUMMARY:'.ics_text($project['title']),'LOCATION:'.ics_text($project['venue'].' — '.$project['address']),'DESCRIPTION:'.ics_text($project['schedule'] ?? ''),'END:VEVENT','END:VCALENDAR'] as $line) echo ics_line($line);
