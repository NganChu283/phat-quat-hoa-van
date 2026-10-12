<?php $otherEvents=array_values(array_filter($events,fn($event)=>!$project || (int)$event['id']!==(int)$project['id'])); ?>
<?php if ($otherEvents): ?>
<section class="event-list section" aria-labelledby="event-list-title">
  <div class="event-list-heading"><p class="eyebrow">HÀNH TRÌNH DỰ ÁN</p><h2 id="event-list-title"><?= $project?'Các sự kiện khác':'Sự kiện của dự án' ?></h2></div>
  <div class="event-cards">
  <?php foreach ($otherEvents as $event):
    $eventStart=!empty($event['starts_at'])?project_start($event):null;
    $eventEnd=!empty($event['ends_at'])?new DateTimeImmutable($event['ends_at']):null;
    $status=!$eventStart?'Chờ cập nhật lịch':($eventStart->getTimestamp()>time()?'Sắp diễn ra':($eventEnd && $eventEnd->getTimestamp()<time()?'Đã kết thúc':($eventEnd?'Đang diễn ra':'Đã đến giờ bắt đầu')));
  ?>
    <article class="event-card">
      <div class="event-card-date" aria-label="<?= $eventStart?e('Ngày '.$eventStart->format('d/m/Y')):'Lịch sẽ được cập nhật' ?>">
        <?php if ($eventStart): ?><time datetime="<?= e($eventStart->format('Y-m-d')) ?>"><strong><?= e($eventStart->format('d')) ?></strong><span>THÁNG <?= e($eventStart->format('m')) ?> · <?= e($eventStart->format('Y')) ?></span></time>
        <?php else: ?><strong aria-hidden="true">✦</strong><span>CHỜ LỊCH</span><?php endif; ?>
      </div>
      <div class="event-card-copy">
        <p class="event-card-status"><?= e($status) ?></p><h3><?= e($event['title']) ?></h3>
        <?php if (!empty($event['subtitle'])): ?><p class="event-card-subtitle"><?= e($event['subtitle']) ?></p><?php endif; ?>
        <?php if (!empty($event['description'])): ?><p class="event-card-description"><?= e($event['description']) ?></p><?php endif; ?>
        <?php if (!empty($event['schedule'])): ?><details class="event-schedule"><summary>Lịch hoạt động <span aria-hidden="true">+</span></summary><p><?= e($event['schedule']) ?></p></details><?php endif; ?>
      </div>
      <dl class="event-card-meta">
        <div><dt>Thời gian · giờ Việt Nam</dt><dd><?php if ($eventStart): ?><time datetime="<?= e($eventStart->format('c')) ?>"><?= e($eventStart->format('H:i · d/m/Y')) ?></time><?php else: ?>Lịch sẽ được cập nhật.<?php endif; ?><?php if ($eventEnd): ?><span class="event-card-end">Đến <?= e($eventEnd->format('H:i · d/m/Y')) ?></span><?php endif; ?></dd></div>
        <div><dt>Địa điểm</dt><dd><?= e(($event['venue'] ?? '') ?: 'Đang cập nhật') ?><?php if (!empty($event['address'])): ?><span class="event-card-address"><?= e($event['address']) ?></span><?php endif; ?></dd></div>
      </dl>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
